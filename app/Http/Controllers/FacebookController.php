<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookPost;
use Illuminate\Support\Str;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Http;


class FacebookController extends Controller
{

    public function store(Request $request)
    {
        // 🔐 API SECRET CHECK
        if ($request->query('titkos') !== env('API_SECRET')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
        }

        // 🐍 RUN PYTHON SCRAPER
        $pythonPath = public_path('scrape_facebook.py');
        $cmd = sprintf('cd %s && python3 %s 2>&1', public_path(), basename($pythonPath));
        $output = shell_exec($cmd);

        Log::info('Facebook scrape command', [
            'cmd' => $cmd,
            'output_length' => strlen($output ?? '')
        ]);

        // 📦 PARSE JSON
        $data = json_decode($output ?? '{}', true);

        if (!$data || ($data['status'] ?? '') === 'error') {
            return response()->json([
                'success' => false,
                'error' => $data['error'] ?? 'No data returned',
                'raw_output' => $output,
            ], 500);
        }

        $post = $data['latest_1'];

        // ❌ EMPTY TITLE SAFETY
        if (empty($post['title'])) {
            return response()->json([
                'success' => false,
                'error' => 'Empty post title',
            ], 422);
        }

        // ❌ SKIP IF TITLE ALREADY EXISTS
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

        // ☁️ CLOUDINARY IMAGE UPLOAD
        $cloudinaryImageUrl = '';

        if (!empty($post['image_url'])) {
            try {
                // Download image from Facebook
                $imageResponse = Http::timeout(15)->get($post['image_url']);

                if ($imageResponse->successful()) {
                    $tempPath = storage_path('app/temp_fb_image.jpg');
                    file_put_contents($tempPath, $imageResponse->body());

                    // Upload to Cloudinary
                    $cloudinary = new Cloudinary();
                    $upload = $cloudinary->uploadApi()->upload($tempPath, [
                        'folder' => 'facebook_posts',
                        'quality' => 'auto',
                        'fetch_format' => 'auto',
                        'resource_type' => 'image',
                    ]);

                    $cloudinaryImageUrl = $upload['secure_url'] ?? '';

                    // Cleanup temp file
                    @unlink($tempPath);
                }
            } catch (\Throwable $e) {
                Log::error('Cloudinary upload failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 💾 SAVE TO DATABASE
        DB::table('facebook_posts')->insert([
            'title' => $post['title'],
            'url' => $post['url'],
            'image_url' => $cloudinaryImageUrl,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'saved' => true,
            'post' => [
                'title' => $post['title'],
                'url' => $post['url'],
                'image_url' => $cloudinaryImageUrl,
            ]
        ]);
    }


public function facebookPostAPI(Request $request)
{
    $auth = $request->header('Authorization');
    $timestamp = $request->header('X-Timestamp');
    $signature = $request->header('X-Signature');

    if (!$auth || !$timestamp || !$signature) {
        return response()->json(['error' => 'Missing headers'], 401);
    }

    if (!str_starts_with($auth, 'Bearer ')) {
        return response()->json(['error' => 'Bad auth format'], 401);
    }

    if (!hash_equals(env('API_TOKEN'), substr($auth, 7))) {
        return response()->json(['error' => 'Bad token'], 401);
    }

    if (abs(time() - (int)$timestamp) > 300) {
        return response()->json(['error' => 'Expired'], 401);
    }

    $expected = hash_hmac('sha256', $timestamp, env('API_TOKEN'));

    if (!hash_equals($expected, $signature)) {
        return response()->json([
            'error' => 'Bad signature',
            'expected' => $expected, // TEMP
            'received' => $signature // TEMP
        ], 401);
    }

    return response()->json(
        FacebookPost::latest()->take(5)->get()
    );
}





    
}
