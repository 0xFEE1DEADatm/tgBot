<?php

namespace App\Http\Controllers;
use App\Models\Product;

class ProductController extends Controller
{
    public function showTitle()
    {
        $product = Product::first();

        // Проверка
        if ($product) {
            return response()->json(['message' => 'Product title: ' . $product->title]);
        } else {
            return response()->json(['message' => 'No products found']);
        }
    }
}
