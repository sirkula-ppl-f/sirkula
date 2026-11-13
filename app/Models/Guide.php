<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guide extends Model
{
    public const PLACEMENTS = [
        'listing_form' => 'Form listing',
        'detail_page' => 'Halaman detail',
        'dashboard' => 'Dashboard',
    ];

    public const STATUSES = [
        'draft' => 'Draf',
        'published' => 'Terbit',
        'archived' => 'Diarsipkan',
    ];

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
        $placements = array_values(array_intersect($placements, array_keys(self::PLACEMENTS)));

        $this->placements()->whereNotIn('placement', $placements)->delete();
        foreach ($placements as $placement) {
            $this->placements()->firstOrCreate(['placement' => $placement]);
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPlacementKeysAttribute(): array
    {
        return $this->placements->pluck('placement')->all();
    }

    public function getPlacementLabelsAttribute(): array
    {
        return $this->placements
            ->map(fn ($p) => self::PLACEMENTS[$p->placement] ?? $p->placement)
            ->all();
    }
}
