<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_cards', function (Blueprint $table): void {
            $table->string('package_label')->nullable()->after('card_password');
        });

        Schema::table('order_cards', function (Blueprint $table): void {
            $table->string('package_label')->nullable()->after('card_password');
        });
    }

    public function down(): void
    {
        Schema::table('order_cards', function (Blueprint $table): void {
            $table->dropColumn('package_label');
        });

        Schema::table('hotspot_cards', function (Blueprint $table): void {
            $table->dropColumn('package_label');
        });
    }
};
