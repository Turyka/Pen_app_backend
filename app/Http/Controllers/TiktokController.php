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
    if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $events = TiktokPost::orderBy('updated_at', 'asc')
        ->take(5)
        ->get()
        ->map(function ($event) {
            return [
                'title' => Str::words($event->title, 6, '...'),
                'url' => $event->url,
                'image_url' => $event->image_url,
            ];
        });

    return response()->json($events);
    }   
   
}