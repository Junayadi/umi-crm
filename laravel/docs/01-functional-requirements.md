# 01 — Kebutuhan Fungsional

Dasar: [inventaris](00-codebase-inventory.md). Setiap kalimat “Sistem harus” merupakan rumusan *The system shall* dari perilaku kode, bukan penambahan fitur. Prioritas **Must** menunjukkan fungsi inti atau kontrol akses/integritas; **Should** menunjukkan dukungan operasional. Prioritas merupakan inferensi analis dan belum merupakan keputusan mitra. Aktor `Semua pengguna` berarti ketiga role dengan akun aktif; route operasional juga mensyaratkan password sudah diganti apabila diwajibkan.

## Autentikasi dan akun

| ID | Module | Requirement description | Actor | Priority | Source (route/controller/view) |
|---|---|---|---|---|---|
| FR-01 | Autentikasi | Sistem harus menerima login dengan email, password, dan role yang sesuai akun aktif, menolak kredensial/role tidak sesuai, serta membatasi POST login melalui `throttle:10,1`. | Pengunjung | Must | `login`, `login.store`; `AuthController@create/store`; `auth/login.blade.php` |
| FR-02 | Autentikasi | Sistem harus mengakhiri autentikasi, menginvalidasi session, memperbarui token CSRF, dan mengarahkan pengguna ke login ketika logout. | Semua pengguna | Must | `logout`; `AuthController@destroy`; `layouts/app.blade.php` |
| FR-03 | Navigasi | Sistem harus mengarahkan CS dari home ke kategori aktif pertama dan role lain ke dashboard; bila tidak ada kategori aktif, home CS menghasilkan 404 dengan pesan kategori belum tersedia. | Semua pengguna | Must | `home`; `HomeController@__invoke` |
| FR-04 | Password | Sistem harus menerima perubahan password dengan password saat ini yang benar, password baru minimal delapan karakter, berbeda dari sebelumnya, dan konfirmasi cocok; menyimpan hash serta memperbarui session. | Semua pengguna | Must | `password.edit/update`; `PasswordController@edit/update`; `password-change.blade.php` |
| FR-05 | Password | Sistem harus mengarahkan akun dengan `must_change_password` ke perubahan password sebelum mengakses modul operasional; perubahan yang berhasil menghapus kewajiban tersebut. | Semua pengguna | Must | `EnsurePasswordIsChanged`; `AuthController@store`; `PasswordController@update`; `routes/web.php` |

## Rekap lead

| ID | Module | Requirement description | Actor | Priority | Source (route/controller/view) |
|---|---|---|---|---|---|
| FR-06 | Lead | Sistem harus menampilkan rekap seluruh staf pada kategori aktif, 50 baris per halaman, urutan tanggal/ID menurun, dan total kategori untuk kelima jumlah status di seluruh halaman. | Semua pengguna | Must | `leads.index`; `LeadController@index`; `leads/index.blade.php` |
| FR-07 | Lead | Sistem harus menyimpan rekap tanggal, pemilik CS, sumber, kategori, dan lima jumlah integer 0–9999; input kosong menjadi nol; tanggal baru berada antara hari ini minus 30 hari dan hari ini; CS selalu menjadi pemilik input sendiri. | Semua pengguna | Must | `leads.store`; `LeadController@store/save`; `Lead::STATUSES` |
| FR-08 | Lead | Sistem harus mengubah rekap sesuai `LeadPolicy`: CS hanya milik sendiri yang tidak lebih tua dari 30 hari; Manajemen/Supervisor dapat mengubah seluruh rekap termasuk tanggal lama. | Semua pengguna sesuai batas | Must | `leads.update`, `leads.index?edit=ID`; `LeadController@update/save`; `Lead::isEditableBy`; `LeadPolicy@update` |
| FR-09 | Lead | Sistem harus melakukan soft delete rekap yang diizinkan, dengan batas kepemilikan/usia sama seperti perubahan, dan menyembunyikannya dari query normal. | Semua pengguna sesuai batas | Must | `leads.destroy`; `LeadController@destroy`; `LeadPolicy@delete`; `Lead` |
| FR-10 | Lead | Sistem harus mencegah duplikat aktif kombinasi `entry_date`, `user_id`, `source_id`, `category_id`, menawarkan akses edit bila diizinkan, dan menangani benturan unique constraint saat penyimpanan bersamaan. | Semua pengguna | Must | `LeadController@save`; `leads_unique_active_entry`; `leads/index.blade.php` |
| FR-11 | Lead | Sistem harus meminta konfirmasi tambahan sebelum menyimpan apabila salah satu jumlah status melebihi 10; penyimpanan dilanjutkan setelah `confirm_high` benar. | Semua pengguna | Must | `LeadController::HIGH_THRESHOLD`; `LeadController@save`; script `leads/index.blade.php` |

## Analitik dan ekspor

| ID | Module | Requirement description | Actor | Priority | Source (route/controller/view) |
|---|---|---|---|---|---|
| FR-12 | Dashboard | Sistem harus menampilkan `Diagram Saya` berupa ringkasan, diagram per sumber, dan rincian yang hanya mencakup lead CS yang sedang login. | Customer Service | Must | `dashboard`; `DashboardController@index/dashboardData`; `dashboard.blade.php` |
| FR-13 | Dashboard | Sistem harus menampilkan `Dashboard Analitik` lintas staf dengan total status, performa per staf, closing per sumber/kategori, serta rincian rekap. | Manajemen, Supervisor | Must | `dashboard`; `DashboardController@dashboardData`; `dashboard.blade.php` |
| FR-14 | Dashboard | Sistem harus menerapkan filter tanggal inklusif maksimal 31 hari, kategori, sumber, dan staf khusus non-CS; filter tidak valid menampilkan error dan kembali ke default awal bulan sampai hari ini. | Semua pengguna | Must | `DashboardController@dashboardData`; GET form dan autosubmit `dashboard.blade.php` |
| FR-15 | Ekspor | Sistem harus menghasilkan XLSX seluruh rekap yang cocok dengan query dashboard, bukan hanya halaman aktif; CS dibatasi ke data sendiri meskipun parameter staf dikirim. | Manajemen, Supervisor; CS melalui endpoint langsung | Should | `dashboard.export`; `DashboardController@export`; `DashboardXlsxExport@create` |

## Master data dan pemulihan akun

| ID | Module | Requirement description | Actor | Priority | Source (route/controller/view) |
|---|---|---|---|---|---|
| FR-16 | Master data | Sistem harus menampilkan daftar akun, sumber, kategori, role, dan status hanya kepada Supervisor. | Supervisor | Must | `master-data.index`; `MasterDataController@index/ensureSupervisor`; `master-data.blade.php` |
| FR-17 | Akun staf | Sistem harus membuat akun dengan nama, email unik yang dinormalisasi lowercase, role valid, status aktif, password acak 16 karakter yang ditampilkan sekali, dan kewajiban mengganti password. | Supervisor | Must | `master-data.users.store`; `MasterDataController@storeUser` |
| FR-18 | Akun staf | Sistem harus mengaktifkan/menonaktifkan akun lain, menolak penonaktifan diri sendiri dan melindungi Supervisor aktif terakhir; akun nonaktif dikeluarkan saat mengakses route terlindungi. | Supervisor | Must | `master-data.users.status`; `MasterDataController@toggleUser`; `EnsureActiveAccount` |
| FR-19 | Pemulihan akun | Sistem harus mereset password akun lain menjadi password acak 16 karakter yang ditampilkan sekali serta mewajibkan penggantian; reset diri sendiri ditolak dan endpoint dibatasi `throttle:10,1`. | Supervisor | Must | `password.resets.store`; `PasswordController@reset`; `master-data.blade.php` |
| FR-20 | Sumber | Sistem harus menambah sumber lead dengan nama wajib, unik, maksimal 100 karakter, dan status aktif. | Supervisor | Must | `master-data.sources.store`; `MasterDataController@storeSource` |
| FR-21 | Sumber | Sistem harus mengubah status aktif sumber tanpa menghapus riwayat dan menolak penonaktifan sumber aktif terakhir. | Supervisor | Must | `master-data.sources.status`; `MasterDataController@toggleSource` |
| FR-22 | Kategori | Sistem harus menambah kategori layanan dengan nama wajib, unik, maksimal 100 karakter, dan status aktif. | Supervisor | Must | `master-data.categories.store`; `MasterDataController@storeCategory` |
| FR-23 | Kategori | Sistem harus mengubah status aktif kategori tanpa menghapus riwayat dan menolak penonaktifan kategori aktif terakhir. | Supervisor | Must | `master-data.categories.status`; `MasterDataController@toggleCategory` |

## Audit

| ID | Module | Requirement description | Actor | Priority | Source (route/controller/view) |
|---|---|---|---|---|---|
| FR-24 | Audit | Sistem harus menampilkan log perubahan hanya kepada Supervisor, 30 baris per halaman dengan urutan terbaru, pelaku, objek, deskripsi, dan detail sebelum/sesudah. | Supervisor | Should | `audit-logs.index`; `AuditLogController@index`; `audit-logs.blade.php` |
| FR-25 | Audit | Sistem harus memfilter log berdasarkan tanggal, pelaku, dan aktivitas `tambah`, `ubah`, `hapus`, atau `reset_password`, serta menolak filter tidak valid. | Supervisor | Should | `AuditLogController@index`; form `audit-logs.blade.php` |
| FR-26 | Audit | Sistem harus mencatat perubahan lead, penambahan/status master data, pembuatan/status akun, dan reset password dalam transaksi perubahan terkait tanpa menyimpan password dalam audit. | Pengguna pelaku perubahan; Supervisor pembaca | Must | `LeadController`, `MasterDataController`, `PasswordController@reset`; `AuditLog@record/recordChange` |

## Batas interpretasi

- Tidak ada CRUD pelanggan individual, rename master data, edit nama/email/role akun, hard delete, restore lead, registrasi publik, atau reset melalui email pada route yang diperiksa.
- Snapshot sebelum/sesudah lead hanya memuat lima jumlah status, bukan seluruh metadata. Pergantian password sendiri tidak dicatat oleh `AuditLog`.
- Validasi dashboard gagal mengembalikan seluruh filter ke default. Endpoint ekspor memakai perilaku sama tanpa halaman error; hasil dapat memakai periode default.
- Halaman/create lead menolak kategori nonaktif; update/delete tidak memiliki pemeriksaan aktif kategori yang sama, walaupun tetap melewati policy dan scoped binding.
- TODO: confirm with team — putuskan apakah akses langsung ekspor CS, pembacaan rekap lintas staf oleh CS, dan fallback ekspor saat filter tidak valid merupakan perilaku bisnis yang disetujui.

Pemetaan FR ke UC tersedia dalam [03-use-cases.md](03-use-cases.md); seluruh FR memiliki kasus positif/negatif dalam [11-black-box-test-cases.md](11-black-box-test-cases.md).
