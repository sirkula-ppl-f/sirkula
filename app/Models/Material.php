<?php

namespace App\Models;

use App\Models\Concerns\HasUsageCheck;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Jenis material. Data master nasional, tidak per wilayah.
 *
 * Kontrak:
 * - PBI-07/08: tabel listings wajib memakai kolom material_id (FK ke materials, disarankan
 *   ->constrained()->restrictOnDelete()).
 * - PBI-05: tabel reference_prices wajib memakai kolom material_id.
 * - Pilihan material di form: Material::active()->orderBy('name')->get().
 * - Material yang sudah dipakai listing atau harga acuan tidak bisa dihapus, hanya dinonaktifkan.
 */
#[Fillable(['name', 'slug', 'description', 'is_active'])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory, HasUsageCheck;

    /**
     * Nilai default model, disamakan dengan default kolom di database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where($query->qualifyColumn('is_active'), true);
    }

    /**
     * Material dianggap dipakai bila ada baris listings atau reference_prices dengan material_id ini.
     *
     * @return array<string, string>
     */
    protected static function usageReferences(): array
    {
        return [
            'listings' => 'material_id',
            'reference_prices' => 'material_id',
        ];
    }
}
