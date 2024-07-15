<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
use SergiX44\Nutgram\Nutgram;
<<<<<<< HEAD
=======
use App\Http\Controllers\ProductController;
>>>>>>> 21403cc (Initial commit)

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

<<<<<<< HEAD
Route::post('/webhook', [TelegramController::class, 'index']);

=======
// Route::post('/webhook', [TelegramController::class, 'index']);

// Route::get('/product/title', [ProductController::class, 'showTitle']);

Route::post('/webhook', [TelegramController::class, 'index']);
>>>>>>> 21403cc (Initial commit)
