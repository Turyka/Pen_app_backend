<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Kozlemeny;
use App\Models\Eszkozok;
class KozlemenyController extends Controller
{
    public function keszit()
    {
        return view('kozlemeny_keszit');
    }

  private function sendPushNotification($tokens, $data)
{
    $serverKey = env('FCM_SERVER_KEY');

    $headers = [
        'Authorization: key=' . $serverKey,
        'Content-Type: application/json',
    ];

    $body = [
        'registration_ids' => $tokens, // list of FCM tokens
        'notification' => [
            'title' => $data['title'],
            'body'  => $data['body'],
        ],
        'data' => $data, // optional, custom data
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
    // 📩 Naptár mentése (POST)
    public function store(Request $request, Kozlemeny $kozlemeny)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'string',
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

        Kozlemeny::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'ertesites' => $request->input('ertesites'),
            'created' => $user->teljes_nev,
        ]);



        if ($request->input('ertesites')) {
        $tokens = Eszkozok::where('kozlemeny_ertesites', true)
        ->whereNotNull('fcm_token')
        ->pluck('fcm_token')
        ->toArray();
            
    if (!empty($tokens)) {
        $this->sendPushNotification($tokens, [
            'title' => $request->input('title'),
            'body'  => $request->input('description') ?? '',
        ]);
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
