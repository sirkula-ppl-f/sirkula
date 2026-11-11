<?php

namespace App\Http\Requests\Admin;

/**
 * Aturan sama dengan tambah kategori, tetapi nama kategori itu sendiri tidak dianggap duplikat.
 */
class UpdateCategoryRequest extends StoreCategoryRequest
{
    protected function ignoredCategoryId(): ?int
    {
        return $this->route('category')->id;
    }
}
