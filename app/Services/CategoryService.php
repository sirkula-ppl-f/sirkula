<?php

namespace App\Services;

use App\Enums\ListingType;
use App\Exceptions\MasterDataInUseException;
use App\Models\Category;
use App\Services\Concerns\HandlesMasterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    use HandlesMasterData;

    public const PER_PAGE = 15;

    /**
     * Daftar kategori untuk halaman admin, beserta tipe listing dan status dipakai (tanpa N+1).
     *
     * @param  array{search?: ?string, active?: ?bool, type?: ?ListingType}  $filters
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query()->withInUse()->with('categoryListingTypes');

        $this->searchName($query, $filters['search'] ?? null);

        if (isset($filters['active'])) {
            $query->where($query->qualifyColumn('is_active'), $filters['active']);
        }

        if (isset($filters['type'])) {
            $query->forListingType($filters['type']);
        }

        return $query->orderBy($query->qualifyColumn('name'))
            ->orderBy($query->qualifyColumn('id'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  array{name: string, description?: ?string, listing_types: array<int, ListingType|string>}  $data
     */
    public function create(array $data): Category
    {
        $types = $this->toListingTypes($data['listing_types']);

        return DB::transaction(function () use ($data, $types) {
            $category = Category::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug(Category::query(), $data['name'], 'kategori'),
                'description' => $data['description'] ?? null,
            ]);

            $this->addListingTypes($category, $types);

            return $category;
        });
    }

    /**
     * Ubah nama, deskripsi, dan tipe listing. Slug tidak pernah berubah.
     *
     * Tipe listing yang masih dipakai listing dengan kategori ini tidak boleh dihapus.
     *
     * @param  array{name: string, description?: ?string, listing_types: array<int, ListingType|string>}  $data
     *
     * @throws ValidationException
     */
    public function update(Category $category, array $data): Category
    {
        $types = $this->toListingTypes($data['listing_types']);

        return DB::transaction(function () use ($category, $data, $types) {
            $current = $category->listingTypes();
            $removed = $current->reject(fn (ListingType $type) => $types->contains($type));
            $added = $types->reject(fn (ListingType $type) => $current->contains($type));

            $blocked = $category->listingTypesInUse()->filter(fn (ListingType $type) => $removed->contains($type));

            if ($blocked->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'listing_types' => __('master-data.categories.type_in_use', [
                        'types' => $blocked->map(fn (ListingType $type) => $type->label())->join(', ', ' dan '),
                    ]),
                ]);
            }

            $category->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            if ($removed->isNotEmpty()) {
                $category->categoryListingTypes()
                    ->whereIn('listing_type', $removed->map(fn (ListingType $type) => $type->value))
                    ->delete();
            }

            $this->addListingTypes($category, $added);

            if ($removed->isNotEmpty() || $added->isNotEmpty()) {
                $category->touch();
            }

            return $category->unsetRelation('categoryListingTypes');
        });
    }

    /**
     * Nonaktifkan kategori. Listing lama tetap menunjuk kategori ini; data lain tidak diubah.
     */
    public function deactivate(Category $category): void
    {
        $category->update(['is_active' => false]);
    }

    public function activate(Category $category): void
    {
        $category->update(['is_active' => true]);
    }

    /**
     * Hapus kategori yang belum dipakai.
     *
     * Bila database menolak karena foreign key dari tabel lain (misalnya listing dibuat tepat
     * sebelum dihapus), penolakan itu juga dilaporkan sebagai "sudah dipakai".
     *
     * @throws MasterDataInUseException
     */
    public function delete(Category $category): void
    {
        if ($category->isInUse()) {
            throw new MasterDataInUseException(__('master-data.categories.in_use'));
        }

        try {
            DB::transaction(function () use ($category) {
                $category->categoryListingTypes()->delete();
                $category->delete();
            });
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            throw new MasterDataInUseException(__('master-data.categories.in_use'), 0, $e);
        }
    }

    /**
     * @param  array<int, ListingType|string>  $values
     * @return Collection<int, ListingType>
     */
    private function toListingTypes(array $values): Collection
    {
        return collect($values)
            ->map(fn (ListingType|string $value) => $value instanceof ListingType ? $value : ListingType::from($value))
            ->unique(fn (ListingType $type) => $type->value)
            ->values();
    }

    /**
     * @param  Collection<int, ListingType>  $types
     */
    private function addListingTypes(Category $category, Collection $types): void
    {
        $category->categoryListingTypes()->createMany(
            $types->map(fn (ListingType $type) => ['listing_type' => $type])->all()
        );
    }
}
