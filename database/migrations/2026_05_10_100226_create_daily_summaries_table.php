<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_summaries', function (Blueprint $table) {

            $table->id();

            $table->string('child_id')->index();

            $table->date('summary_date');

            $table->integer('total_sessions')->default(0);

            $table->integer('total_questions')->default(0);

            $table->integer('correct_answers')->default(0);

            $table->float('accuracy')->default(0);

            $table->integer('time_spent')->default(0);

            $table->float('consistency')->default(0);

            $table->float('skill_diversity')->default(0);

            $table->float('mastery_score')->default(0);

            $table->text('generated_explanation')->nullable();

            $table->integer('algorithm_version')->default(1);

            $table->timestamps();

            $table->unique(['child_id', 'summary_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
    }
};
