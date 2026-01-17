<?php

use App\Http\Controllers\Api\SourceController;
use Illuminate\Support\Facades\Route;

Route::get('/sources', [SourceController::class, 'index']);
Route::post('/sources', [SourceController::class, 'store']);
Route::patch('/sources/{source}', [SourceController::class, 'update']);
Route::delete('/sources/{source}', [SourceController::class, 'destroy']);
