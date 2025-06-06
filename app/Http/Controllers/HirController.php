<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Artisan;
use App\Models\Hir;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use DOMDocument;
use DOMXPath;

class HirController extends Controller
{

    public function seedDatabase()
    {


    Artisan::call('migrate:refresh', ['--force' => true]);
    Artisan::call('db:seed');

    return response()->json(['message' => 'Database refreshed and seeded successfully.']);
    }
    
    public function scrape()
    {
        Artisan::call('scrape:hirek');

        return response()->json(['message' => 'Sikeres lekérés']);
    }

    // Show saved news
    public function index(Request $request)
    {
        $hirek = \App\Models\Hir::latest()->get();

        return view('index', compact('hirek'));
    }

    public function apiIndex(Request $request)
    {
        $perPage = $request->get('per_page', 5);

        $hirek = Hir::orderBy('id', 'desc')->paginate($perPage);

        return response()->json($hirek);
    }

    public function torol()
    {
        Hir::truncate();

        return redirect('/hirek');
    }

}

