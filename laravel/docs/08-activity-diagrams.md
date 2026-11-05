# 08 — Activity Diagram

## Pemilihan alur

| Alur | Alasan kepentingan dan kompleksitas | Traceability |
|---|---|---|
| A-01 — Menyimpan rekap lead (create/update) | Inti pencatatan CRM; melibatkan izin, batas tanggal, sumber/pemilik, duplikat, konfirmasi angka, transaksi dan audit | UC-05–06; FR-07–08, FR-10–11, FR-26 |
| A-02 — Analitik dan ekspor XLSX | Menyediakan informasi pengambilan keputusan; filter tervalidasi, pembatasan role, agregasi, pagination dan ekspor seluruh hasil | UC-08–11; FR-12–15 |
| A-03 — Reset Supervisor dan pergantian password wajib | Mengendalikan pemulihan akses dan keamanan; izin Supervisor, password sementara sekali tampil, audit, login serta middleware wajib ganti | UC-15, UC-01, UC-03; FR-01, FR-04–05, FR-19, FR-26 |

Urutan mengikuti controller, bukan rancangan fitur baru. Diagram sequence pada [09](09-sequence-diagrams.md) menggunakan tiga alur yang sama.

## A-01 — Penyimpanan rekap lead

Sumber: `LeadController@index/store/update/save`, `LeadPolicy`, `Lead::isEditableBy`, migration lead. Diagram berfokus pada create/update; soft delete dijelaskan di UC-07 dan class/ERD.

```mermaid
flowchart TD
    Start([Mulai]) --> Auth{Login, aktif, password selesai?}
    Auth -->|Tidak| Redirect[Redirect middleware]
    Redirect --> End([Selesai])
    Auth -->|Ya| Mode{Create atau update?}
    Mode -->|Create| Cat{Kategori aktif?}
    Cat -->|Tidak| NotFound[404]
    Cat -->|Ya| Policy[Gate create]
    Mode -->|Update| Bind{Lead cocok dengan kategori?}
    Bind -->|Tidak| NotFound
    Bind -->|Ya| UpdateGate[Gate update: pemilik dan usia bagi CS]
    UpdateGate --> Allowed{Diizinkan?}
    Policy --> Allowed
    Allowed -->|Tidak| Forbidden[403]
    Allowed -->|Ya| Input[Validasi tanggal, sumber, pemilik non-CS dan jumlah]
    Input --> Valid{Valid?}
    Valid -->|Tidak| Error[Redirect dengan error dan input]
    Error --> End
    Valid -->|Ya| Normalize[CS menjadi pemilik sendiri; jumlah kosong menjadi 0]
    Normalize --> Dup{Kombinasi aktif sudah ada?}
    Dup -->|Ya| Duplicate[Redirect dengan duplicate_id; edit jika diizinkan]
    Duplicate --> End
    Dup -->|Tidak| High{Ada jumlah lebih dari 10 dan belum dikonfirmasi?}
    High -->|Ya| Warning[Tampilkan peringatan jumlah tinggi]
    Warning --> Confirm{Pilihan pengguna}
    Confirm -->|Periksa lagi| End
    Confirm -->|Ya, simpan| Resubmit[Kirim ulang confirm_high = 1]
    Resubmit --> Auth
    High -->|Tidak| Tx[Transaksi: save Lead dan record AuditLog]
    Tx --> Unique{Benturan unique constraint?}
    Unique -->|Ya| Race[Rollback; error muat ulang dan edit]
    Unique -->|Tidak| Success[Redirect daftar dengan status sukses]
    Race --> End
    Success --> End
    NotFound --> End
    Forbidden --> End
```

Create semua role dibatasi tanggal ≥hari ini minus 30 hari dan ≤hari ini. Update CS memakai batas sama; update non-CS tidak memiliki batas usia minimum. Update tidak menambahkan pengecekan kategori aktif seperti create. Exception selain benturan unique tidak memiliki pesan pemulihan custom di method ini; transaksi tetap mengikuti rollback framework.

## A-02 — Analitik dan ekspor

Sumber: `DashboardController@index/export/dashboardData`, `DashboardXlsxExport`, `dashboard.blade.php`.

```mermaid
flowchart TD
    Start([Mulai]) --> Access{Middleware mengizinkan?}
    Access -->|Tidak| Redirect[Redirect login atau wajib ganti password]
    Redirect --> End([Selesai])
    Access -->|Ya| Filter[Terima query from, to, category, source; staff untuk non-CS]
    Filter --> Validate{Filter valid; rentang inklusif maksimal 31 hari?}
    Validate -->|Tidak| Default[Buang seluruh filter; pakai awal bulan sampai hari ini; siapkan error]
    Validate -->|Ya| Accepted[Gunakan filter tervalidasi]
    Default --> Scope{Customer Service?}
    Accepted --> Scope
    Scope -->|Ya| Own[Query dibatasi user_id sendiri; staff diabaikan]
    Scope -->|Tidak| All[Query lintas staf atau staf terpilih]
    Own --> Query[Terapkan tanggal, kategori, sumber; hitung agregasi]
    All --> Query
    Query --> Type{Request index atau export?}
    Type -->|Index| Data{Ada hasil?}
    Data -->|Ya| View[Render total, SVG chart dan rincian 50 baris per halaman]
    Data -->|Tidak| Empty[Render empty state dan total nol]
    View --> DisplayError[Tampilkan error filter jika ada]
    Empty --> DisplayError
    DisplayError --> End
    Type -->|Export| Temp{Temporary file dapat dibuat?}
    Temp -->|Tidak| Fail[Exception; bersihkan resource yang dibuat]
    Temp -->|Ya| Lazy[Baca lead lazy 500; tulis XML worksheet]
    Lazy --> Zip{ZIP workbook berhasil?}
    Zip -->|Tidak| Fail
    Zip -->|Ya| Stream[Stream unduhan XLSX seluruh hasil; hapus temporary files]
    Stream --> End
    Fail --> End
```

Ekspor invalid-filter memakai default yang sama tetapi tidak merender pesan error halaman. CS dapat meminta endpoint langsung walaupun tombol tidak terlihat. Tidak ada query pembaruan database pada alur ini.

## A-03 — Reset dan pergantian password wajib

Sumber: `PasswordController@reset/update`, `AuthController@store`, `EnsurePasswordIsChanged`, `master-data.blade.php`, `password-change.blade.php`.

```mermaid
flowchart TD
    Start([Mulai]) --> S[Supervisor memilih Reset password dan konfirmasi]
    S --> Limit{Middleware dan rate limit mengizinkan?}
    Limit -->|Tidak| Block[Redirect atau penolakan; 429 bila dibatasi]
    Limit -->|Ya| Role{Aktor Supervisor?}
    Role -->|Tidak| Forbidden[403]
    Role -->|Ya| Self{Target sama dengan aktor?}
    Self -->|Ya| NotFound[404]
    Self -->|Tidak| Gen[Hasilkan password acak 16 karakter]
    Gen --> Tx[Transaksi: simpan hash, flag wajib ganti, audit tanpa password]
    Tx --> Once[Flash password sekali pada Master Data]
    Once --> Deliver[Supervisor menyampaikan password langsung kepada pemilik]
    Deliver --> Login[Pengguna login memakai password sementara dan role akun]
    Login --> Valid{Input, kredensial, status aktif dan role valid?}
    Valid -->|Tidak| LoginError[Error login; akun tetap tidak mendapat akses]
    LoginError --> Login
    Valid -->|Ya| Session[Login dan regenerate session]
    Session --> Forced[Redirect password.edit karena flag wajib ganti]
    Forced --> Form[Isi password saat ini, baru dan konfirmasi]
    Form --> Check{Password lama benar; baru minimal 8, berbeda dan cocok konfirmasi?}
    Check -->|Tidak| Error[Error validasi; flag tetap true]
    Error --> Form
    Check -->|Ya| Save[Hash password baru; flag false; regenerate session]
    Save --> Home[Redirect home sesuai role]
    Home --> End([Selesai])
    Block --> End
    Forbidden --> End
    NotFound --> End
```

Reset tidak mengaktifkan akun nonaktif. Penyampaian password adalah langkah manusia yang diarahkan teks UI, bukan pengiriman email otomatis. Tidak ada bukti mekanisme aplikasi yang mencabut semua session perangkat lain.
