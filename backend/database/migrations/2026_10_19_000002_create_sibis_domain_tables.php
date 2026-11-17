<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel domain siBis. Ini yang hilang dari SDD (BAB III tidak punya 3.1: rancangan basis data).
 * SQLite di lokal, MySQL di produksi (SRS 3.2.1) — hindari fitur khusus MySQL di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        // UC-01: "data usaha" pelaku usaha saat register.
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('business_type')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        // RF-01/RF-17: konten profil perusahaan, satu baris (CMS mengedit baris ini).
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->text('services')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        // RF-02/03/04/14/15/16: artikel hukum. Slug dipakai di URL (hindari ID mentah).
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('status', 16)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        // UC-07..UC-09: satu sesi = satu percakapan konsultasi.
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        // Isi percakapan + blok referensi sumber hukum (UC-07 langkah 2.4).
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->longText('body');
            $table->json('sources')->nullable();
            $table->boolean('degraded')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        // UC-10 / RF-25: penilaian jawaban AI. Satu nilai per jawaban per user (bisa diubah).
        Schema::create('answer_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['chat_message_id', 'user_id']);
        });

        // UC-11 / RF-26: laporan jawaban tidak akurat.
        Schema::create('answer_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->text('note')->nullable();
            $table->string('status', 16)->default('baru');
            $table->timestamps();
        });

        // UC-03: OTP admin. Disimpan sebagai hash, sekali pakai, berbatas waktu.
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });

        // NFR-21: audit perubahan konten (siapa, kapan, apa).
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('answer_reports');
        Schema::dropIfExists('answer_ratings');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('business_profiles');
    }
};
