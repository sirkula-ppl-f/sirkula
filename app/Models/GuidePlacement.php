<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidePlacement extends Model
{
    public $timestamps = false;

    protected $fillable = ['guide_id', 'placement'];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
