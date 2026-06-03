U<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_packs', function (Blueprint $table) {
            $table->string('latest_published_version')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('content_packs', function (Blueprint $table) {
            $table->unsignedInteger('latest_published_version')->nullable()->change();
        });
    }
};
