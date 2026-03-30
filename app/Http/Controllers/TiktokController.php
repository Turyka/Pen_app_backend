<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\TiktokPost;

class TiktokController extends Controller
{
    /**
     * POST /api/tiktok-keres
     * Python scraper sends posts here
     */
    public function store(Request $request)
    {
        // API Key validation
        $apiKey = $request->header('X-API-KEY');
        if (!$apiKey || !hash_equals(env('API_TIKTOK', 'dQw4w9WgXcQ'), $apiKey)) {
            Log::warning('TikTok scraper: Invalid API key', ['key' => substr($apiKey, 0, 8) ?? 'none']);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate input
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'url' => 'required|url|max:500',
            'image_url' => 'nullable|url|max:1000',
        ]);

        // Find existing post by URL
        $post = TiktokPost::where('url', $validated['url'])->first();

        if (!$post) {
            // CREATE new post
            $post = TiktokPost::create($validated);
            Log::info('TikTok post CREATED', ['id' => $post->id, 'url' => $validated['url']]);
            return response()->json([
                'success' => true,
                'action' => 'created',
                'id' => $post->id,
            ], 201);
        }

        // UPDATE image if changed
        if (isset($validated['image_url']) && $validated['image_url'] !== $post->image_url) {
            $post->update(['image_url' => $validated['image_url']]);
            Log::info('TikTok post UPDATED image', ['id' => $post->id]);
            return response()->json([
                'success' => true,
                'action' => 'updated_image',
                'id' => $post->id,
            ], 200);
        }

        // NO CHANGE
        Log::info('TikTok post NO CHANGE', ['id' => $post->id]);
        return response()->json([
            'success' => true,
            'action' => 'no_change',
            'id' => $post->id,
        ], 200);
    }

    /**
     * GET /api/tiktok-post
     * Frontend fetches latest posts
     */
    public function TiktokPostAPI(Request $request)
    {
        $auth = $request->header('Authorization');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        // 1. Check headers
        if (!$auth || !$timestamp || !$signature) {
            return response()->json(['error' => 'Missing headers'], 401);
        }

        // 2. Bearer token
        if (!str_starts_with($auth, 'Bearer ')) {
            return response()->json(['error' => 'Bad auth format'], 401);
        }

        $token = substr($auth, 7);
        if (!hash_equals(env('API_TOKEN'), $token)) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // 3. Timestamp (5min window)
        if (abs(time() - (int)$timestamp) > 300) {
            return response()->json(['error' => 'Timestamp expired'], 401);
        }

        // 4. HMAC signature
        $expected = hash_hmac('sha256', $timestamp, env('API_TOKEN'));
        if (!hash_equals($expected, $signature)) {
            Log::warning('TikTok API: Bad signature', [
                'timestamp' => $timestamp,
                'expected' => substr($expected, 0, 16),
                'received' => substr($signature, 0, 16)
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 5. Return latest 5 posts
        $posts = TiktokPost::orderByDesc('id')
            ->take(5)
            ->get(['title', 'url', 'image_url'])
            ->map(function ($p) {
                return [
                    'title' => Str::words($p->title, 6, '...'),
                    'url' => $p->url,
                    'image_url' => $p->image_url,
                ];
            });

        return response()->json($posts);
    }
}