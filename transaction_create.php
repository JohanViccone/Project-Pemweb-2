<?php
require __DIR__ . '/init.php';
require_login();

$pdo = db();

$rooms = $pdo->query("SELECT room_number, price_per_day, status FROM rooms ORDER BY room_number ASC")->fetchAll();

// Map room_number => price
$priceMap = [];
foreach ($rooms as $r) {
  $priceMap[$r['room_number']] = (int)$r['price_per_day'];
}

$errors = [];
$data = [
  'guest_name' => '',
  'room_number' => '',
  'check_in' => '',
  'check_out' => '',
  'price_per_day' => '',
  'total_days' => '1',
  'booking_type' => 'langsung',
  'down_payment' => '',
  'paid_amount' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $data['guest_name'] = trim($_POST['guest_name'] ?? '');
  $data['room_number'] = trim($_POST['room_number'] ?? '');
  $data['check_in'] = $_POST['check_in'] ?? '';
  $data['check_out'] = $_POST['check_out'] ?? '';
  $data['price_per_day'] = (int)preg_replace('/[^0-9]/', '', ($_POST['price_per_day'] ?? '0'));
  $data['booking_type'] = $_POST['booking_type'] ?? 'langsung';
  $data['down_payment'] = (int)preg_replace('/[^0-9]/', '', ($_POST['down_payment'] ?? '0'));
  $data['paid_amount'] = (int)preg_replace('/[^0-9]/', '', ($_POST['paid_amount'] ?? '0'));

  if ($data['guest_name'] === '') $errors[] = 'Nama tamu wajib diisi.';
  if ($data['room_number'] === '') $errors[] = 'Nomor kamar wajib diisi.';
  if ($data['check_in'] === '' || $data['check_out'] === '') $errors[] = 'Tanggal check-in dan check-out wajib diisi.';
  if (!in_array($data['booking_type'], ['boking','langsung'], true)) $errors[] = 'Jenis pemesanan tidak valid.';

  // cek kamar
  $roomStmt = $pdo->prepare("SELECT * FROM rooms WHERE room_number=:rn LIMIT 1");
  $roomStmt->execute([':rn' => $data['room_number']]);
  $room = $roomStmt->fetch();
  if (!$room) {
    $errors[] = 'Nomor kamar tidak ditemukan.';
  } else {
    if ($room['status'] === 'maintenance') $errors[] = 'Kamar maintenance tidak bisa dipesan.';
    if ($room['status'] === 'occupied') $errors[] = 'Kamar sedang terisi.';
  }

  // harga default dari kamar jika kosong
  if ($data['price_per_day'] <= 0 && $room) {
    $data['price_per_day'] = (int)$room['price_per_day'];
  }
  if ($data['price_per_day'] <= 0) $errors[] = 'Harga / hari harus diisi.';

  // hitung hari
  if (!$errors) {
    try {
      $days = days_between($data['check_in'], $data['check_out']);
      $data['total_days'] = (string)$days;
    } catch (Throwable $e) {
      $errors[] = 'Tanggal tidak valid.';
    }
  }

  if (!$errors) {
    $totalAmount = (int)$data['total_days'] * (int)$data['price_per_day'];
    $due = max($totalAmount - (int)$data['down_payment'], 0);
    $paid = (int)$data['paid_amount'];

    $remaining = max($due - $paid, 0);
    $change = max($paid - $due, 0);

    $pdo->beginTransaction();
    try {
      // insert guest
      $gStmt = $pdo->prepare("INSERT INTO guests (name) VALUES (:n)");
      $gStmt->execute([':n' => $data['guest_name']]);
      $guestId = (int)$pdo->lastInsertId();

      // insert stay
      $sStmt = $pdo->prepare("
        INSERT INTO stays (guest_id, room_id, check_in, check_out, status)
        VALUES (:gid, :rid, :ci, :co, 'menginap')
      ");
      $sStmt->execute([
        ':gid' => $guestId,
        ':rid' => (int)$room['id'],
        ':ci' => $data['check_in'],
        ':co' => $data['check_out'],
      ]);
      $stayId = (int)$pdo->lastInsertId();

      // insert transaction
      $tStmt = $pdo->prepare("
        INSERT INTO transactions
          (stay_id, booking_type, total_days, price_per_day, total_amount, down_payment, paid_amount, remaining_amount, change_amount, status)
        VALUES
          (:sid, :bt, :days, :ppd, :total, :dp, :paid, :rem, :chg, 'menginap')
      ");
      $tStmt->execute([
        ':sid' => $stayId,
        ':bt' => $data['booking_type'],
        ':days' => (int)$data['total_days'],
        ':ppd' => (int)$data['price_per_day'],
        ':total' => $totalAmount,
        ':dp' => (int)$data['down_payment'],
        ':paid' => $paid,
        ':rem' => $remaining,
        ':chg' => $change,
      ]);

      // update room status -> occupied
      $pdo->prepare("UPDATE rooms SET status='occupied' WHERE id=:id")->execute([':id' => (int)$room['id']]);

      $pdo->commit();
      set_flash('success', 'Transaksi berhasil diproses.');
      redirect('report.php');
    } catch (Throwable $e) {
      $pdo->rollBack();
      $errors[] = 'Gagal menyimpan transaksi. Pastikan data valid.';
    }
  }
}

$pageTitle = "Transaksi dan Boking - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'topbar';
$heroTitle = 'Transaksi dan Boking';

include __DIR__ . '/layout/header.php';
?>

<script>
  window.ROOM_PRICE_MAP = <?= json_encode($priceMap, JSON_UNESCAPED_UNICODE) ?>;
</script>

<section class="tx-card">
  <?php if ($errors): ?>
    <div class="flash flash-error" style="margin: 0 0 14px;">
      <?= e(implode(" ", $errors)) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

    <div class="tx-grid">
      <div class="tx-col">
        <label>Nama Tamu</label>
        <input class="input" name="guest_name" value="<?= e($data['guest_name']) ?>" />

        <label>Check-in</label>
        <input class="input" id="check_in" type="date" name="check_in" value="<?= e($data['check_in']) ?>" />

        <label>Harga / hari</label>
        <input class="input" id="price_per_day" name="price_per_day" value="<?= e((string)$data['price_per_day']) ?>" placeholder="contoh: 1200000" />
      </div>

      <div class="tx-col">
        <label>Nomor Kamar</label>
        <input class="input" id="room_number" name="room_number" list="roomsList" value="<?= e($data['room_number']) ?>" placeholder="contoh: 101" />
        <datalist id="roomsList">
          <?php foreach ($rooms as $r): ?>
            <option value="<?= e($r['room_number']) ?>"><?= e($r['status']) ?></option>
          <?php endforeach; ?>
        </datalist>

        <label>Check-out</label>
        <input class="input" id="check_out" type="date" name="check_out" value="<?= e($data['check_out']) ?>" />

        <div class="form-row" style="margin-top:8px;">
          <div>
            <label>Total Hari</label>
            <input class="input" id="total_days" name="total_days" readonly value="<?= e($data['total_days']) ?>" />
          </div>
          <div>
            <label>Jenis Pemesanan :</label>
            <select class="input" name="booking_type">
              <option value="boking" <?= $data['booking_type']==='boking'?'selected':'' ?>>Boking</option>
              <option value="langsung" <?= $data['booking_type']==='langsung'?'selected':'' ?>>Langsung</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="tx-totals">
      <div>
        <div class="tx-line">
          <span>Total Harga :</span>
          <span class="value" id="tx_total"><?= e(format_rupiah(0)) ?></span>
        </div>

        <div class="tx-line" style="margin-top:14px;">
          <span>Uang Muka (DP) :</span>
          <span style="min-width: 220px;">
            <input class="input" id="down_payment" name="down_payment" value="<?= e((string)$data['down_payment']) ?>" placeholder="contoh: 550000" />
          </span>
        </div>
      </div>

      <div>
        <div class="tx-line">
          <span>Sisa Pembayaran :</span>
          <span class="value" id="tx_remaining"><?= e(format_rupiah(0)) ?></span>
        </div>

        <div class="tx-line" style="margin-top:14px;">
          <span>Uang yang Dibayar :</span>
          <span style="min-width: 220px;">
            <input class="input" id="paid_amount" name="paid_amount" value="<?= e((string)$data['paid_amount']) ?>" placeholder="contoh: 1500000" />
          </span>
        </div>

        <div class="tx-line" style="margin-top:14px; justify-content:flex-end;">
          <span>Kembalian :</span>
          <span class="value" id="tx_change"><?= e(format_rupiah(0)) ?></span>
        </div>

        <div class="tx-actions">
          <button class="btn" type="submit">Proses Pemesanan</button>
        </div>
      </div>
    </div>
  </form>

  <div style="margin-top:14px;color:#666;font-weight:700;">
    Tips: Isi tanggal check-in/out &amp; nomor kamar dulu, total hari &amp; total harga akan otomatis dihitung.
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
