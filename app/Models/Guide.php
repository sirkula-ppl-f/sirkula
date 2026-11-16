<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guide extends Model
{
    use HasFactory;

    // Daftar nilai yang valid. Label tampilannya ada di lang/id/guides.php.
    public const PLACEMENTS = ['listing_form', 'detail_page', 'dashboard'];

    public const STATUSES = ['draft', 'published', 'archived'];

    protected $fillable = ['title', 'slug', 'body', 'status', 'views_count', 'interactions_count'];

    public function placements(): HasMany
    {
        return $this->hasMany(GuidePlacement::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeForPlacement(Builder $query, string $placement): Builder
    {
        return $query->whereHas('placements', fn ($q) => $q->where('placement', $placement));
    }

    public function syncPlacements(array $placements): void
    {
        $placements = array_values(array_intersect($placements, self::PLACEMENTS));

        $this->placements()->whereNotIn('placement', $placements)->delete();
        foreach ($placements as $placement) {
            $this->placements()->firstOrCreate(['placement' => $placement]);
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return __('guides.statuses.'.$this->status);
    }

    public function getPlacementKeysAttribute(): array
    {
        return $this->placements->pluck('placement')->all();
    }

    public function getPlacementLabelsAttribute(): array
    {
        return $this->placements
            ->map(fn ($p) => __('guides.placements.'.$p->placement))
            ->all();
    }
}
