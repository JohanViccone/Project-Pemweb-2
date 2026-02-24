<?php
require __DIR__ . '/init.php';
require_login();

$pdo = db();

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($q !== '') {
  $where[] = "(g.name LIKE :q OR r.room_number LIKE :q)";
  $params[':q'] = '%' . $q . '%';
}
if ($status !== '' && in_array($status, ['menginap','checkout'], true)) {
  $where[] = "t.status = :status";
  $params[':status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("
  SELECT COUNT(*) AS c
  FROM transactions t
  JOIN stays s ON s.id = t.stay_id
  JOIN guests g ON g.id = s.guest_id
  JOIN rooms r ON r.id = s.room_id
  {$whereSql}
");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['c'];
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("
  SELECT t.*, s.check_in, s.check_out, g.name AS guest_name, r.room_number
  FROM transactions t
  JOIN stays s ON s.id = t.stay_id
  JOIN guests g ON g.id = s.guest_id
  JOIN rooms r ON r.id = s.room_id
  {$whereSql}
  ORDER BY t.id DESC
  LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

function tx_badge(string $status): array {
  switch ($status) {
    case 'checkout': return ['Checkout', 'badge-blue'];
    case 'menginap': return ['Menginap', 'badge-green'];
    default: return [$status, 'badge-blue'];
  }
}

$pageTitle = "Laporan Transaksi - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'topbar';
$heroTitle = 'Laporan Transaksi';
$heroSubtitle = 'Kelola data check in dan chek out Kamar.';

include __DIR__ . '/layout/header.php';
?>

<section class="section">
  <form class="searchbar" method="get" action="">
    <div class="search">
      <span class="icon" style="opacity:.65"><?= svg_icon('file', 18) ?></span>
      <input name="q" placeholder="Cari nama atau nomor kamar" value="<?= e($q) ?>" />
    </div>

    <div style="display:flex; gap:10px; align-items:center;">
      <select class="input" name="status" style="width:180px; padding: 12px 12px; border-radius:999px; border:1px solid var(--border);">
        <option value="">Semua</option>
        <option value="menginap" <?= $status==='menginap'?'selected':'' ?>>Menginap</option>
        <option value="checkout" <?= $status==='checkout'?'selected':'' ?>>Checkout</option>
      </select>
      <button class="filter-btn" type="submit">
        <span class="icon"><?= svg_icon('filter', 20) ?></span>
        Filter
      </button>
    </div>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Nama</th>
          <th>NO Kamar</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Durasi</th>
          <th>Tagihan</th>
          <th class="td-center">Status</th>
          <th class="td-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" style="padding:18px;">Data tidak ditemukan.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <?php [$label, $cls] = tx_badge($r['status']); ?>
          <tr>
            <td><b><?= e($r['guest_name']) ?></b></td>
            <td><?= e($r['room_number']) ?></td>
            <td><?= e(format_date_id($r['check_in'])) ?></td>
            <td><?= e(format_date_id($r['check_out'])) ?></td>
            <td><?= e((string)$r['total_days']) ?> Hari</td>
            <td><?= e(format_rupiah($r['total_amount'])) ?></td>
            <td class="td-center"><span class="badge <?= e($cls) ?>"><?= e($label) ?></span></td>
            <td class="td-center">
              <div class="actions">
                <a class="icon-btn" href="transaction_edit.php?id=<?= (int)$r['id'] ?>" title="Edit">
                  <?= svg_icon('pencil', 20) ?>
                </a>

                <form method="post" action="transaction_delete.php" onsubmit="return confirm('Hapus transaksi ini?');" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                  <button class="icon-btn danger" type="submit" title="Hapus" style="background:transparent;cursor:pointer;">
                    <?= svg_icon('trash', 20) ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination-pill">
    <div class="pill">
      <?php
        $prev = max(1, $page - 1);
        $next = min($totalPages, $page + 1);
        $qs = function($p) use ($q, $status) {
          $arr = ['page' => $p];
          if ($q !== '') $arr['q'] = $q;
          if ($status !== '') $arr['status'] = $status;
          return http_build_query($arr);
        };
      ?>
      <a href="?<?= e($qs($prev)) ?>">kembali</a>
      <span><?= e((string)$page) ?></span>
      <a href="?<?= e($qs($next)) ?>">next</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
