<?php

namespace App\Services;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Harga acuan material per provinsi (PBI-05, FR-008).
 *
 * Satu-satunya titik penentu "hari ini" (WIB) untuk harga acuan. config/app.php tetap UTC.
 * Harga acuan bersifat informatif: service ini tidak pernah menulis ke tabel listings.
 */
class ReferencePriceService
{
    /**
     * Zona waktu untuk tanggal berlaku, label Berlaku/Terjadwal, dan tampilan waktu terbit.
     */
    public const TIMEZONE = 'Asia/Jakarta';

    public const PER_PAGE = 15;

    /**
     * Tanggal berlaku paling awal yang boleh diterbitkan.
     */
    public const MIN_EFFECTIVE_DATE = '2020-01-01';

    /**
     * Tanggal berlaku paling lambat: hari ini (WIB) ditambah sekian hari.
     */
    public const MAX_DAYS_AHEAD = 90;

    /**
     * Harga per kg paling besar (rupiah).
     */
    public const MAX_PRICE = 10_000_000;

    /**
     * Batas naik ke wilayah induk saat mencari provinsi, untuk berjaga dari data parent_id yang berputar.
     */
    private const MAX_REGION_DEPTH = 5;

    /**
     * Awal hari ini di WIB.
     */
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * Tanggal berlaku paling lambat yang boleh diterbitkan hari ini.
     */
    public function maxEffectiveDate(): CarbonImmutable
    {
        return $this->today()->addDays(self::MAX_DAYS_AHEAD);
    }

    /**
     * Terbitkan harga acuan baru. Data sudah divalidasi FormRequest; published_by diisi dari $admin.
     *
     * @param  array{material_id: int|string, region_id: int|string, price_per_kg: int|string, effective_date: string, source: string}  $data
     *
     * @throws AuthorizationException bila $admin bukan admin
     */
    public function publish(array $data, User $admin): ReferencePrice
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException(__('reference-prices.publish_forbidden'));
        }

        return ReferencePrice::create([
            'material_id' => (int) $data['material_id'],
            'region_id' => (int) $data['region_id'],
            'price_per_kg' => (int) $data['price_per_kg'],
            'effective_date' => $data['effective_date'],
            'source' => trim($data['source']),
            'published_by' => $admin->id,
        ]);
    }

    /**
     * Harga acuan terbaru untuk material di provinsi $region pada saat $on (bawaan: sekarang).
     *
     * - "Terbaru": effective_date <= tanggal $on di WIB, urut effective_date terbaru lalu id terbesar.
     * - $region yang bukan provinsi (kab/kota) dinaikkan ke provinsi induknya lewat parent_id.
     * - Material nonaktif tidak disaring; pemanggil yang memutuskan perlu ditampilkan atau tidak.
     * - null bila belum ada harga yang berlaku.
     */
    public function latestFor(Material $material, Region $region, ?CarbonInterface $on = null): ?ReferencePrice
    {
        $province = $this->provinceOf($region);

        if ($province === null) {
            return null;
        }

        $date = $on === null ? $this->today() : CarbonImmutable::instance($on)->setTimezone(self::TIMEZONE);

        return ReferencePrice::query()
            ->where('material_id', $material->getKey())
            ->where('region_id', $province->getKey())
            ->effectiveOn($date->toDateString())
            ->newestFirst()
            ->first();
    }

    /**
     * Provinsi (region dengan parent_id null) untuk $region. null bila rantai parent_id rusak.
     */
    public function provinceOf(Region $region): ?Region
    {
        $current = $region;

        for ($depth = 0; $current->parent_id !== null; $depth++) {
            if ($depth >= self::MAX_REGION_DEPTH || $current->parent === null) {
                return null;
            }

            $current = $current->parent;
        }

        return $current;
    }
}
