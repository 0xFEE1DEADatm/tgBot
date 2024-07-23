<?php

namespace App\Http\Controllers;

use SergiX44\Nutgram\Nutgram;
use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

use Telegram\Bot\Api;
// use Telegram\Bot\Keyboard\Keyboard;
// use Telegram\Bot\Objects\LabeledPrice;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Inline\CallbackQuery;
use SergiX44\Nutgram\Telegram\Types\Payment\LabeledPrice;
use SergiX44\Nutgram\Handlers\CollectHandlers;

use App\Models\Product;
use App\Models\Message;


class TelegramController extends Controller
{
    public function index(Request $request, Nutgram $bot)
    {
        \Log::info('Webhook received');

        $apiBot = new Api(env('TELEGRAM_BOT_TOKEN'));
        $ownerId = env('BOT_OWNER_ID');
        $products = Product::all();
        $messages = Message::all();

        // Обработка команды /start
        $bot->onCommand('start', function (Nutgram $bot) use (&$ownerId, &$messages) {
            $message = $messages->first(); //
            $chat_id = $bot->message()->from->id;

            $response = $message->title . "\n";
            $response .= $message->description . "\n";

            // Кнопки со ссылками, если есть в buttons_json
            if ($message->buttons_json) {
                $buttons = json_decode($message->buttons_json, true);
                $keyboard = InlineKeyboardMarkup::make();
                foreach ($buttons as $button) {
                    $keyboard->addRow(
                        InlineKeyboardButton::make($button['text'], url: $button['url'])
                    );
                }
                $bot->sendMessage(
                    text: $response,
                    chat_id: $chat_id,
                    reply_markup: $keyboard
                );
            } else {
                $bot->sendMessage(
                    text: $response,
                    chat_id: $chat_id
                );
            }

            // Добавляем дополнительные команды для владельца бота
            if ($chat_id == $ownerId) {
                $commands = "There is an additional command for owner:\n";
                $commands .= "/edit - Edit messages or products\n";

                // Отправляем сообщение с доступными командами
                $bot->sendMessage(
                    text: $commands,
                    chat_id: $chat_id
                );
            }
            
        });

        $bot->onCommand('products', function (Nutgram $bot) use (&$products) {
            $chat_id = $bot->message()->from->id;

            if ($products->isEmpty()) {
                $bot->sendMessage(
                    text: 'Products are out of stock',
                    chat_id: $chat_id
                );
                return;
            }

            foreach ($products as $product) {
                $response = "Name: " . $product->title . "\n";
                $response .= "Description: " . $product->description . "\n";
                $response .= "Price: " . ($product->price / 100 * 100) . ' RUB';
                $callback_data = "type:{$product->id}";
                
                $keyboard = InlineKeyboardMarkup::make();
                $keyboard->addRow(
                    InlineKeyboardButton::make('Buy', callback_data: $callback_data)
                );

                $bot->sendMessage(
                    text: $response,
                    chat_id: $chat_id,
                    reply_markup: $keyboard
                );
            }
        });

        // Обработка callback запроса при нажатии кнопки "Buy"
        $bot->onCallbackQueryData('type:{id}', function (Nutgram $bot) use (&$products, &$apiBot) {
            $callbackData = $bot->callbackQuery()->data;
            $productId = explode(':', $callbackData)[1];

            $product = $products->firstWhere('id', $productId);
            
            if (!$product) {
                $bot->sendMessage(
                    text: 'Product not found',
                    chat_id: $bot->callbackQuery()->from->id
                );
                return;
            }

            $chat_id = $bot->callbackQuery()->from->id;

            $apiBot->sendInvoice([
                'chat_id' => $chat_id,
                'title' => "Buy the " . $product->title,
                'description' => 'To pay:',
                'payload' => 'invoice_payload_' . $productId,
                'provider_token' => env('PROVIDER_TOKEN'),
                'currency' => 'RUB',
                'prices' => [
                    new LabeledPrice(label: 'Content Access', amount: $product->price * 100)
                ]
            ]);

            $bot->answerCallbackQuery(
                text: 'Processing your request...',
                show_alert: false
            );
        });

        // Обработка команды /edit
        $bot->onCommand('edit', function (Nutgram $bot) use (&$products, &$messages) {
            $chatId = $bot->message()->chat->id;
    
            foreach ($products as $product) {
                $response = "Product Name: " . $product->title . "\n";
                $response .= "Description: " . $product->description . "\n";
                $response .= "Price: " . ($product->price / 100 * 100) . ' RUB';
                $callbackDataProduct = "edit_product:{$product->id}";
                
                $keyboard = InlineKeyboardMarkup::make();
                $keyboard->addRow(
                    InlineKeyboardButton::make('Edit Product', callback_data: $callbackDataProduct)
                );

                $bot->sendMessage(
                    chat_id: $chatId,
                    text: $response,
                    reply_markup: $keyboard
                );
            }

            foreach ($messages as $message) {
                $response = "Message ID: " . $message->id . "\n";
                $response .= "Title: " . $message->title . "\n";
                $response .= "Description: " . $message->description;
                $callbackDataMessage = "edit_message:{$message->id}";
                
                $keyboard = InlineKeyboardMarkup::make();
                $keyboard->addRow(
                    InlineKeyboardButton::make('Edit Message', callback_data: $callbackDataMessage)
                );

                $bot->sendMessage(
                    chat_id: $chatId,
                    text: $response,
                    reply_markup: $keyboard
                );
            }
            
        });

        // // Обработка callback-запросов для редактирования
        // $bot->onCallbackQuery(function (Nutgram $bot) {
        //     $callbackQuery = $bot->CallbackQuery();
        //     $callbackData = $callbackQuery->data;
        //     $chatId = $bot->message()->chat->id;
            

        //     if (strpos($callbackData, 'edit_product:') === 0) {
        //         $productId = str_replace('edit_product:', '', $callbackData);
        //         $bot->sendMessage(chat_id: $chatId, text: "Please enter new data for the product in the format: id,title,description,price");

        //         $bot->setGlobalData("edit_product_id", $productId);
        //     } elseif (strpos($callbackData, 'edit_message:') === 0) {
        //         $messageId = str_replace('edit_message:', '', $callbackData);
        //         $bot->sendMessage(chat_id: $chatId, text: "Please enter new data for the message in the format: id,title,content");

        //         $bot->setGlobalData("edit_message_id", $messageId);
        //     }
        // });

        // // Обработка новых данных от пользователя
        // $bot->onMessage(function (Nutgram $bot) {
        //     $chatId = $bot->message()->chat->id;
        //     $text = $bot->message()->text;

        //     $editProductId = $bot->getGlobalData("edit_product_id");
        //     if ($editProductId) {
        //         list($id, $title, $description, $price) = explode(',', $text);

        //         $product = Product::find($id);
        //         if ($product) {
        //             $product->title = $title;
        //             $product->description = $description;
        //             $product->price = $price; 
        //             $product->save();
        //             $bot->sendMessage($chatId, "Product updated successfully!");
        //         } else {
        //             $bot->sendMessage($chatId, "Product not found.");
        //         }

        //         $bot->setGlobalData("edit_product_id", null); // Сброс состояния
        //     }

        //     $editMessageId = $bot->getGlobalData("edit_message_id");
        //     if ($editMessageId) {
        //         list($id, $title, $description) = explode(',', $text);

        //         $message = Message::find($id);
        //         if ($message) {
        //             $message->title = $title;
        //             $message->description = $description;
        //             $message->save();
        //             $bot->sendMessage($chatId, "Message updated successfully!");
        //         } else {
        //             $bot->sendMessage($chatId, "Message not found.");
        //         }

        //         $bot->setGlobalData("edit_message_id", null); // Сброс состояния
        //     }
        // });

        // return response()->json(['status' => 'ok']);

        $bot->run();
    }
}



// class TelegramController extends Controller
// {
    
//     public function index(Request $request, Nutgram $bot) 
//     {
//         \Log::info('Webhook received');

//         $products = Product::all();
        
//         $bot->onCommand('start', function (Nutgram $bot) {
//             $messages = Message::all();
//             $message = $messages->first(); /// сделать чтобы не ферст 
//             $chat_id = $bot->message()->from->id;

//             $response = $message->title."\n";
//             $response .= $message->description."\n";

//             //добавить кнопки со ссылками если есть в buttons json

//             // if ($message->buttons_json) {
//             //     $keyboard = InlineKeyboardMarkup::make()->addRow(
//             //         InlineKeyboardButton::make('Button', callback_data: "type:{$message->buttons_json}")
//             //     );
//             // }
            
//             $bot->sendMessage(
//                 text: $response,
//                 chat_id: $chat_id,
//                 // reply_markup: $keyboard
//             );
//         });

//         $bot->onCommand('products', function (Nutgram $bot) use (&$products) {

//             $chat_id = $bot->message()->from->id;
            
//             if ($products->isEmpty()) {
//                 $bot->sendMessage(
//                     text: 'Products are out of stock',
//                     chat_id: $bot->message()->from->id
//                 );
//                 return;
//             }

//             foreach ($products as $product) {
//                 $response = "Name: " . $product->title . "\n";
//                 $response .= "Description: " . $product->description . "\n";
//                 $response .= "Price: " . ($product->price / 100 * 100) . ' RUB';
        
//                 $callback_data = "type:buy:{$product->id}";
                
//                 $keyboard = InlineKeyboardMarkup::make();
//                 $keyboard->addRow(
//                     InlineKeyboardButton::make('Buy', callback_data: $callback_data)
//                 );
        
//                 $bot->sendMessage(
//                     text: $response,
//                     chat_id: $chat_id,
//                     reply_markup: $keyboard
//                 );
//             }

//         });

             

//         $bot->run();
//     }
// }



// когда покупатель попадает в бота видит приветственное сообщ с текстом который задает владелец бота 
// по нажатии кнопки старт выдается продающее сообщение, в нем можем редактировать (у владельца должен быть доступ к редактированию бота) картинку заголовок и текст описания 
// для оплаты нужно установить цену и название товара оторый продаем  
// когда пользователь провел оплату выдается 1 сообщение в котором тоже редактируется картинка заголовок описание и есть возможность добавлять кнопки
// произвольное кол-во кнопок при этом каждая кнопка - ссылка 


// // jsonSerialize где make

// $currentProductIndex = ($currentProductIndex + 1) % $products->count();

//         // Проверка, существует ли значение $currentProductIndex в сессии
//         if (!session()->has('currentProductIndex')) {
//             $currentProductIndex = 0; // Присвоение значения 0, если значение не существует в сессии
//             session(['currentProductIndex' => $currentProductIndex]); // Сохранение значения в сессии
//         } else {
//             $currentProductIndex = session('currentProductIndex'); // Получение значения из сессии
//         }

//         // Обновление значения $currentProductIndex в сессии
//         session(['currentProductIndex' => $currentProductIndex]);

//         // Запись сообщения в лог-файл
//         \Log::info('Session updated: currentProductIndex = ' . $currentProductIndex);

            


        // // Обработчик нажатия на кнопку "Next"
        // $bot->onCallbackQueryData("type:next", function (Nutgram $bot) use (&$currentProductIndex, &$products) {

        //     // \Log::info('product n'.$currentProductIndex);
            
        //     $currentProductIndex = ($currentProductIndex + 1) % $products->count(); 
        //     $product = $products[$currentProductIndex];

        //     // \Log::info('product n'.$currentProductIndex);

        //     $response = "Name: " . $product->title . "\n";
        //     $response .= "Description: " . $product->description . "\n";
        //     $response .= "Price: " . ($product->price / 100 * 100) . ' RUB';

        //     $keyboard = InlineKeyboardMarkup::make();
        //     $keyboard->addRow(
        //         InlineKeyboardButton::make('Buy', callback_data: "type:buy,product_id:{$product->id}")
        //     );
        //     $keyboard->addRow(
        //         InlineKeyboardButton::make('Next', callback_data: "type:next")
        //     );
        //     if ($currentProductIndex > 0) {
        //         $keyboard->addRow(
        //             InlineKeyboardButton::make('Previous', callback_data: "type:previous")
        //         );
        //     }

        //     $bot->editMessageReplyMarkup(
        //         chat_id: $bot->callbackQuery()->from->id,
        //         message_id: $bot->callbackQuery()->message->message_id,
        //         reply_markup: $keyboard
        //     );
        //     $bot->editMessageText(
        //         text: $response,
        //         chat_id: $bot->callbackQuery()->from->id,
        //         message_id: $bot->callbackQuery()->message->message_id,
        //         reply_markup: $keyboard
        //     );
        // });

        // Обработчик нажатия на кнопку "Previous"

        // $bot->onCallbackQueryData("type:previous", function (Nutgram $bot) use (&$currentProductIndex, &$products) {


        //     \Log::info('product p'.$currentProductIndex);

        //     $currentProductIndex = ($currentProductIndex - 1) % $products->count();
        //     $product = $products[$currentProductIndex];

        //     \Log::info('product p'.$currentProductIndex);

        //     $response = "Name: " . $product->title . "n";
        //     $response .= "Description: " . $product->description . "n";
        //     $response .= "Price: " . ($product->price / 100 * 100) . ' RUB';

        //     $keyboard = InlineKeyboardMarkup::make();
        //     $keyboard->addRow(
        //         InlineKeyboardButton::make('Buy', callback_data: "type:buy,product_id:{$product->id}")
        //     );
        //     $keyboard->addRow(
        //         InlineKeyboardButton::make('Next', callback_data: "type:next")
        //     );
        //     if ($currentProductIndex > 0) {
        //         $keyboard->addRow(
        //             InlineKeyboardButton::make('Previous', callback_data: "type:previous")
        //         );
        //     }

        //     $bot->editMessageReplyMarkup(
        //         chat_id: $bot->callbackQuery()->from->id,
        //         message_id: $bot->callbackQuery()->message->message_id,
        //         reply_markup: $keyboard
        //     );
        //     $bot->editMessageText(
        //         text: $response,
        //         chat_id: $bot->callbackQuery()->from->id,
        //         message_id: $bot->callbackQuery()->message->message_id,
        //         reply_markup: $keyboard
        //     );
        // });

           
        // $bot->onCallbackQueryData("type:buy:1", function (Nutgram $bot, array $matches) use (&$products) {    
        //     $callbackData = $bot->callbackQuery()->data;
        //     \Log::info('авиыркаир' );

        //         $callbackData = $bot->callbackQuery()->data;
        //         $productId = explode(':', $callbackData);
        //         \Log::info('Callback data: ' . $callbackData);

        //         $productId = $matches[1];
        //         \Log::info($productId);

        //         $product = $products->firstWhere('id', $productId);
        //         $callbackDataArray = explode(':', $callbackData);
                
        //         if (count($callbackDataArray) != 3 || $callbackDataArray[0] != 'type' || $callbackDataArray[1] != 'buy') {
        //             $bot->sendMessage(
        //                 text: 'Invalid callback data',
        //                 chat_id: $bot->callbackQuery()->from->id
        //             );
        //             return;
        //         }
                
        //         $productId = $callbackDataArray[2];
        //         if (!$product) {
        //             $bot->sendMessage(
        //                 text: 'Product not found',
        //                 chat_id: $bot->callbackQuery()->from->id
        //             );
        //             return;
        //         }
        
        //         $chat_id = $bot->callbackQuery()->from->id;
        
        //         $bot->sendInvoice(
        //             chat_id: $chat_id,
        //             title: "Buy the " . $product->title,
        //             description: 'To pay:',
        //             payload: 'invoice_payload',
        //             provider_token: env('PROVIDER_TOKEN'),
        //             currency: 'RUB',
        //             prices: [new LabeledPrice(label: 'Content Access', amount: $product->price * 100)]
        //         );
        
        //         $bot->answerCallbackQuery(
        //             callback_query_id: $bot->callbackQuery()->id,
        //             text: 'Processing your request...',
        //             show_alert: false
        //         );
            
        // });
        