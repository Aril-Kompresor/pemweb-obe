export function cariAlatById(daftar, id) {
  return daftar.find(alat => alat.id === Number(id));
}

export function buatRingkasanAlat(alat) {
  const { id, nama, jenis, lokasi } = alat;
  return `Alat #${id}: ${nama} merupakan peralatan ${jenis} yang disimpan di ${lokasi}.`;
}

export function ringkasInventaris(data) {
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
