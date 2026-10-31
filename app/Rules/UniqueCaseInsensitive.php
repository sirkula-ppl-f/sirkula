<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Tolak nilai yang sudah ada di kolom tabel tanpa membedakan huruf kapital.
 *
 * Rule::unique membandingkan dengan "=", yang case-sensitive di SQLite. Rule ini membandingkan
 * LOWER(kolom) dengan nilai yang di-lowercase, sehingga hasilnya sama di SQLite dan MySQL.
 * Catatan: LOWER() di SQLite hanya mengubah huruf ASCII.
 *
 * Contoh: new UniqueCaseInsensitive('categories', 'name', ignoreId: $category->id)
 */
class UniqueCaseInsensitive implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $column = 'name',
        private ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $query = DB::table($this->table);
        $column = $query->getGrammar()->wrap($this->column);

        $exists = $query
            ->whereRaw("LOWER({$column}) = ?", [mb_strtolower($value)])
            ->when($this->ignoreId !== null, fn ($query) => $query->where('id', '!=', $this->ignoreId))
            ->exists();

        if ($exists) {
            $fail('validation.unique')->translate();
        }
    }
}
