# Pemweb OBE - Sistem Informasi UMKM Pesisir

## 1. Identitas Proyek
- **Mata Kuliah:** Pemrograman Web (OBE)
- **Studi Kasus:** Sistem Informasi & E-Commerce UMKM Wilayah Pesisir (Technopreneurship Komoditas Hasil Laut)
- **Pendekatan:** Pengembangan Web Bertahap (Incremental Development)
- **Nama / NIM:** Muhammad Aril Saputra - 2440304029

---

## 2. Deskripsi Halaman
Proyek ini merupakan implementasi halaman landing page berbasis **HTML5 Semantik murni** yang dirancang sebagai pondasi awal (*Tahap 1: Staging Dasar HTML*) untuk ekosistem digital UMKM Pesisir. 

Halaman ini berfungsi sebagai portal informasi, etalase katalog produk olahan hasil laut (seperti Keripik Ikan Tenggiri dan Terasi Udang Rebon), serta sarana komunikasi/inquiry interaktif antara konsumen dan pelaku usaha pesisir. Struktur dokumen mematuhi standar aksesibilitas web (WCAG 2.1) dan hierarki heading terstruktur (`h1` &rarr; `h2` &rarr; `h3`).

---

## 3. Catatan Fitur & Daftar Tugas Selesai (Checklist)

- [x] **Struktur Semantik Standar W3C:** Menggunakan `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<figure>`, `<figcaption>`, `<address>`, dan `<footer>`.
- [x] **Hierarki Heading Teratur:** Memiliki satu `<h1>` utama, dilanjutkan dengan `<h2>` pada setiap seksi konten, dan `<h3>` pada setiap kartu artikel produk.
- [x] **Aksesibilitas Gambar (WCAG 2.1):** 
  - Gambar konten informatif dilengkapi atribut `alt` deskriptif.
  - Elemen gambar dekoratif dilengkapi atribut `alt=""` dan `role="presentation"` / `aria-hidden="true"`.
- [x] **Formulir Kontak Semantik:** Form input dilengkapi pasangan `<label for="...">` dan `<input id="...">` yang terhubung secara eksplisit.
- [x] **Aksesibilitas Navigasi Keyboard (WCAG 2.1 Focus State):** Seluruh tautan navigasi dan kontrol formulir memiliki *focus indicator* yang sangat jelas (`:focus-visible` ring warna Sky Blue dengan outline-offset 2px), tanpa jebakan fokus (*no keyboard trap*).
- [x] **Arsitektur CSS & Design Tokens (Tahap 2):**
  - Terhubung dengan `styles.css` eksternal dan Google Fonts `Plus Jakarta Sans`.
  - CSS Reset modern dan universal `box-sizing: border-box`.
  - Custom properties (`:root`) untuk token warna maritim pesisir, skala spacing 8-pt, dan border radius.
  - Navigasi responsif berbasis **Flexbox** dan kartu etalase produk berbasis **CSS Grid** (termasuk grid internal kartu).
  - *Content-driven media queries* teruji pada viewport **320px, 768px, 1024px, dan 1280px+ (desktop)** dengan zero-overflow (`overflow-x = 0`).
- [x] **Interaktivitas JavaScript (Tahap 3):**
  - Array inventaris peralatan pesisir (`daftarAlat` / `inventaris`).
  - Fitur pencarian alat real-time (`#search`) case-insensitive dengan pesan ramah jika tidak ditemukan.
  - Fitur filter lokasi dan pencarian ID alat.
  - **Tombol Detail & Event Delegation (Praktikum 2):** Tombol dinamis dengan atribut dataset `data-detail`, ditangani menggunakan *single event listener* pada container induk (`#daftar-alat`) melalui event delegation, serta menampilkan dialog semantik HTML5 (`<dialog id="modal-detail">`).

---

## 4. Panduan Menjalankan Proyek (Laragon 5)

1. **Lokasi Direktori:**
   Pastikan folder repositori ini diletakkan pada direktori root web Laragon:
   ```text
   C:\laragon\www\pemweb-obe
   ```

2. **Menjalankan Server Lokal:**
   - Buka aplikasi **Laragon 5**.
   - Klik tombol **Start All** untuk menyalakan modul Apache dan MySQL.

3. **Mengakses Halaman Web:**
   Buka peramban web (Google Chrome / Mozilla Firefox / Microsoft Edge) dan akses URL berikut:
   - `http://localhost/pemweb-obe`
   - atau melalui virtual host Laragon: `http://pemweb-obe.test`

---

## 5. Audit & Log Penggunaan AI (AI Usage Governance)
Proses pengerjaan dan refaktorisasi kode pada modul ini didokumentasikan secara transparan sesuai kaidah integritas akademik RPS OBE. Rincian prompt, verifikasi arsitektur, dan justifikasi teknis dicatat secara berkala pada file:
👉 **[AI_USAGE_LOG.md](AI_USAGE_LOG.md)**
