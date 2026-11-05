# 15 — Panduan Instalasi

## 1. Batas dan keselamatan

Panduan disusun dari `composer.json`, `composer.lock`, `package.json`, dependency Vite terpasang, `vite.config.js`, `config`, migration, seeder, dan `phpunit.xml`. Perintah berikut adalah instruksi bagi pemasang; **migrate, seed, build, dan test tidak dijalankan sebagai bagian penulisan dokumentasi**. Gunakan lingkungan lokal/database kosong khusus pengembangan terlebih dahulu. Jangan menjalankan seeder demo pada database operasional.

Tidak ada nilai `.env`, password database, token, atau kredensial deployment yang disalin. Satu-satunya kredensial yang dicantumkan adalah akun demo yang didefinisikan eksplisit oleh `UmiSeeder`.

## 2. Kebutuhan

| Komponen | Kebutuhan berdasarkan kode |
|---|---|
| PHP | Constraint proyek `^8.3`; metadata lingkungan pemeriksaan menyatakan PHP 8.3 |
| Composer | Diperlukan untuk dependency terkunci; versi executable tidak diverifikasi |
| Laravel | 13.38.0 pada dependency terpasang; instal mengikuti `composer.lock` |
| MySQL | Mendukung JSON, generated virtual column, dan unique index atas kolom generated pada migration `leads`; versi server operasional belum diverifikasi |
| PHP extensions | Platform requirement Laravel/dependency; aplikasi membutuhkan PDO MySQL, `mbstring`, dan `zip`/`ZipArchive`; periksa dengan `composer check-platform-reqs` |
| Node.js | Package Vite 8.3.5 terpasang mendeklarasikan `^20.19.0 || >=22.12.0`; pilih versi yang juga memenuhi dependency lain pada lock file |
| npm | Diperlukan jika memasang dependency/build frontend; gunakan lock file proyek |
| Filesystem | `storage`, `bootstrap/cache`, dan direktori temporary sistem dapat ditulis user proses PHP |
| Web server | Development server Artisan untuk lokal; web root deployment harus `public` |

Gunakan MySQL untuk memverifikasi skema operasional; konfigurasi test SQLite memory tidak membuktikan perilaku MySQL identik. Jangan menganggap opsi driver default `sqlite` pada `config/database.php` merupakan konfigurasi deployment CRM.

## 3. Mendapatkan kode

URL repository dan direktori hasil clone harus diisi sesuai repository tim; placeholder di bawah bukan URL nyata.

```sh
git clone <URL_REPOSITORY_TIM>
cd <DIREKTORI_HASIL_CLONE_YANG_BERISI_ARTISAN>
```

Jika repository memiliki subdirektori `laravel`, masuk ke subdirektori tersebut. Seluruh perintah lanjutan dijalankan dari direktori yang memuat `artisan`, `composer.json`, dan `package.json`.

TODO: confirm with team — tentukan URL repository resmi, struktur clone, serta versi Composer/npm dan MySQL yang disepakati untuk reproduksi instalasi.

## 4. Dependency backend

```sh
composer install
composer check-platform-reqs
```

Gunakan `install`, bukan `update`, agar versi mengikuti lock file. Script Composer dapat menjalankan package discovery; pastikan path dan izin filesystem benar. Jangan mengubah dependency aplikasi untuk sekadar mengatasi konfigurasi lokal.

## 5. Menyiapkan environment — nama variabel saja

Template `.env.example` tersedia. Buat `.env` lokal dari template tanpa menimpa `.env` yang sudah ada. Contoh perintah berikut hanya untuk instalasi baru setelah memastikan tujuan belum ada:

```sh
cp .env.example .env
```

Pada Windows tanpa perintah `cp`, gunakan fitur copy file editor/OS. **Jangan commit `.env` atau memasukkan nilainya ke laporan.** Tinjau template dan `config` secara lokal; dokumentasi ini hanya mencantumkan nama variabel, tidak nilai.

| Kelompok | Nama variabel | Petunjuk pengisian tanpa nilai |
|---|---|---|
| Identitas dan mode aplikasi | `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL` | Sesuaikan nama, mode lokal, debug, dan alamat instalasi; jangan aktifkan debug pada operasional |
| Kunci dan locale | `APP_KEY`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Kunci dihasilkan lewat Artisan; locale sesuai keputusan tim |
| Database MySQL | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Gunakan driver MySQL dan database/user khusus aplikasi; sebagian nama ini perlu ditambahkan sesuai `config/database.php` |
| Database opsional | `DB_URL`, `DB_SOCKET`, `DB_CHARSET`, `DB_COLLATION`, `MYSQL_ATTR_SSL_CA` | Isi hanya bila diperlukan konfigurasi koneksi; periksa konflik URL koneksi dengan field terpisah |
| Session | `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE` | Sesuaikan driver, cookie/domain, dan perlindungan transport; konfigurasi referensi ada di `config/session.php` |
| Cache / queue | `CACHE_STORE`, `QUEUE_CONNECTION` | Bila memakai database, jalankan migration tabel infrastrukturnya |
| Logging | `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL` | Tentukan keluaran log dan level sesuai environment |
| Storage / build | `FILESYSTEM_DISK`, `VITE_APP_NAME` | Ikuti kebutuhan lingkungan dan template; halaman CRM memuat CSS publik langsung |

Variabel mail, Redis, AWS, dan layanan lain ada pada konfigurasi/template, tetapi tidak ada alur bisnis inti yang terbukti mengharuskan integrasi tersebut. Jangan membuat/mengisi secret layanan yang tidak digunakan. Timezone pada `config/app.php` merupakan nilai konfigurasi kode, bukan variabel `APP_TIMEZONE` yang dideklarasikan; jangan menganggap nama variabel tersebut tersedia.

Setelah konfigurasi dasar siap:

```sh
php artisan key:generate --no-interaction
```

Lakukan hanya pada instalasi baru atau sesuai prosedur pengelolaan kunci. Mengganti kunci instalasi berjalan dapat memengaruhi data terenkripsi/session; jangan melakukannya sebagai perbaikan umum tanpa analisis.

## 6. Migration dan seeding lokal

Buat database kosong melalui administrator MySQL/perangkat database yang disetujui dan berikan hak yang dibutuhkan aplikasi. Pastikan konfigurasi menunjuk database yang benar sebelum melanjutkan.

```sh
php artisan migrate --no-interaction
```

Untuk **environment demo lokal saja**, setelah memahami risiko pada bagian berikut:

```sh
php artisan db:seed --no-interaction
```

Jangan gunakan `migrate:fresh`, `migrate:refresh`, atau penghapusan database untuk instalasi operasional yang memiliki data. Tidak ada kebutuhan membuat model/migration baru dalam panduan ini.

### Perilaku dan risiko seeder

`DatabaseSeeder` memanggil `UmiSeeder`:

- Sources: `Instagram`, `Facebook`, `Linktree`, `WhatsApp`.
- Categories: `Umroh`, `Haji Khusus`, `Haji Mujamalah`.
- Source/category menggunakan `firstOrCreate` berdasarkan nama; record nonaktif yang sudah ada tidak otomatis diaktifkan.
- Tujuh akun demo di bawah **selalu diproses**, tidak hanya pada environment lokal.
- Akun dengan email sama ditimpa nama, role, hash password demo, status aktif, flag tidak wajib ganti password, serta waktu verifikasi emailnya. **Reseeding dapat mengembalikan password akun demo yang sudah diganti menjadi password demo.**
- Hanya generator lead contoh yang memiliki guard `app()->isLocal()` dan tabel `leads` benar-benar kosong. Soft-deleted rows juga membuat tabel tidak kosong.
- Lead contoh mencakup hari ini sampai 34 hari sebelumnya, untuk lima CS dan tiga kategori dengan sumber terpilih; tidak semua kombinasi tanggal lampau dibuat. Insert batch tidak membuat audit eksplisit.
- Seeder tidak dibungkus transaksi menyeluruh; kegagalan parsial tidak otomatis diperbaiki dengan menjalankan ulang karena guard tabel kosong.

### Akun demo yang didefinisikan seeder

Password plaintext pada tabel ini **sengaja berasal dari literal seeder**, bukan data `.env` atau akun operasional. Tidak ada klaim kredensial database aktif masih sama.

| Nama | Email seeder | Role | Password seeder |
|---|---|---|---|
| Tanti | `tanti@contoh.com` | Customer Service | `password` |
| Asrofa | `asrofa@contoh.com` | Customer Service | `password` |
| Nuri | `nuri@contoh.com` | Customer Service | `password` |
| Fajrin | `fajrin@contoh.com` | Customer Service | `password` |
| Dadang | `dadang@contoh.com` | Customer Service | `password` |
| Manajemen | `manajemen@contoh.com` | Manajemen | `password` |
| Supervisor | `supervisor@contoh.com` | Supervisor | `password` |

Seluruh akun tersebut diaktifkan dan **tidak** diwajibkan mengganti password oleh seeder. Ini berbeda dengan pembuatan/reset akun melalui UI. Pisahkan demo dari penggunaan operasional dan jangan menjalankan ulang seeder pada data nyata.

## 7. Dependency dan build frontend

```sh
npm ci
npm run build
```

`package.json` memiliki script `build` dan `dev`; Vite memakai entry `resources/css/app.css` dan `resources/js/app.js`. Untuk pengembangan asset, jalankan di terminal terpisah:

```sh
npm run dev
```

Halaman CRM utama memuat **`public/css/umi.css` langsung** dan memakai script inline Blade, bukan entry Vite. Build tersedia untuk jalur asset proyek/starter dan tidak boleh dianggap otomatis mengubah CSS operasional tersebut. `welcome.blade.php` memakai Vite tetapi tidak menjadi route landing CRM. Font operasional diambil dari Google Fonts; font eksternal memerlukan jaringan browser, sedangkan form/rekap tetap merupakan aplikasi server-rendered.

## 8. Menjalankan secara lokal

Setelah konfigurasi, migration, dan seeding lokal opsional:

```sh
php artisan serve
```

Perintah ini berjalan terus sampai dihentikan; jalankan sendiri pada terminal pengembangan. Gunakan alamat yang benar-benar dicetak oleh development server, bukan mengasumsikan domain/port tertentu. Buka `/login`, gunakan akun demo jika telah di-seed, dan pilih role yang sesuai.

Script `composer run dev` juga dideklarasikan dan meneruskan ke `php artisan dev`; kebutuhan tooling serta proses turunannya mengikuti package terpasang. Server Artisan dan Vite bukan resep deployment production. Tidak ditemukan job bisnis yang mewajibkan worker untuk alur CRUD/dashboard/ekspor sinkron.

Untuk verifikasi route:

```sh
php artisan route:list --except-vendor -vv --no-interaction
```

Expected berdasarkan kode yang diperiksa: 21 route aplikasi. Health `/up` dikonfigurasi terpisah dalam `bootstrap/app.php`.

## 9. Pengujian setelah instalasi

Hanya pada environment test terpisah, periksa `phpunit.xml` sebelum menjalankan:

```sh
php artisan test --compact
```

Konfigurasi test memakai SQLite memory, session/cache array, dan queue sinkron. Pastikan extension SQLite serta kebutuhan XML/ZIP test tersedia bila menjalankan suite. Test tersebut bukan verifikasi kesetaraan penuh dengan MySQL; uji migration dan constraint pada MySQL lokal juga diperlukan. Hasil pengujian tidak diisi pada dokumentasi ini karena belum dijalankan untuk pekerjaan dokumentasi.

`composer run setup` tersedia, tetapi menggabungkan install, copy template bila diperlukan, key generation, migration, npm install/build dan tidak menjalankan seeder. Sebaiknya gunakan langkah terpisah di atas agar target database dan risiko setiap tahap jelas. Jangan menjalankannya pada instalasi berdata tanpa meninjau script.

## 10. Troubleshooting

| Gejala | Pemeriksaan dan tindakan |
|---|---|
| `vendor/autoload.php` tidak ditemukan | Jalankan `composer install` dari root aplikasi yang benar |
| Platform requirement gagal | Periksa PHP CLI yang digunakan, constraint lock file dan extension; jangan mengakali dengan mengabaikan requirement |
| `No application encryption key` | Pastikan `.env` lokal ada; pada instalasi baru jalankan key generation; jangan memublikasikan nilai APP_KEY |
| Koneksi database gagal | Periksa nama variabel database, layanan MySQL, user/izin dan target database; jangan menyalin password ke tiket publik |
| Tabel tidak ditemukan | Pastikan database yang dituju benar dan jalankan migration yang belum diterapkan; periksa driver session/cache yang dipilih |
| Migration generated column/index gagal | Periksa dukungan versi MySQL dan pesan migration; jangan mengganti migration diam-diam karena akan menyimpang dari kode yang didokumentasikan |
| Home CS menghasilkan 404 | Pastikan ada kategori layanan aktif; `HomeController` menampilkan Belum ada kategori layanan yang aktif. jika tidak ada |
| Login demo gagal | Seeder mungkin belum dijalankan, password telah berubah, role salah, atau akun nonaktif; jangan reseed data nyata sebagai perbaikan cepat |
| Selalu diarahkan ke Ganti Password | Akun baru/reset memiliki flag wajib ganti; selesaikan perubahan dengan password saat ini yang benar |
| CSS/UI tidak berubah setelah build | CRM memakai `public/css/umi.css`; periksa asset yang benar dan cache browser; jalur Vite diperbarui lewat build/dev |
| `Unable to locate file in Vite manifest` | Untuk view yang memakai Vite, pasang dependency lalu jalankan `npm run build` atau development Vite |
| Ekspor XLSX gagal | Periksa extension ZIP, izin temporary directory, ruang disk, error I/O dan log aplikasi; filter harus valid |
| 419 / session tidak bertahan | Muat ulang form; periksa cookie domain/path/secure, origin/HTTPS dan penyimpanan session; jangan menonaktifkan forgery protection |
| Konfigurasi lokal tampak belum berubah | Tinjau cache konfigurasi; operator dapat menjalankan `php artisan config:clear --no-interaction` pada environment yang benar |

TODO: confirm with team — verifikasi panduan pada mesin bersih dan dokumentasikan konfigurasi deployment, TLS, izin, backup, serta pengelolaan akun demo yang disetujui; belum ada hasil instalasi ulang atau deployment pada pekerjaan ini.
