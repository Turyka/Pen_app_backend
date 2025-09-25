<?php

namespace App\Http\Controllers;

use App\Models\Kepfeltoltes;
use Illuminate\Http\Request;

class KepfeltoltesController extends Controller
{

    public function index()
    {
        $kepfeltoltesek = \App\Models\Kepfeltoltes::paginate(10);


    return view('dashboard.kepfeltoltes', compact('kepfeltoltesek'));
    }

    public function create()
    {
        return view('kepfeltoltes_keszit');
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_type' => 'required|string|max:255',
            'event_type_img' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Store image in public/img
        $path = $request->file('event_type_img')->store('img', 'public');

        // Save in DB
        Kepfeltoltes::create([
            'event_type' => $request->event_type,
            'event_type_img' => 'storage/' . $path,
        ]);

        return redirect()->back()->with('success', 'Eseménytípus sikeresen feltöltve!');
    }

    public function edit(Kepfeltoltes $kepfeltoltes)
    {

        return view('kepfeltoltes_edit', compact('kepfeltoltes'));
    }

    public function update(Request $request, $id)
{
    $kepfeltoltes = Kepfeltoltes::findOrFail($id);

    $request->validate([
        'event_type' => 'required|string|max:255',
        'event_type_img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    // Név frissítése
    $kepfeltoltes->event_type = $request->event_type;

    if ($request->hasFile('event_type_img')) {
        if ($kepfeltoltes->event_type_img && file_exists(public_path($kepfeltoltes->event_type_img))) {
            unlink(public_path($kepfeltoltes->event_type_img));
        }

        $path = $request->file('event_type_img')->store('img', 'public');
        $kepfeltoltes->event_type_img = 'storage/' . $path;
    }

    $kepfeltoltes->save();

    return redirect()->back()->with('success', 'Eseménytípus sikeresen frissítve!');
}

}
