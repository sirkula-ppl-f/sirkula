<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingImage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
