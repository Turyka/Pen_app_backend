<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Cloudinary\Cloudinary;

class TiktokController extends Controller
{
    public function store(Request $request)
    {
        // Path to Python script
       $scriptPath = base_path('scrape_tiktok.js');
    
    if (!file_exists($scriptPath)) {
        return response()->json([
            'success' => false,
            'error' => 'JavaScript script not found at: ' . $scriptPath
        ], 500);
    }
    
    // Execute Node.js script (SAME logic as Python)
    $output = shell_exec("node " . escapeshellarg($scriptPath) . " 2>&1");
        
        // Debug: Log output
        Log::info('Python output: ' . substr($output, 0, 500));
        
        // Parse JSON OBJECT
        $result = json_decode($output, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
                'raw_output' => substr($output, 0, 1000)
            ]);
        }
        
        // Check status
        if ($result['status'] !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'No videos found from TikTok',
                'python_output' => $output
            ]);
        }
        
        $video = $result['latest_1'];
        if (empty($video['url']) || empty($video['title'])) {
            return response()->json([
                'success' => false,
                'message' => 'No valid video data from TikTok',
                'data' => $video
            ]);
        }
        
        // **FIXED**: Only use EXISTING columns
        $url = $video['url'];
        $title = $video['title'];
        $imageUrl = $video['image_url'] ?? null;
        
        // Check for duplicate URL
        $exists = DB::table('tiktok_posts')
                   ->where('url', $url)
                   ->exists();
        
        if ($exists) {
            return response()->json([
                'success' => true,
                'message' => 'Video already exists (no duplicate saved)',
                'video' => $video,
                'action' => 'skipped_duplicate'
            ]);
        }
        
        try {
            // **COMPATIBLE** with existing table - NO new columns
            DB::table('tiktok_posts')->insert([
                'title' => substr($title, 0, 255),
                'url' => $url,
                'image_url' => $imageUrl ? substr($imageUrl, 0, 500) : null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('✅ Saved new TikTok video: ' . $url);
            
            return response()->json([
                'success' => true,
                'message' => '🎉 New video saved successfully!',
                'video' => $video,
                'video_id' => $video['url']
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error saving video: ' . $e->getMessage(), ['video' => $video]);
            
            return response()->json([
                'success' => false,
                'error' => 'Database save failed: ' . $e->getMessage(),
                'video' => $video
            ], 500);
        }
    }
    
   
}