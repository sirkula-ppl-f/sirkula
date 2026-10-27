<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingImage extends Model
{
    use HasFactory;

    // Memberikan izin ke Laravel untuk mengisi kolom ini secara otomatis lewat fungsi create()
    protected $fillable = ['listing_id', 'image_path'];

    // Relasi balik ke tabel listings
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
