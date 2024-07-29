<?php

namespace App\Http\Controllers;
use App\Models\Message;

class MessageController extends Controller
{
    public function showTitle()
    {
        $message = Message::first();

        // Проверка
        if ($message) {
            return response()->json(['message' => 'Message title: ' . $message->title]);
        } else {
            return response()->json(['message' => 'No products found']);
        }
    }
}
