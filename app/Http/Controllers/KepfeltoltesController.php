<?php

namespace App\Http\Controllers;

use App\Models\Kepfeltoltes;
use Illuminate\Http\Request;
use Cloudinary\Cloudinary;

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
        'event_type_img' => 'required|image|mimes:jpg,jpeg,png|max:5120',
    ]);

    // Initialize Cloudinary with your CLOUDINARY_URL
    $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));

    // Upload the image
    $result = $cloudinary->uploadApi()->upload(
        $request->file('event_type_img')->getRealPath(),
        ['folder' => 'img_naptar']
    );

    // Get secure URL
    $uploadedFileUrl = $result['secure_url'];

    // Save to DB
    Kepfeltoltes::create([
        'event_type' => $request->event_type,
        'event_type_img' => $uploadedFileUrl,
    ]);

    return redirect("/dashboard/kepfeltoltes")
        ->with('success', 'Eseménytípus sikeresen feltöltve!');
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
        'event_type_img' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
    ]);

    // Név frissítése
    $kepfeltoltes->event_type = $request->event_type;

    // Ha új képet töltöttek fel
    if ($request->hasFile('event_type_img')) {
        // Régi kép törlése, ha létezik
        if ($kepfeltoltes->event_type_img && file_exists(public_path($kepfeltoltes->event_type_img))) {
            unlink(public_path($kepfeltoltes->event_type_img));
        }

        // Új fájlnév generálása
        $filename = uniqid() . '.' . $request->file('event_type_img')->getClientOriginalExtension();

        // Kép áthelyezése public/img_naptar mappába
        $request->file('event_type_img')->move(public_path('img_naptar'), $filename);

        // Adatbázis frissítése új fájlnévvel
        $kepfeltoltes->event_type_img = 'img_naptar/' . $filename;
    }

    $kepfeltoltes->save();

    return redirect("/dashboard/kepfeltoltes")->with('success', 'Eseménytípus sikeresen frissítve!');
}



public function destroy(Kepfeltoltes $kepfeltoltes)
{
    // Töröljük a képfájlt, ha létezik
    if ($kepfeltoltes->event_type_img && file_exists(public_path($kepfeltoltes->event_type_img))) {
        unlink(public_path($kepfeltoltes->event_type_img));
    }

    // Adatbázis rekord törlése
    $kepfeltoltes->delete();
    return redirect()->route('kepfeltoltes')->with('success', 'Esemény sikeresen törölve!');
}
}
