<?php

namespace App\Http\Controllers;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Http\Request;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
<<<<<<< HEAD
=======
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

use Nutgram\Handlers\CommandHandler;
use Nutgram\Handlers\TextHandler;

use App\Models\Product;
// use App\Models\Message;
// use App\Models\OrderHistory;
// use App\Models\Customers;
>>>>>>> 21403cc (Initial commit)

class TelegramController extends Controller
{
    public function index(Request $request, Nutgram $bot)
    {
        \Log::info('Webhook received');
        
        $bot->onCommand('start', function(Nutgram $bot){
<<<<<<< HEAD
            $bot->sendMessage(
                text: 'Hello!',
                reply_markup: ReplyKeyboardMarkup::make()->addRow(
                    KeyboardButton::make('Time'),
                )
            );
        });
        
        $bot->onText('Time', function (Nutgram $bot) {
            date_default_timezone_set('Europe/Moscow');
            $currentTime = date('H:i:s');
            $response = 'Now is ' . $currentTime;;
            $chat_id = $bot -> message() -> from -> id;

            $bot->sendMessage(chat_id: $chat_id, text: $response);
        });
        
        $bot->run();
    }

}
=======
            $products = Product::all();
            $product = $products->first(); 
            $chat_id = $bot->message()->from->id;

            $response = "Name: " . $product->title . "\n";
            $response .= "Description: " . $product->description . "\n";
            $response .= "Price: " . $product->price;
            
            $keyboard = InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make('Buy', callback_data: 'buy_' . $product->id)
            );

            $bot->sendMessage(
                text: $response,
                chat_id: $chat_id,
                reply_markup: $keyboard
            );
        });
        
        $bot->onCallbackQuery(function (Nutgram $bot) {
            $callbackData = $bot->callbackQuery()->data;
            
            if (strpos($callbackData, 'buy_') === 0) {
                $productId = substr($callbackData, strlen('buy_'));
                $product = Product::findOrFail($productId);
    
                $response = "You have chosen '{$product->title}'. Now you can pay for it.";
                $chat_id = $bot->callbackQuery()->from->id;
    
                $bot->sendMessage(chat_id: $chat_id, text: $response);
            }
        });

        $bot->run();
    }
}


//  \Log::info('Webhook received');
        
//         $bot->onCommand('start', function(Nutgram $bot){
//             $bot->sendMessage(
//                 text: 'Hello!',
//                 reply_markup: ReplyKeyboardMarkup::make()->addRow(
//                     KeyboardButton::make('Time'),
//                 )
//             );
//         });
        
//         $bot->onText('Time', function (Nutgram $bot) {
//             date_default_timezone_set('Europe/Moscow');
//             $currentTime = date('H:i:s');
//             $response = 'Now is ' . $currentTime;;
//             $chat_id = $bot -> message() -> from -> id;

//             $bot->sendMessage(chat_id: $chat_id, text: $response);
//         });
        
//         $bot->run();


// $customers = Customers::all();
        // $orders = OrderHistory::all();
        // $message = Message::all();
        
 // if ($products->isNotEmpty()) {
            //     $product = $products->first(); 
            //     $chat_id = $bot->message()->from->id;

            //     $response = "Название: " . $product->title . "\n";
            //     $response .= "Описание: " . $product->description . "\n";
            //     $response .= "Цена: " . $product->price;

            //     $bot->sendMessage(
            //         chat_id: $chat_id,
            //         text: $response,
            //     );

            // } else {
            //     $text = "Продукт не найден.";
            //     $chat_id = $bot->message()->from->id;
            //     $bot->sendMessage(
            //         chat_id: $chat_id,
            //         text: $text,
            //     );
            // }

            // if ($customers->isNotEmpty()) {
            //     $customer = $customers->first(); 
            //     $chat_id = $bot->message()->from->id;

            //     $response = "ID покупателя: " . $customer->telegram_id . "\n";
            //     $response .= "Покупатель: " . $customer->customer_name . "\n";

            //     $bot->sendMessage(
            //         chat_id: $chat_id,
            //         text: $response,
            //     );

            // } else {
            //     $text = "Покупатель не найден.";
            //     $chat_id = $bot->message()->from->id;
            //     $bot->sendMessage(
            //         chat_id: $chat_id,
            //         text: $text,
            //     );
            // }

// $bot->onText('Buy', function (Nutgram $bot) {
        //     $response = '"Buying"';
        //     $chat_id = $bot -> message() -> from -> id;

        //     $bot->sendMessage(chat_id: $chat_id, text: $response);
        // });
>>>>>>> 21403cc (Initial commit)
