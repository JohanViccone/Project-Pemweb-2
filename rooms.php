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
  $where[] = "(rooms.room_number LIKE :q OR rooms.type LIKE :q)";
  $params[':q'] = '%' . $q . '%';
}
if ($status !== '' && in_array($status, ['available','occupied','maintenance'], true)) {
  $where[] = "rooms.status = :status";
  $params[':status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM rooms {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['c'];
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("SELECT * FROM rooms {$whereSql} ORDER BY room_number ASC LIMIT {$limit} OFFSET {$offset}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$summary = $pdo->query("
  SELECT
    COUNT(*) AS total,
    SUM(status='available') AS available,
    SUM(status='occupied') AS occupied,
    SUM(status='maintenance') AS maintenance
  FROM rooms
")->fetch();

function room_badge(string $status): array {
  switch ($status) {
    case 'occupied': return ['Terisi', 'badge-blue'];
    case 'available': return ['Tersedia', 'badge-green'];
    case 'maintenance': return ['Maintence', 'badge-red'];
    default: return [$status, 'badge-blue'];
  }
}

$pageTitle = "Manajemen Kamar - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'topbar';
$heroTitle = 'Manajemen Kamar';
$heroSubtitle = 'Kelola data check in dan chek out Kamar.';
$heroActionHtml = '<a class="btn" href="room_form.php"><span class="icon">' . svg_icon('plus', 22) . '</span>Tambah Kamar</a>';

include __DIR__ . '/layout/header.php';
?>

<div class="summary-row">
  <div class="summary-card">
    <div class="summary-icon" style="background:#AFCDE3"><?= svg_icon('user', 26) ?></div>
    <div class="summary-text">
      <div class="label">TOTAL KAMAR</div>
      <div class="value"><?= e((string)($summary['total'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon" style="background:#FFF0B8"><?= svg_icon('bed', 26) ?></div>
    <div class="summary-text">
      <div class="label">KAMAR TERSEDIA</div>
      <div class="value"><?= e((string)($summary['available'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon" style="background:#C8FFAE"><?= svg_icon('check', 26) ?></div>
    <div class="summary-text">
      <div class="label">KAMAR TERISI</div>
      <div class="value"><?= e((string)($summary['occupied'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon" style="background:#FFF0B8"><?= svg_icon('settings', 26) ?></div>
    <div class="summary-text">
      <div class="label">MAINTENANCE</div>
      <div class="value"><?= e((string)($summary['maintenance'] ?? 0)) ?></div>
    </div>
  </div>
</div>

<section class="section">
  <form class="searchbar" method="get" action="">
    <div class="search">
      <span class="icon" style="opacity:.65"><?= svg_icon('file', 18) ?></span>
      <input name="q" placeholder="Cari nama atau nomor kamar" value="<?= e($q) ?>" />
    </div>

    <div style="display:flex; gap:10px; align-items:center;">
      <select class="input" name="status" style="width:180px; padding: 12px 12px; border-radius:999px; border:1px solid var(--border);">
        <option value="">Semua</option>
        <option value="available" <?= $status==='available'?'selected':'' ?>>Tersedia</option>
        <option value="occupied" <?= $status==='occupied'?'selected':'' ?>>Terisi</option>
        <option value="maintenance" <?= $status==='maintenance'?'selected':'' ?>>Maintence</option>
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
          <th>NO Kamar</th>
          <th>Tipe</th>
          <th>Harga</th>
          <th class="td-center">Status</th>
          <th class="td-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" style="padding:18px;">Data tidak ditemukan.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <?php [$label, $cls] = room_badge($r['status']); ?>
          <tr>
            <td><b><?= e($r['room_number']) ?></b></td>
            <td><?= e($r['type']) ?></td>
            <td><?= e(format_rupiah($r['price_per_day'])) ?></td>
            <td class="td-center"><span class="badge <?= e($cls) ?>"><?= e($label) ?></span></td>
            <td class="td-center">
              <div class="actions">
                <a class="icon-btn" href="room_form.php?id=<?= (int)$r['id'] ?>" title="Edit">
                  <?= svg_icon('pencil', 20) ?>
                </a>

                <form method="post" action="room_delete.php" onsubmit="return confirm('Hapus kamar ini?');" style="display:inline;">
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
