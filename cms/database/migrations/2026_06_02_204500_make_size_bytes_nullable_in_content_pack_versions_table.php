<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pack_versions', function (Blueprint $table) {
            // Make size_bytes nullable so it doesn't crash if omitted
            $table->integer('size_bytes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('content_pack_versions', function (Blueprint $table) {
            $table->integer('size_bytes')->nullable(false)->change();
        });
    }
};