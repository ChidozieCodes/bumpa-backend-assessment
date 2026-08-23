<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_code', 10)->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('account_name')->nullable();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_kobo');
            $table->string('reference')->unique();
            $table->timestamps();
        });

        Schema::create('achievement_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('achievement_code');
            $table->string('achievement_name');
            $table->string('group');
            $table->timestamp('unlocked_at');
            $table->timestamps();
            $table->unique(['user_id', 'achievement_code']);
        });

        Schema::create('badge_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge_code');
            $table->string('badge_name');
            $table->timestamp('unlocked_at');
            $table->timestamps();
            $table->unique(['user_id', 'badge_code']);
        });

        Schema::create('cashbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_unlock_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_kobo');
            $table->string('reference')->unique();
            $table->string('provider');
            $table->string('provider_reference')->nullable();
            $table->string('status');
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbacks');
        Schema::dropIfExists('badge_unlocks');
        Schema::dropIfExists('achievement_unlocks');
        Schema::dropIfExists('purchases');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_code', 'account_number', 'account_name']);
        });
    }
};
