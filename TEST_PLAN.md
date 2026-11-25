# Rencana Pengujian Sistem (TEST_PLAN.md)
## Sistem Informasi & E-Commerce UMKM Wilayah Pesisir

---

## 1. Pendahuluan dan Ruang Lingkup

Dokumen ini mendokumentasikan perencanaan, metodologi, skenario, dan hasil pengujian menyeluruh untuk aplikasi **Sistem Informasi & E-Commerce UMKM Wilayah Pesisir**. Pengujian dilakukan untuk memastikan bahwa seluruh modul inti, mekanisme otorisasi peran, validasi input, mitigasi kerentanan keamanan web, serta antarmuka responsif beroperasi sesuai dengan spesifikasi fungsional dan standar kualitas perangkat lunak.

### 1.1 Modul dan Fitur Utama yang Diuji
1. **Autentikasi & Manajemen Sesi:** Login dengan verifikasi sandi Bcrypt, regenerasi ID sesi, cookie sesi aman (`HttpOnly`, `SameSite=Lax`, `Secure` pada HTTPS), logout, dan endpoint identitas sesi (`/api/me.php`).
2. **Role-Based Access Control (RBAC):** Penegakan hak akses multi-peran (`admin`, `petugas`, dan `viewer`) pada seluruh operasi backend dan elemen interaktif frontend.
3. **Manajemen Data (CRUD):** Operasi Create, Read, Update, dan Delete untuk entitas Inventaris Peralatan Nelayan dan Pemesanan Layanan secara asinkron (RESTful JSON).
4. **Validasi Input & Integritas Data:** Pemeriksaan tipe data, panjang string, nilai numerik positif, email, nomor kontak, serta parameter sanitasi.
5. **Keamanan Web (Security Hardening):**
   - Proteksi Cross-Site Request Forgery (CSRF) menggunakan token acak 256-bit berbasis sesi dan verifikasi waktu-konstan (`hash_equals`).
   - Mitigasi Cross-Site Scripting (XSS) melalui sanitasi input dan rendering DOM berbasis `textContent` serta `escapeHtml()`.
   - Mitigasi SQL Injection pada seluruh query basis data termasuk parameter pagination (`LIMIT` dan `OFFSET`) menggunakan PDO Prepared Statements.
   - Penanganan error produksi tanpa membocorkan trace internal server atau kredensial database.
6. **Alur Responsif & Aksesibilitas (Responsive & Accessible Flow):** Desain adaptif dari layar smartphone (375px), tablet (768px), hingga desktop (1280px+), serta kepatuhan standar aksesibilitas WCAG 2.1 (Lighthouse audit).

---

## 2. Lingkungan dan Konfigurasi Pengujian

Pengujian baseline lokal dijalankan pada konfigurasi lingkungan berikut:
- **Web Server:** Laragon 5 (Apache 2.4.54 / Nginx) & PHP Built-in Server
- **Bahasa Pemrograman:** PHP 8.5.11 (NTS, Win64 / Ubuntu 22.04 LTS pada CI)
- **Basis Data:** MySQL 8.0.30 / MariaDB 10.6 (Port: 3306, Database: `pemweb_obe`)
- **Peramban Uji:** Google Chrome (Versi terbaru) dengan DevTools & Lighthouse Engine
- **Akun Pengujian:**
  - `admin@pesisir.id` (Peran: `admin`, Hak: Akses Penuh CRUD dan Manajemen Pengguna)
  - `aril@pesisir.id` (Peran: `petugas`, Hak: Read, Create, Update Data Operasional)
  - `nelayan.berkah@pesisir.id` (Peran: `viewer`, Hak: Read-Only, Ditolak saat CUD)
  - Kata sandi default akun pengujian: `password123`

---

## 3. Matriks Kasus Uji (Test Cases Matrix)

Tabel berikut memuat 13 kasus uji formal yang mencakup seluruh skenario fungsional, keamanan, validasi, dan alur responsif sistem:

| Test ID | Modul / Skenario | Langkah Pengujian | Data Input / Payload | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-01** | Autentikasi Pengguna Valid | Masukkan email dan password akun admin pada form login, klik Masuk | Email: `admin@pesisir.id`<br>Password: `password123`<br>CSRF Token Valid | HTTP 200 OK, sesi login aktif, cookie HttpOnly terbit, diarahkan ke dashboard | HTTP 200 OK, sesi admin aktif, data user tersimpan di sesi | **PASS** |
| **TC-02** | Autentikasi Kredensial Salah | Masukkan password yang salah untuk akun terdaftar | Email: `admin@pesisir.id`<br>Password: `salah123` | HTTP 401 Unauthorized, pesan error "Email atau kata sandi tidak valid", sesi tidak dibuat | HTTP 401 Unauthorized, pesan error deskriptif, sesi kosong | **PASS** |
| **TC-03** | Proteksi CSRF Login | Kirim request POST ke `/api/login.php` dengan token CSRF tidak valid atau kosong | Email: `admin@pesisir.id`<br>Password: `password123`<br>CSRF Token: `token_palsu_123` | HTTP 403 Forbidden, pesan "Validasi token CSRF gagal. Permintaan ditolak." | HTTP 403 Forbidden, request login langsung diblokir | **PASS** |
| **TC-04** | Role Access Control (Viewer CUD) | Login sebagai `viewer`, lakukan request DELETE ke `/api/alats.php?id=1` | Akun: `nelayan.berkah@pesisir.id`<br>Method: `DELETE`<br>Target ID: 1 | HTTP 403 Forbidden, pesan error hak akses akun viewer ditolak | HTTP 403 Forbidden, role viewer ditolak melakukan mutasi data | **PASS** |
| **TC-05** | CRUD: Tambah Alat Baru (Admin) | Login sebagai admin, isi modal form tambah alat dengan data valid | Nama: "Jaring Tarik Baru"<br>Kategori: "Tangkap"<br>Jumlah: 15<br>Kondisi: "Baik"<br>Lokasi: "Gudang A" | HTTP 201 Created, entitas tersimpan di basis data, muncul di tabel inventaris | HTTP 201 Created, record baru tersimpan dengan ID baru | **PASS** |
| **TC-06** | CRUD: Pencarian & Filter Data | Masukkan kata kunci pencarian pada input cari di katalog halaman utama | Keyword: "Terasi" atau "Jaring" | HTTP 200 OK, daftar kartu alat/produk hanya menampilkan item yang relevan | HTTP 200 OK, data terfilter secara instan dan tepat | **PASS** |
| **TC-07** | CRUD: Perbarui Data Alat | Pilih alat terdaftar, ubah jumlah stok dan kondisi, simpan pembaruan | Target ID: 2<br>Jumlah baru: 25<br>Kondisi: "Perlu Cek" | HTTP 200 OK, data alat terupdate pada basis data dan antarmuka | HTTP 200 OK, pesan konfirmasi update berhasil | **PASS** |
| **TC-08** | CRUD: Hapus Alat (Admin) | Login sebagai admin, klik tombol hapus pada baris alat, konfirmasi dialog | Target ID alat uji coba valid | HTTP 200 OK, record terhapus dari basis data, baris tabel terhapus | HTTP 200 OK, pesan sukses hapus, record terhapus | **PASS** |
| **TC-09** | Validasi Input Form Kosong | Submit form pemesanan layanan dengan mengosongkan nama pemesan dan nomor HP | Nama: `""`<br>Kontak: `""`<br>Kategori: `""` | Validasi client-side HTML5 memblokir submit, atau API merespons HTTP 400/422 | Form memicu invalid tooltip dan API merespons error validasi 400 | **PASS** |
| **TC-10** | Error Handling API (ID Tidak Ada) | Request data detail alat dengan ID numerik yang tidak ada di basis data | Request: `GET /api/alats.php?id=999999` | HTTP 404 Not Found, response JSON terstruktur `{"status":"error","message":"Data alat ... tidak ditemukan"}` | HTTP 404 Not Found, format JSON bersih tanpa kebocoran PHP trace | **PASS** |
| **TC-11** | Pencegahan Serangan XSS | Inputkan string payload JavaScript pada kolom pencarian dan form kontak | Payload: `<script>alert('XSS')</script><img src=x onerror=alert(1)>` | Karakter dirender sebagai literal text via `textContent`, script tidak dieksekusi oleh browser | Payload tampil utuh sebagai teks statis, tidak ada alert popup | **PASS** |
| **TC-12** | Mitigasi SQL Injection | Kirimkan payload SQL injection pada parameter pagination `limit` dan `offset` | Request: `GET /api/alats.php?limit=10;DROP TABLE alat;--` | Parameter divalidasi sebagai integer positif dan dibind via `PDO::PARAM_INT`, tidak ada syntax injection | HTTP 400 Bad Request atau query dieksekusi aman tanpa perubahan struktur tabel | **PASS** |
| **TC-13** | Alur Tampilan Responsif (Mobile Flow) | Akses portal dan dashboard pada viewport lebar 375px (Mobile) dan 768px (Tablet) | Viewport: 375x667 (iPhone SE/12), 768x1024 (iPad) | Tata letak beradaptasi menjadi 1 kolom, navbar terbungkus rapi, tabel dapat digeser horizontal | Tata letak fleksibel, tidak ada overflow halaman horizontal yang patah | **PASS** |

---

## 4. Hasil Audit Kualitas & Aksesibilitas (Lighthouse)

Audit kualitas dijalankan menggunakan Google Chrome Lighthouse Engine pada halaman beranda (`index.html`) dan antarmuka dashboard.

### 4.1 Temuan Isu Awal (Baseline Audit)
1. **Accessibility - Input Tanpa Label yang Terbaca:** Elemen input pencarian pada dashboard menyembunyikan label menggunakan inline style `display:none`, yang menyebabkan pembaca layar (screen reader) mengabaikan label input.
2. **Accessibility - Dialog Close Button Kurang Deskriptif:** Tombol penutup modal detail alat tidak memiliki atribut `aria-label` yang eksplisit.
3. **Performance & SEO - Atribut Gambar dan Layout Shift:** Penggunaan atribut HTML `width="100%"` alih-alih style responsif CSS serta ketiadaan atribut `loading="lazy"` pada gambar katalog bawah halaman memicu potensi Cumulative Layout Shift (CLS) dan membebani pemuatan awal.
4. **SEO - Meta Description Tidak Ada:** Tag `<meta name="description">` belum didefinisikan pada dokumen utama.

### 4.2 Perbaikan Isu Kualitas yang Diimplementasikan
1. **Perbaikan Utilitas Aksesibilitas (Screen Reader):**
   - Menambahkan utilitas CSS `.sr-only` berbasis standar WCAG 2.1 pada `styles.css`.
   - Mengganti `display:none` pada label pencarian dengan kelas `.sr-only`, serta menambahkan label eksplisit pada input pencarian pemesanan.
2. **Perbaikan Aksesibilitas Tombol & Modal:**
   - Menambahkan atribut `aria-label="Tutup dialog detail inventaris alat"` pada tombol penutup modal `#btn-tutup-detail`.
   - Melengkapi elemen input dan bantuan form pemesanan dengan `aria-describedby` dan `aria-required`.
3. **Optimalisasi Media dan Metadata Halaman:**
   - Menambahkan tag `<meta name="description" content="Portal Sistem Informasi & E-Commerce UMKM Wilayah Pesisir. Layanan pengadaan olahan laut dan inventaris sarana nelayan.">`.
   - Menetapkan dimensi rasio eksplisit (`width="400" height="267"`) serta atribut `loading="lazy"` pada gambar katalog produk.
   - Mengganti atribut inline `width="100%"` pada ilustrasi hero dengan CSS style responsif `style="width: 100%; height: auto;"`.

### 4.3 Hasil Audit Ulang (Post-Fix Verification)
- **Performance:** Mengalami peningkatan pemuatan awal berkat penangguhan gambar (*lazy loading*).
- **Accessibility:** Meningkat ke skor optimal (95 - 100) setelah perbaikan label dan atribut ARIA.
- **Best Practices:** Memenuhi standar modern web development (HTTPS ready, rel="noopener" pada external link, sanitasi input).
- **SEO:** Meningkat ke skor optimal berkat kelengkapan meta tag deskripsi, judul semantik, dan struktur heading hierarkis.

---

## 5. Konfigurasi Deployment & Keamanan Produksi

Aplikasi dirancang untuk dapat dideploy pada lingkungan staging dan production yang aman:

1. **Pencegahan Kebocoran Error (Error Suppression):**
   - Pada file `api/alats.php`, `api/pemesanan.php`, `api/stats.php`, dan `api/login.php`, seluruh alur eksekusi dibungkus dalam blok `try { ... } catch (Throwable $e) { ... }`.
   - Konfigurasi `ini_set('display_errors', '0')` diterapkan secara default.
   - Apabila terjadi kendala pada server, klien hanya menerima pesan umum berformat JSON:
     ```json
     {
       "status": "error",
       "code": 500,
       "message": "Terjadi kesalahan internal pada server. Silakan coba beberapa saat lagi."
     }
     ```
   - Rincian error dan stack trace dicatat secara privat ke file internal log server via `error_log()`, sehingga jalur direktori lokal dan data sensitif tidak pernah bocor ke pengguna publik.

2. **Isolasi Secret & File Sensitif:**
   - File `.env` yang memuat kredensial koneksi basis data dimasukkan ke dalam daftar `.gitignore` dan tidak pernah di-commit ke repository publik.
   - File `.env.example` disediakan sebagai acuan konfigurasi publik dengan nilai kredensial kosong/dummy.
   - Konfigurasi `.htaccess` menerapkan proteksi ketat dengan memblokir akses HTTP langsung ke file `.env*`, `.git*`, `config/`, `database/`, `vendor/`, dan `tests/` dengan respons HTTP 403 Forbidden:
     ```apache
     <FilesMatch "^(\.env|\.git)">
         Require all denied
     </FilesMatch>
     ```

---

## 6. Integrasi Berkelanjutan (Continuous Integration Pipeline)

Untuk menjamin kualitas kode secara otomatis, pipeline CI dikonfigurasikan melalui GitHub Actions (`.github/workflows/ci.yml`).

### Tahapan Otomatis Pipeline CI:
1. **Linting & Syntax Check:** Menjalankan `php -l` pada seluruh file PHP dalam direktori proyek guna mendeteksi kesalahan sintaksis sebelum kode digabungkan.
2. **Automated Security Unit Test:** Menjalankan script pengujian `tests/test_csrf.php` untuk memvalidasi algoritma pembangkitan token CSRF 256-bit, integrasi helper request, dan perbandingan aman berbasis `hash_equals()`.
3. **Execution Guard:** Pipeline secara otomatis berjalan pada setiap `push` dan `pull_request` ke cabang utama (`main` dan `security-review`).

---

## 7. Kesimpulan

Seluruh 13 skenario pengujian fungsional, otorisasi peran, validasi input, mitigasi keamanan (CSRF, XSS, SQLi), dan optimasi aksesibilitas telah dijalankan dan diverifikasi dengan status **PASS**. Aplikasi telah siap digunakan pada lingkungan lokal Laragon 5 maupun lingkungan produksi/staging dengan postur keamanan yang teruji.
