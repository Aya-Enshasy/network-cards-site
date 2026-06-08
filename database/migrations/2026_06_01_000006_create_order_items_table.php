<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
