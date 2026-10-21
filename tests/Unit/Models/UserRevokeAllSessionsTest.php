<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserRevokeAllSessionsTest extends TestCase
{
    use RefreshDatabase;

    private function buatSesi(User $user): void
    {
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);
    }

    public function test_menghapus_semua_sesi_milik_user(): void
    {
        $user = User::factory()->create();
        $this->buatSesi($user);
        $this->buatSesi($user);

        $user->revokeAllSessions();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_sesi_user_lain_tidak_tersentuh(): void
    {
        $user = User::factory()->create();
        $lain = User::factory()->create();
        $this->buatSesi($user);
        $this->buatSesi($lain);

        $user->revokeAllSessions();

        $this->assertSame(1, DB::table('sessions')->where('user_id', $lain->id)->count());
    }

    public function test_remember_token_diganti_dan_tersimpan(): void
    {
        $user = User::factory()->create();
        $tokenLama = $user->remember_token;

        $user->revokeAllSessions();

        $tokenBaru = $user->fresh()->remember_token;
        $this->assertNotSame($tokenLama, $tokenBaru);
        $this->assertSame(60, strlen($tokenBaru));
    }
}
