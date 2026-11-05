# 14 — Panduan Pengguna

## Ruang lingkup dan istilah

Panduan mengikuti tampilan `resources/views/auth/login.blade.php`, `layouts/app.blade.php`, `leads/index.blade.php`, `dashboard.blade.php`, `master-data.blade.php`, `audit-logs.blade.php`, dan `password-change.blade.php`. Path diawali `/` merupakan URI relatif aplikasi, bukan alamat deployment. Ganti `{category}` dengan ID kategori yang dipilih melalui menu; ID tidak selalu sama antarinstalasi. Screenshot masih berupa placeholder dan tidak menyatakan tampilan sudah diuji pada browser.

Sistem menyimpan **rekap jumlah prospek**, bukan identitas pelanggan individual. Satu baris dibedakan oleh tanggal, admin CS, sumber, dan kategori. `Cold`, `Warm`, `Potensial`, `Closing`, dan `Cancel` adalah lima kolom jumlah yang diisi secara terpisah.

## 1. Masuk, navigasi, dan keluar — semua role

### 1.1 Login

1. Buka halaman `/login` pada instalasi yang diberikan tim.
2. Isi `Email` dan `Password` milik akun Anda.
3. Pada `Pilih Peran`, pilih role sesuai akun: `Customer Service`, `Manajemen`, atau `Supervisor`.
4. Pilih `Login`.
5. Apabila diminta mengganti password, lakukan langkah pada bagian 1.3 sebelum membuka modul lain.
6. CS biasanya diarahkan ke kategori aktif pertama; Manajemen dan Supervisor ke dashboard. Halaman yang sebelumnya diminta dapat menjadi tujuan setelah login.

`[Screenshot: Halaman Login — /login]`

Jika muncul `Email atau password salah.`, periksa kembali kredensial. Jika `Peran yang dipilih tidak sesuai dengan akun ini.`, gunakan role yang benar; memilih Supervisor tidak mengubah hak akun Anda. Akun nonaktif menampilkan instruksi menghubungi Supervisor. Teks bantuan pada login: `Lupa password? Hubungi Supervisor untuk mereset password.` Tidak tersedia formulir pemulihan email mandiri.

### 1.2 Navigasi

- Sidebar menampilkan `UMI CRM`, nama pengguna, dan peran.
- Buka `Input Data Leads` untuk memilih kategori aktif. Data awal seeder mencakup `Umroh`, `Haji Khusus`, `Haji Mujamalah`; daftar aktual dapat berbeda karena Supervisor dapat menambah/menonaktifkan kategori.
- CS memakai `Diagram Saya`; Manajemen/Supervisor memakai `Dashboard Analitik`.
- Hanya Supervisor melihat `Master Data` dan `Log Perubahan`.
- Pada layar kecil, gunakan tombol menu untuk membuka sidebar; sentuh overlay untuk menutupnya.
- Pesan sukses muncul melalui notifikasi status. Untuk keluar, pilih `Logout`; Anda kembali ke login.

`[Screenshot: Sidebar dan navigasi berdasarkan role — /leads/{category} atau /dashboard]`

### 1.3 Mengganti password

1. Pilih `Ubah Password`, atau ikuti pengarahan `Ganti Password` wajib.
2. Isi `Password Saat Ini` dengan password yang sedang berlaku, termasuk password sementara dari Supervisor bila relevan.
3. Isi `Password Baru` minimal delapan karakter dan berbeda dari password saat ini.
4. Isi ulang pada `Konfirmasi Password Baru`.
5. Pilih `Simpan Password Baru`.
6. Jika valid, sistem memperbarui password dan mengarahkan ke halaman awal. Jika ada error, perbaiki field terkait; kewajiban ganti tetap berlaku sampai berhasil.

`[Screenshot: Perubahan password biasa dan wajib — /password/change]`

Pesan wajib ganti pada view menyebut akun direset Supervisor; halaman yang sama juga digunakan akun baru. Jangan membagikan password atau memasukkannya ke screenshot laporan.

## 2. Customer Service

### 2.1 Membaca rekap kategori

1. Buka `Input Data Leads` dan pilih kategori.
2. Perhatikan heading `Input Data Leads: {nama kategori}`.
3. Baca baris tanggal, admin, sumber, dan kelima jumlah status. Daftar ini mencakup rekap seluruh staf pada kategori tersebut, bukan hanya milik Anda.
4. Baca total di bagian bawah; total mencakup seluruh entri kategori, bukan hanya halaman yang sedang dibuka.
5. Gunakan `Sebelumnya`/`Berikutnya` bila tersedia; setiap halaman berisi maksimal 50 baris.

`[Screenshot: Daftar dan total rekap kategori — /leads/{category}]`

Belum ada fitur pencarian/filter pada daftar kategori. Kolom tanggal/admin/sumber pada form atas adalah input rekap, bukan filter daftar.

### 2.2 Menambah rekap

1. Pilih kategori yang sesuai.
2. Isi `Tanggal` antara hari ini minus 30 hari dan hari ini; tanggal masa depan ditolak.
3. `Nama Admin` menampilkan nama Anda dan tidak dapat diganti oleh CS.
4. Pilih `Sumber Leads` aktif yang sesuai.
5. Isi `Cold`, `Warm`, `Potensial`, `Closing`, `Cancel` dengan bilangan bulat 0–9999. Kolom kosong dianggap nol.
6. Pilih `Simpan Data`.
7. Jika ada angka di atas 10, periksa kembali. Pilih `Ya, simpan` untuk menyetujui, atau `Periksa lagi` untuk kembali memeriksa tanpa menyimpan.
8. Keberhasilan ditandai `Data disimpan.`.

`[Screenshot: Form rekap dan konfirmasi jumlah tinggi — /leads/{category}]`

Jika muncul `Entri ini sudah ada (...)`, jangan membuat rekap kedua dengan kombinasi sama. Gunakan `Edit entri itu` apabila tersedia dan Anda berizin. Bila sistem menyatakan entri baru saja dibuat orang lain, muat ulang dan edit baris yang sudah ada.

### 2.3 Mengubah dan menghapus

1. Temukan baris milik Anda.
2. Untuk koreksi, pilih `Edit`; form menampilkan data lama. Ubah field dan pilih `Perbarui Data`. Pilih `Batal` jika tidak ingin melanjutkan.
3. Untuk penghapusan, pilih `Hapus` dan setujui `Hapus entri ini?` hanya jika benar-benar diperlukan.
4. Setelah berhasil, pesan `Perubahan disimpan.` atau `Data dihapus.` muncul.

`[Screenshot: Aksi Edit/Hapus dan keadaan Terkunci/Hanya lihat — /leads/{category}]`

Anda hanya dapat mengubah/menghapus milik sendiri yang tanggalnya tidak lebih tua dari 30 hari. Tepat 30 hari masih dapat diedit; lebih lama ditandai `Terkunci`. Baris milik staf lain merupakan `Hanya lihat`. Penghapusan adalah soft delete; tidak tersedia tombol pemulihan pada aplikasi.

### 2.4 Melihat Diagram Saya

1. Pilih `Diagram Saya`.
2. Baca `Total Closing`, `Total Cold`, `Total Warm`, `Total Potensial`, `Total Cancel`.
3. Tinjau `Prospek Anda per sumber leads` dan `Rincian entri`.
4. Ubah `Dari Tanggal`, `Sampai Tanggal`, `Kategori Layanan`, atau `Sumber Leads`. Form diperbarui otomatis saat nilai berubah; `Terapkan` juga tersedia.
5. Gunakan `Reset` untuk kembali ke default awal bulan sampai hari ini.

`[Screenshot: Diagram Saya dan filter — /dashboard]`

Rentang tanggal bersifat inklusif dan maksimal 31 hari; tidak boleh masa depan/terbalik. Jika filter invalid, halaman menampilkan error dan kembali ke default. Diagram/rincian ini hanya memakai data milik Anda walaupun daftar kategori menampilkan lintas staf. Tombol ekspor tidak ditampilkan untuk CS; endpoint langsung tetap mengizinkan unduhan data pribadi sebagaimana matriks izin, tetapi bukan langkah menu normal panduan ini.

## 3. Manajemen

### 3.1 Rekap untuk staf CS

1. Buka kategori melalui `Input Data Leads`.
2. Isi tanggal dan sumber seperti langkah CS.
3. Pada `Nama Admin`, pilih staf CS yang akan menjadi pemilik rekap.
4. Isi jumlah dan pilih `Simpan Data`; tangani duplikat/konfirmasi tinggi dengan cara yang sama.
5. Untuk koreksi/hapus, gunakan `Edit`/`Hapus` pada baris terkait. Manajemen dapat menangani rekap seluruh staf, termasuk entri lama.

`[Screenshot: Pemilihan Nama Admin oleh Manajemen — /leads/{category}]`

Tanggal **rekap baru** tetap dibatasi 30 hari ke belakang untuk semua role. Kelonggaran tanggal lama hanya pada pengeditan non-CS. Saat mengedit, sumber/staf nonaktif yang sudah terpasang pada entri dapat tetap terlihat; hal ini mempertahankan akses riwayat, bukan mengaktifkan akun/master kembali.

### 3.2 Analitik lintas staf

1. Pilih `Dashboard Analitik`.
2. Baca total status, `Performa per staf`, `Closing per sumber leads`, dan `Closing per kategori layanan`.
3. Gunakan `Dari Tanggal`, `Sampai Tanggal`, `Staf CS`, `Kategori Layanan`, `Sumber Leads` sesuai kebutuhan.
4. Nilai default `Semua CS`/`Semua` berarti tidak membatasi dimensi tersebut. Ketika berubah, form mengirim GET otomatis; Anda juga dapat memilih `Terapkan`.
5. Periksa `Rincian entri` dan pagination; gunakan `Reset` bila ingin menghapus filter.

`[Screenshot: Dashboard Analitik, diagram, filter dan rincian — /dashboard]`

Opsi historis dashboard dapat mencakup sumber/kategori/staf nonaktif. Tanpa hasil, sistem menampilkan `Belum ada data untuk filter ini. Coba ubah tanggal atau filter lain.`.

### 3.3 Ekspor Excel

1. Pastikan filter tanggal/kategori/sumber/staf sudah valid dan hasil yang ditampilkan sesuai kebutuhan.
2. Pilih `Export ke Excel (.xlsx)`.
3. Simpan dan buka file `umi-crm-{from}-sd-{to}.xlsx` pada pembaca XLSX.
4. Periksa worksheet `Laporan Leads`; seluruh hasil filter disertakan, bukan hanya halaman 50 baris saat ini.

`[Screenshot: Tombol ekspor dan hasil worksheet — /dashboard dan berkas XLSX]`

Jangan mengandalkan query ekspor invalid untuk menampilkan error: endpoint memakai fallback periode default. Perbaiki filter di dashboard terlebih dahulu. Jika unduhan gagal, laporkan waktu dan pesan error kepada tim tanpa menyertakan password/session.

## 4. Supervisor

Supervisor menggunakan seluruh prosedur rekap/analitik Manajemen, ditambah fungsi berikut.

### 4.1 Membuka Master Data dan menambah akun

1. Pilih `Master Data`.
2. Pada `Staf dan akun`, isi `Nama staf`, `Email`, dan `Peran`.
3. Pilih `Tambah staf`.
4. Setelah berhasil, salin password pada banner `Password sementara untuk {nama}` dan berikan **langsung** kepada pemilik akun.
5. Minta pengguna login dan mengganti password sebelum memakai aplikasi.

`[Screenshot: Master Data — Staf dan akun serta password sementara yang disamarkan — /master-data]`

Email harus unik; sistem menyimpannya lowercase. Password sementara ditampilkan sekali. Jangan memakai screenshot yang memperlihatkan password. Jika tidak sempat disalin, gunakan reset akun lain sesuai prosedur, bukan mencari plaintext di audit/database. Tidak tersedia form edit nama/email/role akun pada implementasi ini.

### 4.2 Mengaktifkan atau menonaktifkan akun

1. Pada daftar `Staf dan akun`, temukan target yang benar.
2. Pilih `Nonaktifkan` atau `Aktifkan` dan setujui konfirmasi setelah memeriksa nama.
3. Periksa perubahan `Status`.
4. Akun nonaktif tidak dapat login; session yang masih ada dikeluarkan pada request terlindungi berikutnya. Riwayat rekap tidak dihapus.

Baris sendiri ditandai `Akun Anda` dan tidak menyediakan aksi status/reset. Sistem juga memiliki guard Supervisor aktif terakhir. Tidak tersedia penghapusan permanen akun melalui UI.

### 4.3 Mereset password akun lain

1. Temukan akun target pada `Master Data`.
2. Pilih `Reset password` dan setujui konfirmasi.
3. Salin password sementara yang hanya ditampilkan sekali dan sampaikan langsung kepada pemilik.
4. Minta pengguna mengganti password saat login.

`[Screenshot: Aksi Reset password dan banner sekali tampil yang disamarkan — /master-data]`

Reset tidak mengaktifkan akun nonaktif. Untuk mengganti password sendiri, gunakan `Ubah Password`. Reset tidak boleh dianggap sebagai pembatalan segera semua session perangkat lain.

### 4.4 Mengelola sumber lead

1. Pada `Sumber leads`, isi `Nama sumber` yang belum ada dan pilih `Tambah`.
2. Untuk mengatur ketersediaan, gunakan `Nonaktifkan`/`Aktifkan` pada baris terkait dan setujui konfirmasi.
3. Pastikan minimal satu sumber tetap aktif; penonaktifan sumber aktif terakhir ditolak.
4. Sumber nonaktif tidak muncul pada input baru, tetapi riwayatnya tetap tersimpan.

`[Screenshot: Bagian Sumber leads — /master-data]`

### 4.5 Mengelola kategori layanan

1. Pada `Kategori layanan`, isi `Nama kategori` yang belum ada dan pilih `Tambah`.
2. Gunakan `Nonaktifkan`/`Aktifkan` pada baris yang benar dan setujui konfirmasi.
3. Minimal satu kategori harus tetap aktif.
4. Kategori aktif muncul pada menu `Input Data Leads`; kategori nonaktif hilang dari menu dan halaman inputnya tidak dapat dibuka.

`[Screenshot: Bagian Kategori layanan — /master-data]`

Tidak ada rename/hard delete sumber/kategori pada UI. Jangan menggunakan request langsung sebagai pengganti menu kategori nonaktif; update/delete endpoint memiliki perbedaan pengecekan aktif yang dicatat pada dokumentasi teknis.

### 4.6 Memeriksa Log Perubahan

1. Pilih `Log Perubahan`.
2. Isi bila diperlukan `Dari Tanggal`, `Sampai Tanggal`, `Pelaku`, dan `Aktivitas`.
3. Pilih aktivitas `Tambah`, `Ubah`, `Hapus`, atau `Reset password`, lalu `Terapkan filter`; gunakan `Reset` untuk menghapus filter.
4. Baca `Waktu`, `Pelaku`, `Aktivitas`, `Objek dan ringkasan`.
5. Buka `Lihat detail` untuk membaca `Sebelum`/`Sesudah`.
6. Gunakan pagination untuk catatan lebih lama; 30 baris per halaman.

`[Screenshot: Filter Log Perubahan dan detail Sebelum/Sesudah — /log-perubahan]`

Log tidak dapat diedit/dihapus melalui UI. Detail lead menyimpan perubahan jumlah status, bukan seluruh metadata lama. Password tidak boleh muncul pada detail audit. Tidak semua aktivitas aplikasi dicatat: login/logout dan perubahan password sendiri tidak memiliki pencatatan audit eksplisit.

## 5. Penanganan kendala umum

| Kondisi | Tindakan pengguna |
|---|---|
| Akun nonaktif | Hubungi Supervisor untuk peninjauan status |
| Lupa password | Minta reset Supervisor; tidak ada pemulihan melalui email mandiri |
| Entri duplikat | Cari/edit rekap yang sudah ada, bukan menambah kombinasi identik |
| Terkunci atau hanya lihat | Gunakan hak role yang benar; minta koreksi oleh pihak berwenang bila diperlukan |
| Tanggal/jumlah tidak valid | Sesuaikan batas tanggal, integer 0–9999, dan rentang dashboard ≤31 hari |
| Halaman 403/404 | Periksa role, status kategori, serta target record; jangan mencoba memintas URL |
| Opsi sumber/staf tidak ada | Mungkin nonaktif; minta Supervisor memeriksa status, tanpa menganggap data hilang |
| Unduhan gagal | Coba filter valid dari dashboard; laporkan error kepada tim untuk pemeriksaan extension ZIP, temporary storage, dan log |

TODO: confirm with team — lengkapi screenshot asli yang sudah disamarkan, alamat akses instalasi, dan saluran dukungan operasional; nilai tersebut tidak tersedia pada kode yang diperiksa.
