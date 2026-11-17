<?php

namespace App\Http\Controllers;

use App\Models\BugReport;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** RF-08/RF-09: kanal bantuan pengguna — lapor bug dan kirim pesan ke admin. */
class SupportController extends Controller
{
    /** UC-21: form laporan bug. Terbuka untuk pengunjung; email tetap diminta untuk tindak lanjut. */
    public function bugForm(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Support/Bug', [
            'defaults' => [
                'name' => $user?->name ?? '',
                'email' => $user?->email ?? '',
            ],
        ]);
    }

    public function bugStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->hasFile('screenshot')
            ? $request->file('screenshot')->store('lapor-bug', 'public')
            : null;

        BugReport::create([
            'user_id' => $request->user()?->id,
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'description' => $data['description'],
            'screenshot_path' => $path,
        ]);

        return redirect()->route('bug.create')
            ->with('success', 'Laporan bug terkirim. Terima kasih sudah membantu kami.');
    }

    /** UC-22: form pesan ke admin, hanya untuk pengguna yang sudah login. */
    public function contactForm(): Response
    {
        return Inertia::render('Support/Contact');
    }

    public function contactStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        ContactMessage::create($data + ['user_id' => $request->user()->id]);

        return redirect()->route('contact.create')
            ->with('success', 'Pesan terkirim. Admin akan membalas lewat email terdaftar.');
    }
}
