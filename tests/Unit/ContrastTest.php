<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * NFR-25 (WCAG 2.1 AA): mengunci pasangan teks/latar yang benar-benar dipakai UI
 * design system "Corporate Trust" (prompt.txt), supaya penggantian token di masa depan
 * tidak diam-diam menurunkan kontras.
 *
 * Sengaja TIDAK diuji (alasan eksplisit):
 * - emerald-500 (#10B981) sebagai TEKS: 2.54:1 di putih -> gagal AA, jadi hijau untuk
 *   teks selalu emerald-700 (5.48:1). emerald-500 hanya untuk titik/ikon penanda.
 * - border kartu slate-100 (1.10:1) dan border input slate-300: 1.4.11 hanya berlaku
 *   untuk informasi yang wajib ada; kartu juga dibedakan oleh shadow berwarna, dan
 *   setiap input punya <label> terlihat. Border input sengaja dinaikkan dari slate-200
 *   (sesuai prompt) ke slate-300 demi keterbacaan bidang isian.
 * - Teks non-aktif (disabled) dikecualikan WCAG 2.1.
 */
class ContrastTest extends TestCase
{
    /** Pasangan [nama, warna teks, warna latar] yang dipakai di UI. */
    private const PAIRS = [
        ['slate-900 di putih', '#0f172a', '#ffffff'],
        ['slate-900 di slate-50', '#0f172a', '#f8fafc'],
        ['slate-700 di putih', '#334155', '#ffffff'],
        ['slate-700 di slate-50', '#334155', '#f8fafc'],
        ['slate-600 di putih', '#475569', '#ffffff'],
        ['slate-600 di slate-50', '#475569', '#f8fafc'],
        ['slate-500 di putih', '#64748b', '#ffffff'],
        ['slate-500 di slate-50', '#64748b', '#f8fafc'],
        ['indigo-600 di putih', '#4f46e5', '#ffffff'],
        ['indigo-600 di slate-50', '#4f46e5', '#f8fafc'],
        ['indigo-600 di indigo-50', '#4f46e5', '#eef2ff'],
        ['violet-600 di putih', '#7c3aed', '#ffffff'],
        ['putih di indigo-600 (tombol)', '#ffffff', '#4f46e5'],
        ['putih di violet-600 (gradient)', '#ffffff', '#7c3aed'],
        ['putih di indigo-900 (CTA gelap)', '#ffffff', '#312e81'],
        ['putih di indigo-950 (CTA gelap)', '#ffffff', '#1e1b4b'],
        ['emerald-700 di putih', '#047857', '#ffffff'],
    ];

    public function test_semua_pasangan_teks_memenuhi_wcag_aa(): void
    {
        foreach (self::PAIRS as [$name, $text, $background]) {
            $ratio = $this->contrast($text, $background);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                "Kontras {$name} hanya ".round($ratio, 2).':1, di bawah AA (4.5:1).',
            );
        }
    }

    /**
     * Penjaga regresi: hijau 500 memang gagal AA, jadi kalau suatu saat dipakai untuk
     * teks, tes ini yang mengingatkan alih-alih diam-diam lolos.
     */
    public function test_emerald_500_tidak_boleh_dipakai_untuk_teks(): void
    {
        $this->assertLessThan(4.5, $this->contrast('#10b981', '#ffffff'));
        $this->assertGreaterThanOrEqual(4.5, $this->contrast('#047857', '#ffffff'));
    }

    private function contrast(string $a, string $b): float
    {
        $high = max($this->luminance($a), $this->luminance($b));
        $low = min($this->luminance($a), $this->luminance($b));

        return ($high + 0.05) / ($low + 0.05);
    }

    private function luminance(string $hex): float
    {
        $channels = array_map(
            fn (string $pair) => $this->linear(hexdec($pair) / 255),
            str_split(ltrim($hex, '#'), 2),
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private function linear(float $channel): float
    {
        return $channel <= 0.03928
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
