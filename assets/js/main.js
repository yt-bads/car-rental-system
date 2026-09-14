// assets/js/main.js
// Custom Global Script — Rental Mobil PT. Wildan Abadi Jaya

'use strict';

// ============================================================
// UTILITIES
// ============================================================

/**
 * Format angka ke format Rupiah Indonesia (JS version)
 * @param {number} angka
 * @returns {string}
 */
function formatRupiahJS(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(angka);
}

/**
 * Konfirmasi hapus data secara dinamis via POST
 * @param {number} id
 * @param {string} formAction
 */
function confirmDelete(id, formAction = 'hapus.php') {
    if (!confirm('Apakah kamu yakin ingin menghapus data ini?\nAksi ini tidak bisa dibatalkan.')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = formAction;
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id';
    input.value = id;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// ============================================================
// INIT ON DOM READY
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    // Auto-close alert setelah 4 detik
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            // Check if bootstrap object exists
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            } else {
                // Fallback fade-out if Bootstrap JS is not loaded yet
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 4000);
    });

    // ============================================================
    // TOGGLE SHOW/HIDE PASSWORD
    // ============================================================
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const targetSelector = this.getAttribute('data-target');
            const passwordInput = document.querySelector(targetSelector);
            const icon = this.querySelector('i');
            if (!passwordInput) return;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            } else {
                passwordInput.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            }
            passwordInput.focus();
        });
    });

    // ============================================================
    // SIDEBAR TOGGLE — RESPONSIVE MOBILE (Admin & Direktur)
    // ============================================================
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar  = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', function () {
            adminSidebar.classList.toggle('is-open');
            // Toggle overlay
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
        });

        // Tutup sidebar saat overlay diklik
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function () {
                adminSidebar.classList.remove('is-open');
                sidebarOverlay.classList.remove('active');
            });
        }
    }

    // ============================================================
    // LOADING SPINNER — PREVENT DOUBLE SUBMIT
    // ============================================================
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const submitBtn = form.querySelector('[type="submit"]');
            if (!submitBtn || submitBtn.disabled) return;

            // Simpan teks asli untuk fallback jika perlu
            submitBtn.dataset.originalText = submitBtn.innerHTML;

            // Ubah tampilan tombol
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
        });
    });
});
