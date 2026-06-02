<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('content_pack_versions', function (Blueprint $table) {
            // Make the checksum column nullable so existing or new 
            // records don't crash if the value is missing.
            $table->string('checksum')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_pack_versions', function (Blueprint $table) {
            // Revert the column back to not-nullable if needed
            $table->string('checksum')->nullable(false)->change();
        });
    }
};