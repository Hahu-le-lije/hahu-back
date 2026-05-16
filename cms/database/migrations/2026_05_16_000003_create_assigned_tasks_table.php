<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assigned_tasks')) {
            Schema::create('assigned_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('child_id')->index();
                $table->unsignedBigInteger('content_id');
                $table->integer('game_type_id');
                $table->string('status')->default('assigned');
                $table->unsignedBigInteger('assigned_by')->nullable()->index();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->foreign('content_id')->references('id')->on('content')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assigned_tasks');
    }
};
