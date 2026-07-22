<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Naptar;
use App\Models\Kepfeltoltes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Eszkozok;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class NaptarController extends Controller
{
    // 📅 Naptár létrehozása (GET)
    public function keszit()
    {
          $kepfeltoltes = Kepfeltoltes::all(); 
    return view('naptar_keszit', compact('kepfeltoltes'));
    }

    // 📩 Naptár mentése (POST)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'event_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ertesites' => 'boolean',
            'link' => "nullable|string"
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
            'ertesites' => $request->input('ertesites'),
            'link' => $request->input('link'),
        ]);

        if ($request->input('ertesites')) {
            $tokens = Eszkozok::where('naptarErtesites', true)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->toArray();

            if (empty($tokens)) {
                Log::warning('No devices with naptarErtesites enabled.');
            } else {
                try {
                    $firebase = app(FirebaseService::class);
                    $firebase->sendNotification(
                        $tokens,
                        "Új bejegyzés a naptárban",
                        "{$request->input('title')} - {$request->input('date')} {$request->input('start_time')}"
                    );
                    Log::info('Firebase notification process completed.');
                } catch (\Exception $e) {
                    Log::error("❌ Exception while sending notification: {$e->getMessage()}");
                }
            }
        }

        return redirect('/dashboard/naptar')->with('success', 'Esemény sikeresen mentve!');
    }

    // ✏️ Naptár szerkesztése (GET)
    public function edit(Naptar $naptar)
    {
        $kepfeltoltes = Kepfeltoltes::all(); 
        return view('naptar_edit', compact('naptar','kepfeltoltes'));
        
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
            'end_time' => 'required|date_format:H:i',
            'event_type' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|string',
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
            'link' => $request->input('link'),
        ]);

        if ($request->input('ertesites') && $request->input('status') == "Elmarad" ) {
            $tokens = Eszkozok::where('naptarErtesites', true)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->toArray();

            if (empty($tokens)) {
                Log::warning('No devices with naptarErtesites enabled.');
            } else {
                try {
                    $firebase = app(FirebaseService::class);
                    $firebase->sendNotification(
                        $tokens,
                        "❌ {$request->input('title')} Esemény elmarad",
                        ""
                    );
                    Log::info('Firebase notification process completed.');
                } catch (\Exception $e) {
                    Log::error("❌ Exception while sending notification: {$e->getMessage()}");
                }
            }
        }
        

        return redirect('/dashboard/naptar')->with('success', 'Esemény sikeresen frissítve!');
    }

    // 🌐 Naptár API (JSON)
public function naptarAPI(Request $request)
{
    $auth = $request->header('Authorization');
    $timestamp = $request->header('X-Timestamp');
    $signature = $request->header('X-Signature');

    // 1️⃣ Check headers
    if (!$auth || !$timestamp || !$signature) {
        return response()->json(['error' => 'Missing headers'], 401);
    }

    // 2️⃣ Validate Bearer token format
    if (!str_starts_with($auth, 'Bearer ')) {
        return response()->json(['error' => 'Bad auth format'], 401);
    }

    // 3️⃣ Check token
    if (!hash_equals(env('API_TOKEN'), substr($auth, 7))) {
        return response()->json(['error' => 'Bad token'], 401);
    }

    // 4️⃣ Timestamp freshness (5 min window)
    if (abs(time() - (int)$timestamp) > 300) {
        return response()->json(['error' => 'Expired'], 401);
    }

    // 5️⃣ Verify HMAC signature
    $expected = hash_hmac('sha256', $timestamp, env('API_TOKEN'));
    if (!hash_equals($expected, $signature)) {
        return response()->json(['error' => 'Bad signature'], 401);
    }

    // 6️⃣ Fetch events
    $events = Naptar::all()->map(function ($event) {
        $kep = \App\Models\Kepfeltoltes::where('event_type', $event->event_type)->first();

        return [
            'id' => $event->id,
            'title' => $event->title,
            'date' => $event->date,
            'start_time' => substr($event->start_time, 0, 5),
            'end_time' => substr($event->end_time, 0, 5),
            'event_type' => $event->event_type,
            'event_type_img' => $kep ? asset($kep->event_type_img) : null,
            'description' => $event->description,
            'status' => $event->status,
            'link' => $event->link,
        ];
    });

    return response()->json($events);
}
}
