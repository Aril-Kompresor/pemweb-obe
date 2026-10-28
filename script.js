const daftarAlat = [
  { id: 1, nama: "Jaring Insang", jenis: "Tangkap", jumlah: 10, lokasi: "Gudang A" },
  { id: 2, nama: "Coolbox", jenis: "Penyimpanan", jumlah: 5, lokasi: "Dermaga 1" },
  { id: 3, nama: "GPS Tracker", jenis: "Navigasi", jumlah: 3, lokasi: "Kapal 01" },
  { id: 4, nama: "Timbangan Digital", jenis: "Pengolahan", jumlah: 7, lokasi: "Gudang A" },
  { id: 5, nama: "Mesin Vacuum Sealer", jenis: "Pengemasan", jumlah: 2, lokasi: "Sentra UMKM" },
  { id: 6, nama: "Life Jacket", jenis: "Keselamatan", jumlah: 20, lokasi: "Dermaga 1" },
  { id: 7, nama: "Lampu Badai Pesisir", jenis: "Navigasi", jumlah: 12, lokasi: "Kapal 01" },
  { id: 8, nama: "Pukat Cincin", jenis: "Tangkap", jumlah: 4, lokasi: "Gudang A" },
  { id: 9, nama: "Fish Finder Sonar", jenis: "Navigasi", jumlah: 2, lokasi: "Kapal 01" },
  { id: 10, nama: "Keranjang Ikan Higienis", jenis: "Penyimpanan", jumlah: 25, lokasi: "Dermaga 1" },
  { id: 11, nama: "Perahu Karet Rescue", jenis: "Keselamatan", jumlah: 1, lokasi: "Dermaga 1" },
  { id: 12, nama: "Solar Cell Panel", jenis: "Pengolahan", jumlah: 6, lokasi: "Sentra UMKM" }
];
const inventaris = daftarAlat; // Variabel inventaris data proyek sesuai modul praktikum

const lokasiTarget = "Gudang A";
const alatDiLokasi = daftarAlat.filter(alat => alat.lokasi === lokasiTarget);

console.log("Semua Alat:", daftarAlat);
console.log("Alat di Lokasi " + lokasiTarget + ":", alatDiLokasi);

function cariAlatById(arg1, arg2) {
  const list = typeof arg2 !== "undefined" ? arg1 : daftarAlat;
  const targetId = typeof arg2 !== "undefined" ? arg2 : arg1;
  return list.find(alat => alat.id === Number(targetId));
}

const idDicari = 3;
const alatDitemukan = cariAlatById(idDicari);
console.log("Alat dengan ID " + idDicari + ":", alatDitemukan);

function buatRingkasanAlat(alat) {
  const { id, nama, jenis, lokasi } = alat;
  return `Alat #${id}: ${nama} merupakan peralatan ${jenis} yang disimpan di ${lokasi}.`;
}

const ringkasanSemuaAlat = daftarAlat.map(alat => buatRingkasanAlat(alat));
console.log("Ringkasan Setiap Alat:");
ringkasanSemuaAlat.forEach(ringkasan => console.log(ringkasan));

const totalJumlahAlat = daftarAlat.reduce((total, alat) => total + alat.jumlah, 0);
console.log("Total Jumlah Alat:", totalJumlahAlat);

function ringkasInventaris(data) {
  if (!Array.isArray(data) || data.length === 0) {
    return {
      totalAlat: 0,
      totalJumlah: 0,
      rataRata: 0,
      alatTerbanyak: null,
      alatTersedikit: null,
      totalPerLokasi: {}
    };
  }

  const totalAlat = data.length;
  const totalJumlah = data.reduce((total, alat) => total + alat.jumlah, 0);
  const rataRata = Number((totalJumlah / totalAlat).toFixed(2));
  const alatTerbanyak = data.reduce((prev, curr) => (curr.jumlah > prev.jumlah ? curr : prev), data[0]);
  const alatTersedikit = data.reduce((prev, curr) => (curr.jumlah < prev.jumlah ? curr : prev), data[0]);
  const totalPerLokasi = data.reduce((acc, alat) => {
    acc[alat.lokasi] = (acc[alat.lokasi] || 0) + alat.jumlah;
    return acc;
  }, {});

  return {
    totalAlat,
    totalJumlah,
    rataRata,
    alatTerbanyak,
    alatTersedikit,
    totalPerLokasi
  };
}

const statistikInventaris = ringkasInventaris(daftarAlat);
console.log("Statistik Inventaris:", statistikInventaris);

function initApp() {
  const containerAlat = document.getElementById("daftar-alat");
  const selectLokasi = document.getElementById("filter-lokasi");
  const inputCariId = document.getElementById("cari-id");
  const btnCariId = document.getElementById("btn-cari-id");
  const btnResetId = document.getElementById("btn-reset-id");
  const search = document.querySelector("#search");
  const limit = document.querySelector("#limit");

  if (!containerAlat) return;

  function buatKartuAlat(alat) {
    const { id, nama, jenis, jumlah, lokasi } = alat;
    const ringkasan = buatRingkasanAlat(alat);

    const card = document.createElement("article");
    card.className = "card";
    card.innerHTML = `
      <h3>${nama} (ID: ${id})</h3>
      <p>Jenis: ${jenis}</p>
      <p>Jumlah: ${jumlah} unit</p>
      <p>Lokasi: <strong>${lokasi}</strong></p>
      <p>${ringkasan}</p>
    `;

    // Tombol Detail dengan Dataset sesuai materi praktikum
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = "Detail";
    button.className = "btn-detail";
    button.dataset.detail = id;
    card.appendChild(button);

    return card;
  }

  function tampilkanDetail(item) {
    if (!item) return;
    const modal = document.getElementById("modal-detail");
    const modalContent = document.getElementById("modal-detail-content");
    if (modal && modalContent) {
      modalContent.innerHTML = `
        <h3>🔍 Detail Informasi Alat</h3>
        <p><strong>Nama Alat:</strong> ${item.nama}</p>
        <p><strong>ID Inventaris:</strong> #${item.id}</p>
        <p><strong>Kategori / Fungsi:</strong> ${item.jenis}</p>
        <p><strong>Ketersediaan Stok:</strong> ${item.jumlah} unit</p>
        <p><strong>Lokasi Penyimpanan:</strong> ${item.lokasi}</p>
        <p><strong>Ringkasan Deskripsi:</strong> ${buatRingkasanAlat(item)}</p>
      `;
      if (typeof modal.showModal === "function") {
        modal.showModal();
      } else {
        modal.setAttribute("open", "");
      }
    }
  }

  // Event Delegation pada container daftar alat (Latihan Praktikum 2)
  containerAlat.addEventListener("click", (event) => {
    const button = event.target.closest("[data-detail]");
    if (!button) return;

    const item = inventaris.find(
      data => data.id === Number(button.dataset.detail)
    );

    tampilkanDetail(item);
  });

  const btnTutupDetail = document.getElementById("btn-tutup-detail");
  const modalDetail = document.getElementById("modal-detail");
  if (btnTutupDetail && modalDetail) {
    btnTutupDetail.addEventListener("click", () => {
      if (typeof modalDetail.close === "function") {
        modalDetail.close();
      } else {
        modalDetail.removeAttribute("open");
      }
    });
    modalDetail.addEventListener("click", (e) => {
      if (e.target === modalDetail) {
        if (typeof modalDetail.close === "function") modalDetail.close();
        else modalDetail.removeAttribute("open");
      }
    });
  }

  function renderItems(items) {
    containerAlat.innerHTML = "";

    if (!items || items.length === 0) {
      containerAlat.innerHTML = `
        <div class="empty-message">
          <p>🔍 Maaf, alat yang Anda cari tidak ditemukan. Silakan periksa kembali ejaan atau coba kata kunci lain.</p>
        </div>
      `;
      return;
    }

    items.forEach(alat => {
      containerAlat.appendChild(buatKartuAlat(alat));
    });
  }

  // Pengaturan Limit dengan LocalStorage (Latihan Praktikum 3)
  if (limit) {
    limit.value = localStorage.getItem("limit") ?? "5";

    limit.addEventListener("change", () => {
      localStorage.setItem("limit", limit.value);
      renderItems(inventaris.slice(0, Number(limit.value)));
    });
  }

  function renderDaftarAlat(lokasi) {
    const batas = limit ? Number(limit.value) : 5;
    const hasil = lokasi === "Semua"
      ? inventaris
      : inventaris.filter(alat => alat.lokasi === lokasi);
    renderItems(hasil.slice(0, batas));
  }

  // Event listener fitur pencarian alat real-time sesuai instruksi praktikum
  if (search) {
    search.addEventListener("input", (event) => {
      const keyword = event.target.value.toLowerCase().trim();
      const batas = limit ? Number(limit.value) : 5;
      const hasil = inventaris.filter(item =>
        item.nama.toLowerCase().includes(keyword)
      );
      renderItems(hasil.slice(0, batas));
    });
  }

  function handleCariById() {
    const id = inputCariId ? inputCariId.value.trim() : "";
    if (!id) {
      const batas = limit ? Number(limit.value) : 5;
      renderItems(inventaris.slice(0, batas));
      return;
    }

    const alat = cariAlatById(inventaris, id);
    containerAlat.innerHTML = "";

    if (!alat) {
      containerAlat.innerHTML = `
        <div class="empty-message">
          <p>🔍 Alat dengan ID ${id} tidak ditemukan.</p>
        </div>
      `;
      return;
    }

    containerAlat.appendChild(buatKartuAlat(alat));
  }

  if (selectLokasi) {
    selectLokasi.addEventListener("change", (e) => {
      if (inputCariId) inputCariId.value = "";
      if (search) search.value = "";
      renderDaftarAlat(e.target.value);
    });
  }

  if (btnCariId) {
    btnCariId.addEventListener("click", handleCariById);
  }

  if (inputCariId) {
    inputCariId.addEventListener("keyup", (e) => {
      if (e.key === "Enter") {
        handleCariById();
      }
    });
  }

  if (btnResetId) {
    btnResetId.addEventListener("click", () => {
      if (inputCariId) inputCariId.value = "";
      if (search) search.value = "";
      if (selectLokasi) selectLokasi.value = "Semua";
      const batas = limit ? Number(limit.value) : 5;
      renderItems(inventaris.slice(0, batas));
    });
  }

  // Tampilkan inventaris sesuai batas preferensi saat halaman pertama kali dimuat
  const batasAwal = limit ? Number(limit.value) : 5;
  renderItems(inventaris.slice(0, batasAwal));

  // Inisialisasi otomatis jika ada parameter URL (?search=... atau ?detail=...)
  if (typeof window !== "undefined" && window.location) {
    const urlParams = new URLSearchParams(window.location.search);
    const initialSearch = urlParams.get("search");
    if (initialSearch && search) {
      search.value = initialSearch;
      search.dispatchEvent(new Event("input"));
    }
    const initialDetail = urlParams.get("detail");
    if (initialDetail) {
      const item = inventaris.find(data => data.id === Number(initialDetail));
      if (item) tampilkanDetail(item);
    }
    const initialLimit = urlParams.get("limit");
    if (initialLimit && limit) {
      limit.value = initialLimit;
      limit.dispatchEvent(new Event("change"));
    }
  }
}

// Inisialisasi Aksesibilitas Formulir Pemesanan & Layanan UMKM
function initAccessibleForm() {
  const form = document.getElementById("form-pemesanan");
  const statusContainer = document.getElementById("form-status");
  const btnReset = document.getElementById("btn-reset-pesanan");
  const btnKirim = document.getElementById("btn-kirim-pesanan");

  if (!form || !statusContainer) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (!form.checkValidity()) {
      statusContainer.className = "form-status is-visible status-error";
      statusContainer.innerHTML = "⚠️ <strong>Mohon periksa kembali:</strong> Harap lengkapi semua kolom bertanda bintang (*) sebelum mengirim formulir.";
      const firstInvalid = form.querySelector(":invalid");
      if (firstInvalid) firstInvalid.focus();
      return;
    }

    const nama = document.getElementById("nama-pemesan") ? document.getElementById("nama-pemesan").value.trim() : "";
    const kontak = document.getElementById("kontak-pemesan") ? document.getElementById("kontak-pemesan").value.trim() : "";
    const kategori = document.getElementById("kategori-layanan") ? document.getElementById("kategori-layanan").value : "";
    const jumlah = document.getElementById("jumlah-pesanan") ? parseInt(document.getElementById("jumlah-pesanan").value, 10) : 1;
    const catatan = document.getElementById("catatan-pesanan") ? document.getElementById("catatan-pesanan").value.trim() : "";

    const payload = {
      nama: nama,
      kontak: kontak,
      kategori: kategori,
      jumlah: jumlah,
      catatan: catatan
    };

    if (btnKirim) {
      btnKirim.disabled = true;
      btnKirim.textContent = "Menyimpan ke Database...";
    }

    try {
      const response = await fetch("api/pemesanan.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (response.ok && result.status === "success") {
        statusContainer.className = "form-status is-visible status-success";
        statusContainer.innerHTML = `✅ <strong>Pemesanan Berhasil Disimpan ke Basis Data!</strong><br>Terima kasih, <strong>${result.data.nama}</strong>. Permintaan untuk <strong>${result.data.kategori}</strong> (${result.data.jumlah} unit/paket) telah tercatat di sistem dengan <strong>ID #${result.data.id}</strong>. Tim UMKM Pesisir akan segera menghubungi kontak <strong>${result.data.kontak}</strong>.`;
        form.reset();
      } else {
        const errorMsg = result.message || (result.errors ? Object.values(result.errors).join("<br>") : "Terjadi kesalahan saat memproses data.");
        statusContainer.className = "form-status is-visible status-error";
        statusContainer.innerHTML = `⚠️ <strong>Pemesanan Gagal Disimpan:</strong> ${errorMsg}`;
      }
    } catch (err) {
      statusContainer.className = "form-status is-visible status-error";
      statusContainer.innerHTML = `⚠️ <strong>Koneksi Gagal:</strong> Tidak dapat terhubung ke server/database. (${err.message})`;
    } finally {
      if (btnKirim) {
        btnKirim.disabled = false;
        btnKirim.textContent = "Kirim Pemesanan";
      }
      statusContainer.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  });

  if (btnReset) {
    btnReset.addEventListener("click", () => {
      statusContainer.className = "form-status";
      statusContainer.innerHTML = "";
    });
  }
}

// Data cadangan jika offline / koneksi internet terputus
const fallbackMeals = [
  {
    idMeal: "53483",
    strMeal: "Acaraje black-eyed pea fritters with shrimp filling (Offline Fallback)",
    strMealThumb: "images/meal-53483.jpg"
  },
  {
    idMeal: "53495",
    strMeal: "Amok Trey Cambodian Fish Curry (Offline Fallback)",
    strMealThumb: "images/meal-53495.jpg"
  },
  {
    idMeal: "52959",
    strMeal: "Baked salmon with fennel & tomatoes (Offline Fallback)",
    strMealThumb: "images/meal-52959.jpg"
  },
  {
    idMeal: "53147",
    strMeal: "Arroz con gambas y calamar (Offline Fallback)",
    strMealThumb: "images/meal-53147.jpg"
  }
];

let dataSeafood = [];

function renderMeals(items, isSearching = false) {
  const container = document.getElementById("daftar-seafood");
  if (!container) return;

  container.innerHTML = "";

  if (!items || items.length === 0) {
    container.innerHTML = `
      <div class="empty-message">
        <p>🔍 Maaf, olahan laut tidak ditemukan. Coba gunakan kata kunci lain.</p>
      </div>
    `;
    return;
  }

  const displayItems = isSearching ? items : items.slice(0, 6);

  displayItems.forEach(meal => {
    const card = document.createElement("article");
    card.className = "card";
    card.innerHTML = `
      <h3>${meal.strMeal}</h3>
      <figure>
        <img src="${meal.strMealThumb}" alt="${meal.strMeal}" width="100%" loading="lazy">
        <figcaption>Kode Menu: #${meal.idMeal}</figcaption>
      </figure>
      <p>Olahan komoditas laut pesisir pilihan.</p>
    `;
    container.appendChild(card);
  });
}

function initPencarianSeafood() {
  const searchInput = document.getElementById("search-seafood");
  if (!searchInput) return;

  searchInput.addEventListener("input", (event) => {
    const keyword = event.target.value.toLowerCase().trim();
    if (!keyword) {
      renderMeals(dataSeafood, false);
      return;
    }

    // Filter array lokal di memori klien TANPA memanggil fetch/API di setiap ketikan
    const hasil = dataSeafood.filter(meal =>
      meal.strMeal.toLowerCase().includes(keyword)
    );
    renderMeals(hasil, true);
  });
}

function tampilkanStatusError(pesan) {
  let statusContainer = document.getElementById("seafood-status");
  if (!statusContainer) {
    const parent = document.getElementById("seafood");
    const containerCards = document.getElementById("daftar-seafood");
    if (parent && containerCards) {
      statusContainer = document.createElement("div");
      statusContainer.id = "seafood-status";
      statusContainer.setAttribute("role", "status");
      statusContainer.setAttribute("aria-live", "polite");
      parent.insertBefore(statusContainer, containerCards);
    }
  }
  if (!statusContainer) return;

  statusContainer.innerHTML = `
    <div class="status-error-box">
      <p>⚠️ <strong>Gagal memuat data API:</strong> ${pesan} Menampilkan data cadangan.</p>
      <button type="button" id="btn-retry-seafood" class="btn-retry">🔄 Coba Lagi</button>
    </div>
  `;

  const btnRetry = document.getElementById("btn-retry-seafood");
  if (btnRetry) {
    btnRetry.addEventListener("click", () => {
      btnRetry.disabled = true;
      btnRetry.textContent = "Memuat ulang...";
      loadUsers();
    });
  }
}

function bersihkanStatusError() {
  const statusContainer = document.getElementById("seafood-status");
  if (statusContainer) {
    statusContainer.innerHTML = "";
  }
}

async function loadUsers() {
  const endpoint = "https://www.themealdb.com/api/json/v1/1/filter.php?c=Seafood";

  // Cek jika browser sedang dalam mode offline
  if (typeof navigator !== "undefined" && navigator.onLine === false) {
    console.warn("⚠️ Mode offline terdeteksi. Memuat data cadangan (fallback).");
    tampilkanStatusError("Koneksi internet tidak tersedia (Offline).");
    dataSeafood = fallbackMeals;
    renderMeals(dataSeafood);
    return dataSeafood;
  }

  try {
    const response = await fetch(endpoint);
    console.log("Response: " + response.ok);
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }

    const data = await response.json();
    const items = data.meals || data;
    console.log("Data berhasil ditarik dari endpoint:", items);
    bersihkanStatusError();
    dataSeafood = items;
    renderMeals(dataSeafood);
    return items;
  } catch (error) {
    console.warn("Gagal terhubung ke endpoint internet:", error.message);
    console.info("Mengalihkan ke data cadangan (fallback)...");
    tampilkanStatusError(error.message);
    dataSeafood = fallbackMeals;
    renderMeals(dataSeafood);
    return fallbackMeals;
  }
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      initApp();
      initAccessibleForm();
      initPencarianSeafood();
      loadUsers();
    });
  } else {
    initApp();
    initAccessibleForm();
    initPencarianSeafood();
    loadUsers();
  }
}


