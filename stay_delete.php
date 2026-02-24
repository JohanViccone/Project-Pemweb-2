<?php
require __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('guests.php');
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  set_flash('error', 'ID tidak valid.');
  redirect('guests.php');
}

$pdo = db();

$stmt = $pdo->prepare("SELECT room_id FROM stays WHERE id=:id");
$stmt->execute([':id' => $id]);
$stay = $stmt->fetch();
if (!$stay) {
  set_flash('error', 'Data tidak ditemukan.');
  redirect('guests.php');
}

$roomId = (int)$stay['room_id'];

$pdo->beginTransaction();
try {
  $pdo->prepare("DELETE FROM stays WHERE id=:id")->execute([':id' => $id]);

  // Jika tidak ada stay aktif lain di kamar itu, set tersedia (kecuali maintenance)
  $active = $pdo->prepare("SELECT COUNT(*) AS c FROM stays WHERE room_id=:rid AND status='menginap'");
  $active->execute([':rid' => $roomId]);
  $c = (int)$active->fetch()['c'];
  if ($c <= 0) {
    $pdo->prepare("UPDATE rooms SET status='available' WHERE id=:id AND status<>'maintenance'")->execute([':id' => $roomId]);
  }

  $pdo->commit();
  set_flash('success', 'Data tamu berhasil dihapus.');
} catch (Throwable $e) {
  $pdo->rollBack();
  set_flash('error', 'Gagal menghapus data.');
}

redirect('guests.php');
