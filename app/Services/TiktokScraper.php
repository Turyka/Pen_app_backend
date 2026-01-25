<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class TiktokController extends Controller
{
    public function store(Request $request)
    {
        // RENDER TIMEOUTS
        set_time_limit(25);
        ini_set('memory_limit', '128M');
        
        $username = trim($request->input('username', 'pannonegyetem'));
        $scriptPath = base_path('scrape_tiktok_render.py');
        
        // VALIDATE SCRIPT
        if (!file_exists($scriptPath)) {
            return response()->json(['success' => false, 'error' => 'scrape_tiktok_render.py missing'], 500);
        }

        // EXECUTE - 18s MAX
        $command = "timeout 18s python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($username) . " 2>&1";
        
        Log::info("TikTok scrape", ['username' => $username]);
        
        $result = Process::timeout(20)->run($command);
        $output = trim($result->output());
        
        // TIMEOUT CHECK
        if (!$result->successful()) {
            Log::warning("Scraper timeout", ['username' => $username, 'code' => $result->exitCode()]);
            return response()->json([
                'success' => false, 
                'message' => 'TikTok too slow',
                'videos' => []
            ], 408);
        }

        // EXTRACT JSON FROM OUTPUT
        if (preg_match('/\[.*\]/s', $output, $matches)) {
            $videos = json_decode($matches[0], true) ?? [];
        } else {
            $videos = [];
        }
        
        if (!is_array($videos)) {
            Log::error("Invalid JSON", ['output' => substr($output, 0, 300)]);
            return response()->json(['success' => false, 'videos' => []]);
        }

        // SAVE TO DB - NO DUPLICATES
        $saved = 0;
        $duplicates = 0;
        
        foreach ($videos as $video) {
            if (empty($video['url'])) continue;
            
            $exists = DB::table('tiktok_posts')->where('url', $video['url'])->exists();
            if ($exists) {
                $duplicates++;
                continue;
            }
            
            DB::table('tiktok_posts')->insert([
                'title' => substr($video['title'] ?? 'TikTok', 0, 255),
                'url' => $video['url'],
                'image_url' => substr($video['image_url'] ?? '', 0, 500),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $saved++;
        }

        Log::info("Scrape done", [
            'username' => $username,
            'found' => count($videos),
            'saved' => $saved,
            'duplicates' => $duplicates
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Done!',
            'summary' => [
                'username' => $username,
                'found' => count($videos),
                'saved' => $saved,
                'duplicates' => $duplicates
            ],
            'videos' => $videos
        ]);
    }

    public function show()
    {
        $videos = DB::table('tiktok_posts')->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'videos' => $videos]);
    }
}