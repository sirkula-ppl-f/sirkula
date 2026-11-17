<?php

namespace App\Enums;

/**
 * Kondisi barang untuk listing PRELOVED.
 * Nilai backing sama dengan teks yang disimpan di listings.item_condition.
 */
enum ItemCondition: string
{
    case SepertiBaru = 'Seperti Baru';
    case Baik = 'Baik';
    case Cukup = 'Cukup';
    case Kurang = 'Kurang';

    public function label(): string
    {
        return $this->value;
    }
}
