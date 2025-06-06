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

    public function seedDatabase()
{
    // Ensure it's only run in local environment

    $output = new BufferedOutput();

    // Run migration
    $migrateStatus = Artisan::call('migrate:refresh', ['--force' => true], $output);
    $migrateOutput = $output->fetch();


    // Run seeding
    $seedStatus = Artisan::call('db:seed', [], $output);
    $seedOutput = $output->fetch();

    // Log outputs (optional)
    Log::info('Migration Output: ' . $migrateOutput);
    Log::info('Seeding Output: ' . $seedOutput);

    // Check both status codes
    if ($migrateStatus === 0 && $seedStatus === 0) {
        return response()->json([
            'message' => 'Database refreshed and seeded successfully.',
            'migrate_output' => $migrateOutput,
            'seed_output' => $seedOutput
        ]);
    }

    return response()->json([
        'message' => 'An error occurred during migration or seeding.',
        'migrate_status' => $migrateStatus,
        'seed_status' => $seedStatus,
        'migrate_output' => $migrateOutput,
        'seed_output' => $seedOutput
    ], 500);
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

