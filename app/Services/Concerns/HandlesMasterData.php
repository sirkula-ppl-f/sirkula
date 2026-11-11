<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Helper bersama untuk service data master (kategori dan material).
 */
trait HandlesMasterData
{
    /**
     * Slug unik dari nama. Bila sudah dipakai, ditambah -2, -3, dan seterusnya.
     * $fallback dipakai bila nama tidak menghasilkan slug (misalnya hanya berisi simbol).
     */
    protected function uniqueSlug(Builder $query, string $name, string $fallback): string
    {
        $base = Str::slug($name) ?: $fallback;
        $slug = $base;

        for ($suffix = 2; (clone $query)->where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /**
     * Filter nama yang memuat $term. Karakter %, _, dan ! di-escape supaya dicari sebagai huruf biasa.
     *
     * ESCAPE '!' ditulis langsung di SQL karena backslash berperilaku beda di MySQL dan SQLite.
     */
    protected function searchName(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '') {
            return;
        }

        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
        $column = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn('name'));

        $query->whereRaw("{$column} LIKE ? ESCAPE '!'", ['%'.$escaped.'%']);
    }
}
