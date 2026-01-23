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

    $profile = $request->input('profile', 'pannonegyetem'); // Optional profile param
    $pythonPath = public_path('scrape_tiktok.py');

    $cmd = sprintf('cd %s && python3 %s %s 2>&1', public_path(), basename($pythonPath), $profile);
    $output = shell_exec($cmd);

    Log::info('TikTok scrape command', [
        'cmd' => $cmd,
        'output_length' => strlen($output ?? '')
    ]);

    $posts = json_decode($output ?? '[]', true);

    if (empty($posts)) {
        return response()->json([
            'success' => false,
            'error' => 'Scraping failed',
            'raw_output' => $output,
        ], 500);
    }

    $saved = 0;
    foreach ($posts as $post) {
        if (!$post['url']) continue;

        // Skip duplicates
        $exists = DB::table('tiktok_posts')
            ->where('url', $post['url'])
            ->exists();

        if ($exists) continue;

        DB::table('tiktok_posts')->insert([
            'title' => $post['title'] ?? '',
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
        'total_fetched' => count($posts)
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
