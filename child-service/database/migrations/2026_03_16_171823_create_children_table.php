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

            // External parent/household owner id from the parent service.
            $table->string('parent_id')->index();

            $table->string('first_name');
            $table->string('last_name')->nullable();

            $table->string('username')->unique();
            $table->string('password'); // hashed

            $table->text('avatar')->nullable();
            $table->string('subscription_id')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('skill_level')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('credentials_rotated_at')->nullable();

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
