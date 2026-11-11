<?php

namespace App\Services;

use App\Exceptions\MasterDataInUseException;
use App\Models\Material;
use App\Services\Concerns\HandlesMasterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;

class MaterialService
{
    use HandlesMasterData;

    public const PER_PAGE = 15;

    /**
     * Daftar material untuk halaman admin, beserta status dipakai (tanpa N+1).
     *
     * @param  array{search?: ?string, active?: ?bool}  $filters
     * @return LengthAwarePaginator<int, Material>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Material::query()->withInUse();

        $this->searchName($query, $filters['search'] ?? null);

        if (isset($filters['active'])) {
            $query->where($query->qualifyColumn('is_active'), $filters['active']);
        }

        return $query->orderBy($query->qualifyColumn('name'))
            ->orderBy($query->qualifyColumn('id'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  array{name: string, description?: ?string}  $data
     */
    public function create(array $data): Material
    {
        return Material::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug(Material::query(), $data['name'], 'material'),
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Ubah nama dan deskripsi. Slug tidak pernah berubah.
     *
     * @param  array{name: string, description?: ?string}  $data
     */
    public function update(Material $material, array $data): Material
    {
        $material->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return $material;
    }

    /**
     * Nonaktifkan material. Listing dan harga acuan lama tetap menunjuk material ini; data lain tidak diubah.
     */
    public function deactivate(Material $material): void
    {
        $material->update(['is_active' => false]);
    }

    public function activate(Material $material): void
    {
        $material->update(['is_active' => true]);
    }

    /**
     * Hapus material yang belum dipakai.
     *
     * Bila database menolak karena foreign key dari tabel lain (misalnya listing atau harga acuan
     * dibuat tepat sebelum dihapus), penolakan itu juga dilaporkan sebagai "sudah dipakai".
     *
     * @throws MasterDataInUseException
     */
    public function delete(Material $material): void
    {
        if ($material->isInUse()) {
            throw new MasterDataInUseException(__('master-data.materials.in_use'));
        }

        try {
            $material->delete();
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            throw new MasterDataInUseException(__('master-data.materials.in_use'), 0, $e);
        }
    }
}
