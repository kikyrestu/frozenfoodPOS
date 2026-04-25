// ============================================================
//  ADMIN SHARED UTILITIES
//  Fungsi-fungsi ini dibutuhkan oleh semua halaman admin
//  (openModal, closeModal, dll tidak ada di pos.js saat isAdminPage=true)
// ============================================================

function formatRp(amount) {
  return 'Rp ' + parseFloat(amount || 0).toLocaleString('id-ID');
}

function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(str || ''));
  return d.innerHTML;
}

function openModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.style.display = 'flex';
    requestAnimationFrame(() => el.classList.add('active'));
  }
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.remove('active');
    setTimeout(() => { el.style.display = 'none'; }, 250);
  }
}

function showToast(msg, type = 'success') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = {
    success: 'fa-check-circle',
    error:   'fa-times-circle',
    warning: 'fa-exclamation-triangle',
    info:    'fa-info-circle'
  };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i> ${escHtml(msg)}`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';
    toast.style.transition = 'all 0.3s';
    setTimeout(() => toast.remove(), 300);
  }, 2800);
}

// Tutup modal saat klik area overlay (luar modal)
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.modal-overlay').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (e.target === el) closeModal(el.id);
    });
  });
});
