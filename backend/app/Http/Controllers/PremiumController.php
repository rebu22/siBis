<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\MidtransService;
use App\Support\GuestQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

/**
 * RF-07 / UC-12: berlangganan premium lewat Midtrans.
 * RF-21 / UC-13: riwayat & monitoring pembayaran (sisi admin ada di AdminController).
 */
class PremiumController extends Controller
{
    public function index(Request $request, MidtransService $midtrans): Response
    {
        $user = $request->user();

        return Inertia::render('Premium/Index', [
            'plan' => $user ? $user->effectivePlan() : 'free',
            'premiumUntil' => $user?->premium_until?->toDateTimeString(),
            'quota' => $user
                ? ['remaining' => $user->quotaRemaining(), 'limit' => $user->dailyQuota()]
                : ['remaining' => GuestQuota::remaining($request), 'limit' => GuestQuota::DAILY_LIMIT],
            'price' => (int) config('services.midtrans.premium_price'),
            'days' => (int) config('services.midtrans.premium_days'),
            'configured' => $midtrans->configured(),
            'isGuest' => $user === null,
            'transactions' => $user ? $user->transactions()
                ->latest('id')
                ->take(10)
                ->get()
                ->map(fn (Transaction $transaction) => [
                    'order_id' => $transaction->order_id,
                    'amount' => $transaction->amount,
                    'days' => $transaction->days,
                    'status' => $transaction->status,
                    'payment_type' => $transaction->payment_type,
                    'paid_at' => $transaction->paid_at?->toDateTimeString(),
                    'created_at' => $transaction->created_at?->toDateTimeString(),
                ]) : [],
        ]);
    }

    /** Buat transaksi pending lalu arahkan ke halaman pembayaran Midtrans. */
    public function checkout(Request $request, MidtransService $midtrans): RedirectResponse
    {
        if (! $midtrans->configured()) {
            return back()->with('error', 'Pembayaran belum dikonfigurasi. Hubungi admin lewat menu Bantuan.');
        }

        $price = (int) config('services.midtrans.premium_price');

        if ($price < 1) {
            return back()->with('error', 'Harga paket premium belum diatur.');
        }

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'order_id' => 'SIBIS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'plan' => 'premium',
            'amount' => $price,
            'days' => (int) config('services.midtrans.premium_days'),
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        try {
            $snap = $midtrans->createSnap($transaction);

            $transaction->update([
                'snap_token' => $snap['token'] ?? null,
                'snap_redirect_url' => $snap['redirect_url'] ?? null,
                'raw_response' => $snap,
            ]);
        } catch (Throwable $e) {
            $transaction->update([
                'status' => 'failure',
                'raw_response' => ['error' => $e->getMessage()],
            ]);

            Log::warning('Checkout Midtrans gagal', [
                'order_id' => $transaction->order_id,
                'reason' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memulai pembayaran. Coba lagi beberapa saat lagi.');
        }

        if (! $transaction->snap_redirect_url) {
            return back()->with('error', 'Midtrans tidak mengembalikan halaman pembayaran. Coba lagi.');
        }

        return redirect()->away($transaction->snap_redirect_url);
    }

    /** Halaman kembali dari Midtrans: tanyakan status langsung ke API. */
    public function finish(Request $request, MidtransService $midtrans): RedirectResponse
    {
        $orderId = (string) $request->query('order_id', '');

        $transaction = $request->user()->transactions()
            ->where('order_id', $orderId)
            ->first();

        abort_if(! $transaction, 404);

        $midtrans->safeStatus($transaction);

        $transaction->refresh();

        if ($transaction->isPaid()) {
            $until = $request->user()->fresh()?->premium_until?->format('d M Y');

            return redirect()->route('premium.index')
                ->with('success', 'Pembayaran diterima. Paket Premium aktif sampai '.$until.'.');
        }

        return redirect()->route('premium.index')->with(
            'error',
            'Pembayaran belum terkonfirmasi (status: '.Transaction::label($transaction->status).'). '
            .'Selesaikan pembayaran, lalu buka halaman ini lagi.'
        );
    }

    /** Webhook Midtrans (tanpa login; CSRF dikecualikan di bootstrap/app.php). */
    public function notification(Request $request, MidtransService $midtrans): JsonResponse
    {
        try {
            $transaction = $midtrans->handleNotification($request->all());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        if (! $transaction) {
            return response()->json(['message' => 'Order tidak dikenal.'], 404);
        }

        return response()->json(['status' => 'ok']);
    }
}
