<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Artisan;
use App\Models\Hir;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class HirController extends Controller
{

    public function scrape(Request $request)
    {

    
         if ($request->query('titkos') !== env('API_SECRET')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
        }

        // Capture the output of the command
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $result = Artisan::call('scrape:hirek', [], $output);
        
        $commandOutput = $output->fetch();
        
        return response()->json([
            'message' => 'Sikeres lekérés',
            'command_output' => $commandOutput
        ]);
    }

    // Show saved news
    public function index(Request $request)
    {
        $hirek = \App\Models\Hir::latest()->get();

        return view('index', compact('hirek'));
    }

    public function apiIndex(Request $request)
    {
        if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the latest 10 news
        $hirek = Hir::orderBy('id', 'desc')
                    ->take(10)
                    ->get();

        // Return only the data array
        return response()->json([
            'data' => $hirek
        ]);
    }

    public function torol(Request $request)
    {
        if ($request->query('titkos') !== env('API_SECRET')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
        }

        Hir::truncate();

        return redirect('/hirek');
    }

}

