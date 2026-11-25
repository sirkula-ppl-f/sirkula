<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ID permintaan untuk jejak audit (FR-021), dipakai moderasi listing (PBI-06).
 *
 * Memakai header X-Request-Id bila formatnya aman, selain itu membuat UUID baru. Nilainya disimpan
 * di atribut request, jadi pemanggilan berulang dalam satu request menghasilkan nilai yang sama.
 * Sengaja tanpa middleware global (bootstrap/app.php tidak diubah).
 */
final class RequestId
{
    public const HEADER = 'X-Request-Id';

    /**
     * Huruf, angka, titik, garis bawah, dan tanda hubung; 8–64 karakter.
     * \A dan \z (bukan ^ dan $) supaya newline di akhir header tidak ikut lolos.
     */
    private const PATTERN = '/\A[A-Za-z0-9._-]{8,64}\z/';

    private const ATTRIBUTE = 'sirkula.request_id';

    public static function for(Request $request): string
    {
        $stored = $request->attributes->get(self::ATTRIBUTE);

        if (is_string($stored)) {
            return $stored;
        }

        $header = $request->headers->get(self::HEADER);
        $id = is_string($header) && preg_match(self::PATTERN, $header) === 1
            ? $header
            : (string) Str::uuid();

        $request->attributes->set(self::ATTRIBUTE, $id);

        return $id;
    }
}
