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
if ($status !== '' && in_array($status, ['menginap','selesai'], true)) {
  $where[] = "s.status = :status";
  $params[':status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("
  SELECT COUNT(*) AS c
  FROM stays s
  JOIN guests g ON g.id = s.guest_id
  JOIN rooms r ON r.id = s.room_id
  {$whereSql}
");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['c'];
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("
  SELECT s.*, g.name AS guest_name, r.room_number
  FROM stays s
  JOIN guests g ON g.id = s.guest_id
  JOIN rooms r ON r.id = s.room_id
  {$whereSql}
  ORDER BY s.id DESC
  LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$summary = $pdo->query("
  SELECT
    (SELECT COUNT(*) FROM guests) AS total_guests,
    (SELECT COUNT(*) FROM stays WHERE status='menginap') AS total_menginap,
    (SELECT COUNT(*) FROM stays WHERE status='selesai') AS checkout_selesai,
    (SELECT COUNT(*) FROM rooms WHERE status='available') AS kamar_tersedia
")->fetch();

function stay_badge(string $status): array {
  switch ($status) {
    case 'menginap': return ['Menginap', 'badge-blue'];
    case 'selesai': return ['Selesai', 'badge-green'];
    default: return [$status, 'badge-blue'];
  }
}

$pageTitle = "Manajemen Tamu - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'topbar';
$heroTitle = 'Manajemen Tamu';
$heroSubtitle = 'Kelola data check in dan chek out tamu.';
$heroActionHtml = '<a class="btn" href="transaction_create.php"><span class="icon">' . svg_icon('plus', 22) . '</span>Tambah Tamu Baru</a>';

include __DIR__ . '/layout/header.php';
?>

<div class="summary-row">
  <div class="summary-card">
    <div class="summary-icon" style="background:#AFCDE3"><?= svg_icon('user', 26) ?></div>
    <div class="summary-text">
      <div class="label">TOTAL TAMU</div>
      <div class="value"><?= e((string)($summary['total_guests'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon" style="background:#FFF0B8"><?= svg_icon('bed', 26) ?></div>
    <div class="summary-text">
      <div class="label">TOTAL MENGINAP</div>
      <div class="value"><?= e((string)($summary['total_menginap'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon" style="background:#C8FFAE"><?= svg_icon('check', 26) ?></div>
    <div class="summary-text">
      <div class="label">CHEKOUT SELESAI</div>
      <div class="value"><?= e((string)($summary['checkout_selesai'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon" style="background:#FFF0B8"><?= svg_icon('settings', 26) ?></div>
    <div class="summary-text">
      <div class="label">KAMAR TERSEDIA</div>
      <div class="value"><?= e((string)($summary['kamar_tersedia'] ?? 0)) ?></div>
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
        <option value="menginap" <?= $status==='menginap'?'selected':'' ?>>Menginap</option>
        <option value="selesai" <?= $status==='selesai'?'selected':'' ?>>Selesai</option>
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
          <th>Tamu</th>
          <th>Kamar</th>
          <th>Check In</th>
          <th>Check Out</th>
          <th class="td-center">Status</th>
          <th class="td-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" style="padding:18px;">Data tidak ditemukan.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <?php [$label, $cls] = stay_badge($r['status']); ?>
          <?php $initial = strtoupper(first_char($r['guest_name'])); ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:14px;">
                <span class="avatar-circle"><?= e($initial) ?></span>
                <span style="font-weight:800;"><?= e($r['guest_name']) ?></span>
              </div>
            </td>
            <td><?= e($r['room_number']) ?></td>
            <td><?= e($r['check_in']) ?></td>
            <td><?= e($r['check_out']) ?></td>
            <td class="td-center"><span class="badge <?= e($cls) ?>"><?= e($label) ?></span></td>
            <td class="td-center">
              <div class="actions">
                <a class="icon-btn" href="stay_form.php?id=<?= (int)$r['id'] ?>" title="Edit">
                  <?= svg_icon('pencil', 20) ?>
                </a>

                <form method="post" action="stay_delete.php" onsubmit="return confirm('Hapus data menginap ini?');" style="display:inline;">
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
