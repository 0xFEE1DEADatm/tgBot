<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
use SergiX44\Nutgram\Nutgram;
use App\Http\Controllers\ProductController;

Route::post('/webhook', [TelegramController::class, 'index']);


