// assets/js/pesan.js
// Kalkulasi Otomatis Form Pemesanan — Rental Mobil PT. Wildan Abadi Jaya

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const tglSewa       = document.getElementById('tanggal_sewa');
    const tglKembali    = document.getElementById('tanggal_kembali');
    const lamaSewa      = document.getElementById('lama_sewa');
    const totalBiaya    = document.getElementById('total_biaya');
    const totalDisplay  = document.getElementById('total_display');
    const elHargaPerHari = document.getElementById('harga_per_hari');

    if (!tglSewa || !tglKembali || !lamaSewa || !totalBiaya || !elHargaPerHari) {
        return; // Guard clause jika elemen tidak ditemukan di halaman
    }

    const hargaPerHari  = parseFloat(elHargaPerHari.value);

    // Batas minimum tanggal sewa = hari ini
    const today = new Date().toISOString().split('T')[0];
    tglSewa.setAttribute('min', today);

    // Listener saat tanggal sewa berubah
    tglSewa.addEventListener('change', function () {
        if (!this.value) {
            tglKembali.value = '';
            tglKembali.setAttribute('disabled', 'true');
            return;
        }

        tglKembali.removeAttribute('disabled');
        
        // Minimum tanggal kembali = 1 hari setelah sewa
        const minKembali = new Date(this.value);
        minKembali.setDate(minKembali.getDate() + 1);
        
        const minKembaliStr = minKembali.toISOString().split('T')[0];
        tglKembali.setAttribute('min', minKembaliStr);
        
        // Jika tanggal kembali yang sudah ada kurang dari minimum baru, reset
        if (tglKembali.value && tglKembali.value < minKembaliStr) {
            tglKembali.value = '';
        }
        
        hitungTotal();
    });

    // Listener saat tanggal kembali berubah
    tglKembali.addEventListener('change', hitungTotal);

    // Fungsi utama menghitung durasi & total biaya
    function hitungTotal() {
        if (!tglSewa.value || !tglKembali.value) {
            lamaSewa.value = 0;
            totalBiaya.value = 0;
            if (totalDisplay) {
                totalDisplay.textContent = formatRupiahJS(0);
            }
            return;
        }

        const d1   = new Date(tglSewa.value);
        const d2   = new Date(tglKembali.value);
        
        // Hitung selisih dalam milidetik dan ubah ke hari
        const diffTime = d2.getTime() - d1.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays <= 0) {
            alert('Tanggal kembali harus setelah tanggal sewa (minimal 1 hari).');
            tglKembali.value = '';
            lamaSewa.value = 0;
            totalBiaya.value = 0;
            if (totalDisplay) {
                totalDisplay.textContent = formatRupiahJS(0);
            }
            return;
        }

        const total = diffDays * hargaPerHari;
        lamaSewa.value   = diffDays;
        totalBiaya.value = total;

        if (totalDisplay) {
            totalDisplay.textContent = formatRupiahJS(total);
        }
    }
    
    // Panggil hitungTotal pada saat inisialisasi awal jika form sudah terisi (misalnya saat reload/error validation)
    if (tglSewa.value && tglKembali.value) {
        hitungTotal();
    }
});
