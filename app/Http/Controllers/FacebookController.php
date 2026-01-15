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

    if (!$data || $data['status'] === 'error') {
        return response()->json([
            'success' => false,
            'error' => $data['error'] ?? 'No data',
            'raw_output' => $output,
        ], 500);
    }

    $post = $data['latest_1'];

    // ❌ Skip if same title already exists
    $exists = DB::table('facebook_posts')
        ->where('title', $post['title'])
        ->exists();

    if ($exists) {
        return response()->json([
            'success' => true,
            'saved' => false,
            'skipped' => true,
            'reason' => 'Same title already exists'
        ]);
    }

    // ✅ Save only if new
    DB::table('facebook_posts')->insert([
        'title' => $post['title'],
        'url' => $post['url'],
        'image_url' => $post['image_url'],
        'updated_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'saved' => true,
        'post' => $post
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
