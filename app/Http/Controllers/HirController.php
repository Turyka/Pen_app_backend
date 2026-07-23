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
        $auth = $request->header('Authorization');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if (!$auth || !$timestamp || !$signature) {
            return response()->json(['error' => 'Missing headers'], 401);
        }

        if (!str_starts_with($auth, 'Bearer ')) {
            return response()->json(['error' => 'Bad auth format'], 401);
        }

        if (!hash_equals(env('API_TOKEN'), substr($auth, 7))) {
            return response()->json(['error' => 'Bad token'], 401);
        }

        if (abs(time() - (int)$timestamp) > 300) {
            return response()->json(['error' => 'Expired'], 401);
        }

        $expected = hash_hmac('sha256', $timestamp, env('API_TOKEN'));
        if (!hash_equals($expected, $signature)) {
            return response()->json(['error' => 'Bad signature'], 401);
        }

        $hirek = Hir::orderBy('id', 'desc')
                    ->take(10)
                    ->get();

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

