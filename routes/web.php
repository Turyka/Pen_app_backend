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


// Commandok
Route::get('/commandok', [KezdoController::class, 'command']);

Route::get('/', function () {
    return view('login');
})->name('login')->middleware('guest');

Route::post('/login', [KezdoController::class, 'authenticate'])
    ->name('login_store')
    ->middleware('throttle:5,1'); 

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');



//HIREK
Route::get('/hirek', [HirController::class, 'index']);
Route::get('/hirek/kap', [HirController::class, 'scrape']);
Route::get('/hirek/torol', [HirController::class, 'torol']);
Route::get('/seed', [HirController::class, 'seedDatabase']);



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





Route::get('/scrape-postok', [PostokController::class, 'scrape']);

Route::get('/eszkozok/kiir', [EszkozokController::class, 'index']);

Route::get('/ping', function () {
    Log::info('Pinged at: ' . now());
    return response('pong', 200);
});


