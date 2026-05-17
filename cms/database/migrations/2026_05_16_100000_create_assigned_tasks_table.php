<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assigned_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('child_id');
            $table->unsignedBigInteger('content_id');
            $table->unsignedTinyInteger('game_type_id');
            $table->string('status')->default('pending');
            $table->string('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->foreign('content_id')->references('id')->on('content')->cascadeOnDelete();
            $table->index('child_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assigned_tasks');
    }
};
