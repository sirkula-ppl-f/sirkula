<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    // Mengizinkan kolom-kolom ini diisi secara massal
    protected $fillable = [
        'name',
        'email',
        'password',
        'region_id', // <-- Pastikan baris ini ada di dalam model User
    ];

    /**
     * Relasi ke model User (Satu region bisa dimiliki oleh banyak user).
     */
    public function users()
    {
        return $this->hasMany(User::class, 'region_id');
    }
}
