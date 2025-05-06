<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HirController;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
//napelem projecthez
//Route::get('/register', [UserController::class,'create']);
//Route::post('/users', [UserController::class,'store']);

Route::get('/hirek', [HirController::class, 'index']);
Route::get('/hirek/scrape', [HirController::class, 'scrape']); // optional manual trigger

Route::get('/ping', function () {
    Log::info('Pinged at: ' . now()); // Optional: logs each ping
    return response('pong', 200);
});


