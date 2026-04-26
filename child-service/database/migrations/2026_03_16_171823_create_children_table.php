<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();

            // Foreign key to parents table
            $table->foreignId('parent_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name')->nullable();

            $table->string('username')->unique();
            $table->string('password'); // hashed

            $table->string('avatar')->nullable();
            $table->string('subscription_id')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('skill_level')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
