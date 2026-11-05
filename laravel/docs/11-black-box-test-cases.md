# 11 — Kasus Uji Black-box

## Petunjuk pelaksanaan

Kasus berikut adalah **rencana pengujian**, bukan laporan test yang telah dijalankan. Kolom **Actual result** dan **Status** sengaja kosong. Gunakan database pengujian terpisah dan salinan data yang dapat dibuang; jangan menjalankan mutasi terhadap data operasional. Identitas input di bawah merupakan data sintetis untuk test, bukan akun nyata atau kredensial runtime.

Konvensi: **H** = tanggal hari pengujian menurut timezone aplikasi; **CS-A/CS-B**, **M**, **S** = akun aktif masing-masing Customer Service, Manajemen, dan Supervisor; akun operasional tidak wajib ganti password kecuali disebutkan. **K1**, **S1** = kategori dan sumber aktif; **L1** = rekap CS-A pada H; **L-lama** = rekap pada H−31. Gunakan ID aktual fixture ketika mengganti `{category}`, `{lead}`, `{user}`. Jumlah default valid: `cold=3,warm=2,potential=1,closing=1,cancel=0`.

Kasus negatif berarti menguji masukan tidak valid/akses tidak diizinkan/kondisi tanpa hasil, bukan mengharuskan seluruh kasus mengembalikan error HTTP. Dashboard invalid-filter justru harus fallback sesuai kode. Untuk request yang diblokir validasi HTML, ulangi melalui HTTP client dengan session/CSRF yang valid agar validasi server ikut diperiksa. Kasus akses langsung adalah pengujian kontrak endpoint, bukan menu yang dibuat-buat.

## Tabel kasus uji

| TC ID | FR ID | Scenario | Preconditions | Test steps | Test data | Expected result | Actual result | Status |
|---|---|---|---|---|---|---|---|---|
| TC-01 | FR-01 | Positif: login sesuai akun | Guest; CS-A aktif | Buka login; isi tiga field; pilih Login | Email fixture CS-A; password fixture benar; role `customer_service` | Session terbentuk; menuju intended destination bila ada; jika tidak ada, home mengarah ke kategori aktif pertama | | |
| TC-02 | FR-01 | Negatif: password salah | Guest; CS-A ada | Kirim login dengan password salah | Password sintetis yang tidak cocok | Error `Email atau password salah.`; tetap guest | | |
| TC-03 | FR-02 | Positif: logout | CS-A login | Pilih Logout; akses ulang halaman terlindungi | POST `/logout` | Redirect login; halaman terlindungi tidak dapat dibuka tanpa login kembali | | |
| TC-04 | FR-02 | Negatif: guest mengirim logout | Guest | Kirim POST logout dengan session/token yang sah sebagai guest | POST `/logout` | Middleware auth mengarah ke login; tidak membuat session login | | |
| TC-05 | FR-03 | Positif: pengarahan home | K1 kategori aktif pertama | Login CS-A dan buka `/`; ulangi sebagai M | Role CS dan Manajemen | CS menuju K1; M menuju dashboard | | |
| TC-06 | FR-03 | Negatif: home CS tanpa kategori aktif | Fixture tanpa kategori aktif | Sebagai CS-A buka `/` | Tidak ada kategori aktif | 404 dengan pesan Belum ada kategori layanan yang aktif.; tidak mengarah ke kategori nonaktif/tidak ada | | |
| TC-07 | FR-04 | Positif: password berubah | CS-A login; tahu password saat ini | Isi password lama, baru dan konfirmasi; simpan | Password baru sintetis ≥8 karakter, berbeda; konfirmasi sama | Status sukses; menuju home; login baru memakai password baru berhasil | | |
| TC-08 | FR-04 | Negatif: password saat ini salah | CS-A login | Kirim perubahan dengan password lama salah | Password baru valid; current salah | Error password saat ini; password lama tetap berlaku | | |
| TC-09 | FR-05 | Positif: pembatasan dan pelepasan wajib ganti | CS-A `must_change_password=true` | Login; coba dashboard; selesaikan pergantian password | Password sementara dan baru valid | Sebelum ganti diarahkan password.edit; setelah berhasil dashboard dapat diakses | | |
| TC-10 | FR-05 | Negatif: memintas halaman wajib ganti | CS-A login dengan flag true | Request langsung GET lead dan POST lead | Payload lead valid | Keduanya dialihkan ke password.edit; tidak ada lead baru | | |
| TC-11 | FR-06 | Positif: daftar lintas staf dan total | K1 memiliki 51 rekap nonduplikat lintas staf | Buka K1; periksa total; pilih Berikutnya | 51 rekap dan jumlah fixture yang diketahui | Halaman pertama 50 baris; halaman berikut sisa; total mencakup 51; CS dapat membaca baris staf lain | | |
| TC-12 | FR-06 | Negatif: kategori nonaktif | K1 nonaktif | GET `/leads/{K1}` | ID kategori nonaktif | 404; halaman input kategori tidak tersedia | | |
| TC-13 | FR-07 | Positif: tambah rekap dan jumlah kosong | Kombinasi belum ada; CS-A login | Isi H dan S1; kosongkan warm; simpan | cold=3; warm kosong; lainnya ≤10 | Rekap tersimpan atas nama CS-A; warm menjadi 0; status Data disimpan. | | |
| TC-14 | FR-07 | Negatif: tanggal masa depan | Kombinasi belum ada | Kirim tambah lead pada H+1 | Tanggal H+1; jumlah valid | Error tanggal; tidak ada rekap baru | | |
| TC-15 | FR-08 | Positif: edit sendiri tepat batas umur | CS-A memiliki rekap H−30 | Pilih Edit; ubah cold; Perbarui Data | cold=4; tanggal tetap H−30 | Perubahan disimpan; batas tepat 30 hari tetap diizinkan | | |
| TC-16 | FR-08 | Negatif: edit lead staf lain | CS-A login; L1 milik CS-B | Kirim PUT langsung ke L1 | Payload valid; ID kategori cocok | 403; rekap tidak berubah | | |
| TC-17 | FR-09 | Positif: soft delete milik sendiri | CS-A memiliki L1 H | Pilih Hapus dan setujui confirm; periksa daftar/dashboard | L1 | Status Data dihapus.; baris hilang dari daftar/total normal; riwayat audit tetap dapat dibaca Supervisor | | |
| TC-18 | FR-09 | Negatif: hapus rekap terkunci | CS-A memiliki L-lama H−31 | Kirim DELETE langsung | ID L-lama | 403; rekap tetap ada dan tetap masuk hasil normal | | |
| TC-19 | FR-10 | Positif: kombinasi berbeda diterima | L1 kombinasi H/CS-A/S1/K1 ada; S2 aktif | Tambah kombinasi sama kecuali sumber | Sumber S2 | Rekap kedua diterima; tidak dianggap duplikat L1 | | |
| TC-20 | FR-10 | Negatif: kombinasi aktif identik | L1 ada dan bisa diedit CS-A | Tambah H/CS-A/S1/K1 lagi | Jumlah valid ≤10 | Tidak membuat baris baru; tampil peringatan duplikat dan tautan Edit entri itu | | |
| TC-21 | FR-11 | Positif: menyetujui angka tinggi | Kombinasi baru | Isi cold=11; Simpan Data; pilih Ya, simpan | cold=11; lainnya 0 | Muncul peringatan sebelum simpan; setelah konfirmasi tersimpan satu rekap | | |
| TC-22 | FR-11 | Negatif: tidak mengonfirmasi angka tinggi | Kombinasi baru | Isi cold=11; simpan; pilih Periksa lagi | cold=11 | Peringatan dapat ditutup; tanpa confirm_high tidak ada rekap baru | | |
| TC-23 | FR-12 | Positif: diagram pribadi | CS-A dan CS-B punya rekap periode default | Login CS-A; buka Diagram Saya | Jumlah milik A dan B berbeda | Total/diagram/rincian hanya milik A; heading Diagram Saya | | |
| TC-24 | FR-12 | Negatif: mencoba staf lain melalui query | CS-A login; CS-B punya data | GET dashboard dengan staff=CS-B | `staff={CS-B}` | Query tetap milik CS-A; data CS-B tidak masuk | | |
| TC-25 | FR-13 | Positif: analitik lintas staf | A dan B punya data pada periode | Login M; buka Dashboard Analitik | Fixture jumlah lintas staf diketahui | Total gabungan sesuai; performa staf dan closing sumber/kategori ditampilkan | | |
| TC-26 | FR-13 | Negatif: CS meminta varian lintas staf | CS-A login | GET `/dashboard` | Role CS-A | Hanya varian Diagram Saya; tidak memperoleh analitik lintas staf | | |
| TC-27 | FR-14 | Positif: filter valid tepat 31 hari | M login; data di dalam/luar batas | Pilih from=H−30,to=H dan filter staf/kategori/sumber | Rentang inklusif 31 hari | Hasil sesuai semua filter; query dipertahankan saat pagination | | |
| TC-28 | FR-14 | Negatif: rentang 32 hari | M login | GET dashboard from=H−31,to=H | Rentang inklusif 32 hari | HTTP 200 dengan pesan maksimal 31 hari; semua filter kembali ke default awal bulan–H | | |
| TC-29 | FR-15 | Positif: ekspor seluruh hasil filter | M login; >50 baris cocok | Terapkan filter; pilih Export ke Excel (.xlsx); buka file | Tanggal valid dan fixture >50 rekap | File XLSX nyata berisi seluruh hasil, bukan hanya 50; worksheet Laporan Leads; nama sesuai periode | | |
| TC-30 | FR-15 | Negatif: CS mencoba ekspor data CS lain | CS-A login; A/B punya data | GET langsung dashboard/export dengan staff=CS-B | Filter tanggal valid; staff B | Unduhan diizinkan tetapi hanya memuat CS-A; tidak membocorkan data B | | |
| TC-31 | FR-16 | Positif: membaca master | S login | Pilih Master Data | Daftar akun/sumber/kategori fixture | Ketiga bagian dan status ditampilkan | | |
| TC-32 | FR-16 | Negatif: Manajemen membuka master | M login | GET `/master-data` langsung | Role manajemen | 403 | | |
| TC-33 | FR-17 | Positif: akun baru dan normalisasi | S login; email belum ada | Isi Nama staf, Email, Peran; Tambah staf; buka ulang Master Data | Nama Uji; email `STAF.UJI@example.test`; role customer_service | Akun aktif; email lowercase; password sementara sekali tampil; login akun mewajibkan ganti | | |
| TC-34 | FR-17 | Negatif: email duplikat setelah normalisasi | Email `staf.uji@example.test` ada | Tambah akun dengan email uppercase yang sama | `STAF.UJI@example.test` | Error email sudah terdaftar; tidak membuat akun kedua | | |
| TC-35 | FR-18 | Positif: nonaktifkan lalu aktifkan akun lain | S login; CS-B aktif | Nonaktifkan B; request terlindungi sebagai B; aktifkan kembali | Target CS-B | Status berubah; B dikeluarkan ketika nonaktif; dapat login lagi setelah aktif | | |
| TC-36 | FR-18 | Negatif: nonaktifkan diri sendiri | S login | PATCH status ke ID S sendiri | Target diri sendiri | 404; S tetap aktif | | |
| TC-37 | FR-19 | Positif: reset akun lain sekali tampil | S login; CS-B ada | Reset password B; salin; buka ulang Master Data; login B memakai password baru | Target B; password dihasilkan sistem | Password 16 karakter tampil sekali; password lama gagal untuk login baru; B diarahkan wajib ganti | | |
| TC-38 | FR-19 | Negatif: non-Supervisor reset akun | M login; target B ada | POST reset langsung dengan token valid | Target B | 403; password B tidak berubah | | |
| TC-39 | FR-20 | Positif: tambah sumber unik | S login | Isi Nama sumber; Tambah | `Sumber Uji` belum ada | Sumber baru aktif dan tersedia pada input lead baru | | |
| TC-40 | FR-20 | Negatif: sumber duplikat | Sumber Uji ada | Tambah sumber nama sama | `Sumber Uji` | Error sumber sudah ada; jumlah record tidak bertambah | | |
| TC-41 | FR-21 | Positif: toggle sumber dan pertahankan riwayat | S1 dan S2 aktif; lead historis memakai S1 | Nonaktifkan S1; buka input baru/dashboard; aktifkan S1 kembali | Target S1 | S1 hilang dari pilihan baru saat nonaktif; riwayat/dashboard tetap ada; aktif kembali tersedia | | |
| TC-42 | FR-21 | Negatif: sumber aktif terakhir | Hanya S1 aktif | Nonaktifkan S1 | Target S1 | Error minimal satu sumber tetap aktif; S1 tidak berubah | | |
| TC-43 | FR-22 | Positif: tambah kategori | S login | Isi Nama kategori; Tambah | `Layanan Uji` unik | Kategori aktif tersimpan dan muncul di Input Data Leads | | |
| TC-44 | FR-22 | Negatif: nama kategori kosong | S login | Kirim form tanpa nama | category_name kosong | Error wajib diisi; tidak membuat kategori | | |
| TC-45 | FR-23 | Positif: toggle kategori tanpa menghapus riwayat | K1/K2 aktif; ada lead K1 | Nonaktifkan K1; periksa navigasi/dashboard; aktifkan kembali | Target K1 | K1 hilang dari navigasi saat nonaktif; riwayat tetap tersedia pada dashboard; aktif kembali muncul | | |
| TC-46 | FR-23 | Negatif: kategori aktif terakhir | Hanya K1 aktif | Nonaktifkan K1 | Target K1 | Error minimal satu kategori tetap aktif; K1 tidak berubah | | |
| TC-47 | FR-24 | Positif: membaca audit dan detail | S login; >30 audit fixture | Buka Log Perubahan; Lihat detail; halaman berikut | Log terbaru dan snapshot fixture | Urut waktu/ID menurun; 30 per halaman; sebelum/sesudah terbaca | | |
| TC-48 | FR-24 | Negatif: akses audit oleh CS | CS-A login | GET `/log-perubahan` | Role customer_service | 403; isi audit tidak ditampilkan | | |
| TC-49 | FR-25 | Positif: filter audit gabungan | S login; log berbeda tanggal/pelaku/aksi | Isi tanggal, Pelaku, Aktivitas; Terapkan filter | actor CS-A; action tambah; rentang mencakup fixture | Hanya log yang memenuhi seluruh filter tampil | | |
| TC-50 | FR-25 | Negatif: rentang audit terbalik | S login | Kirim from=H,to=H−1 | Tanggal akhir sebelum awal | Error tanggal akhir; tidak menjalankan filter invalid sebagai hasil sah | | |
| TC-51 | FR-26 | Positif: audit mutasi dan perlindungan password | S login; akun/sumber/kategori test tersedia | Tambah/edit/hapus lead; tambah/toggle master/akun; reset; periksa Log Perubahan | Input valid; password sementara yang dihasilkan | Aksi terkait tercatat dengan pelaku/objek; snapshot jumlah lead sesuai; tidak ada plaintext/hash password di detail audit | | |
| TC-52 | FR-26 | Negatif: validasi gagal tidak membuat audit sukses | S login; hitung log awal | Tambah sumber duplikat; periksa log | Nama sumber sudah ada | Tidak ada log tambah sukses baru untuk operasi yang ditolak; master tetap | | |
| TC-53 | FR-01 | Negatif: role tidak sesuai | Guest; password CS-A benar | Login CS-A dengan role supervisor | Role supervisor | Error peran tidak sesuai; tidak memperoleh session Supervisor | | |
| TC-54 | FR-01 | Negatif: akun nonaktif | CS-B nonaktif; guest | Login menggunakan password dan role benar | Akun B | Error akun nonaktif; tetap guest | | |
| TC-55 | FR-01 | Negatif: rate limit login | Guest; rate limiter bersih | Kirim 11 POST login dalam satu menit dari konteks limiter sama | Kredensial salah; IP/session test terkontrol | Request setelah batas dibatasi 429; catat konteks limiter aktual | | |
| TC-56 | FR-04 | Negatif: password baru lemah/konfirmasi tidak cocok/sama | CS-A login | Ulangi tiga variasi terpisah | Baru 7 karakter; konfirmasi berbeda; baru sama password lama | Tiap variasi ditolak dengan error terkait; password tidak berubah | | |
| TC-57 | FR-07 | Negatif: batas jumlah server | Kombinasi baru | Ulangi POST dengan variasi jumlah | cold=-1; 1.5; 10000 | Tiap variasi ditolak; tidak ada lead baru | | |
| TC-58 | FR-07 | Negatif: tanggal baru terlalu lama | CS-A dan M login bergantian | Tambah rekap baru H−31 | H−31; jumlah valid | Kedua role ditolak; kelonggaran non-CS bukan untuk create | | |
| TC-59 | FR-07 | Negatif: pemilik CS dipalsukan | CS-A login | POST valid dengan user_id CS-B tambahan | Kombinasi baru; user_id B | Pemilik tetap CS-A; parameter tidak memberi kewenangan input atas nama B | | |
| TC-60 | FR-08 | Positif: non-CS mengedit rekap lama | M login; L-lama H−31 | Edit dan simpan rekap lama | Tanggal tetap lama; cold=4 | Berhasil; batas usia CS tidak diterapkan pada M | | |
| TC-61 | FR-08 | Negatif: scoped binding kategori tidak cocok | M login; L1 milik K1; K2 ada | PUT URL K2/L1 | Payload valid | 404; L1 tidak berpindah kategori melalui binding yang salah | | |
| TC-62 | FR-10 | Positif: input ulang sesudah soft delete | Kombinasi L1 sudah soft-deleted | Tambah kombinasi yang sama | H/CS-A/S1/K1 | Rekap aktif baru diterima; baris lama tetap historis | | |
| TC-63 | FR-10 | Negatif: penyimpanan bersamaan | Dua session berizin; kombinasi belum ada | Kirim dua POST kombinasi identik hampir bersamaan | Payload sama | Maksimal satu baris aktif; request lain mendapat peringatan duplikat atau error benturan constraint, bukan baris kedua | | |
| TC-64 | FR-15 | Negatif: filter ekspor invalid | M login; fixture default/di luar default berbeda | GET export dengan rentang 32 hari | from=H−31,to=H | Ekspor menggunakan periode default awal bulan–H; bukan jaminan penolakan atau filter 32 hari | | |
| TC-65 | FR-19 | Negatif: Supervisor reset diri sendiri | S login | POST reset ID sendiri | Target S | 404; password sendiri tidak direset oleh endpoint ini | | |
| TC-66 | FR-25 | Negatif: action tidak dikenal | S login | GET log dengan action di luar whitelist | action=invalid_test_action | Validasi gagal; tidak menerima aktivitas fiktif | | |

## Matriks ringkas cakupan minimum

| FR | Positif | Negatif |
|---|---|---|
| FR-01 | TC-01 | TC-02 |
| FR-02 | TC-03 | TC-04 |
| FR-03 | TC-05 | TC-06 |
| FR-04 | TC-07 | TC-08 |
| FR-05 | TC-09 | TC-10 |
| FR-06 | TC-11 | TC-12 |
| FR-07 | TC-13 | TC-14 |
| FR-08 | TC-15 | TC-16 |
| FR-09 | TC-17 | TC-18 |
| FR-10 | TC-19 | TC-20 |
| FR-11 | TC-21 | TC-22 |
| FR-12 | TC-23 | TC-24 |
| FR-13 | TC-25 | TC-26 |
| FR-14 | TC-27 | TC-28 |
| FR-15 | TC-29 | TC-30 |
| FR-16 | TC-31 | TC-32 |
| FR-17 | TC-33 | TC-34 |
| FR-18 | TC-35 | TC-36 |
| FR-19 | TC-37 | TC-38 |
| FR-20 | TC-39 | TC-40 |
| FR-21 | TC-41 | TC-42 |
| FR-22 | TC-43 | TC-44 |
| FR-23 | TC-45 | TC-46 |
| FR-24 | TC-47 | TC-48 |
| FR-25 | TC-49 | TC-50 |
| FR-26 | TC-51 | TC-52 |

## Hubungan dengan test otomatis yang sudah ada

`tests/Feature/DashboardTest.php` mendukung sebagian TC-05, TC-23–30, TC-29–30; `MasterDataTest.php` mendukung sebagian TC-31–36 dan TC-39–46; `PasswordFlowTest.php` mendukung sebagian TC-07–10, TC-37; `AuditLogTest.php` mendukung sebagian TC-47–52. Ini pemetaan statis, bukan klaim test dijalankan atau seluruh langkah black-box sudah tercakup. Test bernama perlindungan Supervisor terakhir belum benar-benar membuktikan cabang guard terakhir; jangan menganggap seluruh matriks izin sudah diuji otomatis.

Tidak ada hasil pelaksanaan pada tabel di atas. Catat hasil nyata, bukti/screenshot, waktu, browser, dataset, dan defect ID setelah pengujian di lingkungan yang disetujui. Target nonfungsional memerlukan pengujian terpisah sebagaimana [02](02-non-functional-requirements.md).
