<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Eszkozok;
class EszkozokController extends Controller
{
    public function index()
    {
        $devices = Eszkozok::orderBy('created_at', 'desc')->get();
    return view('eszkozok', compact('devices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'device' => 'required|string',
            'os' => 'required|string',
            'appVersion' => 'required|string',
        ]);

        Eszkozok::updateOrCreate(
            ['device_id' => $validated['id']], // or 'uuid' if your table uses that
            [
                'device' => $validated['device'],
                'os' => $validated['os'],
                'app_version' => $validated['appVersion'],
            ]
        );

        return response()->json(['success' => true]);
    }
}
