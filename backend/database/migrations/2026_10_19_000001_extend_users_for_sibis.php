<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SRS 3.2.1: akun menyimpan status langganan (Free/Premium) dan kuota konsultasi.
 * Kuota = 5 pertanyaan/hari (UC-07); premium +100 (UC-12).
 * ponytail: kuota harian disimpan sebagai 2 kolom (kuota_date/kuota_used), bukan tabel
 * riwayat. Ganti ke tabel quota_ledger kalau nanti butuh audit pemakaian per hari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default('user')->index();
            $table->string('plan', 16)->default('free');
            $table->timestamp('premium_until')->nullable();
            $table->date('quota_date')->nullable();
            $table->unsignedInteger('quota_used')->default(0);
            $table->timestamp('two_factor_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'plan', 'premium_until', 'quota_date', 'quota_used', 'two_factor_at']);
        });
    }
};
