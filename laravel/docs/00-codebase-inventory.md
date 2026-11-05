# 00 — Inventaris Codebase

## Dasar dan batas pemeriksaan

Dokumentasi ini merupakan rekayasa balik statis terhadap kode yang diperiksa pada 4 November 2026. Semua path sumber dihitung dari root proyek Laravel, yaitu direktori yang berisi `artisan`. Pernyataan implementasi tidak menyatakan bahwa pengujian manual, UAT, atau deployment telah berhasil. `.env` dan data kredensial runtime tidak dibaca. Metode Prototyping dan presentasi prototype v1/v2 merupakan konteks yang disampaikan pemilik proyek, bukan hasil yang dapat dibuktikan dari kode.

Hasil `php artisan route:list --except-vendor -vv --no-interaction`: **21 route aplikasi**. `bootstrap/app.php` juga mengonfigurasi health endpoint `/up`. Direktori `.ai/rules` tidak tersedia. Tidak ditemukan `FormRequest` atau service layer domain terpisah; validasi berada dalam controller. Seluruh dokumentasi lanjutan menggunakan inventaris ini sebagai dasar.

## Versi dan dependencies

| Komponen | Versi teramati | Bukti |
|---|---|---|
| PHP | 8.3 menurut metadata aplikasi; constraint `^8.3` | Metadata aplikasi; `composer.json` |
| Laravel | 13.38.0; constraint `^13.17` | Metadata aplikasi; `composer show --direct` |
| Database | MySQL menurut metadata aplikasi | `config/database.php`; metadata aplikasi |
| Node.js / MySQL server | Versi runtime tidak diperiksa | TODO: confirm with team — tetapkan versi Node.js dan MySQL untuk lingkungan operasional |

Dependencies PHP langsung terpasang:

| Package | Versi | Peruntukan |
|---|---|---|
| `laravel/framework` | 13.38.0 | Framework aplikasi |
| `laravel/tinker` | 3.0.2 | Console interaktif |
| `fakerphp/faker` | 1.24.1 | Data pengembangan/pengujian |
| `laravel/boost` | 2.10.3 | Perangkat bantuan pengembangan |
| `laravel/pail` | 1.2.7 | Pembacaan log pengembangan |
| `laravel/pao` | 1.1.5 | Keluaran pengujian untuk agent |
| `laravel/pint` | 1.32.1 | Formatter PHP |
| `mockery/mockery` | 1.6.15 | Test doubles |
| `nunomaduro/collision` | 8.9.5 | Penyajian error console |
| `phpunit/phpunit` | 12.5.40 | Pengujian otomatis |

Dependencies JavaScript langsung yang dideklarasikan `package.json`, dengan versi terpasang menurut metadata:

| Package | Constraint | Versi terpasang |
|---|---|---|
| `@tailwindcss/vite` | `^4.0.0` | 4.3.3 |
| `tailwindcss` | `^4.0.0` | 4.3.3 |
| `vite` | `^8.0.0` | 8.3.5 |
| `laravel-vite-plugin` | `^3.1` | 3.2.0 |
| `concurrently` | `^10.0.3` | 10.0.6 |
| `@laravel/multiplex` (optional) | `^0.4.1` | 0.4.5 |

Tabel ini mencakup seluruh dependencies langsung, bukan seluruh dependencies transitif. Resolusi transitif lengkap tersimpan di `composer.lock` dan `package-lock.json`; keberadaan package transitif tidak otomatis berarti fitur package tersebut digunakan aplikasi. Tidak ada package Excel khusus: XLSX dibentuk oleh `DashboardXlsxExport` menggunakan `ZipArchive` dan XML.

## Struktur utama

```text
app/
  Enums/Role.php
  Exports/DashboardXlsxExport.php
  Http/Controllers/         # 7 controller fitur dan base Controller
  Http/Middleware/         # pemeriksaan akun aktif dan pergantian password
  Models/                  # 5 model; Concerns/HasUmiAccount
  Policies/LeadPolicy.php
  Providers/AppServiceProvider.php
bootstrap/app.php
config/
database/{migrations,seeders,factories}/
public/css/umi.css          # stylesheet yang dipakai halaman CRM
resources/views/           # Blade, SVG chart, dan script inline
resources/{css,js}/         # entry Vite; bukan asset utama halaman CRM
routes/{web,console}.php
tests/{Feature,Unit}/
```

## Modul dan sumber

| Modul | Controller / komponen | Route / view utama |
|---|---|---|
| Autentikasi | `AuthController` | `/login`, `/logout`; `auth/login.blade.php` |
| Pengarahan awal | `HomeController` | `/` |
| Password | `PasswordController` | `/password/change`, `/password-resets/{user}` |
| Rekap lead | `LeadController`, `LeadPolicy` | `/leads/{category}`; `leads/index.blade.php` |
| Analitik dan ekspor | `DashboardController`, `DashboardXlsxExport` | `/dashboard`, `/dashboard/export`; `dashboard.blade.php` |
| Master data | `MasterDataController` | `/master-data`; `master-data.blade.php` |
| Riwayat perubahan | `AuditLogController`, `AuditLog` | `/log-perubahan`; `audit-logs.blade.php` |

**Makna lead:** satu baris merupakan rekap jumlah `cold`, `warm`, `potential`, `closing`, dan `cancel` untuk kombinasi tanggal, staf CS, sumber, dan kategori. Tidak ada model pelanggan individual, transaksi perjalanan, pembayaran, pemesanan, atau komunikasi otomatis. Kelima jumlah bukan state eksklusif satu pelanggan.

## Aktor dan role

| Aktor | Nilai role | Ringkasan akses teramati |
|---|---|---|
| Pengunjung | Tidak ada | Login |
| Customer Service | `customer_service` | Membaca rekap kategori seluruh staf; input sendiri; mengubah/menghapus milik sendiri selama belum terkunci; dashboard pribadi |
| Manajemen | `manajemen` | Rekap lintas staf; input untuk CS; perubahan tanpa batas usia entri; analitik lintas staf |
| Supervisor | `supervisor` | Akses Manajemen ditambah master data, reset password akun lain, dan audit log |

Sumber role: `app/Enums/Role.php`. Detail batas akses: [04-roles-and-permissions.md](04-roles-and-permissions.md). Tombol ekspor disembunyikan dari CS, tetapi endpoint tetap mengizinkan ekspor milik sendiri.

## Model dan tabel

| Model | Tabel | Catatan |
|---|---|---|
| `User` | `users` | Autentikasi; trait `HasUmiAccount`; role dan status |
| `Source` | `sources` | Sumber lead; `is_active` |
| `Category` | `categories` | Kategori layanan; `is_active` |
| `Lead` | `leads` | `SoftDeletes`; aturan kepemilikan dan kunci 30 hari |
| `AuditLog` | `audit_logs` | Pelaku, subject polymorphic, snapshot perubahan |
| Tidak ada model proyek | `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Tabel infrastruktur bawaan |

Sembilan file migration mendefinisikan **12 tabel** di atas. Dua migration merupakan penambahan kolom pada tabel yang sudah ada. Tabel repository `migrations` dikelola framework, bukan `Schema::create()` migration proyek; dikecualikan dari cakupan ERD 12 tabel. Daftar kolom dan constraint: [06-erd-and-data-dictionary.md](06-erd-and-data-dictionary.md).

## Data awal dan bukti pengujian

`DatabaseSeeder` memanggil `UmiSeeder`: empat sumber (`Instagram`, `Facebook`, `Linktree`, `WhatsApp`), tiga kategori (`Umroh`, `Haji Khusus`, `Haji Mujamalah`), lima akun CS, satu Manajemen, satu Supervisor. Akun demo dan risiko reseeding dijelaskan dalam [15-installation-guide.md](15-installation-guide.md). Lead contoh hanya dihasilkan ketika lingkungan `local` dan tabel `leads` benar-benar kosong; akun demo tidak memiliki guard lokal tersebut.

Terdapat 21 method test: 20 Feature dan satu Unit placeholder. Cakupan statis meliputi dashboard/ekspor, master data, password, audit, serta redirect pengunjung. Belum ditemukan test khusus CRUD lead, duplikat, konfirmasi angka tinggi, dan matriks kunci 30 hari. Test tidak dijalankan untuk pekerjaan dokumentasi ini demi menghindari perubahan file/database di luar `docs/`.

## Ketidakpastian awal

- TODO: confirm with team — validasi ruang lingkup bisnis, persetujuan prioritas, dan kesesuaian hasil rekayasa balik dengan kebutuhan mitra.
- TODO: confirm with team — sediakan bukti evaluasi, tanggal, peserta, dan revisi prototype v1/v2; kode tidak membuktikan riwayat presentasi.
