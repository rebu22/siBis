<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Integrasi Midtrans (RF-07/RF-21). Default sandbox; produksi lewat env.
 *
 * Alur yang dipakai:
 *   1. checkout -> POST Snap /transactions -> redirect_url Midtrans
 *   2. pengguna bayar -> kembali ke /premium/selesai?order_id=...
 *   3. halaman selesai menanyakan status ke API Midtrans (webhook tidak bisa
 *      menjangkau localhost), lalu mengaktifkan premium bila lunas
 *   4. webhook /midtrans/notification tetap disediakan untuk produksi
 */
class MidtransService
{
    public function configured(): bool
    {
        return filled($this->serverKey());
    }

    /** @return array{token?: string, redirect_url?: string} */
    public function createSnap(Transaction $transaction): array
    {
        $transaction->loadMissing('user');

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($this->snapBase().'/transactions', [
                'transaction_details' => [
                    'order_id' => $transaction->order_id,
                    'gross_amount' => $transaction->amount,
                ],
                'item_details' => [[
                    'id' => $transaction->plan,
                    'price' => $transaction->amount,
                    'quantity' => 1,
                    'name' => 'siBis Premium '.$transaction->days.' hari',
                ]],
                'customer_details' => [
                    'first_name' => $transaction->user?->name ?? 'Pengguna siBis',
                    'email' => $transaction->user?->email,
                ],
                'callbacks' => [
                    'finish' => route('premium.finish'),
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Midtrans menolak permintaan: '.($response->json('error_messages.0') ?? $response->status())
            );
        }

        return $response->json() ?? [];
    }

    /** Status terbaru dari API Midtrans; dipakai halaman selesai. */
    public function status(string $orderId): array
    {
        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->timeout(20)
            ->get($this->apiBase().'/'.rawurlencode($orderId).'/status');

        return $response->json() ?? [];
    }

    /**
     * Validasi signature webhook Midtrans, lalu sinkronkan transaksi.
     * Mengembalikan null bila order tidak dikenal.
     */
    public function handleNotification(array $payload): ?Transaction
    {
        $expected = hash('sha512', ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .$this->serverKey());

        if (! hash_equals($expected, (string) ($payload['signature_key'] ?? ''))) {
            throw new InvalidArgumentException('Signature Midtrans tidak valid.');
        }

        $transaction = Transaction::query()
            ->where('order_id', $payload['order_id'] ?? '')
            ->first();

        if (! $transaction) {
            return null;
        }

        $this->sync($transaction, $payload);

        return $transaction;
    }

    /**
     * Terapkan status Midtrans ke transaksi. Premium hanya diaktifkan sekali,
     * supaya webhook yang dikirim berulang tidak memperpanjang masa aktif.
     */
    public function sync(Transaction $transaction, array $payload): void
    {
        $status = (string) ($payload['transaction_status'] ?? $transaction->status);
        $fraud = $payload['fraud_status'] ?? null;

        $paid = $status === 'settlement'
            || ($status === 'capture' && $fraud === 'accept');

        $wasPaid = $transaction->isPaid();

        $transaction->forceFill([
            'status' => $paid ? 'settlement' : $status,
            'payment_type' => $payload['payment_type'] ?? $transaction->payment_type,
            'paid_at' => $paid ? ($transaction->paid_at ?? now()) : $transaction->paid_at,
            'raw_response' => $payload,
        ])->save();

        if ($paid && ! $wasPaid) {
            $transaction->user?->activatePremium($transaction->days);

            AuditLog::record('transaction.paid', $transaction, [
                'order_id' => $transaction->order_id,
                'amount' => $transaction->amount,
            ]);
        }
    }

    /** Gagal memanggil Midtrans tidak boleh membuat checkout 500. */
    public function safeStatus(Transaction $transaction): void
    {
        if (! $this->configured() || $transaction->isPaid()) {
            return;
        }

        try {
            $this->sync($transaction, $this->status($transaction->order_id));
        } catch (Throwable $e) {
            Log::warning('Sinkronisasi status Midtrans gagal', [
                'order_id' => $transaction->order_id,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    private function serverKey(): string
    {
        return (string) config('services.midtrans.server_key');
    }

    private function snapBase(): string
    {
        return config('services.midtrans.production')
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    private function apiBase(): string
    {
        return config('services.midtrans.production')
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }
}
