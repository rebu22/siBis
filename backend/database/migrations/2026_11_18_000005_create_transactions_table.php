<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF-07/RF-21: transaksi premium lewat Midtrans. Satu baris = satu percobaan bayar.
 * Status mengikuti Midtrans (pending/settlement/expire/cancel/deny/failure).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_id', 64)->unique();
            $table->string('plan', 16)->default('premium');
            $table->unsignedInteger('amount');
            $table->unsignedSmallInteger('days')->default(30);
            $table->string('status', 24)->default('pending')->index();
            $table->string('payment_type', 40)->nullable();
            $table->string('snap_token', 128)->nullable();
            $table->text('snap_redirect_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
