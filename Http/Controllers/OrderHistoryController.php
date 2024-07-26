<?php

namespace App\Http\Controllers;
use App\Models\OrderHistory;

class OrderHistoryController extends Controller
{
    public function showTitle()
    {
        $order = OrderHistory::first();

        // Проверка
        if ($order) {
            return response()->json(['message' => 'Order history date: ' . $order->order_date]);
        } else {
            return response()->json(['message' => 'No products found']);
        }
    }
}
