<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pack_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_pack_id')->constrained('content_packs')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('checksum', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->json('payload');
            $table->string('min_app_version')->default('1.0.0');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['content_pack_id', 'version']);
            $table->index(['content_pack_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pack_versions');
    }
};
