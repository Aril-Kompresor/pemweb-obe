# Pemweb OBE - Sistem Informasi UMKM Pesisir

## 1. Identitas Proyek
- **Mata Kuliah:** Pemrograman Web (OBE)
- **Studi Kasus:** Sistem Informasi & E-Commerce UMKM Wilayah Pesisir (Technopreneurship Komoditas Hasil Laut)
- **Pendekatan:** Pengembangan Web Bertahap (Incremental Development)
- **Nama / NIM:** [Nama / NIM Mahasiswa]

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
- [x] **Aksesibilitas Navigasi Keyboard:** Seluruh tautan navigasi dan kontrol formulir dapat dijelajahi dengan urutan fokus logis (*Tab Order*) tanpa jebakan fokus (*no keyboard trap*).
- [x] **Pembersihan CSS:** Kode murni HTML5 semantik tanpa dependensi CSS eksternal maupun inline style untuk staging awal.

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
