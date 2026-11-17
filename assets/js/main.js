/**
 * SIP-Barang Kampus - Frontend Interaction Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Initialize Lucide Icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // 2. Mobile Menu Toggle
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', function () {
      mobileMenu.classList.toggle('hidden');
    });
  }

  // 3. Password Visibility Toggle
  const togglePasswordBtns = document.querySelectorAll('.toggle-password');
  togglePasswordBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const input = this.parentElement.querySelector('input');
      if (input) {
        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        const icon = this.querySelector('i');
        if (icon) {
          icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
          if (typeof lucide !== 'undefined') lucide.createIcons();
        }
      }
    });
  });

  // 4. Riwayat Tabs Switching
  const tabButtons = document.querySelectorAll('.history-tab-btn');
  const tabContents = document.querySelectorAll('.history-tab-content');
  if (tabButtons.length > 0) {
    tabButtons.forEach(btn => {
      btn.addEventListener('click', function () {
        const targetTab = this.getAttribute('data-target');
        
        tabButtons.forEach(b => {
          b.classList.remove('bg-blue-100', 'text-blue-700', 'border-blue-600');
          b.classList.add('bg-slate-100', 'text-slate-600');
        });
        this.classList.remove('bg-slate-100', 'text-slate-600');
        this.classList.add('bg-blue-100', 'text-blue-700');

        tabContents.forEach(content => {
          if (content.id === targetTab) {
            content.classList.remove('hidden');
          } else {
            content.classList.add('hidden');
          }
        });
      });
    });
  }

  // 5. Modal Klaim Handling
  const openModalKlaimBtns = document.querySelectorAll('.btn-open-klaim');
  const modalKlaim = document.getElementById('modalKlaim');
  const closeModalBtns = document.querySelectorAll('.btn-close-modal');

  function openModal() {
    if (modalKlaim) {
      modalKlaim.classList.remove('hidden');
      modalKlaim.classList.add('flex');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal() {
    if (modalKlaim) {
      modalKlaim.classList.add('hidden');
      modalKlaim.classList.remove('flex');
      document.body.style.overflow = '';
    }
  }

  openModalKlaimBtns.forEach(btn => {
    btn.addEventListener('click', openModal);
  });

  closeModalBtns.forEach(btn => {
    btn.addEventListener('click', closeModal);
  });

  if (modalKlaim) {
    modalKlaim.addEventListener('click', function (e) {
      if (e.target === modalKlaim) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modalKlaim.classList.contains('hidden')) {
        closeModal();
      }
    });
  }

  // 6. Submit Klaim Mock (Triggers toast & redirects to klaim-berhasil.php)
  const formKlaim = document.getElementById('formKlaim');
  if (formKlaim) {
    formKlaim.addEventListener('submit', function (e) {
      e.preventDefault();
      closeModal();
      showToast('Klaim berhasil diajukan', 'Menunggu konfirmasi dari admin. Kami akan memberi tahu kamu setelah diproses.');
      setTimeout(() => {
        window.location.href = 'klaim-berhasil.php';
      }, 1200);
    });
  }

  // 7. Toast Notification Handler
  window.showToast = function (title, message) {
    const toast = document.getElementById('toastNotification');
    if (!toast) return;

    const toastTitle = document.getElementById('toastTitle');
    const toastDesc = document.getElementById('toastDesc');
    if (toastTitle) toastTitle.textContent = title;
    if (toastDesc) toastDesc.textContent = message;

    toast.classList.remove('hidden');
    toast.classList.add('toast-animate');

    setTimeout(() => {
      hideToast();
    }, 5000);
  };

  window.hideToast = function () {
    const toast = document.getElementById('toastNotification');
    if (toast) {
      toast.classList.add('hidden');
    }
  };

  const closeToastBtn = document.getElementById('closeToastBtn');
  if (closeToastBtn) {
    closeToastBtn.addEventListener('click', hideToast);
  }

  // 8. OTP Input Auto Focus & Paste
  const otpInputs = document.querySelectorAll('.otp-digit');
  if (otpInputs.length > 0) {
    otpInputs.forEach((input, index) => {
      input.addEventListener('input', function (e) {
        const val = this.value;
        if (val.length >= 1) {
          this.value = val[val.length - 1]; // keep last typed
          if (index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
          }
        }
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && this.value === '' && index > 0) {
          otpInputs[index - 1].focus();
        }
      });

      input.addEventListener('paste', function (e) {
        e.preventDefault();
        const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
        if (/^\d+$/.test(pasteData)) {
          const digits = pasteData.slice(0, otpInputs.length).split('');
          digits.forEach((d, i) => {
            if (otpInputs[i]) otpInputs[i].value = d;
          });
          const nextIdx = Math.min(digits.length, otpInputs.length - 1);
          otpInputs[nextIdx].focus();
        }
      });
    });

    // Countdown Timer for OTP
    const countdownEl = document.getElementById('otpCountdown');
    if (countdownEl) {
      let seconds = 45;
      const interval = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
          clearInterval(interval);
          countdownEl.textContent = 'Kirim ulang sekarang';
          countdownEl.classList.add('cursor-pointer', 'text-blue-600', 'font-medium');
          countdownEl.classList.remove('text-slate-500');
        } else {
          const formatted = seconds < 10 ? '0' + seconds : seconds;
          countdownEl.textContent = `Kirim ulang dalam 00:${formatted}`;
        }
      }, 1000);
    }
  }

  // 9. File Upload Drag & Drop Preview
  const uploadZones = document.querySelectorAll('.upload-zone');
  uploadZones.forEach(zone => {
    const fileInput = zone.querySelector('input[type="file"]');
    const placeholder = zone.querySelector('.upload-placeholder');
    const previewContainer = zone.querySelector('.upload-preview');

    if (!fileInput) return;

    zone.addEventListener('click', () => fileInput.click());

    zone.addEventListener('dragover', (e) => {
      e.preventDefault();
      zone.classList.add('dragover');
    });

    zone.addEventListener('dragleave', () => {
      zone.classList.remove('dragover');
    });

    zone.addEventListener('drop', (e) => {
      e.preventDefault();
      zone.classList.remove('dragover');
      if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        handleFile(fileInput.files[0]);
      }
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files.length > 0) {
        handleFile(fileInput.files[0]);
      }
    });

    function handleFile(file) {
      if (!file) return;
      if (placeholder) placeholder.classList.add('hidden');
      if (previewContainer) {
        previewContainer.classList.remove('hidden');
        previewContainer.classList.add('flex');
        const nameEl = previewContainer.querySelector('.file-name');
        if (nameEl) nameEl.textContent = `${file.name} (${Math.round(file.size / 1024)} KB)`;
      }
    }
  });

  // 10. Pencarian Barang Live Filter & Search
  const searchInput = document.getElementById('searchInput');
  const catalogCards = document.querySelectorAll('.catalog-card');
  const statusCheckboxes = document.querySelectorAll('.filter-status');
  const categoryCheckboxes = document.querySelectorAll('.filter-category');
  const locationSelect = document.getElementById('filterLocation');
  const resetFilterBtn = document.getElementById('btnResetFilter');
  const emptyState = document.getElementById('emptySearchState');
  const totalCountEl = document.getElementById('totalSearchCount');

  function applyFilters() {
    if (catalogCards.length === 0) return;

    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const selectedStatuses = Array.from(statusCheckboxes)
      .filter(cb => cb.checked && cb.value !== 'all')
      .map(cb => cb.value.toLowerCase());
    
    const selectedCategories = Array.from(categoryCheckboxes)
      .filter(cb => cb.checked && cb.value !== 'all')
      .map(cb => cb.value.toLowerCase());

    const selectedLocation = (locationSelect ? locationSelect.value : '').toLowerCase().trim();

    let visibleCount = 0;

    catalogCards.forEach(card => {
      const cardTitle = (card.getAttribute('data-nama') || '').toLowerCase();
      const cardCategory = (card.getAttribute('data-kategori') || '').toLowerCase();
      const cardStatus = (card.getAttribute('data-status') || '').toLowerCase();
      const cardLocation = (card.getAttribute('data-lokasi') || '').toLowerCase();

      const matchQuery = !query || cardTitle.includes(query) || cardLocation.includes(query) || cardCategory.includes(query);
      const matchStatus = selectedStatuses.length === 0 || selectedStatuses.includes(cardStatus);
      const matchCategory = selectedCategories.length === 0 || selectedCategories.includes(cardCategory);
      const matchLocation = !selectedLocation || selectedLocation === 'semua lokasi kampus' || cardLocation.includes(selectedLocation);

      if (matchQuery && matchStatus && matchCategory && matchLocation) {
        card.classList.remove('hidden');
        visibleCount++;
      } else {
        card.classList.add('hidden');
      }
    });

    if (totalCountEl) {
      totalCountEl.textContent = visibleCount;
    }

    if (emptyState) {
      if (visibleCount === 0) {
        emptyState.classList.remove('hidden');
      } else {
        emptyState.classList.add('hidden');
      }
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
  }

  statusCheckboxes.forEach(cb => {
    cb.addEventListener('change', function () {
      if (this.value === 'all' && this.checked) {
        statusCheckboxes.forEach(other => { if (other !== this) other.checked = false; });
      } else if (this.checked) {
        const allCb = Array.from(statusCheckboxes).find(c => c.value === 'all');
        if (allCb) allCb.checked = false;
      }
      applyFilters();
    });
  });

  categoryCheckboxes.forEach(cb => {
    cb.addEventListener('change', function () {
      if (this.value === 'all' && this.checked) {
        categoryCheckboxes.forEach(other => { if (other !== this) other.checked = false; });
      } else if (this.checked) {
        const allCb = Array.from(categoryCheckboxes).find(c => c.value === 'all');
        if (allCb) allCb.checked = false;
      }
      applyFilters();
    });
  });

  if (locationSelect) {
    locationSelect.addEventListener('change', applyFilters);
  }

  if (resetFilterBtn) {
    resetFilterBtn.addEventListener('click', function () {
      if (searchInput) searchInput.value = '';
      statusCheckboxes.forEach(cb => { cb.checked = (cb.value === 'all'); });
      categoryCheckboxes.forEach(cb => { cb.checked = (cb.value === 'all'); });
      if (locationSelect) locationSelect.value = 'Semua Lokasi Kampus';
      applyFilters();
    });
  }
});
