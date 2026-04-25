// ============================================================
//  RECEIPT / INVOICE - Shared Functions
//  Digunakan oleh pos.js dan admin.js
// ============================================================

function buildInvoiceHtml(t) {
  const logoSrc = t.store_logo
    ? (BASE_URL + '/uploads/logo/' + t.store_logo)
    : (BASE_URL + '/assets/images/logo.png');
  const paper = t.receipt_paper_size || '80';
  const sep = paper === '58' ? '--------------------------------' : '----------------------------------------';

  let itemsHtml = '';
  (t.items || []).forEach(i => {
    itemsHtml += `<div class="r-item-name">${escHtml(i.product_name)}</div>`;
    itemsHtml += `<div class="r-item-detail"><span>${i.qty} x ${formatRp(i.price)}</span><span>${formatRp(i.subtotal)}</span></div>`;
  });

  const change = parseFloat(t.change_amount) > 0
    ? `<div class="r-row"><span>Kembalian</span><span>${formatRp(t.change_amount)}</span></div>` : '';

  // Tax line (if tax exists)
  const taxAmount = parseFloat(t.tax_amount || 0);
  const taxLine = taxAmount > 0
    ? `<div class="r-row"><span>Pajak (${t.tax_percent || 0}%)</span><span>${formatRp(taxAmount)}</span></div>` : '';

  // Discount line
  const discount = parseFloat(t.discount || 0);
  const discountLine = discount > 0
    ? `<div class="r-row"><span>Diskon</span><span>-${formatRp(discount)}</span></div>` : '';

  return `
  <div id="printArea" data-paper="${paper}">
    <div class="r-center">
      <img src="${logoSrc}" class="r-logo" onerror="this.style.display='none'">
      <div class="r-store">${escHtml(t.store_name)}</div>
      ${t.store_address ? `<div class="r-addr">${escHtml(t.store_address)}</div>` : ''}
      ${t.store_phone ? `<div class="r-addr">Telp: ${escHtml(t.store_phone)}</div>` : ''}
    </div>
    <div class="r-center" style="font-size:10px;letter-spacing:1px">${sep}</div>
    <div class="r-row"><span>No</span><span>${escHtml(t.invoice_no)}</span></div>
    <div class="r-row"><span>Tgl</span><span>${escHtml(t.created_at)}</span></div>
    ${t.customer_name ? `<div class="r-row"><span>Plg</span><span>${escHtml(t.customer_name)}</span></div>` : ''}
    <div class="r-row"><span>Kasir</span><span>${escHtml(t.cashier_name || '-')}</span></div>
    <div class="r-center" style="font-size:10px;letter-spacing:1px">${sep}</div>
    ${itemsHtml}
    <div class="r-center" style="font-size:10px;letter-spacing:1px">${sep}</div>
    <div class="r-row"><span>Subtotal</span><span>${formatRp(t.subtotal)}</span></div>
    ${discountLine}
    ${taxLine}
    <div class="r-total"><span>TOTAL</span><span>${formatRp(t.total)}</span></div>
    <div class="r-row"><span>Bayar (${escHtml(t.payment_method)})</span><span>${formatRp(t.paid_amount)}</span></div>
    ${change}
    <div class="r-center" style="font-size:10px;letter-spacing:1px">${sep}</div>
    <div class="r-footer">${escHtml(t.receipt_footer || 'Terima kasih telah berbelanja!')}</div>
  </div>`;
}

// ---- Build print CSS for thermal receipt ----
function _buildPrintStyles(paperSize) {
  var pw = paperSize === '58' ? '48mm' : '72mm';
  var ps = paperSize === '58' ? '58mm' : '80mm';

  return [
    // Force paper size for thermal printer
    '@page{size:' + ps + ' auto !important;margin:0 !important}',
    '@media print{',
      'html,body{width:' + pw + ' !important;min-width:0 !important;max-width:' + ps + ' !important;margin:0 !important;padding:0 !important}',
      '#printArea{width:100% !important}',
    '}',
    // Screen styles
    '*{box-sizing:border-box;margin:0;padding:0}',
    'html{width:' + ps + '}',
    'body{font-family:"Courier New",Courier,monospace;width:' + pw + ';max-width:' + ps + ';margin:0 auto;padding:3mm 2mm;font-size:12px;color:#000;background:#fff;line-height:1.4}',
    '.r-center{text-align:center}',
    '.r-logo{max-height:36px;margin:0 auto 3px;display:block}',
    '.r-store{font-size:14px;font-weight:bold;text-align:center}',
    '.r-addr{font-size:10px;text-align:center;line-height:1.2;margin-bottom:2px}',
    '.r-row{display:flex;justify-content:space-between;font-size:11px;line-height:1.3}',
    '.r-item-name{font-size:11px;margin-top:2px}',
    '.r-item-detail{display:flex;justify-content:space-between;font-size:11px;color:#333}',
    '.r-total{display:flex;justify-content:space-between;font-size:14px;font-weight:bold;padding:3px 0;border-top:1px dashed #000;border-bottom:1px dashed #000;margin:4px 0}',
    '.r-footer{text-align:center;font-size:10px;margin-top:6px;padding-bottom:10mm}',
    '.no-print{margin:12px 4px 0;padding:10px;background:#fffbe6;border:1px solid #e6c200;border-radius:6px;font-size:11px;color:#7a6500;text-align:center;line-height:1.5}',
    '@media print{.no-print{display:none !important}}'
  ].join('');
}

/**
 * Print receipt.
 * Mobile: opens new window with receipt + auto-print.
 * Desktop: hidden iframe.
 */
function printReceipt(contentHtml, paperSize) {
  paperSize = paperSize || '80';
  var styles = _buildPrintStyles(paperSize);
  var ps = paperSize === '58' ? '58mm' : '80mm';

  var isMobile = /Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  var hint = isMobile
    ? '<div class="no-print">Tip: Jika ukuran kertas A4, tekan Download PDF di halaman sebelumnya lalu cetak dari file PDF-nya. Ukuran otomatis ' + ps + '.</div>'
    : '';

  var fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8">' +
    '<meta name="viewport" content="width=device-width,initial-scale=1">' +
    '<title>Struk</title><style>' + styles + '</style></head><body>' +
    contentHtml + hint + '</body></html>';

  if (isMobile) {
    printViaMobileWindow(fullHtml);
  } else {
    printViaIframe(fullHtml);
  }
}

/** Desktop: hidden iframe print */
function printViaIframe(fullHtml) {
  try {
    var iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;opacity:0;';
    document.body.appendChild(iframe);

    var doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open();
    doc.write(fullHtml);
    doc.close();

    setTimeout(function () {
      try {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
      } catch (e) {
        printViaMobileWindow(fullHtml);
      }
      try {
        iframe.contentWindow.onafterprint = function() {
          if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
        };
      } catch(e) {}
      setTimeout(function () {
        if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
      }, 60000);
    }, 600);
  } catch (e) {
    printViaMobileWindow(fullHtml);
  }
}

/**
 * Mobile print: window.open + document.write (same-origin, @page CSS works).
 * Avoids Blob URL which some Android Chrome versions ignore @page size on.
 */
function printViaMobileWindow(fullHtml) {
  var printScript = '<script>' +
    'function _doPrint(){window.print()}' +
    'window.onload=function(){' +
      'var imgs=document.querySelectorAll("img");' +
      'if(!imgs.length){setTimeout(_doPrint,400);return}' +
      'var loaded=0,total=imgs.length;' +
      'function check(){loaded++;if(loaded>=total)setTimeout(_doPrint,400)}' +
      'imgs.forEach(function(i){if(i.complete){check()}else{i.onload=check;i.onerror=check}})' +
    '};' +
  '<\/script>';

  // Strategy 1: window.open with document.write (same-origin → @page CSS works)
  var win = window.open('', '_blank');
  if (win) {
    win.document.open();
    win.document.write(fullHtml + printScript);
    win.document.close();
    return;
  }

  // Strategy 2: Blob URL fallback (popup may be blocked in non-click context)
  try {
    var blob = new Blob([fullHtml + printScript], { type: 'text/html' });
    var url = URL.createObjectURL(blob);
    window.open(url, '_blank') || (window.location.href = url);
    setTimeout(function() { URL.revokeObjectURL(url); }, 120000);
  } catch(e) {
    showToast('Gagal membuka print. Coba tekan tombol Cetak lagi.', 'error');
  }
}

/** Legacy fallback alias */
function printViaWindow(fullHtml) { printViaMobileWindow(fullHtml); }
function printViaBlobUrl(fullHtml) { printViaMobileWindow(fullHtml); }

// ---- PDF Library Loader (robust — handles defer, slow CDN, retries) ----
var _pdfLibsLoading = null;

function _pdfLibsReady() {
  return typeof window.html2canvas === 'function' && typeof window.jspdf === 'object';
}

function _ensurePdfLibs() {
  if (_pdfLibsReady()) return Promise.resolve();
  if (_pdfLibsLoading) return _pdfLibsLoading;

  _pdfLibsLoading = new Promise(function(resolve, reject) {

    // Step 1: Maybe defer scripts are still loading — wait up to 3s
    var waited = 0;
    function waitForDefer() {
      if (_pdfLibsReady()) { _pdfLibsLoading = null; resolve(); return; }
      waited += 300;
      if (waited < 3000) { setTimeout(waitForDefer, 300); return; }
      // Step 2: Still not loaded — inject dynamically
      _injectPdfScripts(resolve, reject);
    }
    waitForDefer();
  });

  return _pdfLibsLoading;
}

function _injectPdfScripts(resolve, reject) {
  var needed = 0, done = 0;

  function check() {
    done++;
    if (done >= needed) {
      // Give scripts a moment to register globals
      setTimeout(function() {
        _pdfLibsLoading = null;
        if (_pdfLibsReady()) {
          resolve();
        } else {
          reject(new Error('Library PDF gagal dimuat'));
        }
      }, 200);
    }
  }

  if (typeof window.html2canvas !== 'function') {
    needed++;
    var s1 = document.createElement('script');
    s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
    s1.onload = check;
    s1.onerror = function() { _pdfLibsLoading = null; reject(new Error('html2canvas gagal dimuat dari CDN')); };
    document.head.appendChild(s1);
  }
  if (typeof window.jspdf !== 'object') {
    needed++;
    var s2 = document.createElement('script');
    s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.2/jspdf.umd.min.js';
    s2.onload = check;
    s2.onerror = function() { _pdfLibsLoading = null; reject(new Error('jsPDF gagal dimuat dari CDN')); };
    document.head.appendChild(s2);
  }
  if (needed === 0) {
    _pdfLibsLoading = null;
    if (_pdfLibsReady()) resolve();
    else reject(new Error('Library PDF tidak tersedia'));
  }
}

/**
 * Download receipt as PDF (correct thermal paper size, no print dialog).
 * Works reliably on mobile — generates 80mm/58mm PDF file.
 */
function downloadReceiptPdf(printAreaEl, filename) {
  if (!printAreaEl) { showToast('Data struk belum siap', 'warning'); return; }

  // Find any active download button
  var btn = document.getElementById('btnDownloadPdf') || document.getElementById('btnDownloadPdfHistory');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memuat...'; }

  function resetBtn() {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-download"></i> Download PDF'; }
  }

  _ensurePdfLibs().then(function() {
    _generatePdf(printAreaEl, filename, resetBtn, 'download');
  }).catch(function(err) {
    console.error('PDF lib load error:', err);
    resetBtn();
    showToast('Gagal memuat library PDF. Coba refresh halaman (tarik ke bawah) lalu coba lagi.', 'error');
  });
}

/**
 * Share receipt PDF via Web Share API (mobile: opens share sheet → print via printer app).
 * Falls back to download if sharing is not supported.
 */
function shareReceiptPdf(printAreaEl, filename) {
  if (!printAreaEl) { showToast('Data struk belum siap', 'warning'); return; }

  var btn = document.getElementById('btnSharePdf');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyiapkan...'; }

  function resetBtn() {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-share-nodes"></i> Kirim ke Printer'; }
  }

  _ensurePdfLibs().then(function() {
    _generatePdf(printAreaEl, filename, resetBtn, 'share');
  }).catch(function(err) {
    console.error('PDF lib load error:', err);
    resetBtn();
    showToast('Gagal memuat library PDF. Coba refresh lalu coba lagi.', 'error');
  });
}

function _generatePdf(printAreaEl, filename, resetBtn, mode) {
  var paperSize = printAreaEl.dataset.paper || '80';
  var pdfWidthMm = paperSize === '58' ? 58 : 80;
  var pxWidth = pdfWidthMm * 3.5; // higher multiplier for better quality

  // Clone into an on-screen container (behind overlay) — fixes html2canvas on mobile
  var overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:99998;background:rgba(255,255,255,0.95);display:flex;align-items:flex-start;justify-content:center;overflow:auto;padding:10px;';
  overlay.innerHTML = '<div style="text-align:center;padding:40px 20px;font-size:14px;color:#333"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;margin-bottom:10px;display:block"></i>Membuat PDF...</div>';

  var wrapper = document.createElement('div');
  wrapper.style.cssText = 'position:absolute;top:0;left:0;background:#fff;padding:8px 6px;' +
    'width:' + pxWidth + 'px;font-family:"Courier New",Courier,monospace;color:#000;z-index:99997;';
  wrapper.innerHTML = printAreaEl.innerHTML;

  // Force black/white colors for PDF
  wrapper.querySelectorAll('*').forEach(function(el) {
    el.style.color = '#000';
    el.style.borderColor = '#000';
  });
  wrapper.querySelectorAll('.r-total').forEach(function(el) {
    el.style.borderTop = '1px dashed #000';
    el.style.borderBottom = '1px dashed #000';
  });
  wrapper.querySelectorAll('.r-addr, .r-item-detail, .r-footer').forEach(function(el) {
    el.style.color = '#333';
  });
  wrapper.querySelectorAll('.r-row, .r-item-detail, .r-total').forEach(function(el) {
    el.style.display = 'flex';
    el.style.justifyContent = 'space-between';
  });
  wrapper.querySelectorAll('.r-center').forEach(function(el) { el.style.textAlign = 'center'; });
  wrapper.querySelectorAll('.r-logo').forEach(function(el) {
    el.style.display = 'block';
    el.style.margin = '0 auto 3px';
    el.style.maxHeight = '36px';
  });

  document.body.appendChild(overlay);
  document.body.appendChild(wrapper);

  // Wait a frame for layout, then capture
  requestAnimationFrame(function() {
    setTimeout(function() {
      html2canvas(wrapper, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false,
        width: pxWidth,
        windowWidth: pxWidth
      }).then(function(canvas) {
        document.body.removeChild(wrapper);
        document.body.removeChild(overlay);

        var imgData = canvas.toDataURL('image/png');
        var imgW = canvas.width;
        var imgH = canvas.height;
        var pdfHeightMm = (imgH * pdfWidthMm) / imgW;

        var jsPDF = jspdf.jsPDF;
        var doc = new jsPDF({
          orientation: 'portrait',
          unit: 'mm',
          format: [pdfWidthMm, pdfHeightMm + 5]
        });

        doc.addImage(imgData, 'PNG', 0, 0, pdfWidthMm, pdfHeightMm);

        var safeName = (filename || 'struk') + '.pdf';

        // Share mode: use Web Share API if available
        if (mode === 'share' && navigator.canShare) {
          var pdfBlob = doc.output('blob');
          var pdfFile = new File([pdfBlob], safeName, { type: 'application/pdf' });
          if (navigator.canShare({ files: [pdfFile] })) {
            navigator.share({
              title: 'Struk ' + (filename || ''),
              text: 'Struk pembelian',
              files: [pdfFile]
            }).then(function() {
              resetBtn();
              showToast('Pilih printer thermal kamu dari daftar share.', 'success');
            }).catch(function(err) {
              // User cancelled share — still save as download
              if (err.name !== 'AbortError') {
                doc.save(safeName);
                showToast('Share gagal. PDF sudah di-download.', 'info');
              }
              resetBtn();
            });
            return;
          }
        }

        // Default: download
        doc.save(safeName);
        resetBtn();
        showToast('PDF berhasil di-download! Ukuran kertas: ' + pdfWidthMm + 'mm', 'success');
      }).catch(function(err) {
        if (wrapper.parentNode) document.body.removeChild(wrapper);
        if (overlay.parentNode) document.body.removeChild(overlay);
        console.error('PDF generation error:', err);
        resetBtn();
        showToast('Gagal buat PDF. Coba lagi.', 'error');
      });
    }, 200);
  });
}
