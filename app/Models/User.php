<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Nilai default model, disamakan dengan default kolom di database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::Warga->value,
        'status' => UserStatus::Active->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Simpan email dalam huruf kecil tanpa spasi di awal/akhir.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::lower(trim($value)),
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Cabut semua sesi login user ini di semua perangkat.
     *
     * Menghapus baris tabel sesi milik user dan mengganti remember_token agar
     * cookie "ingat saya" lama tidak berlaku. Hanya efektif bila SESSION_DRIVER=database.
     *
     * Dipakai setelah reset kata sandi, dan wajib dipanggil oleh alur suspend PBI-19 (FR-004).
     * Alasan penangguhan, last-admin guard, dan audit (FR-021) tetap tanggung jawab PBI-19.
     */
    public function revokeAllSessions(): void
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->getKey())
            ->delete();

        $this->forceFill(['remember_token' => Str::random(60)])->save();
    }
}
