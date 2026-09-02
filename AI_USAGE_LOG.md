# AI Usage & Architectural Governance Log
**Document Version:** 1.0.0-PROD  
**Compliance Standard:** ISO/IEC 25010 (Software Quality) & W3C WCAG 2.1 Accessibility Guidelines  
**Project Scope:** Sistem Informasi & E-Commerce UMKM Pesisir (Incremental OBE Web Architecture)  
**Lead Engineer / Author:** Mahasiswa Praktikum Pemrograman Web  

---

## 1. Executive Summary & AI Governance Framework

Dokumen ini merekam secara komprehensif, terstruktur, dan dapat diaudit (*fully auditable*) mengenai pemanfaatan *Generative Artificial Intelligence* (LLM) dalam siklus rekayasa perangkat lunak pada repositori proyek ini. Penerapan AI diposisikan secara ketat sebagai **arsitek pendamping (*pair-programming advisor*) dan akselerator *scaffolding***, di mana setiap keluaran (*output*) wajib melalui proses verifikasi statis, audit aksesibilitas, pengujian fungsional keyboard lokal, serta validasi integritas semantik DOM sebelum dideploy ke basis kode utama.

---

## 2. Comprehensive AI Interaction & Verification Matrix

| No | Timestamp (WITA) | Architectural Phase / Objective | Prompt Strategy & Input Specification | AI Generated Solution (Synthesized Output) | Independent Verification & Audit Protocol | Final Engineering Decision & Rationale |
|:--:|:-----------------|:--------------------------------|:--------------------------------------|:-------------------------------------------|:------------------------------------------|:---------------------------------------|
| **01** | `2026-09-02 09:30` | **DOM Hierarchy Refactoring & Semantic Staging** | *Constraint-Driven Contextual Prompt:* Rekonstruksi struktur lama yang rusak (div-heavy, tag nesting error) menjadi standar HTML5 semantik sesuai RPS UMKM Pesisir. | Dekomposisi arsitektur dokumen menjadi `<header>`, `<nav>`, `<main>`, multiple `<section>`, `<article>`, dan `<footer>` yang valid secara sintaks W3C. | **Static Code Analysis & AST Inspection:** Memastikan tidak ada overlapping tag, closing tag prematur, serta memvalidasi tree node DOM agar terstruktur linier. | **ADOPTED (Full Refactor):** Menghapus struktur invalid lama untuk menjamin kestabilan parsing pada seluruh *rendering engine* peramban modern. |
| **02** | `2026-09-02 09:35` | **WCAG 2.1 Non-Text Content Accessibility Strategy** | *Few-Shot Prompting:* Bangun pola implementasi `alt` text untuk produk informatif vs elemen ornamen visual murni dekoratif. | Penyusunan atribut `alt` kontekstual pada foto produk hasil laut (keripik tenggiri, terasi rebon) serta penyematan `alt=""` dan `role="presentation"` pada ornamen SVG. | **Screen Reader Emulation Audit:** Menguji keterbacaan teks alternatif menggunakan pembaca layar; memverifikasi bahwa ornamen estetika tidak menimbulkan *auditory clutter*. | **ADOPTED (WCAG Compliant):** Memastikan platform inklusif bagi penyandang disabilitas penglihatan sesuai standar aksesibilitas web internasional. |
| **03** | `2026-09-02 09:40` | **Accessible Form Association & Tab-Order Integrity** | *Explicit Requirement Prompt:* Rancang formulir kontak dengan keterikatan kontrol input eksplisit dan kesiapan navigasi keyboard. | Pembuatan elemen `<form>` dengan pasangan eksplisit `<label for="[id]">` dan `<input id="[id]">`, `<textarea>`, serta tombol submit native. | **Keyboard-Only Traversal Test (`Tab` / `Shift+Tab`):** Memverifikasi urutan fokus logis (*logical focus order*), ketiadaan jebakan fokus (*no keyboard trap*), dan trigger submit via `Enter`/`Space`. | **ADOPTED (Zero-Defect):** Menghilangkan risiko disorientasi navigasi keyboard bagi pengguna tunadaksa maupun power-user. |
| **04** | `2026-09-02 09:43` | **Strict Separation of Concerns (Zero-CSS Isolation)** | *Refinement & Negative Constraint Prompt:* Bersihkan dependensi presentasi visual (CSS internal/inline) untuk isolasi modul HTML Tahap 1. | Penghapusan total tag `<style>` dan atribut inline `style="..."` tanpa merusak keterbacaan maupun semantik konten markup. | **RegEx Code Audit (`/style/gi`):** Pemindaian menyeluruh pada file `index.html` untuk memastikan zero-occurrence terhadap sintaks CSS styling. | **ADOPTED (Clean Architecture):** Memisahkan secara disiplin lapisan struktur dokumen (HTML) dari lapisan presentasi visual (CSS) yang akan dikerjakan bertahap pada Tahap 2. |

---

## 3. Quality Assurance & Academic Integrity Declaration

1. **Human-in-the-Loop Supervision:** Setiap baris markup HTML yang dihasilkan telah ditinjau secara manual (*peer-reviewed*) dan disesuaikan dengan studi kasus lokal (UMKM Pesisir & Technopreneurship).
2. **Deterministic Code Ownership:** Mahasiswa memahami secara menyeluruh fungsi setiap tag, relasi antar-node dokumen, serta implikasi aksesibilitas dari kode yang ditulis.
3. **Reproducibility & Traceability:** Seluruh riwayat perintah prompt, perbaikan iteratif, dan commit terdokumentasi secara transparan di dalam sistem kontrol versi Git.