<?php
require __DIR__ . '/init.php';
require_login();

$pdo = db();

$totalGuests = (int)$pdo->query("SELECT COUNT(*) AS c FROM guests")->fetch()['c'];
$totalRooms  = (int)$pdo->query("SELECT COUNT(*) AS c FROM rooms")->fetch()['c'];
$occupied    = (int)$pdo->query("SELECT COUNT(*) AS c FROM rooms WHERE status='occupied'")->fetch()['c'];
$available   = (int)$pdo->query("SELECT COUNT(*) AS c FROM rooms WHERE status='available'")->fetch()['c'];

$pageTitle = "Dashboard - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'topbar';
$heroTitle = 'Dashboard';
include __DIR__ . '/layout/header.php';
?>

<section class="section">
  <div class="grid-4">
    <div class="stat-card">
      <div class="stat-head">Total Tamu</div>
      <div class="stat-body">
        <div class="stat-value"><?= e((string)$totalGuests) ?></div>
        <a class="stat-link" href="guests.php">Tabel Tamu &gt;&gt;</a>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-head">Total Kamar</div>
      <div class="stat-body">
        <div class="stat-value"><?= e((string)$totalRooms) ?></div>
        <a class="stat-link" href="rooms.php">Tabel Kamar &gt;&gt;</a>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-head">Kamar Terisi</div>
      <div class="stat-body">
        <div class="stat-value"><?= e((string)$occupied) ?></div>
        <a class="stat-link" href="rooms.php?status=occupied">Tabel Laporan &gt;&gt;</a>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-head">Kamar Tersedia</div>
      <div class="stat-body">
        <div class="stat-value"><?= e((string)$available) ?></div>
        <a class="stat-link" href="rooms.php?status=available">Tabel Kamar &gt;&gt;</a>
      </div>
    </div>
  </div>

  <div style="margin-top:28px; display:flex; gap:12px; flex-wrap:wrap;">
    <a class="btn" href="transaction_create.php">Transaksi &amp; Boking</a>
    <a class="btn btn-muted" href="report.php">Laporan Transaksi</a>
    <a class="btn btn-muted" href="settings.php">Pengaturan Toko</a>
    <a class="btn btn-muted" href="logout.php">Logout</a>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
