<?php

namespace App\Support;

use App\Services\ReferencePriceService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Format uang dan tanggal Indonesia untuk tampilan, supaya semua halaman konsisten.
 *
 * Locale 'id' dipasang langsung karena APP_LOCALE bawaan 'en'.
 */
final class IndonesianFormat
{
    /**
     * "Rp 3.500"
     */
    public static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * "Rp 3.500/kg"
     */
    public static function pricePerKg(int $amount): string
    {
        return self::rupiah($amount).'/kg';
    }

    /**
     * "8 Nov 2026". Tanggal dipakai apa adanya, tanpa pindah zona waktu.
     */
    public static function date(CarbonInterface $date): string
    {
        return CarbonImmutable::instance($date)->locale('id')->translatedFormat('j M Y');
    }

    /**
     * "8 Nov 2026, 14.05 WIB". Waktu dipindah ke WIB lebih dulu (created_at tersimpan dalam UTC).
     */
    public static function dateTimeWib(CarbonInterface $time): string
    {
        return CarbonImmutable::instance($time)
            ->setTimezone(ReferencePriceService::TIMEZONE)
            ->locale('id')
            ->translatedFormat('j M Y, H.i').' WIB';
    }
}
