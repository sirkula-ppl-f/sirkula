# Panduan Setup Sirkula untuk Anggota Tim

Panduan ini buat kamu yang baru mau mulai ngoding Sirkula. Ikuti urut dari atas ke bawah. Kalau semua langkah beres, kamu bisa langsung jalanin aplikasi di laptop dan bikin PR pertama.

> Semua perintah di sini ditulis untuk **PowerShell di Windows**. Ganti `sirkula-ppl-f` kalau nama organization GitHub kita berbeda.

---

## Daftar Isi

1. [Gambaran stack](#1-gambaran-stack)
2. [Install tools](#2-install-tools)
3. [Terima undangan GitHub](#3-terima-undangan-github)
4. [Setting Git (sekali saja)](#4-setting-git-sekali-saja)
5. [Clone dan jalankan project](#5-clone-dan-jalankan-project)
6. [Setting VS Code](#6-setting-vs-code)
7. [Alur kerja harian](#7-alur-kerja-harian)
8. [Testing](#8-testing)
9. [Aturan tim](#9-aturan-tim)
10. [Troubleshooting](#10-troubleshooting)
11. [Cheatsheet perintah](#11-cheatsheet-perintah)
12. [Deploy (demo/UAT/production)](#12-deploy-demouatproduction)

---

## 1. Gambaran stack

| Bagian | Teknologi |
|---|---|
| Bahasa | PHP 8.3+ |
| Framework | Laravel 13 |
| Tampilan | Blade + Tailwind CSS (lewat Vite), JavaScript murni (tanpa Alpine/React/Vue) |
| Database | MySQL 8 |
| Auth | Laravel Fortify |
| Pembayaran | Midtrans (mode sandbox) |
| Testing | PHPUnit + Laravel Dusk |
| Linter | Laravel Pint |

---

## 2. Install tools

Cek dulu apa yang sudah ada. Buka **PowerShell** dan jalankan:

```powershell
php -v
composer -V
node -v
git --version
mysql --version
```

Yang dibutuhkan:

| Tool | Versi minimal | Catatan |
|---|---|---|
| PHP | **8.3** | PHP bawaan XAMPP biasanya 8.2, **tidak cukup** |
| Composer | 2.x | |
| Node.js | 20 atau 22 LTS | |
| Git | terbaru | |
| MySQL | 8.x | MySQL bawaan XAMPP itu MariaDB, bukan MySQL 8 |
| VS Code | terbaru | |
| Google Chrome | terbaru | dipakai Laravel Dusk |

Pilih **salah satu** opsi di bawah untuk PHP dan MySQL.

### Opsi A — Laragon (disarankan)

Satu paket langsung dapat PHP, MySQL 8, dan Composer.

1. Kalau punya XAMPP, buka XAMPP Control Panel → **Stop** Apache dan MySQL → tutup.
2. Download Laragon dari <https://laragon.org/download> → install di lokasi default (`C:\laragon`).
3. Saat muncul popup lisensi, **tutup saja**. Laragon boleh dipakai gratis untuk keperluan non-komersial (tugas kuliah termasuk).
4. Klik **Start All**.
5. Klik kanan ikon Laragon → **Tools → Path → Add Laragon to Path**.
6. Hapus PHP XAMPP dari PATH supaya tidak bentrok (lihat [cara edit PATH](#cara-edit-path)): hapus baris `C:\xampp\php` dan `C:\xampp\mysql\bin` kalau ada.
7. Tutup semua terminal, buka PowerShell baru, cek ulang:
   ```powershell
   where.exe php
   php -v
   mysql --version
   ```
   `where.exe php` harus menunjuk ke `C:\laragon\...` dan versi PHP minimal 8.3.

MySQL Laragon defaultnya user `root` tanpa password.

### Opsi B — Upgrade PHP saja (kalau mau tetap pakai XAMPP dulu)

Pakai ini kalau belum sempat pindah ke Laragon. Database lokal pakai **SQLite** sementara.

1. Download PHP dari <https://windows.php.net/download> → pilih **PHP 8.4 → VS17 x64 Thread Safe → Zip**.
2. Buat folder `C:\php` (langsung di drive C, **bukan** di dalam `C:\xampp`).
3. Extract isi zip ke `C:\php` sampai file `C:\php\php.exe` ada. Kalau hasil extract malah jadi folder di dalam folder, pindahkan isinya ke `C:\php`.
4. Aktifkan ekstensi yang dibutuhkan. Buka PowerShell, salin semua sekaligus:
   ```powershell
   cd C:\php
   Copy-Item php.ini-development php.ini
   $exts = 'curl','fileinfo','gd','intl','mbstring','openssl','pdo_mysql','pdo_sqlite','sqlite3','zip'
   $ini = Get-Content php.ini
   $ini = $ini -replace '^;\s*extension_dir = "ext"', 'extension_dir = "ext"'
   foreach ($e in $exts) { $ini = $ini -replace "^;extension=$e$", "extension=$e" }
   Set-Content php.ini $ini
   ```
5. Edit PATH (lihat [cara edit PATH](#cara-edit-path)): ganti `C:\xampp\php` jadi `C:\php`. Kalau tidak ada, klik **New**, tulis `C:\php`, lalu **Move Up** sampai paling atas.
6. Kalau belum punya Composer, install dari <https://getcomposer.org/Composer-Setup.exe>. Saat ditanya lokasi PHP, pilih `C:\php\php.exe`.
7. Tutup semua terminal, buka PowerShell baru, cek:
   ```powershell
   where.exe php
   php -v
   composer -V
   php -m | findstr /i "zip intl pdo_mysql pdo_sqlite"
   ```
   Harus menunjuk ke `C:\php\php.exe`, versi 8.4, dan keempat ekstensi muncul.

XAMPP tetap jalan normal karena Apache-nya pakai PHP di foldernya sendiri.

### Tools lainnya

- **Node.js 22 LTS**: <https://nodejs.org>
- **Git**: <https://git-scm.com/download/win> (biarkan opsi default saat install)
- **VS Code**: <https://code.visualstudio.com>

### Cara edit PATH

1. Tekan tombol Windows, ketik **environment variables** → buka *Edit the system environment variables*.
2. Klik **Environment Variables...**
3. Di kotak **System variables** (bawah), pilih **Path** → **Edit...**
4. Ubah atau hapus baris yang diperlukan → **OK**.
5. Ulangi untuk **Path** di kotak **User variables** (atas).
6. **OK** di semua jendela, lalu **tutup dan buka ulang** semua terminal dan VS Code.

---

## 3. Terima undangan GitHub

1. Cek email (atau notifikasi di github.com) untuk undangan dari organization **sirkula-ppl-f**.
2. Klik **Join**.
3. Pastikan kamu bisa membuka repo `https://github.com/sirkula-ppl-f/sirkula`.

---

## 4. Setting Git (sekali saja)

Pakai email yang **sama dengan akun GitHub** supaya commit kamu tercatat atas nama kamu.

```powershell
git config --global user.name "Nama Lengkap Kamu"
git config --global user.email "email-github-kamu@gmail.com"
git config --global init.defaultBranch main
git config --global pull.rebase false
```

Cek hasilnya:

```powershell
git config --global --list
```

---

## 5. Clone dan jalankan project

### 5a. Clone

Pilih folder kerja kamu. Kalau path-nya ada spasi, **wajib pakai tanda kutip**.

```powershell
cd "C:\folder\kerja\kamu"
git clone https://github.com/sirkula-ppl-f/sirkula.git
cd sirkula
code .
```

Saat clone pertama kali, browser akan terbuka untuk login GitHub. Login, lalu kembali ke terminal.

Mulai dari sini, jalankan semua perintah dari **terminal VS Code** (tekan `` Ctrl + ` ``).

### 5b. Install dependensi

```powershell
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 5c. Siapkan database

**Kalau pakai Opsi A (Laragon / MySQL 8):**

```powershell
mysql -u root -e "CREATE DATABASE sirkula CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
```

Atau buat database `sirkula` lewat phpMyAdmin / HeidiSQL di Laragon, lalu jalankan `php artisan migrate`.

**Kalau pakai Opsi B (SQLite sementara):**

1. Buka file `.env`, cari blok `DB_...`, ganti **semuanya** jadi satu baris:
   ```env
   DB_CONNECTION=sqlite
   ```
2. Buat file database kosong, lalu migrate:
   ```powershell
   New-Item database\database.sqlite
   php artisan migrate
   ```

Nanti kalau sudah pindah ke MySQL, kembalikan blok DB di `.env` seperti di `.env.example`, buat database, lalu `php artisan migrate`.

### 5d. Isi key Midtrans (kalau mengerjakan fitur pembayaran)

Minta key sandbox ke PIC pembayaran, lalu isi **di `.env` saja**:

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
MIDTRANS_IS_PRODUCTION=false
```

> Jangan pernah menaruh key di `.env.example` atau di kode.

### 5e. Jalankan aplikasi

Buka **dua terminal** di VS Code (klik ikon **+** di panel terminal).

Terminal 1:
```powershell
php artisan serve
```

Terminal 2:
```powershell
npm run dev
```

Buka <http://127.0.0.1:8000>. Kalau halamannya muncul, setup kamu sudah beres. 🎉

Stop server dengan `Ctrl + C` di masing-masing terminal.

> Jangan pakai `composer run dev` di Windows. Salah satu prosesnya (`pail`) butuh ekstensi yang tidak tersedia di Windows dan akan error.

### 5f. Email lokal (Mailpit)

Email aplikasi (misalnya tautan reset kata sandi) dikirim ke **Mailpit**, bukan ke email sungguhan. `.env.example` sudah diatur ke SMTP `127.0.0.1:1025`.

1. Jalankan Mailpit (`mailpit.exe`, unduh dari <https://github.com/axllent/mailpit/releases> kalau belum punya).
2. Buka kotak masuknya di <http://127.0.0.1:8025>.

> Pakai `127.0.0.1`, bukan `localhost`. Di sebagian laptop `localhost` mengarah ke IPv6 (`::1`) dan halaman Mailpit tidak terbuka.

---

## 6. Setting VS Code

### 6a. Install extension yang direkomendasikan

Repo ini sudah menyimpan daftar extension di `.vscode/extensions.json`. Saat membuka project, VS Code akan menawarkan **"Do you want to install the recommended extensions?"** di pojok kanan bawah. Klik **Install**.

Kalau notifikasinya tidak muncul:

1. Buka tab Extensions (`Ctrl + Shift + X`).
2. Ketik `@recommended` di kolom pencarian.
3. Install semua yang ada di bagian **Workspace Recommendations**.

Isi daftarnya:

| Extension | Fungsinya |
|---|---|
| Laravel (resmi) | Autocomplete route, view, config, dan syntax Blade |
| PHP Intelephense | Autocomplete dan pengecekan error PHP |
| Laravel Pint | Merapikan kode PHP otomatis saat save |
| Tailwind CSS IntelliSense | Autocomplete class Tailwind di Blade |
| EditorConfig | Menyamakan indentasi dan akhir baris antar anggota |
| DotENV | Highlight file `.env` |
| GitLens | Melihat siapa yang mengubah baris kode dan kapan |

### 6b. Setting yang sudah otomatis aktif

File `.vscode/settings.json` di repo sudah mengatur:

- File PHP **dirapikan otomatis oleh Pint setiap save**, jadi CI tidak gagal karena format.
- File `*.blade.php` dikenali sebagai Blade.
- Autocomplete Tailwind aktif di file Blade.
- Akhir baris pakai format Unix (`LF`), supaya tidak ada perubahan "palsu" di Git.

Kamu tidak perlu mengubah apa-apa. Kalau ingin setting pribadi (tema, font, dll.), atur di **User Settings**, jangan ubah `.vscode/settings.json` di repo.

### 6c. Tips biar ngoding lebih enak

- **Matikan validasi PHP bawaan VS Code** kalau muncul error dobel: sudah diatur di `settings.json` (`php.validate.enable: false`).
- **Shortcut berguna:**
  - `Ctrl + P` → buka file dengan cepat (ketik nama file)
  - `Ctrl + Shift + F` → cari teks di seluruh project
  - `F12` → loncat ke definisi class/fungsi
  - `Ctrl + Shift + G` → panel Git (lihat perubahan, stage, commit)
- **Source Control di VS Code** bisa dipakai untuk commit tanpa terminal. Tapi tetap pastikan kamu sedang berada di branch yang benar (lihat pojok kiri bawah VS Code).

---

## 7. Alur kerja harian

Repo punya dua branch utama:

| Branch | Isi |
|---|---|
| `main` | Versi stabil untuk demo/pengumpulan. Hanya diisi dari `develop` di akhir sprint |
| `develop` | Tempat semua fitur digabung. **Default branch** |

Tidak ada yang boleh push langsung ke `main` atau `develop`. Semua perubahan lewat **Pull Request (PR)**.

### Langkah mengerjakan satu PBI

**1. Update `develop` di laptop kamu**

```powershell
git checkout develop
git pull
```

**2. Buat branch baru untuk PBI kamu**

```powershell
git checkout -b feature/PBI-05-listing-barang
```

Format nama branch:
- `feature/PBI-XX-nama-singkat` → fitur baru
- `fix/PBI-XX-nama-singkat` → perbaikan bug
- `test/PBI-XX-nama-singkat` → menambah tes saja

**3. Kalau ada perubahan dependensi atau migration dari teman**, jalankan setelah `git pull`:

```powershell
composer install
npm install
php artisan migrate
```

**4. Ngoding, lalu commit kecil-kecil**

```powershell
git add .
git commit -m "feat: tambah form listing barang"
```

Format pesan commit:

| Awalan | Dipakai untuk |
|---|---|
| `feat:` | fitur baru |
| `fix:` | perbaikan bug |
| `test:` | menambah/mengubah tes |
| `refactor:` | merapikan kode tanpa mengubah perilaku |
| `docs:` | dokumentasi |
| `chore:` | konfigurasi, dependensi, hal teknis lain |

**5. Sebelum push, cek dulu**

```powershell
.\vendor\bin\pint
php artisan test
```

**6. Push branch kamu**

```powershell
git push -u origin feature/PBI-05-listing-barang
```

**7. Buka Pull Request**

1. Buka repo di GitHub, akan muncul tombol **Compare & pull request**. Klik.
2. Pastikan arahnya: `base: develop` ← `compare: feature/PBI-05-...`
3. Isi template PR (PBI terkait, perubahan, cara uji, checklist).
4. Di kolom **Reviewers**, pilih minimal 1 teman.
5. **Create pull request**.

**8. Tunggu CI hijau dan approval**, lalu klik **Merge pull request** → **Delete branch**.

**9. Kembali ke `develop` untuk PBI berikutnya**

```powershell
git checkout develop
git pull
```

### Kalau branch kamu ketinggalan dari `develop`

Misalnya teman sudah merge fitur lain saat kamu masih ngoding:

```powershell
git checkout develop
git pull
git checkout feature/PBI-05-listing-barang
git merge develop
```

Kalau muncul **conflict**, VS Code akan menandai file yang bentrok. Buka filenya, pilih **Accept Current**, **Accept Incoming**, atau **Accept Both** sesuai kebutuhan, simpan, lalu:

```powershell
git add .
git commit -m "chore: merge develop"
git push
```

Kalau ragu waktu menyelesaikan conflict, tanya dulu ke pemilik kode yang bentrok.

### Mereview PR teman

1. Buka tab **Pull requests** → pilih PR.
2. Tab **Files changed** → baca perubahan, beri komentar di baris tertentu kalau perlu.
3. Kalau mau coba langsung di laptop:
   ```powershell
   git fetch
   git checkout feature/PBI-XX-nama-branch-teman
   composer install
   php artisan migrate
   ```
4. **Review changes** → pilih **Approve** atau **Request changes**.

---

## 8. Testing

| Perintah | Fungsi |
|---|---|
| `.\vendor\bin\pint` | Merapikan format semua file PHP |
| `.\vendor\bin\pint --test` | Hanya mengecek format (yang dijalankan CI) |
| `php artisan test` | Menjalankan tes Unit dan Feature |
| `php artisan test --filter=NamaTes` | Menjalankan satu tes tertentu |
| `php artisan dusk` | Menjalankan tes browser (Laravel Dusk) |

### Laravel Dusk

Dusk membuka Chrome otomatis untuk menguji halaman seperti pengguna sungguhan. Dusk memakai database terpisah, `sirkula_dusk`, yang **dikosongkan setiap kali tes jalan**. Data di database `sirkula` kamu tidak tersentuh.

**Persiapan (sekali saja):**

1. Pastikan Google Chrome terpasang.
2. Unduh ChromeDriver yang cocok dengan versi Chrome kamu (ulangi setiap Chrome update):
   ```powershell
   php artisan dusk:chrome-driver --detect
   ```
3. Buat database khusus Dusk (atau lewat HeidiSQL/phpMyAdmin):
   ```powershell
   mysql -u root -e "CREATE DATABASE sirkula_dusk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
4. Buat `.env.dusk.local` dari contoh, lalu isi `APP_KEY` dengan nilai `APP_KEY` dari `.env` kamu. File ini tidak ikut ter-commit.
   ```powershell
   Copy-Item .env.dusk.example .env.dusk.local
   ```

**Menjalankan tes:**

1. Siapkan aset: biarkan `npm run dev` menyala, atau jalankan `npm run build` sekali.
2. Terminal 1, jalankan server **tanpa** `--no-reload`:
   ```powershell
   php artisan serve
   ```
3. Terminal 2:
   ```powershell
   php artisan config:clear
   php artisan dusk
   ```

Hal yang perlu diketahui:

- Selama `php artisan dusk` berjalan, Dusk **menukar `.env` sementara** dengan `.env.dusk.local`, lalu mengembalikannya. Server `php artisan serve` otomatis restart dan ikut memakai `sirkula_dusk`. Kalau server dijalankan dengan `--no-reload`, server tetap memakai database `sirkula`: tes gagal dan data kamu ikut terisi data tes.
- `php artisan config:clear` wajib. Config yang di-cache membuat `.env.dusk.local` diabaikan.
- **Pengaman:** `tests/DuskTestCase.php` membatalkan tes **sebelum** database dikosongkan bila nama database aktif tidak diakhiri `_dusk`. Pesan `Dusk dibatalkan: DB aktif '...' bukan DB Dusk` berarti `.env.dusk.local` belum ada atau isinya salah, atau tes dijalankan langsung lewat `phpunit`, bukan `php artisan dusk`.
- Kalau Dusk terhenti di tengah jalan (misalnya Ctrl + C) dan ada file `.env.backup`, kembalikan `.env` kamu:
  ```powershell
  Copy-Item .env.backup .env -Force
  Remove-Item .env.backup
  ```

Tes Dusk disimpan di folder `tests/Browser`. Setiap PBI wajib punya tes Dusk untuk kriteria penerimaannya.

### CI (GitHub Actions)

Setiap push dan PR ke `main`/`develop` otomatis menjalankan Pint dan `php artisan test` di GitHub. Hasilnya terlihat di PR (centang hijau ✅ atau silang merah ❌). PR **tidak bisa di-merge** kalau CI merah.

Kalau CI merah, klik **Details** di PR untuk lihat langkah mana yang gagal, perbaiki di laptop, commit, dan push lagi. CI akan jalan ulang otomatis.

---

## 9. Aturan tim

1. **Jangan commit `.env`.** File ini berisi konfigurasi dan key pribadi. Sudah ada di `.gitignore`, tapi tetap cek `git status` sebelum commit.
2. **Jangan push langsung ke `main` atau `develop`.** Selalu lewat PR.
3. **Satu PBI = satu branch = satu PR.** Jangan campur beberapa PBI dalam satu PR.
4. **Jangan edit migration yang sudah di-merge.** Kalau perlu mengubah tabel, buat migration baru:
   ```powershell
   php artisan make:migration add_kolom_x_to_nama_tabel_table
   ```
5. **Jangan tambah library frontend** (Alpine, React, Vue, jQuery, dll.). Ketentuan proyek: full Laravel. Interaksi kecil pakai JavaScript murni.
6. **Mau tambah paket Composer atau npm baru?** Diskusikan dulu di grup, karena semua anggota harus ikut install.
7. **Logika harga, stok, dan status pembayaran hanya di server.** Jangan hitung di JavaScript.
8. **Pull `develop` sebelum mulai ngoding** setiap hari supaya conflict tidak menumpuk.

---

## 10. Troubleshooting

**`php -v` masih menampilkan 8.2**
PATH masih menunjuk ke PHP XAMPP. Hapus `C:\xampp\php` dari PATH (User dan System), lalu tutup dan buka ulang terminal. Cek dengan `where.exe php`.

**`mysql` is not recognized**
MySQL belum ada di PATH. Kalau pakai Laragon, jalankan **Tools → Path → Add Laragon to Path**. Kalau belum punya MySQL, pakai Opsi B (SQLite) dulu.

**`composer install` error soal `ext-zip`, `ext-intl`, `ext-fileinfo`, dll.**
Ekstensi PHP belum aktif.
- Laragon: Menu → **PHP → Extensions** → centang ekstensi yang diminta.
- Opsi B: jalankan ulang script di langkah 4 Opsi B.

**`could not find driver`**
Ekstensi `pdo_mysql` atau `pdo_sqlite` belum aktif. Lihat poin di atas.

**`SQLSTATE[HY000] [1049] Unknown database 'sirkula'`**
Database belum dibuat. Jalankan perintah `CREATE DATABASE` di langkah 5c.

**`SQLSTATE[HY000] [2002] No connection could be made`**
MySQL belum menyala. Di Laragon klik **Start All**. Pastikan XAMPP MySQL dimatikan supaya tidak bentrok di port 3306.

**`No application encryption key has been specified`**
Jalankan `php artisan key:generate`.

**`Vite manifest not found`**
Jalankan `npm run dev` di terminal terpisah (atau `npm run build` sekali).

**Port 8000 sudah dipakai**
Jalankan di port lain: `php artisan serve --port=8001`, dan buka `http://127.0.0.1:8001`.

**`cd` ke folder gagal**
Path yang ada spasinya harus pakai tanda kutip, misalnya `cd "C:\KULIAH\PROJECT CODE"`.

**Push ditolak: `protected branch` atau `GH006`**
Kamu sedang mencoba push ke `main`/`develop`. Buat branch baru dulu: `git checkout -b feature/PBI-XX-nama`, lalu push branch itu.

**Push ditolak: `Permission denied` atau `403`**
Undangan organization belum diterima, atau kamu login dengan akun GitHub lain. Cek undangan di github.com, lalu coba push lagi.

**Semua file terlihat berubah padahal tidak diedit**
Biasanya karena akhir baris (CRLF/LF). Jalankan sekali:
```powershell
git config --global core.autocrlf input
```

**Dusk error soal versi ChromeDriver**
Jalankan `php artisan dusk:chrome-driver --detect`.

**`dusk:chrome-driver` gagal dengan `cURL error 60: SSL certificate`**
PHP kamu belum punya daftar sertifikat CA (sering terjadi di PHP hasil download manual, Opsi B). Jangan matikan verifikasi SSL. Pakai CA bundle bawaan Git untuk sekali jalan:
```powershell
php -d curl.cainfo="C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt" -d openssl.cafile="C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt" artisan dusk:chrome-driver --detect
```
Supaya permanen, isi `curl.cainfo` dan `openssl.cafile` di `php.ini` dengan path yang sama.

Masih buntu? Kirim screenshot error lengkap + perintah yang dijalankan ke grup.

---

## 11. Cheatsheet perintah

```powershell
# --- Mulai hari ---
git checkout develop
git pull
composer install
npm install
php artisan migrate

# --- Mulai PBI baru ---
git checkout -b feature/PBI-XX-nama-singkat

# --- Jalankan aplikasi (2 terminal) ---
php artisan serve
npm run dev

# --- Sebelum commit/push ---
.\vendor\bin\pint
php artisan test
git status

# --- Commit & push ---
git add .
git commit -m "feat: deskripsi singkat"
git push -u origin feature/PBI-XX-nama-singkat

# --- Generator Laravel yang sering dipakai ---
php artisan make:model NamaModel -mfc      # model + migration + factory + controller
php artisan make:controller NamaController
php artisan make:request StoreNamaRequest
php artisan make:policy NamaPolicy --model=NamaModel
php artisan make:test NamaTest
php artisan dusk:make NamaDuskTest

# --- Database ---
php artisan migrate                # jalankan migration baru
php artisan migrate:fresh --seed   # reset database + isi data dummy (hati-hati, data lokal hilang)

# --- Bersihkan cache kalau perubahan tidak muncul ---
php artisan optimize:clear
```

---

## 12. Deploy (demo/UAT/production)

Aturan ini **wajib** untuk server apa pun yang bisa diakses orang lain (demo, UAT, production).

Isi `.env` di server:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
```

`SESSION_SECURE_COOKIE=true` hanya jalan di HTTPS.

Perintah deploy:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force   # sekali saja; isi ADMIN_* di .env server dulu
php artisan config:cache
php artisan route:cache
```

**Kenapa wajib:** paket Laravel Dusk (khusus tes) mendaftarkan route `_dusk/login/{userId}` yang bisa dipakai untuk **login sebagai user mana pun, termasuk admin**, selama route itu aktif. Route ini baru hilang kalau `APP_ENV=production` dan paket dev tidak terpasang (`composer install --no-dev`). `APP_DEBUG=false` mencegah detail error dan isi konfigurasi tampil ke pengunjung.

Cek cepat di server setelah deploy:

```bash
php artisan route:list --path=_dusk
```

Hasilnya harus menyatakan tidak ada route yang cocok. Kalau route `_dusk/...` masih muncul, server **belum aman**: perbaiki `.env` dan ulangi `composer install --no-dev`.
