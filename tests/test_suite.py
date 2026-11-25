#!/usr/bin/env python3
"""
Test Suite Otomatis - Sistem Informasi & E-Commerce UMKM Pesisir
Memenuhi spesifikasi Soal 3: Uji Authentication, Role (RBAC), CRUD, Validation, dan API Error.
Menggunakan pustaka standar Python (tanpa dependensi eksternal / pip).
"""

import sys
import json
import urllib.request
import urllib.error
import urllib.parse
import http.cookiejar

# ==============================================================================
# 1. Deteksi Target URL Server
# ==============================================================================
CANDIDATE_URLS = [
    "http://localhost/pemweb-obe",
    "http://localhost",
    "http://127.0.0.1:8088",
    "http://127.0.0.1:8000"
]

def detect_base_url():
    if len(sys.argv) > 1 and sys.argv[1].startswith("http"):
        return sys.argv[1].rstrip("/")
    
    for url in CANDIDATE_URLS:
        target = f"{url}/api/health.php"
        try:
            req = urllib.request.Request(target, headers={"User-Agent": "Python-Test-Runner"})
            with urllib.request.urlopen(req, timeout=1.5) as resp:
                if resp.status == 200:
                    return url
        except Exception:
            continue
    return None

# ==============================================================================
# 2. HTTP Client Berbasis Cookie & JSON
# ==============================================================================
class TestClient:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip("/")
        self.cookie_jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookie_jar))
        self.csrf_token = ""

    def request(self, method, endpoint, data=None, extra_headers=None):
        url = f"{self.base_url}{endpoint}"
        headers = {
            "User-Agent": "Python-OBE-TestRunner/1.0",
            "Accept": "application/json"
        }
        if extra_headers:
            headers.update(extra_headers)

        body_bytes = None
        if data is not None:
            headers["Content-Type"] = "application/json; charset=utf-8"
            body_bytes = json.dumps(data).encode("utf-8")

        req = urllib.request.Request(url, data=body_bytes, headers=headers, method=method.upper())

        try:
            with self.opener.open(req, timeout=5) as response:
                status_code = response.status
                raw = response.read().decode("utf-8")
                try:
                    parsed_json = json.loads(raw)
                except Exception:
                    parsed_json = None
                return status_code, parsed_json, raw
        except urllib.error.HTTPError as e:
            status_code = e.code
            raw = e.read().decode("utf-8")
            try:
                parsed_json = json.loads(raw)
            except Exception:
                parsed_json = None
            return status_code, parsed_json, raw
        except Exception as e:
            return 0, None, str(e)

# ==============================================================================
# 3. Runner & Assertion Engine
# ==============================================================================
total_cases = 0
passed_cases = 0

def assert_test(label, condition, detail=""):
    global total_cases, passed_cases
    total_cases += 1
    if condition:
        passed_cases += 1
        print(f"[PASS] {label}")
    else:
        print(f"[FAIL] {label}" + (f" | {detail}" if detail else ""))

def main():
    base_url = detect_base_url()
    if not base_url:
        print("=" * 70)
        print("ERROR: Server API lokal tidak terdeteksi.")
        print("Pastikan Laragon (Apache) aktif atau jalankan server lokal:")
        print("  php -S 127.0.0.1:8088 -t .")
        print("\nContoh penggunaan manual:")
        print("  python tests/test_suite.py http://127.0.0.1:8088")
        print("  python tests/test_suite.py http://localhost/pemweb-obe")
        print("=" * 70)
        sys.exit(1)

    print("=" * 70)
    print("PENGUJIAN SISTEM API: UMKM PESISIR (PYTHON TEST RUNNER)")
    print(f"Target Base URL : {base_url}")
    print(f"Python Version  : {sys.version.split()[0]}")
    print("=" * 70)
    print()

    client = TestClient(base_url)

    # --------------------------------------------------------------------------
    # MODUL 1: Utilitas & Pembangkitan CSRF Token
    # --------------------------------------------------------------------------
    print("--- 1. MODUL UTILITAS & CSRF TOKEN ---")
    code, json_data, _ = client.request("GET", "/api/health.php")
    assert_test("1.1 Cek Health Check API (200 OK)", code == 200 and json_data.get("status") == "OK")

    code, json_data, _ = client.request("GET", "/api/csrf.php")
    client.csrf_token = (json_data or {}).get("csrf_token", "")
    assert_test(
        "1.2 Ambil Token CSRF Aktif (Hex 64 Karakter)",
        code == 200 and len(client.csrf_token) == 64,
        f"Token: {client.csrf_token[:16]}..."
    )
    print()

    # --------------------------------------------------------------------------
    # MODUL 2: Autentikasi Pengguna & Sesi (Authentication)
    # --------------------------------------------------------------------------
    print("--- 2. MODUL AUTENTIKASI & MANAJEMEN SESI ---")
    # Login dengan password salah
    code, json_data, _ = client.request("POST", "/api/login.php", {
        "email": "admin@pesisir.id",
        "password": "password_salah_123",
        "csrf_token": client.csrf_token
    })
    assert_test("2.1 Login Password Salah Ditolak (401 Unauthorized)", code == 401)

    # Login kredensial valid Admin
    code, json_data, _ = client.request("POST", "/api/login.php", {
        "email": "admin@pesisir.id",
        "password": "password123",
        "csrf_token": client.csrf_token
    })
    if json_data and "csrf_token" in json_data:
        client.csrf_token = json_data["csrf_token"]
    assert_test("2.2 Login Admin Berhasil (200 OK)", code == 200 and json_data.get("status") == "success")

    # Cek profil akun yang sedang aktif di sesi
    code, json_data, _ = client.request("GET", "/api/me.php")
    user_info = (json_data or {}).get("data", {}).get("user", {}) or (json_data or {}).get("data", {})
    role_aktif = user_info.get("role")
    assert_test("2.3 Endpoint Profil /api/me.php Mengonfirmasi Role Admin", code == 200 and role_aktif == "admin")
    print()

    # --------------------------------------------------------------------------
    # MODUL 3: Role-Based Access Control (RBAC)
    # --------------------------------------------------------------------------
    print("--- 3. MODUL ROLE-BASED ACCESS CONTROL (RBAC) ---")
    # Buat client baru khusus sesi Viewer
    viewer_client = TestClient(base_url)
    _, csrf_viewer_resp, _ = viewer_client.request("GET", "/api/csrf.php")
    viewer_client.csrf_token = (csrf_viewer_resp or {}).get("csrf_token", "")

    code, json_data, _ = viewer_client.request("POST", "/api/login.php", {
        "email": "nelayan.berkah@pesisir.id",
        "password": "password123",
        "csrf_token": viewer_client.csrf_token
    })
    if json_data and "csrf_token" in json_data:
        viewer_client.csrf_token = json_data["csrf_token"]
    assert_test("3.1 Login Akun Viewer Berhasil (200 OK)", code == 200)

    # Viewer mencoba menghapus alat (harus ditolak 403 Forbidden)
    code, json_data, _ = viewer_client.request("DELETE", "/api/alats.php?id=1", {
        "csrf_token": viewer_client.csrf_token
    })
    assert_test("3.2 Akun Viewer Ditolak Saat Mencoba DELETE Alat (403 Forbidden)", code == 403)

    # Viewer mencoba menambah alat baru (harus ditolak 403 Forbidden)
    code, json_data, _ = viewer_client.request("POST", "/api/alats.php", {
        "csrf_token": viewer_client.csrf_token,
        "nama": "Alat Ilegal Viewer",
        "kategori": "Tangkap",
        "jumlah": 1,
        "kondisi": "Baik",
        "lokasi": "Gudang A"
    })
    assert_test("3.3 Akun Viewer Ditolak Saat Mencoba POST Alat (403 Forbidden)", code == 403)
    print()

    # --------------------------------------------------------------------------
    # MODUL 4: Operasi CRUD Inventaris Alat (Admin)
    # --------------------------------------------------------------------------
    print("--- 4. MODUL OPERASI CRUD INVENTARIS ALAT (ADMIN) ---")
    # Read: Ambil daftar alat
    code, json_data, _ = client.request("GET", "/api/alats.php?limit=5&offset=0")
    total_alat = (json_data or {}).get("total", 0)
    assert_test("4.1 Read: Ambil Daftar Alat & Paginasi (200 OK)", code == 200 and total_alat >= 0)

    # Create: Tambah alat baru
    new_alat_payload = {
        "csrf_token": client.csrf_token,
        "nama": "Coolbox Nelayan Python Test",
        "kategori": "Penyimpanan",
        "jumlah": 8,
        "kondisi": "Baik",
        "lokasi": "Dermaga 1"
    }
    code, json_data, _ = client.request("POST", "/api/alats.php", new_alat_payload)
    created_id = (json_data or {}).get("data", {}).get("id") or (json_data or {}).get("id")
    assert_test("4.2 Create: Tambah Data Alat Baru (201 Created)", code == 201 and created_id is not None)

    target_id = created_id if created_id else 1

    # Read: Detail berdasarkan ID
    code, json_data, _ = client.request("GET", f"/api/alats.php?id={target_id}")
    assert_test(f"4.3 Read: Ambil Detail Alat ID {target_id} (200 OK)", code == 200 and json_data.get("status") == "success")

    # Update: Perbarui data alat
    update_payload = {
        "csrf_token": client.csrf_token,
        "nama": "Coolbox Nelayan Python Diperbarui",
        "kategori": "Penyimpanan",
        "jumlah": 10,
        "kondisi": "Baik",
        "lokasi": "Gudang A"
    }
    code, json_data, _ = client.request("PUT", f"/api/alats.php?id={target_id}", update_payload)
    assert_test(f"4.4 Update: Perbarui Data Alat ID {target_id} (200 OK)", code == 200)

    # Delete: Hapus data alat yang baru dibuat
    if created_id:
        code, json_data, _ = client.request("DELETE", f"/api/alats.php?id={created_id}", {
            "csrf_token": client.csrf_token
        })
        assert_test(f"4.5 Delete: Hapus Data Alat ID {created_id} (200 OK)", code == 200)
    else:
        assert_test("4.5 Delete: Skip Hapus (ID baru tidak terbit)", True)
    print()

    # --------------------------------------------------------------------------
    # MODUL 5: Validasi Input & Pemesanan Layanan
    # --------------------------------------------------------------------------
    print("--- 5. MODUL VALIDASI INPUT & PEMESANAN ---")
    # Validasi input alat kosong / salah tipe
    code, json_data, _ = client.request("POST", "/api/alats.php", {
        "csrf_token": client.csrf_token,
        "nama": "",
        "kategori": "",
        "jumlah": -10
    })
    assert_test("5.1 Validasi Input: Form Alat Kosong/Negatif Ditolak (422/400)", code in [400, 422])

    # Pemesanan layanan valid
    pesanan_payload = {
        "csrf_token": client.csrf_token,
        "nama": "Koperasi Nelayan Pesisir",
        "kontak": "081298765432",
        "kategori": "Terasi Udang Rebon Asli",
        "jumlah": 5,
        "catatan": "Uji otomatisasi via Python test suite"
    }
    code, json_data, _ = client.request("POST", "/api/pemesanan.php", pesanan_payload)
    assert_test("5.2 Kirim Pemesanan Produk/Alat Valid (200/201 OK)", code in [200, 201])
    print()

    # --------------------------------------------------------------------------
    # MODUL 6: Penanganan Error API & Keamanan
    # --------------------------------------------------------------------------
    print("--- 6. MODUL PENANGANAN API ERROR & KEAMANAN ---")
    # Request ID tidak terdaftar
    code, json_data, _ = client.request("GET", "/api/alats.php?id=999999")
    assert_test("6.1 API Error: Request ID Tidak Ditemukan (404 Not Found)", code == 404)

    # Request parameter ID dengan format tidak valid (harus angka positif)
    code, json_data, _ = client.request("GET", "/api/alats.php?id=invalid_id_format")
    assert_test("6.2 API Error: Format Parameter ID Tidak Valid (400 Bad Request)", code == 400)

    # Mutasi data tanpa CSRF token
    code, json_data, _ = client.request("POST", "/api/alats.php", {
        "nama": "Alat Tanpa Token",
        "kategori": "Tangkap",
        "jumlah": 5,
        "kondisi": "Baik",
        "lokasi": "Gudang A"
    })
    assert_test("6.3 Keamanan: Mutasi Data Tanpa Token CSRF Ditolak (403 Forbidden)", code == 403)
    print()

    # --------------------------------------------------------------------------
    # RINGKASAN HASIL PENGUJIAN
    # --------------------------------------------------------------------------
    print("=" * 70)
    print(f"RINGKASAN AKHIR: {passed_cases} dari {total_cases} Kasus Uji Lulus (PASS)")
    if passed_cases == total_cases:
        print("Status: SEMUA KASUS UJI LULUS SEMPURNA")
    else:
        print("Status: TERDAPAT KASUS UJI YANG GAGAL")
    print("=" * 70)

    if passed_cases != total_cases:
        sys.exit(1)

if __name__ == "__main__":
    main()
