<?php

use App\Http\Controllers\BoardController;
use Illuminate\Support\Facades\Route;

Route::get('/boards', [BoardController::class, 'apiIndex']);
Route::post('/boards', [BoardController::class, 'apiStore']);
Route::get('/boards/{board}', [BoardController::class, 'apiShow']);
Route::put('/boards/{board}', [BoardController::class, 'apiUpdate']);
Route::delete('/boards/{board}', [BoardController::class, 'apiDestroy']);
