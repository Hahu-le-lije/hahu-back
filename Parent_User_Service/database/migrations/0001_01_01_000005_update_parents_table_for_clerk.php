<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            if (! Schema::hasColumn('parents', 'clerk_id')) {
                $table->string('clerk_id')->unique();
            }

            if (! Schema::hasColumn('parents', 'first_name')) {
                $table->string('first_name');
            }

            if (! Schema::hasColumn('parents', 'last_name')) {
                $table->string('last_name');
            }

            if (! Schema::hasColumn('parents', 'phone_number')) {
                $table->string('phone_number')->nullable();
            }
        });

        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'full_name')) {
                $table->dropColumn('full_name');
            }

            if (Schema::hasColumn('parents', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('parents', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'clerk_id')) {
                $table->dropUnique(['clerk_id']);
                $table->dropColumn('clerk_id');
            }

            if (Schema::hasColumn('parents', 'first_name')) {
                $table->dropColumn('first_name');
            }

            if (Schema::hasColumn('parents', 'last_name')) {
                $table->dropColumn('last_name');
            }

            if (Schema::hasColumn('parents', 'phone_number')) {
                $table->dropColumn('phone_number');
            }

            if (! Schema::hasColumn('parents', 'full_name')) {
                $table->string('full_name');
            }

            if (! Schema::hasColumn('parents', 'password')) {
                $table->string('password');
            }

            if (! Schema::hasColumn('parents', 'status')) {
                $table->string('status')->default('active');
            }
        });
    }
};
