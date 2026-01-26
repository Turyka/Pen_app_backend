<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Eszkozok;
use App\Models\Napilogin;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class EszkozokController extends Controller
{

public function reset()
    {
        Schema::dropIfExists('napi_login');

        Schema::create('napi_login', function (Blueprint $table) {
        $table->id();
        $table->string('device_id');
        $table->date('datetime');
        $table->string('fcm_token')->nullable();
        $table->timestamps();
        });

        return 'facebook_posts refreshed';
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
    if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $validated = $request->validate([
        'id' => 'required|string',
        'datetime' => 'required|date',
        'fcm_token' => 'nullable|string',
    ]);

    Napilogin::updateOrCreate(
        [
            'device_id' => $validated['id'],
            'datetime' => $validated['datetime']
        ],
        [
            'fcm_token' => $validated['fcm_token'] ?? null
        ]
    );

    return response()->json(['message' => 'Napi bejelentkezés sikeres']);
}

    public function store(Request $request)
    {
        if ($request->query('titkos') !== env('API_SECRET')) {
            return response()->json(['error' => 'Unauthorized'], 401);
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
