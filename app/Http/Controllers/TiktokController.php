<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TiktokController extends Controller
{
   public function store(Request $request)
{
    ini_set('max_execution_time', 90); // PHP kills after 90s
    
    $scriptPath = base_path('scrape_tiktok.py');
    
    if (!file_exists($scriptPath)) {
        return response()->json(['error' => 'Script missing'], 500);
    }
    
    $start = microtime(true);
    
    // 🔥 NO 'timeout' command - let PHP handle timeout
    $output = shell_exec("python3 " . escapeshellarg($scriptPath) . " 2>&1");
    $duration = round((microtime(true) - $start), 1);
    
    return response()->json([
        'success' => true,
        'duration_seconds' => $duration,
        'raw_output_length' => strlen($output),
        'first_300_chars' => substr($output, 0, 300),
        'full_json' => json_decode($output, true),
        'video' => json_decode($output, true)['latest_1'] ?? null
    ]);
}
    
    public function show()
    {
        $videos = DB::table('tiktok_posts')
                   ->orderBy('created_at', 'desc')
                   ->limit(20)
                   ->get();
        
        return response()->json([
            'success' => true,
            'count' => count($videos),
            'videos' => $videos
        ]);
    }
    
    public function latest()
    {
        $video = DB::table('tiktok_posts')
                  ->orderBy('created_at', 'desc')
                  ->first();
        
        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'No videos in database'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'latest' => $video
        ]);
    }
}