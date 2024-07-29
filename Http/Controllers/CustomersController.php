<?php

namespace App\Http\Controllers;
use App\Models\Customers;

class CustomersController extends Controller
{
    public function showTitle()
    {
        $customer = Customers::first();

        // Проверка
        if ($customer) {
            return response()->json(['message' => 'Customer`s name: ' . $customer->customer_name]);
        } else {
            return response()->json(['message' => 'No products found']);
        }
    }
}
