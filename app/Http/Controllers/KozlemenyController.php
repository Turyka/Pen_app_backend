<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
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
            'type' => 'integer',
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
            'type' => $request->input('type'),
            'created' => $user->teljes_nev,
            'user_id' => $user->id,
        ]);

    if ($request->input('ertesites')) {
        $tokens = Eszkozok::where('kozlemenyErtesites', true)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            Log::warning('Nincs ilyen token egy telfonon se');
        } else {
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
            }
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
        if ($kozlemeny->user_id !== auth()->user()->id) {
        abort(403, 'Hozzáférés megtagadva.');
        }
        return view('kozlemeny_edit', compact('kozlemeny'));
    }

        // 🔄 közlemény frissítése (PUT)
    public function update(Request $request, Kozlemeny $kozlemeny)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'string',
            'ertesites' => 'boolean',
            'type' => 'integer',
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
            'type' => $request->input('type'),
            'created' => $user->teljes_nev,
        ]);

        return redirect('/dashboard/kozlemeny')->with('success', 'Esemény sikeresen frissítve!');
    }

    // 🌐 Naptár API (JSON)
  public function KozlemenyAPI(Request $request)
{
    // 🔐 Header-based auth
    $authHeader = $request->header('Authorization');
    $timestamp  = $request->header('X-Timestamp');
    $signature  = $request->header('X-Signature');

    if (!$authHeader || !$timestamp || !$signature) {
        return response()->json(['error' => 'Missing headers'], 401);
    }

    $token = str_replace('Bearer ', '', $authHeader);

    if ($token !== env('API_TOKEN')) {
        return response()->json(['error' => 'Bad token'], 401);
    }

    $expectedSignature = hash_hmac(
        'sha256',
        $timestamp,
        env('API_TOKEN')
    );

    if (!hash_equals($expectedSignature, $signature)) {
        return response()->json(['error' => 'Bad signature'], 401);
    }

    // ⏱️ Optional replay protection (±5 minutes)
    if (abs(time() - (int)$timestamp) > 300) {
        return response()->json(['error' => 'Expired request'], 401);
    }

    // ✅ Business logic (unchanged)
    $events = Kozlemeny::orderBy('created', 'desc')
        ->take(20)
        ->get()
        ->map(function ($event) {
            return [
                'title'       => $event->title,
                'description' => $event->description,
                'ertesites'   => $event->ertesites,
                'type'        => $event->type,
                'created'     => $event->created,
                'updated_at'  => $event->updated_at
                    ? $event->updated_at->format('Y-m-d H:i:s')
                    : null,
            ];
        });

    return response()->json($events);
}

}
