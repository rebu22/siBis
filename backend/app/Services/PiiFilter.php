<?php

namespace App\Services;

/**
 * NFR-16: redaksi data pribadi sebelum teks dikirim ke LLM/FastAPI.
 * Yang diganti hanya salinan yang dikirim; pesan asli di database tetap utuh.
 */
class PiiFilter
{
    public static function redact(string $text): string
    {
        // NPWP lebih dulu: formatnya mengandung titik dan strip.
        $text = preg_replace(
            '/(?<!\d)\d{2}\.\d{3}\.\d{3}\.\d-\d{3}\.\d{3}(?!\d)/',
            '[npwp]',
            $text
        ) ?? $text;

        $text = preg_replace(
            '/[\w.+-]+@[\w-]+(?:\.[\w-]+)+/u',
            '[email]',
            $text
        ) ?? $text;

        // Nomor Indonesia: 08xx, 628xx, +628xx.
        $text = preg_replace(
            '/(?<!\d)(?:\+62|62|0)8[1-9][0-9]{6,11}(?!\d)/',
            '[telepon]',
            $text
        ) ?? $text;

        // NIK 16 digit.
        $text = preg_replace(
            '/(?<!\d)\d{16}(?!\d)/',
            '[nik]',
            $text
        ) ?? $text;

        return $text;
    }
}
