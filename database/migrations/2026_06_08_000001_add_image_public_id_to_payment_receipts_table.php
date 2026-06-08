<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_receipts', function (Blueprint $table): void {
            $table->string('image_public_id')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('payment_receipts', function (Blueprint $table): void {
            $table->dropColumn('image_public_id');
        });
    }
};
