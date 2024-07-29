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

// Message::create([
//     'id' => '1',
//     'title' => 'Welcome Message',
//     'description' => 'Welcome to our service!',
//     'buttons_json' => '[{"text":"First","url":"https://www.nytimes.com/2021/09/07/science/cat-stripes-genetics.html"},{"text":"Second","url":"https://www.nytimes.com/2024/07/24/us/politics/netanyahu-congress-democrats.html"}];']);

class TelegramController extends Controller
{
    public function index(Request $request, Nutgram $bot)
    {
        \Log::info('Webhook received');

        $apiBot = new Api(env('TELEGRAM_BOT_TOKEN'));
        $ownerIds = explode(',', env('BOT_OWNER_ID'));
        $products = Product::all();
        $messages = Message::all();

        // Обработка команды /start
        $bot->onCommand('start', function (Nutgram $bot) use (&$ownerId, &$messages, &$ownerIds) {
            $message = $messages->first();
            $chat_id = $bot->message()->from->id;

            $response = $message->title . "\n";
            $response .= $message->description . "\n";
            $image_data = $message->image ? base64_decode($message->image) : null;

            if ($message->buttons_json) {
                $buttons = json_decode($message->buttons_json, true);

                // if (json_last_error() !== JSON_ERROR_NONE) {
                //     $bot->sendMessage(
                //         text: "There was an error decoding the buttons JSON.",
                //         chat_id: $chat_id
                //     );
                //     return;
                // }

                if (is_array($buttons)) {
                    $keyboard = InlineKeyboardMarkup::make();
                    foreach ($buttons as $button) {
                        if (isset($button['text']) && isset($button['url'])) {
                            $keyboard->addRow(
                                InlineKeyboardButton::make(text: $button['text'], url: $button['url'])
                            );
                        }
                    }

                    if ($image_data) {
                        $bot->sendPhoto(
                            chat_id: $chat_id,
                            photo: $image_data,
                            caption: $response,
                            reply_markup: $keyboard
                        );
                    } else {
                        $bot->sendMessage(
                            text: $response,
                            chat_id: $chat_id,
                            reply_markup: $keyboard
                        );
                    }
                } else {
                    if ($image_data) {
                        $bot->sendPhoto(
                            chat_id: $chat_id,
                            photo: $image_data,
                            caption: $response,
                            reply_markup: null
                        );
                    } else {
                        $bot->sendMessage(
                            text: $response,
                            chat_id: $chat_id,
                            reply_markup: null
                        );
                    }
                }
            } else {
                if ($image_data) {
                    $bot->sendPhoto(
                        chat_id: $chat_id,
                        photo: $image_data,
                        caption: $response
                    );
                } else {
                    $bot->sendMessage(
                        text: $response,
                        chat_id: $chat_id
                    );
                }
            }

            // Дополнительные команды для владельца бота
            if (in_array($chat_id, $ownerIds)) {
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
                $response = "Product Name: " . $product->title . "\n";
                $response .= "Description: " . $product->description . "\n";
                $response .= "Price: " . ($product->price / 100 * 100) . ' RUB';
                $callback_data_edit_product = "edit_product:{$product->id}";
                $callback_data_delete_product = "delete_product:{$product->id}";

                $keyboard = InlineKeyboardMarkup::make();
                $keyboard->addRow(
                    InlineKeyboardButton::make(text: 'Edit', callback_data: $callback_data_edit_product),
                    InlineKeyboardButton::make(text: 'Delete', callback_data: $callback_data_delete_product)
                );

                $bot->sendMessage(
                    chat_id: $chat_id,
                    text: $response,
                    reply_markup: $keyboard
                );
            }

            $callback_data_add_product = "add_product";
            $keyboard_add = InlineKeyboardMarkup::make();
            $keyboard_add->addRow(
                InlineKeyboardButton::make(text: 'Add', callback_data: $callback_data_add_product)
            );

            $bot->sendMessage(
                chat_id: $chat_id,
                text: "Would you like to add a new product?",
                reply_markup: $keyboard_add
            );

            foreach ($messages as $message) {
                $response = "Title: " . $message->title . "\n";
                $response .= "Description: " . $message->description . "\n";
                $response .= "Buttons: " . $message->buttons_json;

                $callback_data_message = "edit_message:{$message->id}";

                $keyboard = InlineKeyboardMarkup::make();
                $keyboard->addRow(
                    InlineKeyboardButton::make(text: 'Edit', callback_data: $callback_data_message)
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

        // Обработка коллбек-запросов при нажатии кнопок "Edit" и "Delete"
        $bot->onCallbackQuery(function (Nutgram $bot) {
            $callbackData = $bot->callbackQuery()->data;
            $chat_id = $bot->callbackQuery()->from->id;

            \Log::info("Callback Data: " . $callbackData);

            $bot->answerCallbackQuery(
                text: 'Processing your request...',
                show_alert: false
            );

            if (strpos($callbackData, 'edit_product:') === 0) {
                $productId = str_replace('edit_product:', '', $callbackData);
                $bot->setGlobalData("edit_message_id", null);
                \Log::info("Setting edit_product_id to " . $productId);
                $bot->setGlobalData("edit_product_id", $productId);
                $bot->sendMessage(chat_id: $chat_id, text: "Please enter new data for the product in the format: id,title,description,price");
            } elseif (strpos($callbackData, 'delete_product:') === 0) {
                $productId = str_replace('delete_product:', '', $callbackData);
                \Log::info("Deleting product with id " . $productId);
                Product::destroy($productId);
                $bot->sendMessage(chat_id: $chat_id, text: "Product deleted successfully!");
            } elseif ($callbackData === 'add_product') {
                $bot->setGlobalData("edit_product_id", null);
                \Log::info("Adding new product");
                $bot->sendMessage(chat_id: $chat_id, text: "Please enter new product data in the format: id,title,description,price");
            } elseif (strpos($callbackData, 'edit_message:') === 0) {
                $messageId = str_replace('edit_message:', '', $callbackData);
                $bot->setGlobalData("edit_product_id", null);
                \Log::info("Setting edit_message_id to " . $messageId);
                $bot->setGlobalData("edit_message_id", $messageId);
                $bot->sendMessage(chat_id: $chat_id, text: "Please enter new data for the message in the format: id,title,content,buttons(in the format as written above)");
            }
        });

        // Обновление базы данных
        $bot->onMessage(function (Nutgram $bot) use (&$messages, &$products) {
            $chat_id = $bot->message()->chat->id;
            $text = $bot->message()->text;

            $editMessageId = $bot->getGlobalData("edit_message_id");
            $editProductId = $bot->getGlobalData("edit_product_id");

            \Log::info("Received message: " . $text);
            \Log::info("Edit Message ID: " . $editMessageId);
            \Log::info("Edit Product ID: " . $editProductId);

            // messages
            if ($editMessageId) {
                $parts = explode(',', $text, 4); // Разделяем на 4 части

                if (count($parts) < 4) {
                    $bot->sendMessage(chat_id: $chat_id, text: "Incorrect format. Please enter the data as id,title,description,buttons(in JSON format).");
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

            // products
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
            } else {
                list($id, $title, $description, $price) = explode(',', $text);

                // Создание нового продукта
                $product = new Product();
                $product->id = $id;
                $product->title = $title;
                $product->description = $description;
                $product->price = $price;
                $product->save();
                $bot->sendMessage(chat_id: $chat_id, text: "New product added successfully!");
            }
        });

        
        $bot->run();
    }
}

