<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->nullable();
            $table->timestamp('order_date');
            $table->string('order_status', 255);
            $table->decimal('price', 18, 3)->nullable();
            $table->integer('product_id');
            $table->string('external_transaction_id', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_histories');
    }
};
