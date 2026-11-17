<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CircularActivityService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Data contoh pesanan lunas untuk demo Catatan Aktivitas Sirkular (PBI-20) di DB LOKAL.
 * Tidak didaftarkan di DatabaseSeeder. Jalankan manual:
 *
 *     php artisan db:seed --class=CircularActivityDemoSeeder
 *
 * - Ditolak bila DB aktif bukan DB lokal (DB online bersama tim tidak boleh diisi data demo).
 * - Aman dijalankan berulang kali (dilewati bila pesanan demo sudah ada).
 */
class CircularActivityDemoSeeder extends Seeder
{
    public const ORDER_PREFIX = 'ORD-DEMO-PBI20-';

    public const DEMO_EMAIL = 'warga.sirkular@sirkula.test';

    public const PARTNER_EMAIL = 'mitra.sirkular@sirkula.test';

    public function run(): void
    {
        if (! $this->usesLocalDatabase()) {
            throw new RuntimeException(
                'CircularActivityDemoSeeder hanya untuk DB lokal (127.0.0.1/localhost/sqlite). Jangan jalankan di DB online bersama tim.'
            );
        }

        if (Order::query()->where('order_number', 'like', self::ORDER_PREFIX.'%')->exists()) {
            $this->command?->warn('Data demo PBI-20 sudah ada, dilewati.');

            return;
        }

        DB::transaction(function () {
            $warga = User::query()->firstOrCreate(
                ['email' => self::DEMO_EMAIL],
                [
                    'name' => 'Warga Demo Sirkular',
                    'password' => Hash::make('password123'),
                    'role' => UserRole::Warga,
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ]
            );

            $mitra = User::query()->firstOrCreate(
                ['email' => self::PARTNER_EMAIL],
                [
                    'name' => 'Bank Sampah & Pengrajin Mitra',
                    'password' => Hash::make('password123'),
                    'role' => UserRole::Warga,
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ]
            );

            $nowWib = CarbonImmutable::now(CircularActivityService::TIMEZONE);

            $scenarios = [
                [
                    'code' => '001',
                    'months_ago' => 5,
                    'role' => 'seller',
                    'items' => [
                        ['title' => 'Karung Botol PET Terpilah', 'type' => ListingType::Recyclable, 'price' => 20_000, 'qty' => 2, 'weight' => 5.00],
                    ],
                ],
                [
                    'code' => '002',
                    'months_ago' => 4,
                    'role' => 'buyer',
                    'items' => [
                        ['title' => 'Kursi Kerja Preloved', 'type' => ListingType::Preloved, 'price' => 150_000, 'qty' => 1, 'weight' => 6.50],
                        ['title' => 'Novel Koleksi Lama', 'type' => ListingType::Preloved, 'price' => 30_000, 'qty' => 2, 'weight' => null],
                    ],
                ],
                [
                    'code' => '003',
                    'months_ago' => 3,
                    'role' => 'seller',
                    'items' => [
                        ['title' => 'Lot Kardus Lipat Bersih', 'type' => ListingType::Recyclable, 'price' => 15_000, 'qty' => 3, 'weight' => 4.00],
                    ],
                ],
                [
                    'code' => '004',
                    'months_ago' => 2,
                    'role' => 'buyer',
                    'items' => [
                        ['title' => 'Tas Tote Anyaman Plastik Upcycle', 'type' => ListingType::Upcycle, 'price' => 65_000, 'qty' => 1, 'weight' => 0.45],
                    ],
                ],
                [
                    'code' => '005',
                    'months_ago' => 1,
                    'role' => 'seller',
                    'items' => [
                        ['title' => 'Kaleng Aluminium Bersih', 'type' => ListingType::Recyclable, 'price' => 48_000, 'qty' => 1, 'weight' => 4.00],
                        ['title' => 'Lampu Meja Belajar Bekas', 'type' => ListingType::Preloved, 'price' => 45_000, 'qty' => 1, 'weight' => null],
                    ],
                ],
                [
                    'code' => '006',
                    'months_ago' => 0,
                    'role' => 'buyer',
                    'items' => [
                        ['title' => 'Pot Tanaman Komposit Daur Ulang', 'type' => ListingType::Upcycle, 'price' => 40_000, 'qty' => 2, 'weight' => 1.20],
                    ],
                ],
            ];

            foreach ($scenarios as $scenario) {
                $paidAt = $nowWib->subMonths($scenario['months_ago'])->startOfMonth()->addDays(5)->setTime(10, 30)->utc();
                $total = collect($scenario['items'])->sum(fn (array $item) => $item['price'] * $item['qty']);

                $order = Order::query()->create([
                    'buyer_id' => $scenario['role'] === 'buyer' ? $warga->id : $mitra->id,
                    'seller_id' => $scenario['role'] === 'seller' ? $warga->id : $mitra->id,
                    'order_number' => self::ORDER_PREFIX.$scenario['code'],
                    'checkout_key' => 'demo-pbi20-'.$scenario['code'],
                    'status' => OrderStatus::Paid,
                    'total' => $total,
                    'expires_at' => $paidAt->addDay(),
                    'paid_at' => $paidAt,
                ]);

                foreach ($scenario['items'] as $item) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'listing_id' => null,
                        'title_snapshot' => $item['title'],
                        'type_snapshot' => $item['type'],
                        'unit_price' => $item['price'],
                        'weight_per_unit_snapshot' => $item['weight'],
                        'quantity' => $item['qty'],
                        'subtotal' => $item['price'] * $item['qty'],
                    ]);
                }
            }

            // Satu pesanan belum lunas (untuk membuktikan tidak ikut dihitung di halaman aktivitas sirkular)
            $unpaid = Order::query()->create([
                'buyer_id' => $warga->id,
                'seller_id' => $mitra->id,
                'order_number' => self::ORDER_PREFIX.'PENDING',
                'checkout_key' => 'demo-pbi20-pending',
                'status' => OrderStatus::PendingPayment,
                'total' => 100_000,
                'expires_at' => $nowWib->addDay()->utc(),
                'paid_at' => null,
            ]);

            OrderItem::query()->create([
                'order_id' => $unpaid->id,
                'listing_id' => null,
                'title_snapshot' => 'Pesanan Belum Dibayar (Tidak Dihitung)',
                'type_snapshot' => ListingType::Recyclable,
                'unit_price' => 100_000,
                'weight_per_unit_snapshot' => 25.00,
                'quantity' => 1,
                'subtotal' => 100_000,
            ]);
        });
    }

    /**
     * SQLite, atau MySQL/MariaDB dengan host lokal.
     */
    private function usesLocalDatabase(): bool
    {
        $connection = config('database.connections.'.config('database.default'));

        if (($connection['driver'] ?? null) === 'sqlite') {
            return true;
        }

        return in_array((string) ($connection['host'] ?? ''), ['127.0.0.1', 'localhost', '::1'], true);
    }
}
