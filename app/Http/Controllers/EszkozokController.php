<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Eszkozok;
class EszkozokController extends Controller
{
    public function index()
    {
        $devices = Eszkozok::table('adat_eszkozok')->orderBy('created_at', 'desc')->get();
        return view('eszkozok', compact('devices'));
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|uuid',
            'device' => 'nullable|string',
            'os' => 'nullable|string',
            'appVersion' => 'nullable|string',
        ]);

        Eszkozok::updateOrCreate(
            ['device_id' => $validated['id']],
            [
                'device' => $validated['device'],
                'os' => $validated['os'],
                'app_version' => $validated['appVersion'],
            ]
        );

        return response()->json(['message' => 'Usage logged']);
    }
}
