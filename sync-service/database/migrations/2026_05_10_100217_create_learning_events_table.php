<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_events', function (Blueprint $table) {

            // Internal DB ID
            $table->id();

            // Client-provided immutable session/event ID
            $table->string('event_id')->unique();

            // Child identity from Child Service
            $table->string('child_id')->index();

            $table->string('game_type');
            $table->string('content_id');

            // Raw score
            $table->integer('score');

            // Seconds spent
            $table->integer('time_spent');

            // Flexible analytics fields
            $table->jsonb('metrics')->nullable();

            $table->jsonb('skill_breakdown')->nullable();

            // Original event creation time
            $table->timestamp('event_created_at');

            // Used for incremental sync
            $table->timestamp('last_updated')->index();

            // When WE synced it
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            // Helpful composite index
            $table->index(['child_id', 'event_created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_events');
    }
};
