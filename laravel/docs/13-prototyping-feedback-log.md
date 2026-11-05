# 13 — Template Evaluasi dan Revisi Prototyping

## Kedudukan dokumen

Pemilik proyek menyatakan metode Prototyping telah digunakan dan prototype v1/v2 telah dipresentasikan kepada mitra. Kode yang tersedia tidak membuktikan tanggal, peserta, isi masukan, atau persetujuan setiap presentasi. Tabel berikut **sengaja kosong**; tidak ada masukan mitra/dosen atau hasil evaluasi yang direkayasa.

## Cara pengisian

1. Isi satu baris untuk satu masukan berdasarkan catatan rapat, formulir evaluasi, rekaman yang diizinkan, atau konfirmasi pemberi masukan.
2. Tuliskan versi yang benar-benar dievaluasi, tanggal aktual, dan sumber `partner` atau `lecturer` beserta identitas/jabatan sesuai persetujuan pencatatan.
3. Pisahkan isi masukan dari keputusan tim. Kategori dapat berupa fungsi, usability, keamanan, data, atau dokumentasi; kategori ini adalah panduan pengisian, bukan hasil evaluasi.
4. Jelaskan perubahan yang benar-benar dibuat; hubungkan ke FR/UC/TC, issue, commit, atau bukti revisi jika tersedia. Bila belum dilakukan, jangan isi seolah selesai.
5. Status mencerminkan kondisi aktual, misalnya belum ditinjau, disetujui, ditunda, ditolak dengan alasan, diterapkan, atau diverifikasi. Jangan menyamakan keputusan diterima dengan revisi sudah lulus uji.
6. Masukan yang belum memiliki bukti dicatat untuk dikonfirmasi, bukan diatribusi langsung kepada mitra/dosen.

## Log evaluasi dan revisi — kosong

| Version | Date | Source (partner/lecturer) | Feedback | Category | Decision | Change made | Status |
|---|---|---|---|---|---|---|---|
| | | | | | | | |
| | | | | | | | |
| | | | | | | | |
| | | | | | | | |
| | | | | | | | |
| | | | | | | | |

## Possible iteration hints from git history

**Belum diverifikasi sebagai iterasi prototype.** Pemeriksaan read-only `git log -15 --date=short` hanya menampilkan commit berikut pada repository yang tersedia:

| Commit | Tanggal menurut Git | Subject asli | Interpretasi yang diizinkan |
|---|---|---|---|
| `9c9bb06` | 2026-10-20 | `Initial commit` | Hanya petunjuk adanya commit awal; tidak membuktikan fitur CRM, presentasi, atau revisi v1/v2 |

`git status --short` pada direktori aplikasi menampilkan `?? ./`, sehingga codebase yang diperiksa tidak dapat diasumsikan sudah tercakup oleh commit tersebut. Tidak ditemukan rangkaian commit relevan yang cukup untuk menyimpulkan urutan perubahan prototype. Tanggal/subject Git tidak otomatis merupakan tanggal evaluasi mitra. Komentar kode yang menyebut prototype juga tidak membuktikan isi umpan balik.

TODO: confirm with team — lengkapi log dari bukti evaluasi v1/v2 dan, bila tersedia, riwayat Git yang benar-benar melacak implementasi CRM; jangan mengisi masukan berdasarkan dugaan dari kode.

Dokumen terkait: [00-inventory](00-codebase-inventory.md), [01-FR](01-functional-requirements.md), [12-UAT](12-uat-plan.md).
