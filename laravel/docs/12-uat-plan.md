# 12 — Rencana User Acceptance Testing

## Tujuan dan kedudukan

UAT bertujuan menilai apakah alur yang tersedia pada kode mendukung pekerjaan perwakilan UMI Tour Travel, dapat digunakan oleh role yang tepat, dan diterima untuk penggunaan yang disepakati. Dokumen ini adalah **rencana dan formulir kosong**, bukan bukti penerimaan, hasil prototype v1/v2, atau hasil SUS.

Ruang lingkup mengikuti 26 FR dan 21 UC, bukan fitur CRM generik. UAT melengkapi, bukan menggantikan, pengujian akses/validasi terinci pada [11-black-box-test-cases.md](11-black-box-test-cases.md) dan pengukuran NFR.

## Peserta dan tanggung jawab yang direncanakan

| Peran peserta | Tanggung jawab |
|---|---|
| Perwakilan Customer Service mitra | Menjalankan input, koreksi, pembacaan rekap, diagram pribadi, dan password |
| Perwakilan Manajemen mitra | Menjalankan analitik lintas staf, filter, ekspor, dan koreksi rekap |
| Perwakilan Supervisor mitra | Menjalankan pengelolaan master/akun, reset, dan pemeriksaan audit |
| Tim pengembang/fasilitator | Menyiapkan environment/data, memberi instruksi netral, mencatat bukti/defect tanpa mengisi jawaban pengguna |
| Dosen/pembimbing bila dilibatkan | Mengamati prosedur dan meninjau kesesuaian dokumentasi; bukan otomatis pemberi persetujuan bisnis |

Nama, jumlah peserta, jadwal, dan otoritas tanda tangan belum ditentukan. TODO: confirm with team — tetapkan peserta nyata tiap role, jadwal, lokasi/environment UAT, dan pihak yang berwenang memberikan sign-off.

## Persiapan dan prosedur

1. Siapkan environment terisolasi dengan versi kode dan migration yang dicatat; gunakan data sintetis tanpa data pribadi operasional.
2. Siapkan akun ketiga role, satu akun wajib ganti password, dua CS untuk memeriksa batas kepemilikan, dua kategori/sumber aktif, rekap baru dan lebih tua dari 30 hari, serta data yang menghasilkan lebih dari satu halaman.
3. Catat build/commit yang diuji, timezone/tanggal, browser/perangkat, dataset, dan kondisi jaringan. Jangan menulis password nyata dalam lembar hasil.
4. Jelaskan tugas, bukan langkah jawaban secara berlebihan. Peserta menjalankan skenario sesuai role. Fasilitator mencatat bantuan yang diperlukan, waktu, hasil, error, dan bukti tanpa mengubah kode saat pengujian berlangsung.
5. Catat defect dan prioritasnya di catatan hasil. Lakukan retest atas revisi dengan merujuk skenario/TC yang sama.
6. Setelah seluruh tugas, peserta mengisi SUS secara mandiri. Simpan jawaban individual dengan identitas peserta terkontrol.
7. Tinjau hasil bersama mitra sebelum menandatangani. Semua kolom hasil pada template tetap kosong sampai kegiatan benar-benar berlangsung.

## Skenario UAT turunan use case

| ID | Skenario tugas | Role peserta | UC / FR | TC pendukung | Hasil yang diharapkan |
|---|---|---|---|---|---|
| UAT-01 | Login dengan role yang benar dan logout setelah selesai | Semua role | UC-01–02 / FR-01–03 | TC-01–06,53–55 | Landing sesuai role dan session berakhir saat logout |
| UAT-02 | Mengganti password sementara dan mencoba akses sebelum/sesudahnya | Semua role | UC-03 / FR-04–05 | TC-07–10,56 | Wajib ganti tidak dapat dilewati; password baru valid membuka akses |
| UAT-03 | Membaca rekap kategori seluruh staf, total, dan halaman berikut | CS, Manajemen, Supervisor | UC-04 / FR-06 | TC-11–12 | Data/total kategori terbaca; pembatasan kategori aktif sesuai kode |
| UAT-04 | Menambah rekap valid dan mencoba kombinasi duplikat serta jumlah >10 | Semua role | UC-05 / FR-07,10–11,26 | TC-13–14,19–22,57–59,62 | Jumlah benar tersimpan; duplikat tidak menambah baris; jumlah tinggi menunggu konfirmasi |
| UAT-05 | Memperbaiki dan menghapus rekap dengan perbedaan pemilik/usia | CS dan non-CS | UC-06–07 / FR-08–09,26 | TC-15–18,60–61 | CS dibatasi pemilik/30 hari; non-CS dapat menangani entri lama; soft delete menjaga riwayat |
| UAT-06 | Meninjau capaian pribadi dan memastikan data staf lain tidak masuk dashboard pribadi | CS | UC-08 / FR-12 | TC-23–24 | Diagram Saya hanya memakai milik peserta |
| UAT-07 | Membandingkan capaian staf melalui dashboard dan filter yang valid/invalid | Manajemen, Supervisor | UC-09–10 / FR-13–14 | TC-25–28 | Analitik sesuai filter; invalid-filter ditampilkan dan kembali ke default |
| UAT-08 | Mengunduh dan membuka XLSX seluruh hasil filter | Manajemen, Supervisor | UC-11 / FR-15 | TC-29–30,64 | Workbook dapat dibuka dan seluruh hasil filter tercakup; batas data CS diuji melalui TC terpisah |
| UAT-09 | Membuka Master Data, menambah akun, dan mengubah status akun lain | Supervisor | UC-12–14 / FR-16–18,26 | TC-31–36 | Akun baru aktif/wajib ganti; status akun lain berubah; perubahan diri ditolak |
| UAT-10 | Mereset akun staf dan meminta staf menyelesaikan ganti password | Supervisor dan pemilik akun | UC-15,01,03 / FR-19,01,04–05,26 | TC-37–38,65 | Password sementara sekali tampil; tidak masuk audit; staf mengganti sebelum melanjutkan |
| UAT-11 | Menambah/mengatur sumber dan kategori sambil menjaga pilihan aktif terakhir | Supervisor | UC-16–19 / FR-20–23,26 | TC-39–46 | Opsi baru tersedia; nonaktif hilang dari input baru; pilihan aktif terakhir terlindungi |
| UAT-12 | Menemukan aktivitas melalui log, filter, dan detail sebelum/sesudah | Supervisor | UC-20–21 / FR-24–26 | TC-47–52,66 | Pelaku/objek/aksi benar; filter sesuai; informasi password tidak terlihat |

## Form hasil UAT — kosong

Kolom `scenario` dan `expected` mengacu pada tabel skenario di atas. Seluruh sel sengaja kosong untuk pengisian saat UAT; jangan mengisi pass/fail berdasarkan pembacaan kode.

| scenario | expected | result | notes | pass/fail |
|---|---|---|---|---|
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |
| | | | | |

## Kriteria penerimaan yang diusulkan

- Seluruh skenario Must dapat diselesaikan sesuai hak akses; tidak ada defect blocker/kritis terbuka yang disepakati.
- Perbedaan perilaku yang terdokumentasi—misalnya baca rekap lintas staf oleh CS, ekspor endpoint CS, dan fallback ekspor—dibahas dan diterima atau dicatat sebagai perubahan yang diminta, bukan dinyatakan lulus sepihak.
- Sasaran usability/SUS dan tingkat penyelesaian tugas mengikuti usulan NFR-14, setelah disepakati. Nilai SUS bukan satu-satunya dasar kelulusan keamanan/integritas.

TODO: confirm with team — sepakati kriteria penerimaan, klasifikasi defect, retest, dan ambang SUS sebelum UAT.

## Kuesioner System Usability Scale — 10 item

Instrumen berikut menggunakan padanan Bahasa Indonesia untuk sepuluh pernyataan SUS standar; bukan hasil kuesioner yang pernah diberikan. Urutan dan polaritas positif/negatif dipertahankan. Peserta memberi satu jawaban per item pada skala **1 = sangat tidak setuju; 2 = tidak setuju; 3 = netral; 4 = setuju; 5 = sangat setuju**.

| No. | Pernyataan | Jawaban 1–5 |
|---|---|---|
| 1 | Saya merasa ingin sering menggunakan sistem ini. | |
| 2 | Saya merasa sistem ini terlalu rumit secara tidak perlu. | |
| 3 | Saya merasa sistem ini mudah digunakan. | |
| 4 | Saya merasa memerlukan bantuan orang teknis untuk dapat menggunakan sistem ini. | |
| 5 | Saya merasa berbagai fungsi dalam sistem ini terintegrasi dengan baik. | |
| 6 | Saya merasa terdapat terlalu banyak ketidakkonsistenan dalam sistem ini. | |
| 7 | Saya membayangkan kebanyakan orang akan belajar menggunakan sistem ini dengan sangat cepat. | |
| 8 | Saya merasa sistem ini sangat merepotkan untuk digunakan. | |
| 9 | Saya merasa sangat percaya diri ketika menggunakan sistem ini. | |
| 10 | Saya perlu mempelajari banyak hal sebelum dapat mulai menggunakan sistem ini. | |

### Rumus penilaian

Untuk respons item ke-i sebesar `x_i`:

- Item ganjil 1, 3, 5, 7, 9: kontribusi `x_i − 1`.
- Item genap 2, 4, 6, 8, 10: kontribusi `5 − x_i`.
- `SUS = 2,5 × [Σ kontribusi ganjil + Σ kontribusi genap]`.
- Rentang skor 0–100; **bukan persentase keberhasilan tugas**. Nilai kelompok dihitung setelah tersedia skor individual; catat jumlah peserta dan metode agregasi. Jangan mengganti respons yang kosong dengan tebakan.

TODO: confirm with team — tetapkan sumber/adaptasi Bahasa Indonesia SUS yang digunakan dalam laporan akademik dan prosedur penanganan jawaban tidak lengkap; teks ini belum dinyatakan sebagai terjemahan tervalidasi tertentu.

## Sign-off — kosong

| Elemen | Isian |
|---|---|
| Nama proyek/build yang diuji | |
| Tanggal/periode UAT | |
| Environment dan dataset | |
| Ringkasan hasil dan defect terbuka | |
| Keputusan penerimaan / syarat revisi | |
| Nama dan jabatan wakil mitra | |
| Tanda tangan wakil mitra / tanggal | |
| Nama wakil tim pengembang | |
| Tanda tangan tim / tanggal | |
| Nama pengamat/pembimbing jika disyaratkan | |
| Tanda tangan pengamat / tanggal | |
