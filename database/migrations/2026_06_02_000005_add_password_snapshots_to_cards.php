<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('hotspot_cards', function (Blueprint $table): void {
            $table->string('card_password')->nullable()->after('card_code');
        });

        Schema::table('order_cards', function (Blueprint $table): void {
            $table->string('card_password')->nullable()->after('card_code');
        });

        DB::table('hotspot_cards')
            ->whereNull('card_password')
            ->orderBy('id')
            ->get(['id', 'card_code'])
            ->each(function (object $card): void {
                DB::table('hotspot_cards')
                    ->where('id', $card->id)
                    ->update(['card_password' => 'PASS-'.$card->card_code]);
            });

        DB::table('order_cards')
            ->leftJoin('hotspot_cards', 'order_cards.hotspot_card_id', '=', 'hotspot_cards.id')
            ->select('order_cards.id', 'hotspot_cards.card_password')
            ->orderBy('order_cards.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('order_cards')
                    ->where('id', $row->id)
                    ->update(['card_password' => $row->card_password]);
            });
    }

    public function down(): void
    {
        Schema::table('order_cards', function (Blueprint $table): void {
            $table->dropColumn('card_password');
        });

        Schema::table('hotspot_cards', function (Blueprint $table): void {
            $table->dropColumn('card_password');
        });
    }
};
