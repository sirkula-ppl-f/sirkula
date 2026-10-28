<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = [
        'name',
        'code',
        'parent_id',
    ];

    /**
     * Relasi ke model User (Satu region bisa dimiliki oleh banyak user).
     */
    public function users()
    {
        return $this->hasMany(User::class, 'region_id');
    }

    /**
     * Relasi parent (Provinsi untuk Kabupaten/Kota nanti).
     */
    public function parent()
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    /**
     * Relasi children (Kabupaten/Kota dari Provinsi).
     */
    public function children()
    {
        return $this->hasMany(Region::class, 'parent_id');
    }
}
