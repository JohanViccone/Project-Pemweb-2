<?php
require __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('rooms.php');
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  set_flash('error', 'ID tidak valid.');
  redirect('rooms.php');
}

$pdo = db();

try {
  $stmt = $pdo->prepare("DELETE FROM rooms WHERE id=:id");
  $stmt->execute([':id' => $id]);
  set_flash('success', 'Kamar berhasil dihapus.');
} catch (Throwable $e) {
  // biasanya gagal karena foreign key (sudah ada transaksi/stay)
  set_flash('error', 'Tidak bisa menghapus kamar ini karena sudah dipakai di data menginap/transaksi.');
}

redirect('rooms.php');
