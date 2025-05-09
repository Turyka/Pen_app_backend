<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Naptar;
use Illuminate\Support\Facades\Validator;

class NaptarController extends Controller
{
    public function index()
    {
        return view('naptar');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'event_type' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Naptar::create([
            'title' => $request->input('title'),
            'date' => $request->input('date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'event_type' => $request->input('custom_event_type') ?: $request->input('event_type'),
            'description' => $request->input('description'),
        ]);

        return redirect()->back()->with('success', 'Esemény sikeresen mentve!');
    }

    public function naptarAPI()
    {
        $events = Naptar::all()->map(function ($event) {
            $baseName = strtolower(str_replace(' ', '', $event->event_type));
            $pngPath = public_path("img/{$baseName}.png");
            $jpgPath = public_path("img/{$baseName}.jpg");

            if (file_exists($pngPath)) {
                $event->event_type = asset("img/{$baseName}.png");
            } elseif (file_exists($jpgPath)) {
                $event->event_type = asset("img/{$baseName}.jpg");
            }
            
            return [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->date,
                'start_time' => substr($event->start_time, 0, 5),
                'end_time' => substr($event->end_time, 0, 5),
                'event_type' => $event->event_type,
                'description' => $event->description,
                'created_at' => $event->created_at,
            ];
        });

        return response()->json($events);
    }
}
