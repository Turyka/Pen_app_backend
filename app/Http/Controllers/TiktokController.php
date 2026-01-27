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
        // Validate ONLY allowed fields
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'image_url' => 'nullable|url',
        ]);

        // Save to database
        $post = TiktokPost::create([
            'title' => $validated['title'],
            'url' => $validated['url'],
            'image_url' => $validated['image_url'] ?? null,
        ]);

        // Return success response
        return response()->json([
            'success' => true,
            'id' => $post->id,
        ], 201);
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
                'title' => Str::words($event->title, 6, '...'),
                'url' => $event->url,
                'image_url' => $event->image_url,
            ];
        });

    return response()->json($events);
    }   
   
}