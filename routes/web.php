<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HirController;
use App\Http\Controllers\NaptarController;
use App\Http\Controllers\PostokController;
use App\Http\Controllers\KezdoController;
use App\Http\Controllers\EszkozokController;
use App\Http\Controllers\KozlemenyController;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\KepfeltoltesController;
use App\Http\Controllers\DatabaseController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;



Route::get('/scrape-facebook', function (\Illuminate\Http\Request $request) {

    // 🔐 Check API secret
    if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json([
            'success' => false,
            'error' => 'Unauthorized'
        ], 403);
    }

    $pythonPath = public_path('scrape_facebook.py');

    $cmd = sprintf('cd %s && python3 %s 2>&1', public_path(), basename($pythonPath));
    $output = shell_exec($cmd);

    Log::info('Facebook scrape command', [
        'cmd' => $cmd,
        'output_length' => strlen($output ?? '')
    ]);

    $data = json_decode($output ?? '{}', true);

    if (!$data || $data['status'] === 'error') {
        return response()->json([
            'success' => false,
            'error' => $data['error'] ?? 'No data',
            'raw_output' => $output,
        ], 500);
    }

    $post = $data['latest_1'];

    // ❌ Skip if same title already exists
    $exists = DB::table('facebook_posts')
        ->where('title', $post['title'])
        ->exists();

    if ($exists) {
        return response()->json([
            'success' => true,
            'saved' => false,
            'skipped' => true,
            'reason' => 'Same title already exists'
        ]);
    }

    // ✅ Save only if new
    DB::table('facebook_posts')->insert([
        'title' => $post['title'],
        'url' => $post['url'],
        'image_url' => $post['image_url'],
        'updated_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'saved' => true,
        'post' => $post
    ]);
});


Route::get('/instagram-crawl', [App\Http\Controllers\InstaCrawlerController::class, 'crawl']);

// Commandok
Route::get('/commandok', [KezdoController::class, 'command'])->middleware('auth');

Route::get('/', function () {
    return view('login');
})->name('login')->middleware('guest');

Route::post('/login', [KezdoController::class, 'authenticate'])
    ->name('login_store')
    ->middleware('throttle:10,1'); 

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

Route::get('/database/backup', [DatabaseController::class, 'backup']);
Route::get('/database/telefon/refresh', [DatabaseController::class, 'refreshAdatEszkozok'])->middleware('auth');
Route::get('/database/restore-newest', [DatabaseController::class, 'restoreNewest']);



//HIREK 
Route::get('/hirek', [HirController::class, 'index']);
Route::get('/hirek/kap', [HirController::class, 'scrape']);
Route::get('/hirek/torol', [HirController::class, 'torol']);
Route::get('/seed', [HirController::class, 'seedDatabase']);
Route::get('/migrate-refresh', [DatabaseController::class, 'migrateRefresh']);



//Dashboard
Route::get('/dashboard/main', [KezdoController::class, 'dashboard'])->name('dashboard')->middleware('auth');


//Naptár
Route::get('/dashboard/naptar', [KezdoController::class, 'naptar'])->name('naptar')->middleware('auth');

//NAPTÁR Készít
Route::get('/dashboard/naptar/keszit', [NaptarController::class, 'keszit'])->name('keszit_naptar')->middleware('auth');
Route::post('/dashboard/naptar/store', [NaptarController::class, 'store'])->name('naptar.store')->middleware('auth');

//Naptár Update
Route::get('/dashboard/naptar/{naptar}/edit', [NaptarController::class, 'edit'])->name('naptar.edit')->middleware('auth');
Route::put('/dashboard/naptar/{naptar}', [NaptarController::class, 'update'])->name('naptar.update')->middleware('auth');
Route::delete('/dashboard/naptar/{naptar}', [NaptarController::class, 'destroy'])->name('naptar.destroy')->middleware('auth');

//Kozlemény
Route::get('/dashboard/kozlemeny', [KezdoController::class, 'kozlemeny'])->name('kozlemeny')->middleware('auth');

//Kozlemenyek Készít
Route::get('/dashboard/kozlemeny/keszit', [KozlemenyController::class, 'keszit'])->name('keszit_kozlemeny')->middleware('auth');
Route::post('/dashboard/kozlemeny/store', [KozlemenyController::class, 'store'])->name('kozlemeny.store')->middleware('auth');

//Kozlemenyek Update
Route::get('/dashboard/kozlemeny/{kozlemeny}/edit', [KozlemenyController::class, 'edit'])->name('kozlemeny.edit')->middleware('auth');
Route::put('/dashboard/kozlemeny/{kozlemeny}', [KozlemenyController::class, 'update'])->name('kozlemeny.update')->middleware('auth');
Route::delete('/dashboard/kozlemeny/{kozlemeny}', [KozlemenyController::class, 'destroy'])->name('kozlemeny.destroy')->middleware('auth');



Route::get('/dashboard/kepfeltoltes', [KepfeltoltesController::class, 'index'])->name('kepfeltoltes')->middleware('auth');
Route::get('/kepfeltoltes/keszit', [KepfeltoltesController::class, 'create'])->name('kepfeltoltes.create')->middleware('auth');
Route::post('/kepfeltoltes/store', [KepfeltoltesController::class, 'store'])->name('kepfeltoltes.store')->middleware('auth');
Route::get('/dashboard/kepfeltoltes/{kepfeltoltes}/edit', [KepfeltoltesController::class, 'edit'])->name('kepfeltoltes.edit')->middleware('auth');
Route::delete('/kepfeltoltes/destroy/{kepfeltoltes}', [KepfeltoltesController::class, 'destroy'])->name('kepfeltoltes.destroy')->middleware('auth');
Route::put('/dashboard/kepfeltoltes/{kepfeltoltes}', [KepfeltoltesController::class, 'update'])->name('kepfeltoltes.update')->middleware('auth');


// User müveletek
Route::middleware(['auth', 'role:Admin,Elnök'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});


Route::get('/eszkozok/kiir', [EszkozokController::class, 'index'])->middleware('auth');

Route::get('/ping', function () {
    Log::info('Pinged at: ' . now());
    return response('pong', 200);
});


