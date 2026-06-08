<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('order_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotspot_card_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'hotspot_card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cards');
    }
};
