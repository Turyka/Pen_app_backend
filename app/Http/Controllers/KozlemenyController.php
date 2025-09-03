<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Kozlemeny;
use App\Models\Eszkozok;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class KozlemenyController extends Controller
{
    public function keszit()
    {
        return view('kozlemeny_keszit');
    }

    // 📩 Naptár mentése (POST)
    public function store(Request $request, Kozlemeny $kozlemeny)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'string|nullable',
            'ertesites' => 'boolean',
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

        // Create record
        Kozlemeny::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'ertesites' => $request->input('ertesites'),
            'created' => $user->teljes_nev,
        ]);

        // 🔔 Handle notifications
        if ($request->input('ertesites')) {
            $tokens = Eszkozok::where('kozlemenyErtesites', true)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->toArray();

            if (empty($tokens)) {
                Log::warning('No FCM tokens found in DB for notifications.', ['tokens' => $tokens]);
                dd('No FCM tokens found in DB!'); // stop here for debugging
            }

            try {
                $firebase = app(FirebaseService::class);
                $firebase->sendNotification(
                    $tokens,
                    $request->input('title'),
                    $request->input('description') ?? ''
                );
                Log::info('Firebase notification process completed.');
            } catch (\Exception $e) {
                Log::error("❌ Exception while sending notification: {$e->getMessage()}");
                dd("❌ Firebase did not return a result. Check env path or API config.");
            }
        }

        return redirect('/dashboard/kozlemeny')->with('success', 'Esemény sikeresen mentve!');
    }

    
    public function destroy(Kozlemeny $kozlemeny)
    {
    $kozlemeny->delete();

    return redirect()->route('kozlemeny')->with('success', 'Esemény sikeresen törölve!');
    }

    public function edit(Kozlemeny $kozlemeny)
    {
        return view('kozlemeny_edit', compact('kozlemeny'));
    }

        // 🔄 közlemény frissítése (PUT)
    public function update(Request $request, Kozlemeny $kozlemeny)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'string',
            'ertesites' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        
         $user = Auth::user();

        if (!$user) {
            return redirect()->back()->with('error', 'Hozzáférés megtagadva: nem bejelentkezett felhasználó.');
        }

        $kozlemeny->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'ertesites' => $request->input('ertesites'),
            'created' => $user->teljes_nev,
        ]);

        return redirect('/dashboard/kozlemeny')->with('success', 'Esemény sikeresen frissítve!');
    }

    // 🌐 Naptár API (JSON)
    public function KozlemenyAPI(Request $request)
    {
        if ($request->query('titkos') !== env('API_SECRET')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $events = Kozlemeny::all()->map(function ($event) {
            return [
            'title' => $event->title,
            'description' => $event->description,
            'ertesites' => $event->ertesites,
            'created' => $event->created,
            'updated_at'  => $event->updated_at ? $event->updated_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json($events);
    }

}
