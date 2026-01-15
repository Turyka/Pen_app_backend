<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\TiktokPost;
use Illuminate\Support\Str;


class TiktokController extends Controller
{
    public function store(Request $request)
    {
        // Secret key check
        if ($request->query('titkos') !== env('API_SECRET')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
        }

        $pythonPath = public_path('scrape_tiktok.py');

        // Profile is hardcoded in Python script, no need to pass it
        $cmd = sprintf('cd %s && python3 %s 2>&1', public_path(), basename($pythonPath));
        $output = shell_exec($cmd);

        Log::info('TikTok scrape command', [
            'cmd' => $cmd,
            'output_length' => strlen($output ?? '')
        ]);

        $data = json_decode($output ?? '{}', true);

        if (!$data || $data['status'] !== 'success' || empty($data['posts'])) {
            return response()->json([
                'success' => false,
                'error' => $data['error'] ?? 'Scraping failed',
                'raw_output' => $output,
            ], 500);
        }

        $saved = 0;
        foreach ($data['posts'] as $post) {
            if (!$post['url']) continue;

            // Skip duplicates
            $exists = DB::table('tiktok_posts')
                ->where('url', $post['url'])
                ->exists();

            if ($exists) continue;

            DB::table('tiktok_posts')->insert([
                'url' => $post['url'],
                'image_url' => $post['image_url'] ?? '',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $saved++;
        }

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'total_fetched' => count($data['posts'])
        ]);
    }

    public function TiktokPostAPI(Request $request)
    {
    if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $events = TiktokPost::orderBy('updated_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($event) {
            return [
                'url' => $event->url,
                'image_url' => $event->image_url,
            ];
        });

    return response()->json($events);
    }
}
