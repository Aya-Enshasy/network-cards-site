<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('networks', function (Blueprint $table): void {
            $table->text('bank_transfer_details')->nullable()->after('bank_account');
        });
    }

    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table): void {
            $table->dropColumn('bank_transfer_details');
        });
    }
};
