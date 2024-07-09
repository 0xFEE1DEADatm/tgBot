<?php

use App\Http\Controllers\TelegramController;

return [
    'token' => env('TELEGRAM_BOT_TOKEN'),
    'handlers' => [
        TelegramController::class,
    ],
];


// curl -F "url=https://44ee-109-106-141-75.ngrok-free.app/api/webhook" "https://api.telegram.org/bot7231228257:AAFqKynvpKj_1GA0f_foa86x5D9f3ZSMu30/setWebhook"
// curl "https://api.telegram.org/bot/7231228257:AAFqKynvpKj_1GA0f_foa86x5D9f3ZSMu30/getWebhookInfo"
//curl -X POST "https://api.telegram.org/bot"7231228257:AAFqKynvpKj_1GA0f_foa86x5D9f3ZSMu30"/sendMessage" -d "chat_id="994019490"&text=Привет!"


