<?php
require __DIR__ . '/init.php';
require_login();

$pageTitle = "Data Master - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'sidebar';
$activeMenu = 'data_master';
$heroTitle = 'Data Master';
$heroSubtitle = 'Akses manajemen kamar dan tamu.';

include __DIR__ . '/layout/header.php';
?>

<section class="section">
  <div class="panel" style="max-width: 980px; margin: 0 auto;">
    <h2 style="margin:0 0 12px;">Menu Data Master</h2>
    <p style="margin:0 0 18px; color:#666; font-weight:700;">Pilih data yang ingin kamu kelola.</p>

    <div style="display:flex; gap:14px; flex-wrap:wrap;">
      <a class="btn" href="rooms.php">Manajemen Kamar</a>
      <a class="btn" href="guests.php">Manajemen Tamu</a>
      <a class="btn btn-muted" href="report.php">Laporan Transaksi</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
