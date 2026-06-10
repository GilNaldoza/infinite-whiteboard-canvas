<?php

use App\Http\Controllers\BoardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('boards.index');
});

Route::get('/boards', [BoardController::class, 'index'])->name('boards.index');
Route::post('/boards', [BoardController::class, 'store'])->name('boards.store');
Route::get('/boards/create', [BoardController::class, 'create'])->name('boards.create');
Route::get('/boards/{board}', [BoardController::class, 'show'])->name('boards.show');
Route::put('/boards/{board}', [BoardController::class, 'update'])->name('boards.update');
Route::delete('/boards/{board}', [BoardController::class, 'destroy'])->name('boards.destroy');

Route::redirect('/whiteboards', '/boards')->name('whiteboards.index');
Route::redirect('/whiteboard/create', '/boards/create')->name('whiteboard.create');

