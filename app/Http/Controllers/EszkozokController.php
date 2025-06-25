<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Eszkozok;
use App\Models\Napilogin;
class EszkozokController extends Controller
{
    public function index()
    {
        $devices = Eszkozok::orderBy('created_at', 'desc')->get();
        $napi = Napilogin::orderBy('created_at', 'desc')->get();
        dd($napi);
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
        ]);

        Napilogin::create([
            'device_id' => $validated['id'],
            'datetime' => $validated['datetime'],
        ]);

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
        ]);

        Eszkozok::updateOrCreate(
            ['device_id' => $validated['id']],
            [
                'device' => $validated['device'],
                'os' => $validated['os'],
            ]
        );

        return response()->json(['success' => true]);
    }
}
