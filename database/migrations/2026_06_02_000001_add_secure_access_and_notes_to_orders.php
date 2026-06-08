<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('access_token', 80)->nullable()->unique()->after('order_number');
            $table->text('notes')->nullable()->after('phone');
            $table->text('rejection_reason')->nullable()->after('order_status');
            $table->index('phone');
        });

        DB::table('orders')
            ->whereNull('access_token')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $order): void {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['access_token' => Str::random(48)]);
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['phone']);
            $table->dropColumn(['access_token', 'notes', 'rejection_reason']);
        });
    }
};
