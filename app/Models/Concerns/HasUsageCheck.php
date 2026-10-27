<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cek apakah data master sudah dipakai tabel milik PBI lain (listings, reference_prices).
 *
 * Tabel tersebut dibuat PBI lain dan bisa belum ada, jadi setiap referensi dijaga dengan
 * Schema::hasColumn (bernilai false juga bila tabelnya belum ada).
 */
trait HasUsageCheck
{
    /**
     * Tabel dan kolom FK yang menunjuk ke model ini.
     *
     * @return array<string, string> nama tabel => nama kolom
     */
    abstract protected static function usageReferences(): array;

    /**
     * Referensi yang tabel dan kolomnya sudah ada di database.
     *
     * @return array<string, string>
     */
    protected static function existingUsageReferences(): array
    {
        return array_filter(
            static::usageReferences(),
            fn (string $column, string $table) => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Tambah atribut in_use (0/1) lewat subquery, supaya daftar tidak menjalankan query per baris.
     */
    #[Scope]
    protected function withInUse(Builder $query): void
    {
        if ($query->getQuery()->columns === null) {
            $query->select($query->qualifyColumn('*'));
        }

        $checks = collect(static::existingUsageReferences())
            ->map(fn (string $column, string $table) => 'exists ('.DB::table($table)
                ->selectRaw('1')
                ->whereColumn($table.'.'.$column, $query->qualifyColumn($this->getKeyName()))
                ->toSql().')');

        $query->selectRaw('('.($checks->isEmpty() ? '0' : $checks->implode(' or ')).') as in_use');
    }

    /**
     * Apakah data ini sudah dipakai (lihat usageReferences() di model).
     *
     * PBI-07/08 wajib memakai kolom listings.category_id dan listings.material_id,
     * PBI-05 wajib memakai reference_prices.material_id, supaya pengecekan ini berlaku.
     */
    public function isInUse(): bool
    {
        if (array_key_exists('in_use', $this->attributes)) {
            return (bool) $this->attributes['in_use'];
        }

        foreach (static::existingUsageReferences() as $table => $column) {
            if (DB::table($table)->where($column, $this->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }
}
