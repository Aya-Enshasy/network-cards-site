<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('duration_hours');
            $table->decimal('price', 10, 2);
            $table->string('speed')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['network_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
