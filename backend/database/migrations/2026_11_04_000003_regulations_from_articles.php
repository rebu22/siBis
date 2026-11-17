<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Artikel teks diganti kebijakan berbentuk PDF (keputusan pemilik produk 2026-11-04).
 * Tabel dipakai ulang supaya slug, kategori, status, dan pencarian tidak perlu dibangun ulang;
 * yang berubah hanya isinya: `body` teks → `file_path` PDF + jejak dokumen di layanan RAG.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('articles', 'regulations');

        Schema::table('regulations', function (Blueprint $table) {
            $table->string('file_path')->nullable();
            $table->unsignedInteger('file_size')->default(0);
            $table->string('rag_doc_id', 64)->nullable()->index();
        });

        Schema::table('regulations', function (Blueprint $table) {
            $table->dropColumn('body');
        });
    }

    public function down(): void
    {
        Schema::table('regulations', function (Blueprint $table) {
            $table->longText('body')->nullable();
        });

        Schema::table('regulations', function (Blueprint $table) {
            $table->dropIndex(['rag_doc_id']);
            $table->dropColumn(['file_path', 'file_size', 'rag_doc_id']);
        });

        Schema::rename('regulations', 'articles');
    }
};
