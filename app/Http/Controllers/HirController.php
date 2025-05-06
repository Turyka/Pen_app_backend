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
    
    public function scrape()
    {
        Artisan::call('scrape:hirek');

        return response()->json(['message' => 'Scraping triggered.']);
    }

    // Show saved news
    public function index()
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

}

