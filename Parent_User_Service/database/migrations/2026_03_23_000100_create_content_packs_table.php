<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_packs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('game_type')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->unsignedBigInteger('size_mb')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('latest_published_version')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'latest_published_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_packs');
    }
};
