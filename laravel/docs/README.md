# Dokumentasi UMI CRM — UMI Tour Travel

Dokumentasi hasil rekayasa balik kode Laravel, disusun pada 4 November 2026. Root sumber adalah direktori yang berisi `artisan`; seluruh path sumber di dokumen dihitung dari root tersebut. Bahasa uraian adalah Bahasa Indonesia, sedangkan identifier kode/route/tabel dipertahankan.

## Indeks dokumen

| Dokumen | Isi ringkas |
|---|---|
| [00 — Codebase inventory](00-codebase-inventory.md) | Dasar bersama: versi, dependencies langsung, struktur, modul, role, model, tabel, data awal dan batas verifikasi |
| [01 — Functional requirements](01-functional-requirements.md) | 26 FR yang diturunkan dari perilaku kode, aktor, prioritas inferensi, serta sumber implementasi |
| [02 — Non-functional requirements](02-non-functional-requirements.md) | 28 NFR beserta bukti, status implementasi/verifikasi dan usulan target terukur |
| [03 — Use cases](03-use-cases.md) | Diagram global, 21 UC, traceability FR dan skenario utama/alternatif setiap UC |
| [04 — Roles and permissions](04-roles-and-permissions.md) | Matriks akses ketiga role dan enforcement middleware, policy, binding serta controller |
| [05 — Sitemap and routes](05-sitemap-and-routes.md) | Sitemap per role, seluruh 21 route aplikasi, alias middleware, dan health endpoint terpisah |
| [06 — ERD and data dictionary](06-erd-and-data-dictionary.md) | ERD 12 tabel migration, seluruh kolom, tipe, default, nullability, indeks dan delete behavior FK |
| [07 — System architecture](07-system-architecture.md) | MVC aktual, request lifecycle, autentikasi, stack, ekspor dan kebutuhan runtime |
| [08 — Activity diagrams](08-activity-diagrams.md) | Tiga alur prioritas: penyimpanan lead, analitik/ekspor, serta reset dan wajib ganti password |
| [09 — Sequence diagrams](09-sequence-diagrams.md) | Interaksi participant aktual untuk tiga alur yang sama dengan activity diagram |
| [10 — Class diagram](10-class-diagram.md) | Lima model Eloquent, fillable efektif, relasi, method/scope, trait dan enum role |
| [11 — Black-box test cases](11-black-box-test-cases.md) | 66 rencana TC; setiap FR mempunyai kasus positif/negatif; hasil aktual/status kosong |
| [12 — UAT plan](12-uat-plan.md) | Rencana peserta/prosedur, 12 skenario UAT, lembar hasil/sign-off kosong, serta 10 item SUS dan rumus |
| [13 — Prototyping feedback log](13-prototyping-feedback-log.md) | Template evaluasi/revisi kosong dan petunjuk Git yang dinyatakan belum terverifikasi |
| [14 — User manual](14-user-manual.md) | Langkah pengguna per role berdasarkan label UI aktual dan placeholder screenshot |
| [15 — Installation guide](15-installation-guide.md) | Instalasi lokal, nama variabel environment, migration/seeder, build, akun demo dan troubleshooting |
| [README — Indeks ini](README.md) | Navigasi, hasil pemeriksaan konsistensi, batas bukti dan seluruh TODO gabungan |

## Cara membaca dan menggunakan

1. Mulai dari inventaris, kemudian FR/NFR dan matriks permission sebelum membaca diagram atau merencanakan pengujian.
2. `FR-xx`, `NFR-xx`, `UC-xx`, `TC-xx`, dan `UAT-xx` mengidentifikasi kebutuhan/skenario yang sama saat dirujuk lintas dokumen. `A-01–03` dan `SQ-01–03` berpasangan berdasarkan tiga alur bisnis yang sama.
3. Prioritas dan target NFR yang disebut usulan belum merupakan persetujuan mitra. `Implemented` berarti ada mekanisme kode, bukan bukti lulus pengujian atau jaminan deployment.
4. Isi hasil TC/UAT/SUS dan log prototype hanya setelah pengujian/evaluasi nyata. Jangan menggunakan dokumentasi statis sebagai pengganti bukti pelaksanaan.
5. Semua kode diagram berada dalam fenced block `mermaid`; gunakan renderer Mermaid untuk inspeksi visual. Tidak ada gambar/screenshot asli yang ditambahkan.

## Hasil pemeriksaan konsistensi dokumentasi

Pemeriksaan statis dilakukan tanpa menjalankan migration, seeder, suite test, build, formatter, atau server baru:

- Inventaris route awal menggunakan `php artisan route:list --except-vendor -vv --no-interaction`: 21 route aplikasi; definisi `routes/web.php` dibaca langsung.
- Seluruh **26 FR** memiliki pemetaan dalam tabel **21 UC**.
- Seluruh **26 FR** memiliki minimal satu kasus positif dan negatif dalam **66 TC**.
- Seluruh sel `Actual result` dan `Status` pada tabel TC kosong.
- Himpunan **12 tabel** dan kolom hasil konsolidasi migration cocok dengan ERD serta tabel kamus data. Tabel repository `migrations` milik framework sengaja berada di luar cakupan migration proyek.
- Nama seluruh 21 route pada tabel serta setiap URI sitemap dicocokkan dengan route sumber. Endpoint `/up` dijelaskan terpisah karena berasal dari `bootstrap/app.php`.
- Tautan Markdown relatif dan pasangan code fence diperiksa. Terdapat **15 blok Mermaid**; pemeriksaan struktur bukan klaim seluruh diagram telah dirender dan diperiksa visual.
- Jumlah method test sumber diperiksa: **21**; suite tidak dijalankan, sehingga tidak ada klaim lulus/gagal runtime.
- Audit isi mengoreksi rincian penting: home CS tanpa kategori aktif menghasilkan 404; intended destination dapat melewati home; daftar master data tidak dipaginasi; `casts()` pada model bersifat protected.

Seluruh file yang dibuat/diubah untuk dokumentasi berada di `docs/`. Kode aplikasi, migration, konfigurasi, dan test tidak diedit. Tidak ada `.env` aktual atau kredensial runtime yang disalin; daftar kredensial dalam panduan instalasi hanya berasal dari literal seeder demo. `.env.example` hanya diperiksa untuk keberadaan dan nama variabel, bukan untuk menyalin nilai.

## Perilaku penting yang tidak boleh disalahartikan

- Lead adalah **rekap jumlah harian**, bukan profil prospek/pelanggan individual.
- CS dapat membaca rekap lintas staf pada kategori, tetapi dashboard/ekspor dibatasi data sendiri.
- Tombol ekspor CS disembunyikan; endpoint ekspor langsung tetap mengizinkan data sendiri.
- Dashboard invalid-filter membuang semua filter dan memakai periode default; ekspor memakai fallback yang sama tanpa halaman pesan validasi.
- Hanya lead memakai soft delete. Status nonaktif master/akun tidak sama dengan penghapusan.
- Reset tidak mengaktifkan akun nonaktif dan tidak menyatakan seluruh session perangkat lain dicabut.
- Guard lokal seeder hanya untuk lead contoh; akun demo selalu diproses dan dapat direset ke password demo saat reseeding.
- Riwayat Git yang tersedia tidak membuktikan evaluasi/revisi prototype v1/v2. Metode/presentasi merupakan konteks dari pemilik proyek sampai bukti tim dilengkapi.

## Daftar TODO gabungan

Seluruh **15** item `TODO: confirm with team` pada dokumen 00–15 disalin di bawah. ID `TODO-xx` hanya membantu pelacakan penyelesaian; tidak menandakan item telah disetujui atau diselesaikan.

| ID | Sumber | Item yang memerlukan konfirmasi |
|---|---|---|
| TODO-01 | [00 — Versi](00-codebase-inventory.md) | TODO: confirm with team — tetapkan versi Node.js dan MySQL untuk lingkungan operasional |
| TODO-02 | [00 — Ruang lingkup](00-codebase-inventory.md) | TODO: confirm with team — validasi ruang lingkup bisnis, persetujuan prioritas, dan kesesuaian hasil rekayasa balik dengan kebutuhan mitra. |
| TODO-03 | [00 — Prototyping](00-codebase-inventory.md) | TODO: confirm with team — sediakan bukti evaluasi, tanggal, peserta, dan revisi prototype v1/v2; kode tidak membuktikan riwayat presentasi. |
| TODO-04 | [01 — Perilaku bisnis](01-functional-requirements.md) | TODO: confirm with team — putuskan apakah akses langsung ekspor CS, pembacaan rekap lintas staf oleh CS, dan fallback ekspor saat filter tidak valid merupakan perilaku bisnis yang disetujui. |
| TODO-05 | [02 — Keamanan](02-non-functional-requirements.md) | TODO: confirm with team — setujui target HTTPS/cookie, revokasi session lintas perangkat, dan kebijakan keamanan operasional. |
| TODO-06 | [02 — Target kualitas](02-non-functional-requirements.md) | TODO: confirm with team — setujui target usability/SUS, aksesibilitas, perangkat/browser, beban dan waktu respons; ukur sebelum mengubah status menjadi terverifikasi. |
| TODO-07 | [02 — Operasional](02-non-functional-requirements.md) | TODO: confirm with team — tetapkan backup, RPO/RTO, SLA monitoring, retensi audit, serta locale/timezone operasional. |
| TODO-08 | [06 — Skema](06-erd-and-data-dictionary.md) | TODO: confirm with team — verifikasi versi MySQL, hasil penerapan migration, dan kesesuaian skema database operasional; dokumen ini menyatakan definisi kode, bukan kondisi database aktif. |
| TODO-09 | [12 — Peserta UAT](12-uat-plan.md) | TODO: confirm with team — tetapkan peserta nyata tiap role, jadwal, lokasi/environment UAT, dan pihak yang berwenang memberikan sign-off. |
| TODO-10 | [12 — Penerimaan](12-uat-plan.md) | TODO: confirm with team — sepakati kriteria penerimaan, klasifikasi defect, retest, dan ambang SUS sebelum UAT. |
| TODO-11 | [12 — Instrumen SUS](12-uat-plan.md) | TODO: confirm with team — tetapkan sumber/adaptasi Bahasa Indonesia SUS yang digunakan dalam laporan akademik dan prosedur penanganan jawaban tidak lengkap; teks ini belum dinyatakan sebagai terjemahan tervalidasi tertentu. |
| TODO-12 | [13 — Bukti iterasi](13-prototyping-feedback-log.md) | TODO: confirm with team — lengkapi log dari bukti evaluasi v1/v2 dan, bila tersedia, riwayat Git yang benar-benar melacak implementasi CRM; jangan mengisi masukan berdasarkan dugaan dari kode. |
| TODO-13 | [14 — Panduan pengguna](14-user-manual.md) | TODO: confirm with team — lengkapi screenshot asli yang sudah disamarkan, alamat akses instalasi, dan saluran dukungan operasional; nilai tersebut tidak tersedia pada kode yang diperiksa. |
| TODO-14 | [15 — Reproduksi instalasi](15-installation-guide.md) | TODO: confirm with team — tentukan URL repository resmi, struktur clone, serta versi Composer/npm dan MySQL yang disepakati untuk reproduksi instalasi. |
| TODO-15 | [15 — Operasional instalasi](15-installation-guide.md) | TODO: confirm with team — verifikasi panduan pada mesin bersih dan dokumentasikan konfigurasi deployment, TLS, izin, backup, serta pengelolaan akun demo yang disetujui; belum ada hasil instalasi ulang atau deployment pada pekerjaan ini. |
