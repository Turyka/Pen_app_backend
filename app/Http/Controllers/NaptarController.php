<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Naptar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NaptarController extends Controller
{
    // 📅 Naptár létrehozása (GET)
    public function keszit()
    {
        return view('naptar_keszit');
    }

    // 📩 Naptár mentése (POST)
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

        $user = Auth::user();

        if (!$user) {
            return redirect()->back()->with('error', 'Hozzáférés megtagadva: nem bejelentkezett felhasználó.');
        }

        Naptar::create([
            'title' => $request->input('title'),
            'date' => $request->input('date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'event_type' => $request->input('custom_event_type') ?: $request->input('event_type'),
            'description' => $request->input('description'),
            'status' => 'Aktív',
            'created' => $user->teljes_nev,
        ]);

        return redirect('/dashboard/naptar')->with('success', 'Esemény sikeresen mentve!');
    }

    // ✏️ Naptár szerkesztése (GET)
    public function edit(Naptar $naptar)
    {
        return view('naptar_edit', compact('naptar'));
    }

    public function destroy(Naptar $naptar)
    {
    $naptar->delete();

    return redirect()->route('naptar')->with('success', 'Esemény sikeresen törölve!');
    }

    // 🔄 Naptár frissítése (PUT)
    public function update(Request $request, Naptar $naptar)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'event_type' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        if (!$user) {
            return redirect()->back()->with('error', 'Hozzáférés megtagadva: nem bejelentkezett felhasználó.');
        }

        $naptar->update([
            'title' => $request->input('title'),
            'date' => $request->input('date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'event_type' => $request->input('custom_event_type') ?: $request->input('event_type'),
            'status' => $request->input('status'),
            'description' => $request->input('description'),
            'edited' => $user->teljes_nev,
        ]);

        return redirect('/dashboard/naptar')->with('success', 'Esemény sikeresen frissítve!');
    }

    // 🌐 Naptár API (JSON)
    public function naptarAPI(Request $request)
    {
       
        if ($request->query('titkos') !== env('API_SECRET')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $events = Naptar::all()->map(function ($event) {
            $baseName = strtolower(str_replace(' ', '', $event->event_type));
            $pngPath = public_path("img/{$baseName}.png");
            $jpgPath = public_path("img/{$baseName}.jpg");

            // 🖼️ Kép hozzáadása az eseménytípushoz, ha van ilyen fájl
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
                'status' => $event->status
            ];
        });

        return response()->json($events);
    }
}
