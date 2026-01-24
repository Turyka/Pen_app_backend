<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TiktokController extends Controller
{
    public function store(Request $request)
    {
        // Path to Python script
        $scriptPath = base_path('scrape_tiktok.py');
        
        if (!file_exists($scriptPath)) {
            return response()->json([
                'success' => false,
                'error' => 'Python script not found at: ' . $scriptPath
            ], 500);
        }
        
        // Execute Python script
        $output = shell_exec("python3 " . escapeshellarg($scriptPath) . " 2>&1");
        
        // Debug: Log output
        Log::info('Python output: ' . substr($output, 0, 500));
        
        // Try to find JSON array in output
        $jsonStart = strpos($output, '[');
        $jsonEnd = strrpos($output, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            return response()->json([
                'success' => false,
                'error' => 'No valid JSON array found',
                'raw_output' => substr($output, 0, 1000)
            ]);
        }
        
        $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
        $videos = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
                'raw_json' => $jsonString
            ]);
        }
        
        // If no videos found
        if (empty($videos)) {
            return response()->json([
                'success' => false,
                'message' => 'No videos found from TikTok',
                'python_output' => $output
            ]);
        }
        
        // Save to database - NO DUPLICATES
        $savedCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;
        
        foreach ($videos as $video) {
            if (empty($video['url'])) {
                continue; // Skip if no URL
            }
            
            $url = $video['url'];
            $title = $video['title'] ?? 'TikTok Video';
            $imageUrl = $video['image_url'] ?? null;
            
            // Check if URL already exists in database
            $exists = DB::table('tiktok_posts')
                       ->where('url', $url)
                       ->exists();
            
            if ($exists) {
                $duplicateCount++;
                Log::info('Duplicate skipped: ' . $url);
                continue; // Skip duplicate
            }
            
            try {
                // Insert new video
                DB::table('tiktok_posts')->insert([
                    'title' => substr($title, 0, 255),
                    'url' => $url,
                    'image_url' => $imageUrl ? substr($imageUrl, 0, 500) : null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $savedCount++;
                Log::info('Saved new video: ' . $url);
                
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Error saving video: ' . $e->getMessage(), ['video' => $video]);
            }
        }
        
        // Return result
        return response()->json([
            'success' => true,
            'message' => 'Scraping completed!',
            'summary' => [
                'videos_found' => count($videos),
                'videos_saved' => $savedCount,
                'duplicates_skipped' => $duplicateCount,
                'errors' => $errorCount
            ],
            'videos' => $videos
        ]);
    }
    
    public function show()
    {
        // Show all saved videos
        $videos = DB::table('tiktok_posts')
                   ->orderBy('created_at', 'desc')
                   ->get();
        
        return response()->json([
            'success' => true,
            'count' => count($videos),
            'videos' => $videos
        ]);
    }
    
    // Optional: Clear duplicates if any exist
    public function clearDuplicates()
    {
        // Find and delete duplicates (keep the oldest one)
        $duplicates = DB::select("
            SELECT url, COUNT(*) as count, MIN(id) as keep_id
            FROM tiktok_posts 
            GROUP BY url 
            HAVING COUNT(*) > 1
        ");
        
        $deleted = 0;
        foreach ($duplicates as $dup) {
            $deleted += DB::delete("
                DELETE FROM tiktok_posts 
                WHERE url = ? AND id != ?
            ", [$dup->url, $dup->keep_id]);
        }
        
        return response()->json([
            'success' => true,
            'message' => "Removed $deleted duplicate videos",
            'duplicates_found' => count($duplicates)
        ]);
    }
}