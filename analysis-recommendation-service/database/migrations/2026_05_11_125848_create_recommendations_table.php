<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('child_id')->index();
            $table->string('tier');
            $table->text('recommendation_text');
            $table->timestamp('next_update_expected_at');
            $table->timestamps(); // Provides created_at and updated_at
        });
    }

    public function down(): void {
        Schema::dropIfExists('recommendations');
    }
};