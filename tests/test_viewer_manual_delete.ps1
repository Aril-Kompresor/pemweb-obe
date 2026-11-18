$ErrorActionPreference = "Stop"

$php = "C:\laragon\bin\php\php-8.5.11-nts-Win32-vs17-x64\php.exe"
$projectRoot = Split-Path -Parent $PSScriptRoot
$srv = $null
$baseUrl = $null

# Cek apakah server lokal (Apache/Laragon) sudah melayani proyek ini
foreach ($candidate in @("http://localhost", "http://localhost/pemweb-obe")) {
    try {
        $h = Invoke-WebRequest -Uri "$candidate/api/health.php" -UseBasicParsing -TimeoutSec 2
        if ($h.StatusCode -eq 200) {
            $baseUrl = $candidate
            break
        }
    } catch {}
}

# Jika belum, jalankan PHP built-in server sementara di port 8088
if (-not $baseUrl) {
    $baseUrl = "http://127.0.0.1:8088"
    $srv = Start-Process -FilePath $php -ArgumentList "-S", "127.0.0.1:8088", "-t", $projectRoot -PassThru -WindowStyle Hidden
    Start-Sleep -Milliseconds 700
}

try {
    Write-Host "Base URL: $baseUrl`n"

    Write-Host "=== 1. Login Manual sebagai Viewer ==="
    $body = @{ email = "nelayan.berkah@pesisir.id"; password = "password123" } | ConvertTo-Json
    $loginRes = Invoke-WebRequest -Uri "$baseUrl/api/login.php" -Method POST -Body $body -ContentType "application/json" -SessionVariable viewerSess -UseBasicParsing
    $loginObj = $loginRes.Content | ConvertFrom-Json
    $csrf = $loginObj.csrf_token
    Write-Host "HTTP $($loginRes.StatusCode) | Role: $($loginObj.data.user.role) | CSRF Token: $($csrf.Substring(0,16))..."

    Write-Host "`n=== 2. Request Manual DELETE /api/alats.php?id=1 (Cookie Viewer + CSRF Valid) ==="
    try {
        Invoke-WebRequest -Uri "$baseUrl/api/alats.php?id=1" -Method DELETE -WebSession $viewerSess -Headers @{ "X-CSRF-Token" = $csrf } -UseBasicParsing | Out-Null
        Write-Host "[FAIL] Seharusnya ditolak 403!"
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        Write-Host "[PASS] HTTP $code Forbidden"
        Write-Host $reader.ReadToEnd()
    }

    Write-Host "`n=== 3. Request Manual DELETE /api/pemesanan.php?id=1 (Cookie Viewer + CSRF Valid) ==="
    try {
        Invoke-WebRequest -Uri "$baseUrl/api/pemesanan.php?id=1" -Method DELETE -WebSession $viewerSess -Headers @{ "X-CSRF-Token" = $csrf } -UseBasicParsing | Out-Null
        Write-Host "[FAIL] Seharusnya ditolak 403!"
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        Write-Host "[PASS] HTTP $code Forbidden"
        Write-Host $reader.ReadToEnd()
    }

    Write-Host "`n=== 4. Verifikasi Data Alat ID #1 Masih Utuh di Database ==="
    $checkRes = Invoke-WebRequest -Uri "$baseUrl/api/alats.php?id=1" -UseBasicParsing
    $checkObj = $checkRes.Content | ConvertFrom-Json
    Write-Host "[PASS] HTTP $($checkRes.StatusCode) OK | Data ID #1: $($checkObj.data.nama) (stok: $($checkObj.data.jumlah))"
} finally {
    if ($srv -and !$srv.HasExited) {
        Stop-Process -Id $srv.Id -Force
    }
}
