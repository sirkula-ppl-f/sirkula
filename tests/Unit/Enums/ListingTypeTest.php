<?php

namespace Tests\Unit\Enums;

use App\Enums\ListingType;
use PHPUnit\Framework\TestCase;

class ListingTypeTest extends TestCase
{
    public function test_nilai_enum_huruf_kapital(): void
    {
        $this->assertSame(
            ['PRELOVED', 'RECYCLABLE', 'UPCYCLE'],
            array_map(fn (ListingType $type) => $type->value, ListingType::cases())
        );
    }

    public function test_label_dalam_bahasa_indonesia(): void
    {
        $this->assertSame('Barang Bekas', ListingType::Preloved->label());
        $this->assertSame('Material Daur Ulang', ListingType::Recyclable->label());
        $this->assertSame('Produk Upcycle', ListingType::Upcycle->label());
    }
}
