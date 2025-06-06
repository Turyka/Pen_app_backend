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
        try {
            Eszkozok::create([
                'device_id'   => $request->input('id'),
                'device'      => $request->input('device'),
                'os'          => $request->input('os'),
                'app_version' => $request->input('appVersion'),
            ]);
    
            return response()->json(['message' => 'Device usage stored'], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to store device usage',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
