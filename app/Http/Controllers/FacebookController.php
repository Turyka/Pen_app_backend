<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookPost;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class FacebookController extends Controller
{
    public function store(Request $request)
{
    if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json([
            'success' => false,
            'error' => 'Unauthorized'
        ], 403);
    }

    $pythonPath = public_path('scrape_facebook.py');
    $cmd = sprintf('cd %s && python3 %s 2>&1', public_path(), basename($pythonPath));
    $output = shell_exec($cmd);

    Log::info('Facebook scrape command', [
        'cmd' => $cmd,
        'output_length' => strlen($output ?? '')
    ]);

    $data = json_decode($output ?? '{}', true);

    if (!$data || $data['status'] === 'error' || empty($data['latest_1']['title'])) {
        return response()->json([
            'success' => false,
            'error' => $data['error'] ?? 'No valid post found',
            'all_posts_count' => $data['all_posts_count'] ?? 0,
            'raw_output' => substr($output, 0, 500),
        ], 500);
    }

    $post = $data['latest_1'];

    // Save ALWAYS if we have a valid title (no duplicate check needed)
    DB::table('facebook_posts')->updateOrInsert(
        ['url' => $post['url']],
        [
            'title' => $post['title'],
            'image_url' => $post['image_url'],
            'updated_at' => now()
        ]
    );

    return response()->json([
        'success' => true,
        'saved' => true,
        'post' => $post,
        'all_posts_found' => $data['all_posts_count']
    ]);
}


    public function facebookPostAPI(Request $request)
    {
    if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $events = FacebookPost::orderBy('updated_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($event) {
            return [
                'title' => Str::words($event->title, 10, '...'),
                'url' => $event->url,
                'image_url' => $event->image_url,
            ];
        });

    return response()->json($events);
    }   




    public function refreshFacebookPosts()
    {
        Schema::dropIfExists('facebook_posts');

        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->string('url');
            $table->text('image_url')->nullable();
            $table->timestamps();
        });

        return 'facebook_posts refreshed';
    }
}
