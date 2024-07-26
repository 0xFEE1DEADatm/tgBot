<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderHistory extends Model
{
    use HasFactory;
    protected $table = 'order_history';

    protected $fillable = [
        'id',
        'customer_id',
        'order_date',
        'order_status',
        'price',
        'product_id',
        'external_transaction_id',
    ]; 
}
