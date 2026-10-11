# Sirkula

Platform pasar barang bekas dan daur ulang berbasis komunitas kota–desa (PPL Kelompok F).

## Prasyarat
PHP 8.3+, Composer 2, Node 20/22, MySQL 8, Google Chrome (untuk Dusk).

## Setup lokal
```bash
git clone https://github.com/sirkula-ppl-f/sirkula.git
cd sirkula
composer install
npm install
cp .env.example .env
php artisan key:generate
mysql -u root -e "CREATE DATABASE sirkula CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
```

Jalankan aplikasi di dua terminal:
```bash
php artisan serve
npm run dev
```

## Alur kerja
1. `git checkout develop` lalu `git pull`
2. `git checkout -b feature/PBI-XX-nama-singkat`
3. Commit kecil-kecil dengan format `feat: ...`, `fix: ...`, `test: ...`, atau `chore: ...`
4. `git push -u origin feature/PBI-XX-nama-singkat`, lalu buka PR ke `develop`
5. Merge setelah CI hijau dan minimal 1 approval

## Testing
```bash
vendor/bin/pint        # rapikan kode
php artisan test       # unit & feature
php artisan dusk       # browser test (jalankan php artisan serve dulu)
```
