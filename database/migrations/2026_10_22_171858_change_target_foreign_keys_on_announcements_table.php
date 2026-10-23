<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['target_certification_id']);
            $table->dropForeign(['target_user_id']);

            $table->foreign('target_certification_id')
                ->references('id')
                ->on('certifications')
                ->cascadeOnDelete();

            $table->foreign('target_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['target_certification_id']);
            $table->dropForeign(['target_user_id']);

            $table->foreign('target_certification_id')
                ->references('id')
                ->on('certifications')
                ->nullOnDelete();

            $table->foreign('target_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
