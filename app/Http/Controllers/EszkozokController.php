<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Eszkozok;
use App\Models\Napilogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class EszkozokController extends Controller
{


private function checkSecureAuth(Request $request)
{
    $auth = $request->header('Authorization');
    $timestamp = $request->header('X-Timestamp');
    $signature = $request->header('X-Signature');

    if (!$auth || !$timestamp || !$signature) {
        return response()->json(['error' => 'Missing headers'], 401);
    }

    if (!str_starts_with($auth, 'Bearer ')) {
        return response()->json(['error' => 'Bad auth format'], 401);
    }

    $token = substr($auth, 7);

    if (!hash_equals(env('API_TIKTOK'), $token)) {
        return response()->json(['error' => 'Bad token'], 401);
    }

    if (abs(time() - (int)$timestamp) > 300) {
        return response()->json(['error' => 'Expired request'], 401);
    }

    $expected = hash_hmac('sha256', $timestamp, env('API_TIKTOK'));

    if (!hash_equals($expected, $signature)) {
        return response()->json(['error' => 'Bad signature'], 401);
    }

    return null; // ✅ OK
}



    public function index()
    {
        $devices = Eszkozok::orderBy('created_at', 'desc')->get();
        $napi = Napilogin::orderBy('created_at', 'desc')->get();
        dd($devices);
    return view('eszkozok', compact('devices'));
    }

    public function napi(Request $request)
    {
        if ($authError = $this->checkSecureAuth($request)) {
            return $authError;
        }

        $validated = $request->validate([
            'id' => 'required|string',
            'datetime' => 'required|date',
            'fcm_token' => 'nullable|string',
        ]);

        Napilogin::updateOrCreate(
            [
                'device_id' => $validated['id'],
                'datetime' => $validated['datetime'],
            ],
            [
                'fcm_token' => $validated['fcm_token'] ?? null,
            ]
        );

        return response()->json(['message' => 'Napi bejelentkezés sikeres']);
    }

    public function store(Request $request)
    {
        if ($authError = $this->checkSecureAuth($request)) {
            return $authError;
        }

        $validated = $request->validate([
            'id' => 'required|string',
            'device' => 'required|string',
            'os' => 'required|string',
            'fcm_token' => 'required|string',
        ]);

        Eszkozok::updateOrCreate(
            ['device_id' => $validated['id']],
            [
                'device' => $validated['device'],
                'os' => $validated['os'],
                'fcm_token' => $validated['fcm_token'],
            ]
        );

        return response()->json(['success' => true]);
    }

public function update(Request $request, $id)
{
    // Use device_id to find the record
    $eszkoz = Eszkozok::where('device_id', $id)->first();

    if (!$eszkoz) {
        return response()->json(['error' => 'Device not found'], 404);
    }

    // Only update what is sent; device & os optional
    $validated = $request->validate([
        'naptarErtesites' => 'boolean|nullable',
        'kozlemenyErtesites' => 'boolean|nullable',
        'device' => 'nullable|string|max:255',
        'os' => 'nullable|string|max:255',
    ]);

    $eszkoz->update($validated);

    return response()->json([
        'message' => 'Updated successfully',
        'data' => $eszkoz,
    ]);
}

}
