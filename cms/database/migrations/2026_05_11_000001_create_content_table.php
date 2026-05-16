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
        Schema::create('content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_pack_version_id')->constrained('content_pack_versions')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('content');
            $table->unsignedInteger('sequence_order');
            $table->string('difficulty')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite index for ordered retrieval
            $table->index(['content_pack_version_id', 'sequence_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content');
    }
};
