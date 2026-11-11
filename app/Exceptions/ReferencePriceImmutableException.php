<?php

namespace App\Exceptions;

use LogicException;

/**
 * Harga acuan bersifat append-only: entri yang sudah terbit tidak boleh diubah atau dihapus.
 * Koreksi salah input dilakukan dengan menerbitkan entri baru.
 */
class ReferencePriceImmutableException extends LogicException
{
    public static function update(): self
    {
        return new self(__('reference-prices.immutable.update'));
    }

    public static function delete(): self
    {
        return new self(__('reference-prices.immutable.delete'));
    }
}
