<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HirController;
use App\Http\Controllers\NaptarController;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


//HIREK
Route::get('/hirek', [HirController::class, 'index']);
Route::get('/hirek/scrape', [HirController::class, 'scrape']); // optional manual trigger

//NAPTÁR
Route::get('/naptar', [NaptarController::class, 'index']);
Route::post('/naptar/store', [NaptarController::class, 'store'])->name('events.store');



Route::get('/ping', function () {
    Log::info('Pinged at: ' . now());
    return response('pong', 200);
});


