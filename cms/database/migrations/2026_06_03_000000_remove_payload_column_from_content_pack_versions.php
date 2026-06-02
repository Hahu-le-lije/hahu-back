<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pack_versions', function (Blueprint $table) {
            // Drop the old NOT NULL column
            $table->dropColumn('payload');
        });
    }

    public function down(): void
    {
        Schema::table('content_pack_versions', function (Blueprint $table) {
            $table->json('payload')->nullable(); // Re-add if you need to rollback
        });
    }
};