<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('child_subjects')) {
            Schema::create('child_subjects', function (Blueprint $table) {
                $table->id();
                $table->string('child_id')->index();
                $table->integer('game_type_id');
                $table->string('game_type_name');
                $table->boolean('status')->default(true);
                $table->timestamps();

                $table->unique(['child_id', 'game_type_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('child_subjects');
    }
};
