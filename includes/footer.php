<div class="toast-container" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.2/jspdf.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/receipt.js?v=<?= filemtime(ROOT_PATH . '/assets/js/receipt.js') ?>"></script>
<?php if (isset($isAdminPage)): ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<?php else: ?>
<script src="<?= BASE_URL ?>/assets/js/pos.js"></script>
<?php endif; ?>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js').catch(() => {});
}
</script>
</body>
</html>