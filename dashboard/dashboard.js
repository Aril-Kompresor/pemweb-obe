/**
 * Dashboard Manajemen UMKM Pesisir
 * Mengintegrasikan Operasi CRUD REST API (GET collection, GET by id, POST, PUT, DELETE)
 * Sesuai Standar Aksesibilitas WCAG 2.1 AA
 */

// State Aplikasi
let stateAlats = [];
let statePemesanan = [];
let pendingDeleteItem = null; // Menyimpan data { id, nama, type } untuk konfirmasi hapus

// URL Endpoint API
const API_ALATS = '../api/alats.php';
const API_PEMESANAN = '../api/pemesanan.php';
const API_STATS = '../api/stats.php';

// Helper: Tampilkan Notifikasi Global (Live Region)
function showGlobalAlert(message, type = 'success') {
  const alertEl = document.getElementById('global-alert');
  if (!alertEl) return;

  alertEl.className = `alert-banner alert-${type} is-visible`;
  alertEl.innerHTML = `
    <span>${message}</span>
    <button type="button" class="alert-close" aria-label="Tutup notifikasi">&times;</button>
  `;

  const closeBtn = alertEl.querySelector('.alert-close');
  if (closeBtn) {
    closeBtn.onclick = () => {
      alertEl.className = 'alert-banner';
      alertEl.innerHTML = '';
    };
  }

  // Auto hide setelah 6 detik jika sukses
  if (type === 'success') {
    setTimeout(() => {
      if (alertEl.classList.contains('is-visible')) {
        alertEl.className = 'alert-banner';
        alertEl.innerHTML = '';
      }
    }, 6000);
  }
}

// -----------------------------------------------------------------------------
// 1. STATISTIK / KPI METRICS
// -----------------------------------------------------------------------------
async function loadDashboardStats() {
  try {
    const res = await fetch(API_STATS);
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
    const result = await res.json();
    if (result.status === 'success') {
      const { alat, pemesanan } = result.data;
      const elTotalAlat = document.getElementById('kpi-total-alat');
      const elTotalUnit = document.getElementById('kpi-total-unit');
      const elTotalPesan = document.getElementById('kpi-total-pesanan');
      const elPesanMenunggu = document.getElementById('kpi-pesanan-menunggu');

      if (elTotalAlat) elTotalAlat.textContent = alat.total_ragam;
      if (elTotalUnit) elTotalUnit.textContent = `${alat.total_unit} Unit`;
      if (elTotalPesan) elTotalPesan.textContent = pemesanan.total_pesanan;
      if (elPesanMenunggu) elPesanMenunggu.textContent = pemesanan.status_menunggu;
    }
  } catch (err) {
    console.warn('Gagal memuat statistik metrik:', err.message);
  }
}

// -----------------------------------------------------------------------------
// 2. INVENTARIS ALAT: GET COLLECTION & RENDER
// -----------------------------------------------------------------------------
async function loadAlats() {
  const tbody = document.getElementById('tbody-alat');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="8">
        <div class="table-state-box">
          <div class="spinner" aria-hidden="true"></div>
          <p>Sedang memuat data peralatan dari server basis data...</p>
        </div>
      </td>
    </tr>
  `;

  try {
    const res = await fetch(API_ALATS);
    if (!res.ok) {
      throw new Error(`Gagal memuat data (HTTP ${res.status})`);
    }

    const result = await res.json();
    if (result.status === 'success' && Array.isArray(result.data)) {
      stateAlats = result.data;
      renderTableAlats(stateAlats);
    } else {
      throw new Error(result.message || 'Respons API tidak valid');
    }
  } catch (err) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8">
          <div class="table-state-box" style="color: #b91c1c;">
            <p>⚠️ <strong>Gagal memuat data alat:</strong> ${err.message}</p>
            <button type="button" class="btn-primary-action" onclick="loadAlats()" style="margin: 0.5rem auto;">🔄 Coba Lagi</button>
          </div>
        </td>
      </tr>
    `;
    showGlobalAlert(`Gagal mengambil data dari backend: ${err.message}`, 'error');
  }
}

function renderTableAlats(items) {
  const tbody = document.getElementById('tbody-alat');
  if (!tbody) return;

  if (!items || items.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8">
          <div class="table-state-box">
            <p>📭 Tidak ada data alat yang sesuai dengan pencarian atau filter.</p>
          </div>
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = items.map(alat => {
    let badgeClass = 'badge-success';
    if (alat.kondisi === 'Perlu Cek') badgeClass = 'badge-warning';
    if (alat.kondisi === 'Rusak') badgeClass = 'badge-danger';

    const tanggal = alat.updated_at ? new Date(alat.updated_at).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    }) : '-';

    return `
      <tr id="row-alat-${alat.id}">
        <td><strong>#${alat.id}</strong></td>
        <td><strong>${escapeHtml(alat.nama)}</strong></td>
        <td><span class="badge badge-info">${escapeHtml(alat.kategori || alat.jenis || '-')}</span></td>
        <td>${alat.jumlah} unit</td>
        <td><span class="badge ${badgeClass}">${escapeHtml(alat.kondisi)}</span></td>
        <td>📍 ${escapeHtml(alat.lokasi)}</td>
        <td><small style="color: #64748b;">${tanggal}</small></td>
        <td class="text-center">
          <div class="table-actions">
            <button type="button" class="btn-action-edit" data-id="${alat.id}" aria-label="Edit alat ${escapeHtml(alat.nama)}">
              ✏️ Edit
            </button>
            <button type="button" class="btn-action-delete" data-id="${alat.id}" data-nama="${escapeHtml(alat.nama)}" aria-label="Hapus alat ${escapeHtml(alat.nama)}">
              🗑️ Hapus
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// -----------------------------------------------------------------------------
// 3. EDIT ALAT: GET BY ID & POPULATE MODAL FORM
// -----------------------------------------------------------------------------
async function openEditAlatModal(id) {
  const modal = document.getElementById('modal-alat');
  const title = document.getElementById('modal-alat-title');
  const form = document.getElementById('form-alat');
  const alertBox = document.getElementById('modal-alat-alert');

  if (!modal || !form) return;

  // Reset alert modal
  if (alertBox) {
    alertBox.className = 'modal-status-alert';
    alertBox.innerHTML = '';
  }

  // Tampilkan indikator loading sementara
  if (title) title.textContent = `Memuat Data Alat #${id}...`;
  modal.showModal();

  try {
    // Implementasi GET by ID langsung ke backend API
    const res = await fetch(`${API_ALATS}?id=${id}`);
    if (!res.ok) {
      const errRes = await res.json().catch(() => ({}));
      throw new Error(errRes.message || `HTTP ${res.status}`);
    }

    const result = await res.json();
    if (result.status === 'success' && result.data) {
      const item = result.data;
      if (title) title.textContent = `Edit Data Peralatan #${item.id}`;

      document.getElementById('alat-id').value = item.id;
      document.getElementById('alat-nama').value = item.nama;
      document.getElementById('alat-kategori').value = item.kategori || item.jenis;
      document.getElementById('alat-jumlah').value = item.jumlah;
      document.getElementById('alat-kondisi').value = item.kondisi;
      document.getElementById('alat-lokasi').value = item.lokasi;
      document.getElementById('alat-nama').focus();
    } else {
      throw new Error(result.message || 'Data alat tidak ditemukan.');
    }
  } catch (err) {
    modal.close();
    showGlobalAlert(`Gagal mengambil rincian alat ID #${id}: ${err.message}`, 'error');
  }
}

// Buka Modal Tambah Alat Baru (POST)
function openTambahAlatModal() {
  const modal = document.getElementById('modal-alat');
  const title = document.getElementById('modal-alat-title');
  const form = document.getElementById('form-alat');
  const alertBox = document.getElementById('modal-alat-alert');

  if (!modal || !form) return;

  form.reset();
  document.getElementById('alat-id').value = ''; // Kosongkan id menandakan mode create (POST)
  if (title) title.textContent = 'Tambah Peralatan Pesisir Baru';
  if (alertBox) {
    alertBox.className = 'modal-status-alert';
    alertBox.innerHTML = '';
  }

  modal.showModal();
  document.getElementById('alat-nama').focus();
}

// -----------------------------------------------------------------------------
// 4. SUBMIT FORM ALAT: POST CREATE & PUT UPDATE DENGAN PREPARED STATEMENT
// -----------------------------------------------------------------------------
async function handleAlatFormSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const alertBox = document.getElementById('modal-alat-alert');
  const submitBtn = document.getElementById('btn-submit-alat');

  const id = document.getElementById('alat-id').value.trim();
  const isEdit = id !== '';

  const payload = {
    nama: document.getElementById('alat-nama').value.trim(),
    kategori: document.getElementById('alat-kategori').value.trim(),
    jumlah: parseInt(document.getElementById('alat-jumlah').value, 10),
    kondisi: document.getElementById('alat-kondisi').value,
    lokasi: document.getElementById('alat-lokasi').value.trim()
  };

  // Validasi di sisi klien
  if (!payload.nama || payload.nama.length < 3) {
    showModalAlert('Nama alat minimal 3 karakter.', 'error');
    document.getElementById('alat-nama').focus();
    return;
  }
  if (!payload.kategori) {
    showModalAlert('Pilih atau masukkan kategori alat.', 'error');
    document.getElementById('alat-kategori').focus();
    return;
  }
  if (isNaN(payload.jumlah) || payload.jumlah < 1) {
    showModalAlert('Jumlah unit alat minimal 1.', 'error');
    document.getElementById('alat-jumlah').focus();
    return;
  }
  if (!payload.lokasi) {
    showModalAlert('Lokasi penyimpanan wajib diisi.', 'error');
    document.getElementById('alat-lokasi').focus();
    return;
  }

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';
  }

  try {
    let url = API_ALATS;
    let method = 'POST';

    if (isEdit) {
      url = `${API_ALATS}?id=${encodeURIComponent(id)}`;
      method = 'PUT';
      payload.id = parseInt(id, 10);
    }

    const res = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const result = await res.json();

    if (res.ok && (result.status === 'success' || res.status === 200 || res.status === 201)) {
      document.getElementById('modal-alat').close();
      const actionMsg = isEdit 
        ? `Data alat <strong>${escapeHtml(result.data.nama)}</strong> (ID: #${result.data.id}) berhasil diperbarui!`
        : `Data alat baru <strong>${escapeHtml(result.data.nama)}</strong> berhasil ditambahkan dengan ID #${result.data.id}!`;
      showGlobalAlert(actionMsg, 'success');
      loadAlats();
      loadDashboardStats();
    } else {
      const errDetail = result.errors ? Object.values(result.errors).join('<br>') : result.message;
      showModalAlert(errDetail || 'Gagal menyimpan data.', 'error');
    }
  } catch (err) {
    showModalAlert(`Kesalahan koneksi ke server: ${err.message}`, 'error');
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = isEdit ? 'Simpan Pembaruan' : 'Simpan Data Baru';
    }
  }
}

function showModalAlert(msg, type = 'error') {
  const alertBox = document.getElementById('modal-alat-alert');
  if (!alertBox) return;
  alertBox.className = `modal-status-alert alert-${type} is-visible`;
  alertBox.innerHTML = `⚠️ ${msg}`;
}

// -----------------------------------------------------------------------------
// 5. DELETE DENGAN CONFIRMATION DIALOG (EXISTENCE CHECK)
// -----------------------------------------------------------------------------
function promptDelete(type, id, nama) {
  pendingDeleteItem = { type, id, nama };

  const modal = document.getElementById('modal-konfirmasi-hapus');
  const elNama = document.getElementById('konfirmasi-nama-item');
  const elId = document.getElementById('konfirmasi-id-item');

  if (elNama) elNama.textContent = nama;
  if (elId) elId.textContent = id;

  if (modal) {
    modal.showModal();
    const confirmBtn = document.getElementById('btn-konfirmasi-hapus');
    if (confirmBtn) confirmBtn.focus();
  }
}

async function executeConfirmedDelete() {
  if (!pendingDeleteItem) return;

  const { type, id, nama } = pendingDeleteItem;
  const modal = document.getElementById('modal-konfirmasi-hapus');
  const confirmBtn = document.getElementById('btn-konfirmasi-hapus');

  if (confirmBtn) {
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Menghapus...';
  }

  try {
    const endpoint = type === 'pemesanan' ? API_PEMESANAN : API_ALATS;
    const res = await fetch(`${endpoint}?id=${encodeURIComponent(id)}`, {
      method: 'DELETE'
    });

    const result = await res.json();

    if (res.ok && result.status === 'success') {
      if (modal) modal.close();
      showGlobalAlert(`Data ${type === 'pemesanan' ? 'pemesanan' : 'alat'} <strong>${escapeHtml(nama)}</strong> (ID: #${id}) berhasil dihapus.`, 'success');
      if (type === 'pemesanan') {
        loadPemesanan();
      } else {
        loadAlats();
      }
      loadDashboardStats();
    } else {
      if (modal) modal.close();
      showGlobalAlert(`Gagal menghapus data: ${result.message || 'Terjadi kesalahan'}`, 'error');
    }
  } catch (err) {
    if (modal) modal.close();
    showGlobalAlert(`Gagal terhubung ke server saat menghapus: ${err.message}`, 'error');
  } finally {
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = 'Ya, Hapus Permanen';
    }
    pendingDeleteItem = null;
  }
}

// -----------------------------------------------------------------------------
// 6. PEMESANAN LAYANAN: GET, EDIT STATUS, DELETE
// -----------------------------------------------------------------------------
async function loadPemesanan() {
  const tbody = document.getElementById('tbody-pemesanan');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="9">
        <div class="table-state-box">
          <div class="spinner" aria-hidden="true"></div>
          <p>Sedang memuat data pemesanan dari server basis data...</p>
        </div>
      </td>
    </tr>
  `;

  try {
    const res = await fetch(API_PEMESANAN);
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

    const result = await res.json();
    if (result.status === 'success' && Array.isArray(result.data)) {
      statePemesanan = result.data;
      renderTablePemesanan(statePemesanan);
    } else {
      throw new Error(result.message || 'Respons API tidak valid');
    }
  } catch (err) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9">
          <div class="table-state-box" style="color: #b91c1c;">
            <p>⚠️ Gagal memuat data pemesanan: ${err.message}</p>
            <button type="button" class="btn-primary-action" onclick="loadPemesanan()">🔄 Coba Lagi</button>
          </div>
        </td>
      </tr>
    `;
  }
}

function renderTablePemesanan(items) {
  const tbody = document.getElementById('tbody-pemesanan');
  if (!tbody) return;

  if (!items || items.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9">
          <div class="table-state-box">
            <p>📭 Belum ada data pemesanan layanan.</p>
          </div>
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = items.map(pesan => {
    let badgeClass = 'badge-warning';
    if (pesan.status === 'Diproses') badgeClass = 'badge-info';
    if (pesan.status === 'Selesai') badgeClass = 'badge-success';
    if (pesan.status === 'Dibatalkan') badgeClass = 'badge-danger';

    const tanggal = pesan.created_at ? new Date(pesan.created_at).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    }) : '-';

    return `
      <tr id="row-pemesanan-${pesan.id}">
        <td><strong>#${pesan.id}</strong></td>
        <td><strong>${escapeHtml(pesan.nama)}</strong></td>
        <td>📞 ${escapeHtml(pesan.kontak)}</td>
        <td><span class="badge badge-neutral">${escapeHtml(pesan.kategori)}</span></td>
        <td>${pesan.jumlah} unit</td>
        <td><span class="badge ${badgeClass}">${escapeHtml(pesan.status)}</span></td>
        <td><small>${escapeHtml(pesan.catatan || '-')}</small></td>
        <td><small style="color: #64748b;">${tanggal}</small></td>
        <td class="text-center">
          <div class="table-actions">
            <button type="button" class="btn-action-edit" data-type="pemesanan" data-id="${pesan.id}" aria-label="Edit status pemesanan ${escapeHtml(pesan.nama)}">
              ✏️ Status
            </button>
            <button type="button" class="btn-action-delete" data-type="pemesanan" data-id="${pesan.id}" data-nama="Pesanan #${pesan.id} (${escapeHtml(pesan.nama)})" aria-label="Hapus pemesanan ${escapeHtml(pesan.nama)}">
              🗑️ Hapus
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// Buka Modal Edit Status Pemesanan
async function openEditPemesananModal(id) {
  const modal = document.getElementById('modal-pemesanan');
  const form = document.getElementById('form-pemesanan');
  if (!modal || !form) return;

  try {
    const res = await fetch(`${API_PEMESANAN}?id=${id}`);
    const result = await res.json();
    if (res.ok && result.status === 'success' && result.data) {
      const data = result.data;
      document.getElementById('pesanan-id').value = data.id;
      document.getElementById('pesanan-nama').value = data.nama;
      document.getElementById('pesanan-kontak').value = data.kontak;
      document.getElementById('pesanan-kategori').value = data.kategori;
      document.getElementById('pesanan-jumlah').value = data.jumlah;
      document.getElementById('pesanan-status').value = data.status;
      document.getElementById('pesanan-catatan').value = data.catatan || '';
      modal.showModal();
    } else {
      showGlobalAlert(`Data pemesanan ID #${id} tidak ditemukan.`, 'error');
    }
  } catch (err) {
    showGlobalAlert(`Gagal mengambil data pemesanan: ${err.message}`, 'error');
  }
}

// Submit Pembaruan Pemesanan
async function handlePemesananFormSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('pesanan-id').value;
  const payload = {
    status: document.getElementById('pesanan-status').value,
    catatan: document.getElementById('pesanan-catatan').value.trim()
  };

  try {
    const res = await fetch(`${API_PEMESANAN}?id=${encodeURIComponent(id)}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await res.json();
    if (res.ok && result.status === 'success') {
      document.getElementById('modal-pemesanan').close();
      showGlobalAlert(`Status pemesanan #${id} berhasil diperbarui menjadi <strong>${payload.status}</strong>.`, 'success');
      loadPemesanan();
      loadDashboardStats();
    } else {
      showGlobalAlert(`Gagal memperbarui status: ${result.message}`, 'error');
    }
  } catch (err) {
    showGlobalAlert(`Gagal menghubungi server: ${err.message}`, 'error');
  }
}

// -----------------------------------------------------------------------------
// 7. EVENT DELEGATION & EVENT LISTENERS
// -----------------------------------------------------------------------------
function initEventListeners() {
  // Tombol Tambah Alat Baru
  const btnTambahAlat = document.getElementById('btn-tambah-alat');
  if (btnTambahAlat) {
    btnTambahAlat.addEventListener('click', openTambahAlatModal);
  }

  // Tombol Refresh Alat
  const btnRefreshAlat = document.getElementById('btn-refresh-alat');
  if (btnRefreshAlat) {
    btnRefreshAlat.addEventListener('click', () => {
      loadAlats();
      loadDashboardStats();
    });
  }

  // Tombol Refresh Pemesanan
  const btnRefreshPesan = document.getElementById('btn-refresh-pemesanan');
  if (btnRefreshPesan) {
    btnRefreshPesan.addEventListener('click', () => {
      loadPemesanan();
      loadDashboardStats();
    });
  }

  // Event Delegation pada Tabel Alat (Edit & Hapus)
  const tbodyAlat = document.getElementById('tbody-alat');
  if (tbodyAlat) {
    tbodyAlat.addEventListener('click', (e) => {
      const editBtn = e.target.closest('.btn-action-edit');
      if (editBtn) {
        const id = editBtn.dataset.id;
        if (id) openEditAlatModal(id);
        return;
      }

      const deleteBtn = e.target.closest('.btn-action-delete');
      if (deleteBtn) {
        const id = deleteBtn.dataset.id;
        const nama = deleteBtn.dataset.nama || `Alat #${id}`;
        promptDelete('alat', id, nama);
      }
    });
  }

  // Event Delegation pada Tabel Pemesanan (Edit & Hapus)
  const tbodyPemesanan = document.getElementById('tbody-pemesanan');
  if (tbodyPemesanan) {
    tbodyPemesanan.addEventListener('click', (e) => {
      const editBtn = e.target.closest('.btn-action-edit');
      if (editBtn) {
        const id = editBtn.dataset.id;
        if (id) openEditPemesananModal(id);
        return;
      }

      const deleteBtn = e.target.closest('.btn-action-delete');
      if (deleteBtn) {
        const id = deleteBtn.dataset.id;
        const nama = deleteBtn.dataset.nama || `Pemesanan #${id}`;
        promptDelete('pemesanan', id, nama);
      }
    });
  }

  // Submit Form Alat
  const formAlat = document.getElementById('form-alat');
  if (formAlat) {
    formAlat.addEventListener('submit', handleAlatFormSubmit);
  }

  // Submit Form Pemesanan
  const formPemesanan = document.getElementById('form-pemesanan');
  if (formPemesanan) {
    formPemesanan.addEventListener('submit', handlePemesananFormSubmit);
  }

  // Tombol Konfirmasi Hapus Modal
  const btnKonfirmasiHapus = document.getElementById('btn-konfirmasi-hapus');
  if (btnKonfirmasiHapus) {
    btnKonfirmasiHapus.addEventListener('click', executeConfirmedDelete);
  }

  // Tombol Batal Hapus Modal
  const btnBatalHapus = document.getElementById('btn-batal-hapus');
  if (btnBatalHapus) {
    btnBatalHapus.addEventListener('click', () => {
      const modal = document.getElementById('modal-konfirmasi-hapus');
      if (modal) modal.close();
      pendingDeleteItem = null;
    });
  }

  // Tombol Tutup Modal Alat
  const btnTutupModalAlat = document.getElementById('btn-batal-alat');
  if (btnTutupModalAlat) {
    btnTutupModalAlat.addEventListener('click', () => {
      document.getElementById('modal-alat').close();
    });
  }

  // Tombol Tutup Modal Pemesanan
  const btnTutupModalPesan = document.getElementById('btn-batal-pemesanan');
  if (btnTutupModalPesan) {
    btnTutupModalPesan.addEventListener('click', () => {
      document.getElementById('modal-pemesanan').close();
    });
  }

  // Filter & Search Real-Time Alat
  const searchInput = document.getElementById('search-alat');
  const filterKategori = document.getElementById('filter-kategori-alat');
  const filterKondisi = document.getElementById('filter-kondisi-alat');

  function applyAlatFilters() {
    const keyword = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const kat = filterKategori ? filterKategori.value : 'Semua';
    const kon = filterKondisi ? filterKondisi.value : 'Semua';

    const filtered = stateAlats.filter(item => {
      const matchKeyword = !keyword || 
        item.nama.toLowerCase().includes(keyword) || 
        (item.kategori && item.kategori.toLowerCase().includes(keyword)) ||
        (item.lokasi && item.lokasi.toLowerCase().includes(keyword));
      const matchKategori = (kat === 'Semua') || (item.kategori === kat || item.jenis === kat);
      const matchKondisi = (kon === 'Semua') || (item.kondisi === kon);
      return matchKeyword && matchKategori && matchKondisi;
    });

    renderTableAlats(filtered);
  }

  if (searchInput) searchInput.addEventListener('input', applyAlatFilters);
  if (filterKategori) filterKategori.addEventListener('change', applyAlatFilters);
  if (filterKondisi) filterKondisi.addEventListener('change', applyAlatFilters);

  // Tab Switcher
  const tabAlatBtn = document.getElementById('tab-btn-alat');
  const tabPesanBtn = document.getElementById('tab-btn-pemesanan');
  const tabAlatContent = document.getElementById('tab-content-alat');
  const tabPesanContent = document.getElementById('tab-content-pemesanan');

  if (tabAlatBtn && tabPesanBtn) {
    tabAlatBtn.addEventListener('click', () => {
      tabAlatBtn.classList.add('active');
      tabAlatBtn.setAttribute('aria-selected', 'true');
      tabPesanBtn.classList.remove('active');
      tabPesanBtn.setAttribute('aria-selected', 'false');
      if (tabAlatContent) tabAlatContent.classList.add('active');
      if (tabPesanContent) tabPesanContent.classList.remove('active');
    });

    tabPesanBtn.addEventListener('click', () => {
      tabPesanBtn.classList.add('active');
      tabPesanBtn.setAttribute('aria-selected', 'true');
      tabAlatBtn.classList.remove('active');
      tabAlatBtn.setAttribute('aria-selected', 'false');
      if (tabPesanContent) tabPesanContent.classList.add('active');
      if (tabAlatContent) tabAlatContent.classList.remove('active');
      if (statePemesanan.length === 0) {
        loadPemesanan();
      }
    });
  }
}

// Utility: Escape HTML untuk mencegah XSS
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// Inisialisasi saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
  initEventListeners();
  loadDashboardStats();
  loadAlats();
  loadPemesanan();
});

