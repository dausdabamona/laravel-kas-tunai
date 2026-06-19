# Kas Tunai — Poltek KP Sorong

Aplikasi pencatatan kas tunai (migrasi dari Google Apps Script ke Laravel) untuk
pengelolaan keuangan satker pemerintah: transaksi kas, nota, lampiran, perjalanan
dinas, pajak, dan pelaporan (BKU/LPJ).

## Stack

- PHP 8.2+ · Laravel 11 · MySQL (prod) / SQLite (dev)
- Livewire 3 + Alpine.js · Tailwind 3 · Vite 5
- Auth: Laravel Breeze (stack Livewire) + RBAC peran
- Test: Pest · Audit: spatie/laravel-activitylog
- Timezone: `Asia/Jayapura` (WIT) · self-host (LAN kantor)

## Peran (RBAC)

| Peran       | Kemampuan awal                              |
|-------------|---------------------------------------------|
| `operator`  | input transaksi, lihat laporan              |
| `bendahara` | input & verifikasi transaksi, lihat laporan |
| `ppk`       | setujui SPJ, kelola pengguna, lihat laporan |
| `pimpinan`  | lihat laporan                               |

Sumber kebenaran pemetaan peran → kemampuan: `app/Enums/Role.php`.
Gate didaftarkan otomatis di `app/Providers/AppServiceProvider.php`.

## Setup pengembangan

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # membuat akun contoh per peran (kata sandi: "password")
npm run dev                  # terminal terpisah
php artisan serve
```

Akun contoh hasil seeder:

| Email                | Peran     |
|----------------------|-----------|
| `operator@kas.test`  | operator  |
| `bendahara@kas.test` | bendahara |
| `ppk@kas.test`       | ppk       |
| `pimpinan@kas.test`  | pimpinan  |

## Pengujian

```bash
./vendor/bin/pest          # seluruh suite
./vendor/bin/pint          # format kode (PSR-12 + preset Laravel)
```

CI menjalankan Pest otomatis pada setiap push/PR — lihat `.github/workflows/tests.yml`.
