<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF-08/RF-19: laporan bug dari pengguna (boleh tanpa akun, karena form meminta email).
 * RF-09/RF-20: pesan pengguna ke admin (wajib login).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email');
            $table->text('description');
            $table->string('screenshot_path')->nullable();
            $table->string('status', 16)->default('baru')->index();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 160);
            $table->text('body');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('bug_reports');
    }
};
