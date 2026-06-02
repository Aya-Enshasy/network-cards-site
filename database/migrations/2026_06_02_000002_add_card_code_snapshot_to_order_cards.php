<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_cards', function (Blueprint $table): void {
            $table->string('card_code')->nullable()->after('hotspot_card_id');
        });

        DB::table('order_cards')
            ->join('hotspot_cards', 'order_cards.hotspot_card_id', '=', 'hotspot_cards.id')
            ->select('order_cards.id', 'hotspot_cards.card_code')
            ->orderBy('order_cards.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('order_cards')
                    ->where('id', $row->id)
                    ->update(['card_code' => $row->card_code]);
            });
    }

    public function down(): void
    {
        Schema::table('order_cards', function (Blueprint $table): void {
            $table->dropColumn('card_code');
        });
    }
};
