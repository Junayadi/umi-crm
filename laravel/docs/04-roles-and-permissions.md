# 04 — Role dan Permission

Sumber: `app/Enums/Role.php`, `routes/web.php`, `app/Policies/LeadPolicy.php`, `app/Models/Lead.php`, trait `HasUmiAccount`, controller, middleware, serta kondisi Blade. Matriks menyatakan izin **server**, bukan sekadar tombol yang terlihat. C = Create; R = Read; U = Update; D = Delete. D pada lead berarti **soft delete**.

## Matriks hak akses

| Fitur / modul | Pengunjung | Customer Service | Manajemen | Supervisor | FR / UC |
|---|---|---|---|---|---|
| Form dan POST login | Ya, `guest` | Dialihkan bila sudah login | Dialihkan bila sudah login | Dialihkan bila sudah login | FR-01 / UC-01 |
| Home berdasarkan role | Tidak | Kategori aktif pertama; 404 jika tidak ada | Dashboard | Dashboard | FR-03 / UC-01 |
| Logout | Tidak | Ya | Ya | Ya | FR-02 / UC-02 |
| Mengganti password sendiri | Tidak | U | U | U | FR-04–05 / UC-03 |
| Rekap kategori aktif seluruh staf | Tidak | R | R | R | FR-06 / UC-04 |
| Membuat lead | Tidak | C atas nama sendiri | C untuk CS yang valid | C untuk CS yang valid | FR-07 / UC-05 |
| Mengubah lead | Tidak | U milik sendiri, usia ≤30 hari | U seluruh staf, termasuk entri lama | U seluruh staf, termasuk entri lama | FR-08 / UC-06 |
| Menghapus lead | Tidak | D milik sendiri, usia ≤30 hari | D seluruh staf, termasuk entri lama | D seluruh staf, termasuk entri lama | FR-09 / UC-07 |
| Diagram pribadi | Tidak | R, hanya data sendiri | Tidak memakai varian pribadi | Tidak memakai varian pribadi | FR-12 / UC-08 |
| Dashboard lintas staf | Tidak | Tidak | R | R | FR-13 / UC-09 |
| Filter dashboard | Tidak | Tanggal/kategori/sumber | Ditambah staf CS | Ditambah staf CS | FR-14 / UC-10 |
| Ekspor XLSX | Tidak | Ya melalui endpoint, hanya sendiri; tombol tidak tampil | Ya | Ya | FR-15 / UC-11 |
| Daftar akun/sumber/kategori | Tidak | Tidak | Tidak | R | FR-16 / UC-12 |
| Pembuatan akun | Tidak | Tidak | Tidak | C | FR-17 / UC-13 |
| Aktivasi/nonaktivasi akun | Tidak | Tidak | Tidak | U akun lain dengan guard | FR-18 / UC-14 |
| Reset password akun lain | Tidak | Tidak | Tidak | U; bukan diri sendiri | FR-19 / UC-15 |
| Tambah sumber | Tidak | Tidak | Tidak | C | FR-20 / UC-16 |
| Status sumber | Tidak | Tidak | Tidak | U; bukan sumber aktif terakhir | FR-21 / UC-17 |
| Tambah kategori | Tidak | Tidak | Tidak | C | FR-22 / UC-18 |
| Status kategori | Tidak | Tidak | Tidak | U; bukan kategori aktif terakhir | FR-23 / UC-19 |
| Log dan filter perubahan | Tidak | Tidak | Tidak | R | FR-24–25 / UC-20–21 |
| Pencatatan audit | Tidak melalui modul operasional | Otomatis pada mutasi lead | Otomatis pada mutasi lead | Otomatis pada mutasi terkait | FR-26 / UC-05–07,13–19 |
| Rename/hard delete master, edit profil/role akun, restore lead | Tidak tersedia | Tidak tersedia | Tidak tersedia | Tidak tersedia | Tidak ada route |

## Cara enforcement

1. `guest` melindungi halaman dan POST login. Pilihan role hanya diverifikasi terhadap role akun; pengguna tidak memperoleh role melalui pilihan form.
2. Grup `auth` dan `account.active` melindungi password/logout dan seluruh modul. `EnsureActiveAccount` mengeluarkan pengguna yang sudah nonaktif, menginvalidasi session, dan memperbarui token.
3. Grup tambahan `password.changed` mengalihkan akun dengan `must_change_password` ke `password.edit`. Password/logout tetap dapat diakses agar pengguna tidak mengalami redirect loop.
4. `LeadController` memakai `Gate::authorize('create'|'update'|'delete', ...)`. `LeadPolicy` ditemukan melalui konvensi nama Laravel. `viewAny` ada pada policy, tetapi `LeadController@index` tidak memanggilnya secara eksplisit; akses baca tetap dilindungi middleware dan pengecekan kategori aktif.
5. `Lead::isEditableBy` menolak akun nonaktif; CS harus menjadi pemilik dan tidak terkunci. `isLocked` bernilai benar jika tanggal **lebih awal** dari hari ini minus 30 hari. Tepat pada batas 30 hari masih diizinkan. Pengguna aktif non-CS diizinkan mengubah/menghapus seluruh entri.
6. `scopeBindings()` memastikan `{lead}` berada pada `{category}`. Form edit mengambil lead melalui `$category->leads()->findOrFail(...)` sehingga query `edit` tidak memintas kategori.
7. `MasterDataController::ensureSupervisor`, `PasswordController@reset`, dan `AuditLogController@index` memeriksa Supervisor server-side dengan 403. Tidak ada policy master data terpisah atau permission package.
8. Penonaktifan akun sendiri dan reset diri sendiri menghasilkan 404. Ada guard penonaktifan Supervisor aktif terakhir, sumber aktif terakhir, serta kategori aktif terakhir. Guard ini tidak merupakan jaminan terhadap seluruh kemungkinan race condition karena perhitungan tidak memakai lock eksplisit.
9. Blade menyembunyikan menu/aksi yang tidak sesuai; controller/policy tetap menjadi otoritas. Contoh perbedaan UI/server ialah ekspor CS.

## Batas penting

- CS dapat membaca rekap semua staf pada daftar kategori dan totalnya. Pembatasan `user_id` pribadi diterapkan pada dashboard/ekspor, bukan seluruh query lead.
- Semua role tunduk tanggal baru maksimal 30 hari ke belakang dan tidak masa depan; kelonggaran tanggal lama hanya berlaku saat non-CS mengedit.
- GET/create lead menolak kategori nonaktif; update/delete langsung tidak memeriksa `is_active` kategori. Redirect setelah aksi dapat menuju halaman yang kemudian 404.
- Akun nonaktif tidak dapat menggunakan password/logout melalui route tersebut karena middleware lebih dahulu mengeluarkannya.
- Tidak ada revokasi seluruh session perangkat lain secara eksplisit setelah reset/pergantian password. Flag wajib ganti membatasi request operasional berikutnya.

Keputusan bisnis atas perbedaan UI/server ditandai untuk konfirmasi dalam [01-functional-requirements.md](01-functional-requirements.md); target keamanan lanjutan berada pada [02](02-non-functional-requirements.md).
