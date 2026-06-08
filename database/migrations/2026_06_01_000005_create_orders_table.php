<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('phone');
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_status')->default('pending')->index();
            $table->string('order_status')->default('pending')->index();
            $table->timestamps();

            $table->index(['network_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
