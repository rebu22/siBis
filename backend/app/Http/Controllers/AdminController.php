<?php

namespace App\Http\Controllers;

use App\Models\AnswerReport;
use App\Models\AuditLog;
use App\Models\BugReport;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\CompanyProfile;
use App\Models\ContactMessage;
use App\Models\OtpCode;
use App\Models\Regulation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RagClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * UC-03 + area admin: dasbor, gerbang OTP, monitoring pengguna, CRM konten,
 * tindak lanjut laporan jawaban/bug, kotak masuk pesan, pembayaran premium,
 * dan audit log.
 */
class AdminController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'chats' => ChatSession::query()->count(),
                'messages' => ChatMessage::query()->count(),
                'reports_open' => AnswerReport::query()->where('status', 'baru')->count(),
                'regulations' => Regulation::query()->count(),
                'bugs_open' => BugReport::query()->where('status', 'baru')->count(),
                'messages_unread' => ContactMessage::query()->whereNull('read_at')->count(),
                'premium_users' => User::query()
                    ->where('plan', 'premium')
                    ->where(fn ($query) => $query->whereNull('premium_until')->orWhere('premium_until', '>', now()))
                    ->count(),
                'transactions_pending' => Transaction::query()->where('status', 'pending')->count(),
            ],
            'reports' => AnswerReport::query()->with('message:id,body')->latest('id')->take(10)->get(),
        ]);
    }

    public function showTwoFactor(Request $request): Response
    {
        return Inertia::render('Admin/TwoFactor', [
            'verified_at' => $request->user()->two_factor_at?->toDateTimeString(),
            'masked_email' => $this->mask((string) $request->user()->email),
        ]);
    }

    /** UC-03: kirim kode sekali pakai. Kode lama langsung tidak berlaku. */
    public function sendOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->otpCodes()->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->otpCodes()->create([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds(OtpCode::TTL_SECONDS),
        ]);

        // MAIL_MAILER=log saat dev, jadi kode muncul di storage/logs/laravel.log.
        Mail::raw(
            "Kode verifikasi admin siBis: {$code}\n\nBerlaku ".intdiv(OtpCode::TTL_SECONDS, 60).' menit. Jangan bagikan kode ini.',
            fn ($message) => $message->to($user->email)->subject('Kode verifikasi admin siBis'),
        );

        return back()->with('success', 'Kode verifikasi dikirim ke email admin.');
    }

    /** UC-03: maks 5 percobaan per kode, kode disimpan sebagai hash. */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user = $request->user();

        $otp = $user->otpCodes()->whereNull('consumed_at')->latest('id')->first();

        if ($otp === null || ! $otp->isUsable()) {
            return back()->with('error', 'Kode tidak valid atau sudah kedaluwarsa. Minta kode baru.');
        }

        $otp->increment('attempts');

        if (! Hash::check($request->string('code')->toString(), $otp->code_hash)) {
            $remaining = max(0, OtpCode::MAX_ATTEMPTS - $otp->attempts);

            return back()->with('error', "Kode salah. Sisa percobaan: {$remaining}.");
        }

        $otp->update(['consumed_at' => now()]);
        $user->forceFill(['two_factor_at' => now()])->save();

        return redirect()->route('admin.dashboard')->with('success', 'Verifikasi dua faktor berhasil.');
    }

    /** RF-18: monitoring pengguna — paket, pemakaian kuota hari ini, jumlah percakapan. */
    public function users(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->withCount('chatSessions')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plan' => (string) $user->plan,
                'verified' => $user->email_verified_at?->toDateString(),
                'quota_used' => $user->quota_date?->isToday() ? $user->quota_used : 0,
                'quota_limit' => $user->dailyQuota(),
                'sessions' => $user->chat_sessions_count,
                'joined' => $user->created_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'search' => $search,
            'summary' => [
                'total' => User::query()->count(),
                'verified' => User::query()->whereNotNull('email_verified_at')->count(),
                'premium' => User::query()->where('plan', 'premium')->count(),
                'asked_today' => (int) User::query()->whereDate('quota_date', now()->toDateString())->sum('quota_used'),
            ],
        ]);
    }

    /** RF-17 / UC-18: admin mengubah konten profil tanpa menyentuh kode. */
    public function editProfile(): Response
    {
        return Inertia::render('Admin/Profile', [
            'profile' => CompanyProfile::current(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'vision' => ['nullable', 'string', 'max:1000'],
            'mission' => ['nullable', 'string', 'max:2000'],
            'services' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['nullable', 'email', 'max:160'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = CompanyProfile::current() ?? new CompanyProfile;
        $profile->fill($data)->save();

        AuditLog::record('company_profile.update', $profile, ['fields' => array_keys($data)]);

        return back()->with('success', 'Profil perusahaan diperbarui dan langsung tampil di halaman publik.');
    }

    /** RF-26 / UC-11: admin meninjau laporan jawaban AI dan mengubah statusnya. */
    public function reports(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $status = in_array($status, AnswerReport::STATUSES, true) ? $status : '';

        $reports = AnswerReport::query()
            ->with(['message:id,chat_session_id,body', 'user:id,name,email'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AnswerReport $report) => [
                'id' => $report->id,
                'reason' => $report->reason,
                'note' => $report->note,
                'status' => $report->status,
                'created_at' => $report->created_at?->toDateTimeString(),
                'message' => $report->message?->body,
                'reporter' => $report->user ? [
                    'name' => $report->user->name,
                    'email' => $report->user->email,
                ] : ($report->guest_token ? ['name' => 'Tamu', 'email' => null] : null),
            ]);

        $summary = [];

        foreach (AnswerReport::STATUSES as $value) {
            $summary[$value] = AnswerReport::query()->where('status', $value)->count();
        }

        return Inertia::render('Admin/Reports', [
            'reports' => $reports,
            'filters' => ['status' => $status],
            'statuses' => AnswerReport::STATUSES,
            'summary' => $summary,
        ]);
    }

    public function updateReport(Request $request, AnswerReport $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', AnswerReport::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update($data);

        AuditLog::record('answer_report.update', $report, ['status' => $data['status']]);

        return back()->with('success', 'Laporan jawaban diperbarui.');
    }

    /** NFR-21: jejak audit perubahan konten, hanya menampilkan metadata (tanpa isi sensitif). */
    public function audit(Request $request): Response
    {
        $action = trim((string) $request->query('action', ''));

        $logs = AuditLog::query()
            ->with('actor:id,name')
            ->when($action !== '', fn ($query) => $query->where('action', 'like', "%{$action}%"))
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->actor?->name,
                'subject' => $log->subject_type
                    ? class_basename($log->subject_type).' #'.$log->subject_id
                    : null,
                'meta' => $log->meta,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Audit', [
            'logs' => $logs,
            'filters' => ['action' => $action],
        ]);
    }

    /** RF-19 / UC-23: daftar laporan bug, bisa disaring status dan diberi catatan. */
    public function bugReports(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $status = in_array($status, BugReport::STATUSES, true) ? $status : '';

        $reports = BugReport::query()
            ->with('user:id,name')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BugReport $report) => [
                'id' => $report->id,
                'name' => $report->name ?? $report->user?->name,
                'email' => $report->email,
                'description' => $report->description,
                'screenshot_url' => $report->screenshotUrl(),
                'status' => $report->status,
                'admin_note' => $report->admin_note,
                'created_at' => $report->created_at?->toDateTimeString(),
            ]);

        $summary = [];

        foreach (BugReport::STATUSES as $value) {
            $summary[$value] = BugReport::query()->where('status', $value)->count();
        }

        return Inertia::render('Admin/BugReports', [
            'reports' => $reports,
            'filters' => ['status' => $status],
            'statuses' => BugReport::STATUSES,
            'summary' => $summary,
        ]);
    }

    public function updateBugReport(Request $request, BugReport $bugReport): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', BugReport::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $bugReport->update($data);

        AuditLog::record('bug_report.update', $bugReport, ['status' => $data['status']]);

        return back()->with('success', 'Laporan bug diperbarui.');
    }

    /** RF-20 / UC-24: kotak masuk pesan pengguna, bisa ditandai dibaca/belum. */
    public function messages(Request $request): Response
    {
        $filter = (string) $request->query('filter', '');
        $filter = in_array($filter, ['unread', 'read'], true) ? $filter : '';

        $messages = ContactMessage::query()
            ->with('user:id,name,email')
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filter === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ContactMessage $message) => [
                'id' => $message->id,
                'subject' => $message->subject,
                'body' => $message->body,
                'read_at' => $message->read_at?->toDateTimeString(),
                'created_at' => $message->created_at?->toDateTimeString(),
                'sender' => $message->user ? [
                    'name' => $message->user->name,
                    'email' => $message->user->email,
                ] : ($message->email ? ['name' => $message->name, 'email' => $message->email] : null),
            ]);

        return Inertia::render('Admin/Messages', [
            'messages' => $messages,
            'filters' => ['filter' => $filter],
            'summary' => [
                'unread' => ContactMessage::query()->whereNull('read_at')->count(),
                'total' => ContactMessage::query()->count(),
            ],
        ]);
    }

    public function updateMessage(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate(['read' => ['required', 'boolean']]);

        $message->update([
            'read_at' => $data['read'] ? ($message->read_at ?? now()) : null,
        ]);

        return back()->with(
            'success',
            $data['read'] ? 'Pesan ditandai sudah dibaca.' : 'Pesan ditandai belum dibaca.',
        );
    }

    /** RF-21 / UC-13: monitoring pembayaran premium beserta ringkasan pendapatan. */
    public function transactions(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $status = in_array($status, Transaction::STATUSES, true) ? $status : '';

        $transactions = Transaction::query()
            ->with('user:id,name,email')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'order_id' => $transaction->order_id,
                'amount' => $transaction->amount,
                'days' => $transaction->days,
                'status' => $transaction->status,
                'payment_type' => $transaction->payment_type,
                'paid_at' => $transaction->paid_at?->toDateTimeString(),
                'created_at' => $transaction->created_at?->toDateTimeString(),
                'buyer' => $transaction->user ? [
                    'name' => $transaction->user->name,
                    'email' => $transaction->user->email,
                ] : null,
            ]);

        return Inertia::render('Admin/Transactions', [
            'transactions' => $transactions,
            'filters' => ['status' => $status],
            'statuses' => Transaction::STATUSES,
            'summary' => [
                'paid' => Transaction::query()->whereNotNull('paid_at')->count(),
                'revenue' => (int) Transaction::query()->whereNotNull('paid_at')->sum('amount'),
                'pending' => Transaction::query()->where('status', 'pending')->count(),
            ],
        ]);
    }

    /** RF-14: daftar kebijakan untuk CMS, dengan pencarian dan paginasi. */
    public function regulations(Request $request): Response
    {
        $filters = $request->only('q');

        $regulations = Regulation::query()
            ->search($filters['q'] ?? null)
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Regulation $regulation) => [
                'id' => $regulation->id,
                'title' => $regulation->title,
                'slug' => $regulation->slug,
                'category' => $regulation->category,
                'excerpt' => $regulation->excerpt,
                'status' => $regulation->status,
                'size_kb' => (int) round($regulation->file_size / 1024),
                'indexed' => $regulation->rag_doc_id !== null,
                'published_at' => $regulation->published_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Regulations', ['regulations' => $regulations, 'filters' => $filters]);
    }

    /** UC-18: PDF disimpan di disk publik (dibaca pengguna) sekaligus diindeks ke layanan RAG (dikutip chatbot). */
    public function storeRegulation(Request $request, RagClient $rag): RedirectResponse
    {
        $data = $this->validatedRegulation($request);

        $regulation = Regulation::create($data + [
            'user_id' => $request->user()->id,
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        AuditLog::record('regulation.create', $regulation, ['status' => $regulation->status]);

        $sebab = $this->storePdf($regulation, $request, $rag);

        return $sebab === null
            ? back()->with('success', 'Kebijakan tersimpan dan PDF masuk basis pengetahuan.')
            : back()->with('error', 'Kebijakan tersimpan, tetapi PDF belum masuk basis pengetahuan: '.$sebab.' Pakai tombol "Indeks ulang" untuk mencoba lagi.');
    }

    public function updateRegulation(Request $request, Regulation $regulation, RagClient $rag): RedirectResponse
    {
        $data = $this->validatedRegulation($request, $regulation);

        if ($data['status'] === 'published' && $regulation->published_at === null) {
            $data['published_at'] = now();
        }

        $regulation->update($data);

        AuditLog::record('regulation.update', $regulation, ['status' => $regulation->status]);

        // Indeks lama dipertahankan kecuali admin mengunggah PDF baru.
        if (! $request->hasFile('file')) {
            return back()->with('success', 'Perubahan kebijakan tersimpan.');
        }

        $sebab = $this->storePdf($regulation, $request, $rag);

        return $sebab === null
            ? back()->with('success', 'Perubahan tersimpan dan PDF baru sudah diindeks.')
            : back()->with('error', 'Perubahan tersimpan, tetapi PDF baru belum masuk basis pengetahuan: '.$sebab);
    }

    /** Indeks ulang PDF yang sudah tersimpan (mis. saat unggah pertama layanan AI sedang mati). */
    public function reindexRegulation(Regulation $regulation, RagClient $rag): RedirectResponse
    {
        if (! $regulation->file_path) {
            return back()->with('error', 'Kebijakan ini belum punya PDF.');
        }

        $path = Storage::disk('public')->path($regulation->file_path);

        if (! is_file($path)) {
            return back()->with('error', 'Berkas PDF-nya tidak ada di penyimpanan. Unggah ulang lewat tombol Ubah.');
        }

        // UploadedFile dibungkus dari berkas yang sudah ada supaya RagClient tidak perlu dua jalur unggah.
        $file = new UploadedFile($path, basename($path), 'application/pdf', null, true);
        $sebab = $this->index($regulation, $file, $rag);

        if ($sebab === null) {
            AuditLog::record('regulation.reindex', $regulation);

            return back()->with('success', 'PDF diindeks ulang dan siap dikutip chatbot.');
        }

        return back()->with('error', 'Indeks ulang gagal: '.$sebab);
    }

    /** UC-25: hapus kebijakan sekaligus membuang potongannya dari basis pengetahuan. */
    public function destroyRegulation(Regulation $regulation, RagClient $rag): RedirectResponse
    {
        AuditLog::record('regulation.delete', $regulation, ['title' => $regulation->title]);

        if ($regulation->rag_doc_id) {
            $rag->deleteDocument($regulation->rag_doc_id);
        }

        if ($regulation->file_path) {
            Storage::disk('public')->delete($regulation->file_path);
        }

        $regulation->delete();

        return back()->with('success', 'Kebijakan dihapus.');
    }

    /**
     * Simpan PDF ke disk publik lalu indeks ke RAG. Kegagalan indeks tidak membatalkan berkasnya:
     * halaman publik tetap punya PDF, admin cuma diberi tahu bahwa chatbot belum bisa mengutipnya.
     *
     * @return string|null null kalau PDF masuk basis pengetahuan, selain itu alasan asli dari layanan
     */
    private function storePdf(Regulation $regulation, Request $request, RagClient $rag): ?string
    {
        $file = $request->file('file');

        if ($file === null) {
            return null;
        }

        if ($regulation->file_path) {
            Storage::disk('public')->delete($regulation->file_path);
        }

        $path = $file->storeAs('kebijakan', $regulation->slug.'.pdf', 'public');
        $regulation->update(['file_path' => $path, 'file_size' => (int) $file->getSize()]);

        return $this->index($regulation, $file, $rag);
    }

    /** @return string|null null kalau berhasil, selain itu alasan gagal (PDF pindai, layanan mati, ...) */
    private function index(Regulation $regulation, UploadedFile $file, RagClient $rag): ?string
    {
        $lama = $regulation->rag_doc_id;

        try {
            $document = $rag->ingestPdf($file, $regulation->title, route('regulations.show', $regulation->slug));
        } catch (\RuntimeException $e) {
            $regulation->update(['rag_doc_id' => null]);

            return $e->getMessage();
        }

        // Dokumen lama (kalau ada) dibuang setelah yang baru masuk, supaya tidak ada dua salinan.
        if ($lama) {
            $rag->deleteDocument($lama);
        }

        $regulation->update(['rag_doc_id' => $document['id']]);

        return null;
    }

    /**
     * Slug dibuat unik di sini, bukan lewat aturan unique, karena slug boleh dikosongkan
     * dan diambil dari judul. Hasilnya: "judul-sama", "judul-sama-2", dst.
     */
    private function validatedRegulation(Request $request, ?Regulation $regulation = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'alpha_dash'],
            'category' => ['nullable', 'string', 'max:60'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'file' => [$regulation?->file_path ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:20480'],
            'status' => ['required', 'in:draft,published'],
        ]);

        unset($data['file']);

        // Kategori diketik bebas: "perizinan" dan "Perizinan" akan tampil sebagai dua filter terpisah.
        if (array_key_exists('category', $data)) {
            $data['category'] = filled($data['category'])
                ? Str::ucfirst(Str::lower(trim($data['category'])))
                : null;
        }

        $base = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $base = $base !== '' ? $base : 'kebijakan';
        $slug = $base;
        $suffix = 2;

        while (Regulation::query()
            ->when($regulation !== null, fn ($query) => $query->whereKeyNot($regulation->id))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $data['slug'] = $slug;

        return $data;
    }

    /** Hanya tampilkan bentuk samar email di UI supaya alamat lengkap tidak dirender di halaman. */
    private function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::of($name)->substr(0, 2).'***@'.$domain;
    }
}
