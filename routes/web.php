<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DartController;

Route::get('/', [DartController::class, 'setup'])->name('dart.setup');
Route::post('/start', [DartController::class, 'startGame'])->name('dart.start');
Route::get('/game', [DartController::class, 'index'])->name('dart.index');
Route::post('/throw', [DartController::class, 'throwDart'])->name('dart.throw');
Route::post('/reset', [DartController::class, 'resetGame'])->name('dart.reset');
