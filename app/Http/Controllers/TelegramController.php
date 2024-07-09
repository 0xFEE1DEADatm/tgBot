<?php

namespace App\Http\Controllers;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Http\Request;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;

class TelegramController extends Controller
{
    public function index(Request $request, Nutgram $bot)
    {
        \Log::info('Webhook received');
        

        // $chat_id = $bot -> message() -> from -> id;
        // $bot->sendMessage(chat_id: $chat_id, text: $response);


        // $bot->onCommand('start', function (Nutgram $bot) {
        //     $currentTime = date('H:i:s');
        //     $response = "Привет! Текущее время: " . $currentTime;

        //     $chat_id = $bot -> message() -> from -> id;
        //     $bot->sendMessage(chat_id: $chat_id, text: $response);
        // });

       

        // $bot->onCommand('start', function(Nutgram $bot){
        //     $bot->sendMessage(
        //         text: 'Do you want to know what`s time is it?',
        //         reply_markup: ReplyKeyboardMarkup::make()->addRow(
        //             KeyboardButton::make('What time is it?'),
        //         )
        //     );
        // });

    
        // $bot->onText('What time is it?', function (Nutgram $bot) {
        //     $currentTime = date('H:i:s');
        //     $response = "Привет! Текущее время: " . $currentTime;
        //     $chat_id = $bot -> message() -> from -> id;

        //     $bot->sendMessage(chat_id: $chat_id, text: $response);
        // });
        
        $bot->onCommand('start', function(Nutgram $bot){
            $bot->sendMessage(
                text: 'Hello!',
                reply_markup: ReplyKeyboardMarkup::make()->addRow(
                    KeyboardButton::make('Time'),
                )
            );
        });
        
        $bot->onText('Time', function (Nutgram $bot) {
            $currentTime = date('H:i:s');
            $response = 'Now is ' . $currentTime;;
            $chat_id = $bot -> message() -> from -> id;

            $bot->sendMessage(chat_id: $chat_id, text: $response);
        });
        
        $bot->run();
    }

}