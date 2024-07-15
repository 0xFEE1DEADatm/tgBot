<?php

namespace App\Http\Controllers;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Http\Request;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

use Nutgram\Handlers\CommandHandler;
use Nutgram\Handlers\TextHandler;

use App\Models\Product;
// use App\Models\Message;
// use App\Models\OrderHistory;
// use App\Models\Customers;

class TelegramController extends Controller
{
    public function index(Request $request, Nutgram $bot)
    {
        \Log::info('Webhook received');
        
        $bot->onCommand('start', function(Nutgram $bot){
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