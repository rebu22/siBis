<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konsultasi/riwayat/penilaian/laporan kini terbuka untuk tamu.
 * Tamu dikenali lewat token di cookie; `user_id` tetap dipakai bila login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_token', 64)->nullable()->index();
        });

        Schema::table('answer_ratings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_token', 64)->nullable()->index();
        });

        Schema::table('answer_reports', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_token', 64)->nullable()->index();
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
        });
    }

    public function down(): void
    {
        DB::table('chat_sessions')->whereNull('user_id')->delete();
        DB::table('answer_ratings')->whereNull('user_id')->delete();
        DB::table('answer_reports')->whereNull('user_id')->delete();
        DB::table('contact_messages')->whereNull('user_id')->delete();

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn('guest_token');
        });

        Schema::table('answer_ratings', function (Blueprint $table) {
            $table->dropColumn('guest_token');
        });

        Schema::table('answer_reports', function (Blueprint $table) {
            $table->dropColumn('guest_token');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn(['name', 'email']);
        });
    }
};
