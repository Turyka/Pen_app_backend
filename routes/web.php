<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HirController;
use App\Http\Controllers\NaptarController;
use App\Http\Controllers\PostokController;
use App\Http\Controllers\KezdoController;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('login');
})->name('login');

Route::post('/login', [KezdoController::class, 'authenticate'])->name('login_store');

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');



//HIREK
Route::get('/hirek', [HirController::class, 'index']);
Route::get('/hirek/kap', [HirController::class, 'scrape']);
Route::get('/hirek/torol', [HirController::class, 'torol']);

//Dashboard
Route::get('/dashboard/main', [KezdoController::class, 'dashboard'])->name('dashboard')->middleware('auth');


//Naptár
Route::get('/dashboard/naptar', [KezdoController::class, 'naptar'])->name('naptar')->middleware('auth');

//NAPTÁR Készít
Route::get('/dashboard/naptar/keszit', [NaptarController::class, 'keszit'])->name('keszit')->middleware('auth');
Route::post('/dashboard/naptar/store', [NaptarController::class, 'store'])->name('naptar.store')->middleware('auth');

//Naptár Update
Route::get('/dashboard/naptar/{naptar}/edit', [NaptarController::class, 'edit'])->name('naptar.edit')->middleware('auth');
Route::put('/dashboard/naptar/{naptar}', [NaptarController::class, 'update'])->name('naptar.update')->middleware('auth');
Route::delete('/dashboard/naptar/{naptar}', [NaptarController::class, 'destroy'])->name('naptar.destroy')->middleware('auth');

Route::get('/scrape-postok', [PostokController::class, 'scrape']);



Route::get('/ping', function () {
    Log::info('Pinged at: ' . now());
    return response('pong', 200);
});


