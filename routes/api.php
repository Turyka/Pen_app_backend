<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HirController;
use App\Http\Controllers\NaptarController;
use App\Http\Controllers\EszkozokController;
use App\Http\Controllers\KozlemenyController;
use Illuminate\Http\Request;

    //Route::get('/felhasznalo', [UserController::class, 'getUsers']);
    //Route::post('/felhasznalo', [UserController::class, 'addUser']);
    //Route::post('/login', [UserController::class, 'bejelentkezes']);

    Route::get('/hirekAPI', [HirController::class, 'apiIndex']);

    Route::post('/napi-bejelentkezes', [EszkozokController::class, 'napi']);

    Route::get('/naptarAPI', [NaptarController::class, 'NaptarAPI']);

    Route::get('/kozlemenyAPI', [KozlemenyController::class, 'KozlemenyAPI']);


    // eszkozok
    Route::post('/eszkozok', [EszkozokController::class, 'store']);

    /*
    Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin-only', function (Request $request) {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json(['message' => 'Welcome, Admin!']);    
    });
        
    });
    */