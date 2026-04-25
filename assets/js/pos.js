// ===================================
// FUN FROZEN FOOD POS - MAIN JS
// ===================================

// Pastikan BASE_URL tersedia (fallback ke root)
if (typeof BASE_URL === 'undefined') {
  window.BASE_URL = '';
}

// API fetch helper - handles session expiry & non-JSON responses
function apiFetch(url, opts) {
  return fetch(url, opts).then(r => {
    if (r.status === 401) {
      showToast('Sesi habis, silakan login ulang.', 'error');
      setTimeout(() => { window.location.href = BASE_URL + '/login.php'; }, 1500);
      return Promise.reject(new Error('session_expired'));
    }
    if (!r.ok) return Promise.reject(new Error('HTTP ' + r.status));
    return r.json();
  });
}

const POS = {
  cart: [],
  products: [],
  categories: [],
  activeCategory: 0,
  searchQuery: '',
  taxPercent: 0,

  init() {
    this.loadCategories();
    this.loadProducts();
    this.bindEvents();
  },

  // ---- LOAD ----
  loadCategories() {
    apiFetch(BASE_URL + '/api/get_categories.php')
      .then(data => {
        if (data.success) {
          this.categories = data.data;
          this.renderCategories();
        }
      })
      .catch(() => {});
  },

  loadProducts() {
    const params = new URLSearchParams();
    if (this.activeCategory) params.set('category_id', this.activeCategory);
    if (this.searchQuery) params.set('search', this.searchQuery);

    const grid = document.getElementById('productGrid');
    if (grid) grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px"><div class="spinner"></div></div>';

    apiFetch(BASE_URL + '/api/get_products.php?' + params.toString())
      .then(data => {
        if (data.success) {
          this.products = data.data;
          if (typeof data.tax_percent !== 'undefined') this.taxPercent = parseFloat(data.tax_percent) || 0;
          this.renderProducts();
        } else {
          if (grid) grid.innerHTML = '<div style="grid-column:1/-1" class="empty-state"><i class="fa-solid fa-box-open"></i><p>Gagal memuat produk</p></div>';
        }
      })
      .catch(() => {
        if (grid) grid.innerHTML = '<div style="grid-column:1/-1" class="empty-state"><i class="fa-solid fa-wifi"></i><p>Koneksi gagal, coba refresh</p></div>';
      });
  },

  // ---- RENDER ----
  renderCategories() {
    const wrap = document.getElementById('categoryFilter');
    if (!wrap) return;
    let html = `<button class="cat-btn ${this.activeCategory === 0 ? 'active' : ''}" data-id="0">Semua</button>`;
    this.categories.forEach(c => {
      html += `<button class="cat-btn ${this.activeCategory == c.id ? 'active' : ''}" data-id="${c.id}">${escHtml(c.name)}</button>`;
    });
    wrap.innerHTML = html;
    wrap.querySelectorAll('.cat-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        this.activeCategory = parseInt(btn.dataset.id);
        this.loadProducts();
        wrap.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  },

  renderProducts() {
    const grid = document.getElementById('productGrid');
    if (!grid) return;
    if (!this.products.length) {
      grid.innerHTML = `<div style="grid-column:1/-1" class="empty-state"><i class="fa-solid fa-box-open"></i><p>Produk tidak ditemukan</p></div>`;
      return;
    }
    grid.innerHTML = this.products.map(p => this.productCardHtml(p)).join('');
    grid.querySelectorAll('.product-card:not(.out-of-stock)').forEach(card => {
      card.addEventListener('click', () => {
        this.addToCart(parseInt(card.dataset.id));
        card.style.transform = 'scale(0.97)';
        setTimeout(() => { card.style.transform = ''; }, 150);
      });
    });
  },

  productCardHtml(p) {
    const habis    = p.stock <= 0;
    const lowStock = p.stock > 0 && p.stock <= parseInt(p.low_stock_alert);
    const imgSrc   = p.image ? (BASE_URL + '/uploads/products/' + escHtml(p.image)) : null;
    const imgHtml  = imgSrc
      ? `<img class="product-card-img" src="${imgSrc}" alt="${escHtml(p.name)}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\\'product-card-no-img\\'><i class=\\'fa-solid fa-snowflake\\'></i></div>'">`
      : `<div class="product-card-no-img"><i class="fa-solid fa-snowflake"></i></div>`;
    return `
    <div class="product-card ${habis ? 'out-of-stock' : ''}" data-id="${p.id}">
      ${imgHtml}
      ${habis ? '<span class="badge-habis">Habis</span>' : ''}
      <div class="product-card-body">
        <div class="product-card-name">${escHtml(p.name)}</div>
        <div class="product-card-price">${formatRp(p.price)}</div>
        <div class="product-card-stock ${lowStock ? 'stock-low' : ''}">
          <i class="fa-solid fa-box"></i> Stok: ${p.stock} ${escHtml(p.unit)}
        </div>
      </div>
      ${!habis ? '<div class="add-ripple"><i class="fa-solid fa-plus"></i></div>' : ''}
    </div>`;
  },

  // ---- CART ----
  addToCart(productId) {
    const product = this.products.find(p => p.id == productId);
    if (!product || product.stock <= 0) return;

    const existing = this.cart.find(i => i.id == productId);
    if (existing) {
      if (existing.qty >= product.stock) {
        showToast('Stok tidak mencukupi!', 'warning');
        return;
      }
      existing.qty++;
      existing.subtotal = existing.qty * existing.price;
    } else {
      this.cart.push({
        id:       product.id,
        name:     product.name,
        price:    parseFloat(product.price),
        qty:      1,
        unit:     product.unit,
        stock:    product.stock,
        subtotal: parseFloat(product.price)
      });
    }
    this.renderCart();
    showToast(`${product.name} ditambahkan`, 'success');
  },

  removeFromCart(productId) {
    this.cart = this.cart.filter(i => i.id != productId);
    this.renderCart();
  },

  updateQty(productId, delta) {
    const item = this.cart.find(i => i.id == productId);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) {
      this.removeFromCart(productId);
      return;
    }
    if (item.qty > item.stock) {
      item.qty = item.stock;
      showToast('Stok tidak mencukupi!', 'warning');
    }
    item.subtotal = item.qty * item.price;
    this.renderCart();
  },

  clearCart() {
    if (!this.cart.length) return;
    if (confirm('Reset semua item di keranjang?')) {
      this.cart = [];
      this.renderCart();
    }
  },

  getSubtotal() {
    return this.cart.reduce((sum, i) => sum + i.subtotal, 0);
  },

  getTaxAmount() {
    return this.taxPercent > 0 ? Math.round(this.getSubtotal() * this.taxPercent / 100) : 0;
  },

  getTotal() {
    return this.getSubtotal() + this.getTaxAmount();
  },

  renderCart() {
    const container = document.getElementById('cartItems');
    const totalEl   = document.getElementById('cartTotal');
    const countEl   = document.getElementById('cartCount');
    const checkBtn  = document.getElementById('btnCheckout');
    if (!container) return;

    if (countEl) countEl.textContent = this.cart.reduce((s, i) => s + i.qty, 0);
    var fabCount = document.getElementById('cartFabCount');
    if (fabCount) fabCount.textContent = this.cart.reduce((s, i) => s + i.qty, 0);

    if (!this.cart.length) {
      container.innerHTML = `<div class="cart-empty"><i class="fa-solid fa-cart-shopping"></i><p>Keranjang kosong</p><small style="font-size:12px;opacity:0.6">Klik produk untuk menambahkan</small></div>`;
      if (totalEl) totalEl.textContent = formatRp(0);
      if (checkBtn) checkBtn.disabled = true;
      return;
    }

    container.innerHTML = this.cart.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <div class="cart-item-info">
          <div class="cart-item-name">${escHtml(item.name)}</div>
          <div class="cart-item-price">${formatRp(item.price)} / ${escHtml(item.unit)}</div>
          <div class="cart-item-subtotal">${formatRp(item.subtotal)}</div>
        </div>
        <div class="qty-control">
          <button class="qty-btn" onclick="POS.updateQty(${item.id}, -1)">−</button>
          <span class="qty-val">${item.qty}</span>
          <button class="qty-btn" onclick="POS.updateQty(${item.id}, 1)">+</button>
        </div>
        <span class="cart-item-del" onclick="POS.removeFromCart(${item.id})"><i class="fa-solid fa-trash"></i></span>
      </div>`).join('');

    const total = this.getTotal();
    if (totalEl) totalEl.textContent = formatRp(total);
    if (checkBtn) checkBtn.disabled = false;
  },

  // ---- CHECKOUT ----
  openCheckout() {
    if (!this.cart.length) { showToast('Keranjang kosong!', 'warning'); return; }
    const subtotal = this.getSubtotal();
    const taxAmount = this.getTaxAmount();
    const total = this.getTotal();

    // Show tax breakdown if applicable
    const taxRow = document.getElementById('checkoutTaxRow');
    const subtotalRow = document.getElementById('checkoutSubtotalRow');
    if (taxRow && subtotalRow) {
      if (this.taxPercent > 0) {
        subtotalRow.style.display = 'flex';
        taxRow.style.display = 'flex';
        document.getElementById('checkoutSubtotal').textContent = formatRp(subtotal);
        document.getElementById('checkoutTax').textContent = formatRp(taxAmount);
        document.getElementById('checkoutTaxLabel').textContent = `Pajak (${this.taxPercent}%)`;
      } else {
        subtotalRow.style.display = 'none';
        taxRow.style.display = 'none';
      }
    }

    document.getElementById('checkoutTotal').textContent = formatRp(total);
    document.getElementById('paidAmount').value = '';
    document.getElementById('changeAmount').textContent = formatRp(0);
    document.getElementById('paymentMethod').value = 'tunai';
    document.getElementById('paidGroup').style.display = 'block';

    // Quick amount buttons
    const qWrap = document.getElementById('quickAmounts');
    if (qWrap) {
      const amounts = this._quickAmounts(total);
      qWrap.innerHTML = amounts.map(a =>
        `<button type="button" class="btn btn-sm btn-secondary" onclick="POS.setQuickAmount(${a})">${formatRp(a)}</button>`
      ).join('');
    }
    openModal('checkoutModal');
  },

  _quickAmounts(total) {
    const rounds = [1000, 2000, 5000, 10000, 20000, 50000, 100000];
    const result = [];
    for (const r of rounds) {
      const v = Math.ceil(total / r) * r;
      if (!result.includes(v) && result.length < 4) result.push(v);
    }
    return result;
  },

  setQuickAmount(amount) {
    document.getElementById('paidAmount').value = amount;
    this.calcChange();
  },

  onPaymentChange() {
    const method = document.getElementById('paymentMethod').value;
    document.getElementById('paidGroup').style.display = method === 'tunai' ? 'block' : 'none';
    if (method !== 'tunai') {
      document.getElementById('changeAmount').textContent = formatRp(0);
    }
  },

  calcChange() {
    const paid  = parseFloat(document.getElementById('paidAmount').value) || 0;
    const total = this.getTotal();
    const change = paid - total;
    document.getElementById('changeAmount').textContent = formatRp(change > 0 ? change : 0);
    document.getElementById('changeAmount').style.color = change < 0 ? '#e74c3c' : '#f1c40f';
  },

  submitCheckout() {
    const method   = document.getElementById('paymentMethod').value;
    const customer = (document.getElementById('customerName').value || '').trim();
    const paid     = parseFloat(document.getElementById('paidAmount').value) || 0;
    const total    = this.getTotal();

    if (method === 'tunai' && paid < total) {
      showToast('Uang bayar kurang!', 'error'); return;
    }

    const btn = document.getElementById('btnPay');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

    const payload = {
      customer_name:   customer,
      payment_method:  method,
      paid_amount:     method === 'tunai' ? paid : total,
      items: this.cart.map(i => ({
        product_id: i.id, qty: i.qty,
        price: i.price, subtotal: i.subtotal,
        product_name: i.name
      }))
    };

    apiFetch(BASE_URL + '/api/checkout.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(data => {
      if (data.success) {
        closeModal('checkoutModal');
        this.cart = [];
        this.renderCart();
        this.loadProducts();
        this.showInvoice(data.transaction_id, true);
        showToast('Transaksi berhasil!', 'success');
      } else {
        showToast(data.message || 'Gagal!', 'error');
      }
    })
    .catch(err => {
      if (err.message !== 'session_expired') {
        showToast('Koneksi gagal! Coba lagi.', 'error');
      }
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> BAYAR SEKARANG';
    });
  },

  showInvoice(transactionId, autoPrint = false) {
    apiFetch(BASE_URL + `/api/get_transaction.php?id=${transactionId}`)
      .then(data => {
        if (data.success) {
          document.getElementById('invoiceContent').innerHTML = buildInvoiceHtml(data.transaction);
          openModal('invoiceModal');

          // Toggle mobile/desktop buttons
          var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
          var shareBtn = document.getElementById('btnSharePdf');
          document.querySelectorAll('.desktop-only-btn').forEach(function(el) {
            el.style.display = isMobile ? 'none' : '';
          });
          if (shareBtn) {
            shareBtn.style.display = isMobile ? '' : 'none';
          }

          // Auto-open print guide on mobile
          if (isMobile) {
            var guide = document.getElementById('printGuide');
            if (guide) guide.classList.add('open');
          }

          if (autoPrint) {
            if (isMobile) {
              // On mobile, auto-trigger share/download PDF after a short delay
              setTimeout(() => {
                const printArea = document.querySelector('#invoiceContent #printArea');
                if (printArea) {
                  const invoiceNo = printArea.querySelector('.r-row span:last-child');
                  const filename = invoiceNo ? invoiceNo.textContent.trim() : 'struk';
                  if (typeof shareReceiptPdf === 'function' && navigator.canShare) {
                    shareReceiptPdf(printArea, filename);
                  } else {
                    downloadReceiptPdf(printArea, filename);
                  }
                }
              }, 1000);
            } else {
              setTimeout(() => {
                const printArea = document.querySelector('#invoiceContent #printArea');
                if (printArea) {
                  const paperSize = printArea.dataset.paper || '80';
                  printReceipt(printArea.outerHTML, paperSize);
                }
              }, 800);
            }
          }
        }
      });
  },

  printInvoice() {
    const printArea = document.querySelector('#invoiceContent #printArea');
    if (!printArea) { showToast('Data struk belum siap', 'warning'); return; }
    const paperSize = printArea.dataset.paper || '80';
    printReceipt(printArea.outerHTML, paperSize);
  },

  downloadInvoicePdf() {
    const printArea = document.querySelector('#invoiceContent #printArea');
    if (!printArea) { showToast('Data struk belum siap', 'warning'); return; }
    const invoiceNo = printArea.querySelector('.r-row span:last-child');
    const filename = invoiceNo ? invoiceNo.textContent.trim() : 'struk';
    downloadReceiptPdf(printArea, filename);
  },

  shareInvoicePdf() {
    const printArea = document.querySelector('#invoiceContent #printArea');
    if (!printArea) { showToast('Data struk belum siap', 'warning'); return; }
    const invoiceNo = printArea.querySelector('.r-row span:last-child');
    const filename = invoiceNo ? invoiceNo.textContent.trim() : 'struk';
    shareReceiptPdf(printArea, filename);
  },

  downloadHistoryPdf() {
    const printArea = document.querySelector('#historyDetailContent #printArea');
    if (!printArea) { showToast('Data struk belum siap', 'warning'); return; }
    const invoiceNo = printArea.querySelector('.r-row span:last-child');
    const filename = invoiceNo ? invoiceNo.textContent.trim() : 'struk';
    downloadReceiptPdf(printArea, filename);
  },

  // ---- TRANSACTION HISTORY (Reprint) ----
  openHistory() {
    document.getElementById('historyList').innerHTML = '<div style="text-align:center;padding:30px"><div class="spinner"></div></div>';
    openModal('historyModal');
    apiFetch(BASE_URL + '/api/get_transactions.php')
      .then(data => {
        if (data.success && data.data.length) {
          let html = '<div style="display:flex;flex-direction:column;gap:8px">';
          data.data.forEach(t => {
            const isVoid = t.status === 'void';
            html += `<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:var(--dark);border-radius:var(--radius);${isVoid?'opacity:.5':'cursor:pointer'}"
              ${!isVoid ? `onclick="POS.viewHistoryTx(${t.id})"` : ''}>
              <div>
                <div style="font-weight:600;font-size:13px;font-family:monospace">${escHtml(t.invoice_no)}</div>
                <div style="font-size:11px;color:var(--text-muted)">${escHtml(t.customer_name || '-')} &bull; ${escHtml(t.created_at_formatted)}</div>
              </div>
              <div style="text-align:right">
                <div style="font-weight:700;color:var(--yellow)">${formatRp(t.total)}</div>
                <span style="font-size:10px;padding:2px 6px;border-radius:4px;background:${isVoid?'var(--red)':'var(--green)'}20;color:${isVoid?'var(--red)':'var(--green)'}">${isVoid?'Void':'Selesai'}</span>
              </div>
            </div>`;
          });
          html += '</div>';
          document.getElementById('historyList').innerHTML = html;
        } else {
          document.getElementById('historyList').innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-muted)"><i class="fa-solid fa-receipt" style="font-size:32px;margin-bottom:8px;display:block;opacity:.4"></i>Belum ada transaksi</div>';
        }
      })
      .catch(() => {
        document.getElementById('historyList').innerHTML = '<p style="text-align:center;color:var(--red)">Gagal memuat data</p>';
      });
  },

  viewHistoryTx(id) {
    document.getElementById('historyDetailContent').innerHTML = '<div style="text-align:center;padding:30px"><div class="spinner"></div></div>';
    closeModal('historyModal');
    setTimeout(() => {
      openModal('historyDetailModal');
      apiFetch(BASE_URL + `/api/get_transaction.php?id=${id}`)
        .then(data => {
          if (data.success) {
            document.getElementById('historyDetailContent').innerHTML = buildInvoiceHtml(data.transaction);
          } else {
            document.getElementById('historyDetailContent').innerHTML = '<p style="color:var(--red)">Gagal memuat transaksi</p>';
          }
        })
        .catch(() => {
          document.getElementById('historyDetailContent').innerHTML = '<p style="color:var(--red)">Koneksi gagal</p>';
        });
    }, 300);
  },

  reprintInvoice() {
    const printArea = document.querySelector('#historyDetailContent #printArea');
    if (!printArea) { showToast('Data struk belum siap', 'warning'); return; }
    const paperSize = printArea.dataset.paper || '80';
    printReceipt(printArea.outerHTML, paperSize);
  },

  // ---- EVENTS ----
  bindEvents() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      let timer;
      searchInput.addEventListener('input', e => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          this.searchQuery = e.target.value.trim();
          this.loadProducts();
        }, 350);
      });
    }
  }
};

// ---- HELPER FUNCTIONS ----
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
  const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
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

// Tutup modal saat klik overlay
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
    setTimeout(() => { e.target.style.display = 'none'; }, 250);
  }
});

// Init POS
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('productGrid')) {
    POS.init();
  }
});
