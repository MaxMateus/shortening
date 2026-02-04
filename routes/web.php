<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortUrlController;

Route::get('/', function () {
    return view('welcome');
});



Route::post('/short', [ShortUrlController::class, 'store']);

Route::get('/stats/{code}', [ShortUrlController::class, 'stats']);

Route::get('/{code}', [ShortUrlController::class, 'redirect'])
    ->name('short.redirect')
    ->middleware('throttle:short-url-redirect');
