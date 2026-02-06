<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TiktokPost;
use Illuminate\Support\Str;

class TiktokController extends Controller
{
    public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'url' => 'required|url|max:255',
        'image_url' => 'nullable|url',
    ]);

    // Find by URL
    $post = TiktokPost::where('url', $validated['url'])->first();

    // Case 1: URL does not exist → create
    if (!$post) {
        $post = TiktokPost::create([
            'title' => $validated['title'],
            'url' => $validated['url'],
            'image_url' => $validated['image_url'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'action' => 'created',
            'id' => $post->id,
        ], 201);
    }

    // Case 2: URL exists → update image_url if changed
    if (
        array_key_exists('image_url', $validated) &&
        $validated['image_url'] !== $post->image_url
    ) {
        $post->update([
            'image_url' => $validated['image_url'],
        ]);

        return response()->json([
            'success' => true,
            'action' => 'updated',
            'id' => $post->id,
        ], 200);
    }

    // Case 3: URL exists & nothing changed → do nothing
    return response()->json([
        'success' => true,
        'action' => 'no_change',
        'id' => $post->id,
    ], 200);
}

    

    public function TiktokPostAPI(Request $request)
{
    $auth = $request->header('Authorization');
    $timestamp = $request->header('X-Timestamp');
    $signature = $request->header('X-Signature');

    // 1️⃣ Check all headers exist
    if (!$auth || !$timestamp || !$signature) {
        return response()->json(['error' => 'Missing headers'], 401);
    }

    // 2️⃣ Validate Authorization format
    if (!str_starts_with($auth, 'Bearer ')) {
        return response()->json(['error' => 'Bad auth format'], 401);
    }

    // 3️⃣ Validate token
    if (!hash_equals(env('API_TOKEN'), substr($auth, 7))) {
        return response()->json(['error' => 'Bad token'], 401);
    }

    // 4️⃣ Check timestamp freshness (30s window)
    if (abs(time() - (int)$timestamp) > 300) {
        return response()->json(['error' => 'Expired'], 401);
    }

    // 5️⃣ Verify HMAC signature
    $expected = hash_hmac('sha256', $timestamp, env('API_TOKEN'));

    if (!hash_equals($expected, $signature)) {
        return response()->json([
            'error' => 'Bad signature',
            'expected' => $expected, // TEMP for debugging
            'received' => $signature // TEMP for debugging
        ], 401);
    }

    // 6️⃣ Fetch latest 5 TikTok posts with only needed fields
    $events = TiktokPost::orderByDesc('updated_at')
        ->take(5)
        ->get(['title', 'url', 'image_url'])
        ->map(fn($p) => [
            'title' => Str::words($p->title, 6, '...'), // 6 words for TikTok
            'url' => $p->url,
            'image_url' => $p->image_url,
        ]);

    return response()->json($events);
}
   
}