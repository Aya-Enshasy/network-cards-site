<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_cards', function (Blueprint $table): void {
            $table->timestamp('imported_at')->nullable()->after('package_label')->index();
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_cards', function (Blueprint $table): void {
            $table->dropColumn('imported_at');
        });
    }
};
