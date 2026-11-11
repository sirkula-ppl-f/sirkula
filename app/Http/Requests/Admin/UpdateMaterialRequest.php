<?php

namespace App\Http\Requests\Admin;

/**
 * Aturan sama dengan tambah material, tetapi nama material itu sendiri tidak dianggap duplikat.
 */
class UpdateMaterialRequest extends StoreMaterialRequest
{
    protected function ignoredMaterialId(): ?int
    {
        return $this->route('material')->id;
    }
}
