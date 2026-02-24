<?php
require __DIR__ . '/init.php';
require_login();

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  set_flash('error', 'ID tidak valid.');
  redirect('report.php');
}

$stmt = $pdo->prepare("
  SELECT t.*, s.id AS stay_id, s.check_in, s.check_out, s.status AS stay_status,
         g.name AS guest_name, r.id AS room_id, r.room_number, r.status AS room_status
  FROM transactions t
  JOIN stays s ON s.id = t.stay_id
  JOIN guests g ON g.id = s.guest_id
  JOIN rooms r ON r.id = s.room_id
  WHERE t.id=:id
");
$stmt->execute([':id' => $id]);
$tx = $stmt->fetch();

if (!$tx) {
  set_flash('error', 'Transaksi tidak ditemukan.');
  redirect('report.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $bookingType = $_POST['booking_type'] ?? $tx['booking_type'];
  $downPayment = (int)preg_replace('/[^0-9]/', '', ($_POST['down_payment'] ?? $tx['down_payment']));
  $paidAmount  = (int)preg_replace('/[^0-9]/', '', ($_POST['paid_amount'] ?? $tx['paid_amount']));
  $status      = $_POST['status'] ?? $tx['status'];

  if (!in_array($bookingType, ['boking','langsung'], true)) $errors[] = 'Jenis pemesanan tidak valid.';
  if (!in_array($status, ['menginap','checkout'], true)) $errors[] = 'Status tidak valid.';

  $totalAmount = (int)$tx['total_amount'];
  $due = max($totalAmount - $downPayment, 0);
  $remaining = max($due - $paidAmount, 0);
  $change = max($paidAmount - $due, 0);

  if (!$errors) {
    $pdo->beginTransaction();
    try {
      $up = $pdo->prepare("
        UPDATE transactions
        SET booking_type=:bt, down_payment=:dp, paid_amount=:paid, remaining_amount=:rem, change_amount=:chg, status=:st
        WHERE id=:id
      ");
      $up->execute([
        ':bt' => $bookingType,
        ':dp' => $downPayment,
        ':paid' => $paidAmount,
        ':rem' => $remaining,
        ':chg' => $change,
        ':st' => $status,
        ':id' => $id,
      ]);

      // update stay + room status mengikuti status transaksi
      if ($status === 'checkout') {
        $pdo->prepare("UPDATE stays SET status='selesai' WHERE id=:sid")->execute([':sid' => (int)$tx['stay_id']]);
        $pdo->prepare("UPDATE rooms SET status='available' WHERE id=:rid AND status<>'maintenance'")->execute([':rid' => (int)$tx['room_id']]);
      } else {
        $pdo->prepare("UPDATE stays SET status='menginap' WHERE id=:sid")->execute([':sid' => (int)$tx['stay_id']]);
        $pdo->prepare("UPDATE rooms SET status='occupied' WHERE id=:rid")->execute([':rid' => (int)$tx['room_id']]);
      }

      $pdo->commit();
      set_flash('success', 'Transaksi berhasil di-update.');
      redirect('report.php');
    } catch (Throwable $e) {
      $pdo->rollBack();
      $errors[] = 'Gagal update transaksi.';
    }
  }
}

$pageTitle = "Edit Transaksi - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'topbar';
$heroTitle = 'Laporan Transaksi';
$heroSubtitle = 'Edit data transaksi.';
$heroActionHtml = '<a class="btn btn-muted" href="report.php">Kembali</a>';

include __DIR__ . '/layout/header.php';
?>

<section class="section">
  <div class="panel">
    <h2 style="margin:0 0 12px;">Edit Transaksi</h2>

    <?php if ($errors): ?>
      <div class="flash flash-error" style="margin: 0 0 16px;">
        <?= e(implode(" ", $errors)) ?>
      </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:18px;">
      <div class="panel soft" style="box-shadow:none;">
        <div style="font-weight:900; font-size:18px;">Nama Tamu</div>
        <div style="font-size:22px; font-weight:900;"><?= e($tx['guest_name']) ?></div>
      </div>
      <div class="panel soft" style="box-shadow:none;">
        <div style="font-weight:900; font-size:18px;">Nomor Kamar</div>
        <div style="font-size:22px; font-weight:900;"><?= e($tx['room_number']) ?></div>
      </div>
    </div>

    <div style="margin-top:14px;" class="panel soft">
      <div style="display:flex; gap:18px; flex-wrap:wrap;">
        <div><b>Check-in:</b> <?= e(format_date_id($tx['check_in'])) ?></div>
        <div><b>Check-out:</b> <?= e(format_date_id($tx['check_out'])) ?></div>
        <div><b>Durasi:</b> <?= e((string)$tx['total_days']) ?> Hari</div>
        <div><b>Harga/hari:</b> <?= e(format_rupiah($tx['price_per_day'])) ?></div>
        <div><b>Total:</b> <?= e(format_rupiah($tx['total_amount'])) ?></div>
      </div>
    </div>

    <form method="post" style="margin-top:14px;">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <div class="form-row">
        <div>
          <label style="font-weight:800;">Jenis Pemesanan</label>
          <select class="input" name="booking_type">
            <option value="boking" <?= $tx['booking_type']==='boking'?'selected':'' ?>>Boking</option>
            <option value="langsung" <?= $tx['booking_type']==='langsung'?'selected':'' ?>>Langsung</option>
          </select>
        </div>
        <div>
          <label style="font-weight:800;">Status Transaksi</label>
          <select class="input" name="status">
            <option value="menginap" <?= $tx['status']==='menginap'?'selected':'' ?>>Menginap</option>
            <option value="checkout" <?= $tx['status']==='checkout'?'selected':'' ?>>Checkout</option>
          </select>
        </div>
      </div>

      <div class="form-row" style="margin-top:14px;">
        <div>
          <label style="font-weight:800;">Uang Muka (DP)</label>
          <input class="input" name="down_payment" value="<?= e((string)$tx['down_payment']) ?>" />
        </div>
        <div>
          <label style="font-weight:800;">Uang yang Dibayar</label>
          <input class="input" name="paid_amount" value="<?= e((string)$tx['paid_amount']) ?>" />
        </div>
      </div>

      <div class="panel soft" style="margin-top:14px; box-shadow:none;">
        <div style="display:flex; gap:20px; flex-wrap:wrap; font-weight:900;">
          <div>Sisa Pembayaran: <?= e(format_rupiah($tx['remaining_amount'])) ?></div>
          <div>Kembalian: <?= e(format_rupiah($tx['change_amount'])) ?></div>
        </div>
        <div style="color:#666;font-weight:700;margin-top:6px;">
          Nilai sisa/kembalian akan dihitung ulang saat kamu klik Update.
        </div>
      </div>

      <div style="margin-top:18px; display:flex; gap:12px; justify-content:flex-end;">
        <a class="btn btn-muted" href="report.php">Batal</a>
        <button class="btn" type="submit">Update</button>
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
