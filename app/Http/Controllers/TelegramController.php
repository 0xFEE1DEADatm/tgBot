<?php

namespace App\Http\Controllers;

use SergiX44\Nutgram\Nutgram;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Payment\LabeledPrice;

use App\Models\Product;
use App\Models\Message;

class TelegramController extends Controller
{
    public function index(Request $request, Nutgram $bot)
    {
        \Log::info('Webhook received');

        $apiBot = new Api(env('TELEGRAM_BOT_TOKEN'));
        // $ownerId = env('BOT_OWNER_ID');
        $products = Product::all();
        $messages = Message::all();

        // Обработка команды /start
        $bot->onCommand('start', function (Nutgram $bot) use (&$ownerId, &$messages) {
            $message = $messages->first();
            $chat_id = $bot->message()->from->id;

            $response = $message->title . "\n";
            $response .= $message->description . "\n";

            if ($message->buttons_json) {
                $buttons = json_decode($message->buttons_json, true);
        
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $bot->sendMessage(
                        text: "There was an error decoding the buttons JSON.",
                        chat_id: $chat_id
                    );
                    return;
                }

                if (is_array($buttons)) {
                    $keyboard = InlineKeyboardMarkup::make();
                    foreach ($buttons as $button) {
                        if (isset($button['text']) && isset($button['url'])) {
                            $keyboard->addRow(
                                InlineKeyboardButton::make(text: $button['text'], url: $button['url'])
                            );
                        }
                    }
                    $bot->sendMessage(
                        text: $response,
                        chat_id: $chat_id,
                        reply_markup: $keyboard
                    );
                } else {
                    $bot->sendMessage(
                        text: $response,
                        chat_id: $chat_id,
                        reply_markup: null
                    );
                }
            } else {
                $bot->sendMessage(
                    text: $response,
                    chat_id: $chat_id
                );
            }

            $ownerId = $chat_id; // на время 
            // Дополнительные команды для владельца бота
            if ($chat_id == $ownerId) {
                $commands = "There is an additional command for owner:\n";
                $commands .= "/edit - Edit messages or products\n";
                $bot->sendMessage(
                    text: $commands,
                    chat_id: $chat_id
                );
            }
        });

        // Обработка команды /products
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

        // Обработка коллбек-запроса при нажатии кнопки "Buy"
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
            $chat_id = $bot->message()->chat->id;

            foreach ($products as $product) {
                $response = "Product Name: ".$product->title."\n";
                $response .= "Description: ".$product->description."\n";
                $response .= "Price: ".($product->price / 100 * 100).' RUB';
                $callback_data_product = "edit_product:{$product->id}";

                $keyboard = InlineKeyboardMarkup::make();
                $keyboard->addRow(
                    InlineKeyboardButton::make('Edit Product', callback_data: $callback_data_product)
                );

                $bot->sendMessage(
                    chat_id: $chat_id,
                    text: $response,
                    reply_markup: $keyboard
                );
            }

            foreach ($messages as $message) {
                $response = "Title: ".$message->title."\n";
                $response .= "Description: ".$message->description."\n";
                $response .= "Buttons: ".$message->buttons_json;
        
                $callback_data_message = "edit_message:{$message->id}";
        
                $keyboard = InlineKeyboardMarkup::make();
                $keyboard->addRow(
                    InlineKeyboardButton::make('Edit Message', callback_data: $callback_data_message)
                );
        
                if ($message->buttons_json) {        
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $bot->sendMessage(
                            text: "There was an error decoding the buttons JSON.",
                            chat_id: $chat_id
                        );
                        return;
                    }          
                }

                $bot->sendMessage(
                    chat_id: $chat_id,
                    text: $response,
                    reply_markup: $keyboard
                );
                
            }
        });

        // Обработка коллбек-запросов при нажатии кнопки "Edit"
        $bot->onCallbackQuery(function (Nutgram $bot) {
            $callbackData = $bot->callbackQuery()->data;
            $chat_id = $bot->callbackQuery()->from->id;

            $bot->answerCallbackQuery(
                text: 'Processing your request...',
                show_alert: false
            );

            if (strpos($callbackData, 'edit_product:') === 0) {
                $productId = str_replace('edit_product:', '', $callbackData);
                $bot->sendMessage(chat_id: $chat_id, text: "Please enter new data for the product in the format: id,title,description,price");
                $bot->setGlobalData("edit_product_id", $productId);

            } elseif (strpos($callbackData, 'edit_message:') === 0) {
                $messageId = str_replace('edit_message:', '', $callbackData);
                $bot->sendMessage(chat_id: $chat_id, text: "Please enter new data for the message in the format: id,title,content,buttons(in the format as written above)");
                $bot->setGlobalData("edit_message_id", $messageId);

            }
        });
        
        // Обновление базы данных
        $bot->onMessage(function (Nutgram $bot) use (&$messages, &$products) {
            $chat_id = $bot->message()->chat->id;
            $text = $bot->message()->text;
        
            $editMessageId = $bot->getGlobalData("edit_message_id");
            if ($editMessageId) {
                $parts = explode(',', $text, 4); // пока на 4 части
        
                if (count($parts) < 4) {
                    $bot->sendMessage(chat_id: $chat_id, text: "Incorrect format. Please enter the data as id,title,description,text1,url1,text2,url2,...");
                    return;
                }
        
                $id = $parts[0];
                $title = $parts[1];
                $description = $parts[2];
                $buttons_raw = $parts[3];
        
                $buttons = [];
                preg_match_all('/\{[^\}]+\}/', $buttons_raw, $matches);
                foreach ($matches[0] as $match) {
                    $button = json_decode($match, true);
                    if (isset($button['text']) && isset($button['url'])) {
                        $buttons[] = $button;
                    } else {
                        $bot->sendMessage(chat_id: $chat_id, text: "Button data is invalid. Please enter valid JSON for buttons.");
                        return;
                    }
                }

                $buttons_json = json_encode($buttons);
                $message = Message::find($id);
                if ($message) {
                    $message->title = $title;
                    $message->description = $description;
                    $message->buttons_json = $buttons_json;
                    $message->save();
                    $bot->sendMessage(chat_id: $chat_id, text: "Message updated successfully!");
                } else {
                    $bot->sendMessage(chat_id: $chat_id, text: "Message not found.");
                }
                $bot->setGlobalData("edit_message_id", null);  
            }

            $editProductId = $bot->getGlobalData("edit_product_id");
            if ($editProductId) {
                list($id, $title, $description, $price) = explode(',', $text);

                $product = Product::find($id);
                if ($product) {
                    $product->title = $title;
                    $product->description = $description;
                    $product->price = $price;
                    $product->save();
                    $bot->sendMessage(chat_id: $chat_id, text: "Product updated successfully!");
                } else {
                    $bot->sendMessage(chat_id: $chat_id, text: "Product not found.");
                }

                $bot->setGlobalData("edit_product_id", null); 
            }

            foreach ($products as $product) {
                $response = "Name: " . $product->title . "\n";
                $response .= "Description: " . $product->description . "\n";
                $response .= "Price: " . ($product->price / 100 * 100) . ' RUB';
                
                $bot->sendMessage(
                    text: $response,
                    chat_id: $chat_id,
                );
            }
            foreach ($messages as $message) {
                $response = $message->title . "\n";
                $response .= $message->content . "\n";
                $response = $message->buttons . "\n";
                
                $bot->sendMessage(
                    text: $response,
                    chat_id: $chat_id,
                );
            }

        });
        

        $bot->run();
    }
}

