<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('hotspot_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('card_code');
            $table->string('status')->default('available')->index();
            $table->timestamps();

            $table->unique(['network_id', 'card_code']);
            $table->index(['network_id', 'package_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_cards');
    }
};
