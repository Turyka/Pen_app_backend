<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HirController;
use App\Http\Controllers\NaptarController;
use Illuminate\Http\Request;

    //Route::get('/felhasznalo', [UserController::class, 'getUsers']);
    //Route::post('/felhasznalo', [UserController::class, 'addUser']);
    //Route::post('/login', [UserController::class, 'bejelentkezes']);

    Route::get('/hirekAPI', [HirController::class, 'apiIndex']);



    Route::get('/naptarAPI', [NaptarController::class, 'NaptarAPI']);


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