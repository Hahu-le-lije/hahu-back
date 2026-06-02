<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pack_versions', function (Blueprint $table) {
            // Add the missing columns that your new code expects
            $table->json('meta')->nullable();
            $table->string('game_type')->nullable();
            $table->json('content')->nullable();
            
            // If you already have 'checksum' and 'size_bytes' but they aren't 
            // set to nullable, you can add those changes here too:
            $table->string('checksum')->nullable()->change();
            $table->integer('size_bytes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('content_pack_versions', function (Blueprint $table) {
            $table->dropColumn(['meta', 'game_type', 'content']);
        });
    }
};