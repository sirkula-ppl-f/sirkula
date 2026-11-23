<?php

namespace Tests\Unit\Support;

use App\Support\RequestId;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequestIdTest extends TestCase
{
    private function request(?string $header = null): Request
    {
        return Request::create('/admin/listing', 'GET', server: $header === null ? [] : ['HTTP_X_REQUEST_ID' => $header]);
    }

    public function test_header_yang_valid_dipakai_apa_adanya(): void
    {
        foreach (['abc-123_XY.z', 'abcdefgh', str_repeat('A', 64), '9f1c2d3e-4b5a-6789-abcd-ef0123456789'] as $header) {
            $this->assertSame($header, RequestId::for($this->request($header)), "Header: {$header}");
        }
    }

    public function test_header_kosong_atau_tidak_valid_diganti_uuid_baru(): void
    {
        $tidakValid = [
            null,
            '',
            'pendek',                 // kurang dari 8 karakter
            str_repeat('a', 65),      // lebih dari 64 karakter
            'abc def ghi',            // spasi
            "abcdefgh\n",             // newline di akhir
            '<script>alert(1)</script>',
            'id/../../rahasia',
        ];

        foreach ($tidakValid as $header) {
            $id = RequestId::for($this->request($header));

            $this->assertTrue(Str::isUuid($id), 'Header '.json_encode($header).' harus diganti UUID, didapat: '.$id);
            $this->assertNotSame($header, $id);
        }
    }

    public function test_nilai_sama_dalam_satu_request_dan_berbeda_antar_request(): void
    {
        $request = $this->request();

        $pertama = RequestId::for($request);
        $this->assertSame($pertama, RequestId::for($request));

        // Header yang muncul belakangan tidak mengganti nilai yang sudah dipakai di request ini.
        $request->headers->set(RequestId::HEADER, 'header-baru-123');
        $this->assertSame($pertama, RequestId::for($request));

        $this->assertNotSame($pertama, RequestId::for($this->request()));
    }
}
