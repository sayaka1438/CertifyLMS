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
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->restrictOnDelete();

            // TODO: 購入実績のある面談パック削除時の扱いをPM確認後に確定する
            $table->foreignUlid('meeting_pack_id')->nullable()->constrained('meeting_packs')->nullOnDelete();

            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('amount');
            // TODO: PM確認後に決済ステータスを確定する
            $table->string('status')->default('pending');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            // TODO: PM確認後に決済完了日時を保持するか確定する
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
