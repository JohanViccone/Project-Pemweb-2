<?php
require __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('report.php');
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  set_flash('error', 'ID tidak valid.');
  redirect('report.php');
}

$pdo = db();

$stmt = $pdo->prepare("
  SELECT t.id, t.stay_id, s.room_id
  FROM transactions t
  JOIN stays s ON s.id = t.stay_id
  WHERE t.id=:id
");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row) {
  set_flash('error', 'Transaksi tidak ditemukan.');
  redirect('report.php');
}

$stayId = (int)$row['stay_id'];
$roomId = (int)$row['room_id'];

$pdo->beginTransaction();
try {
  // hapus stay -> transaksi akan terhapus karena FK cascade
  $pdo->prepare("DELETE FROM stays WHERE id=:id")->execute([':id' => $stayId]);

  // update status kamar
  $active = $pdo->prepare("SELECT COUNT(*) AS c FROM stays WHERE room_id=:rid AND status='menginap'");
  $active->execute([':rid' => $roomId]);
  $c = (int)$active->fetch()['c'];
  if ($c <= 0) {
    $pdo->prepare("UPDATE rooms SET status='available' WHERE id=:id AND status<>'maintenance'")->execute([':id' => $roomId]);
  }

  $pdo->commit();
  set_flash('success', 'Transaksi berhasil dihapus.');
} catch (Throwable $e) {
  $pdo->rollBack();
  set_flash('error', 'Gagal menghapus transaksi.');
}

redirect('report.php');
