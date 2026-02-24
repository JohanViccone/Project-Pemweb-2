<?php
require __DIR__ . '/init.php';
require_login();

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  set_flash('error', 'ID tidak valid.');
  redirect('guests.php');
}

$stmt = $pdo->prepare("
  SELECT s.*, g.name AS guest_name, g.id AS guest_id, r.id AS room_id, r.room_number, r.status AS room_status
  FROM stays s
  JOIN guests g ON g.id = s.guest_id
  JOIN rooms r ON r.id = s.room_id
  WHERE s.id=:id
");
$stmt->execute([':id' => $id]);
$stay = $stmt->fetch();

if (!$stay) {
  set_flash('error', 'Data tidak ditemukan.');
  redirect('guests.php');
}

$rooms = $pdo->query("SELECT id, room_number, status, price_per_day FROM rooms ORDER BY room_number ASC")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $guestName = trim($_POST['guest_name'] ?? '');
  $roomId = (int)($_POST['room_id'] ?? 0);
  $checkIn = $_POST['check_in'] ?? '';
  $checkOut = $_POST['check_out'] ?? '';
  $status = $_POST['status'] ?? 'menginap';

  if ($guestName === '') $errors[] = 'Nama tamu wajib diisi.';
  if ($roomId <= 0) $errors[] = 'Kamar wajib dipilih.';
  if ($checkIn === '' || $checkOut === '') $errors[] = 'Tanggal check-in dan check-out wajib diisi.';
  if (!in_array($status, ['menginap','selesai'], true)) $errors[] = 'Status tidak valid.';

  // cek room
  $roomStmt = $pdo->prepare("SELECT * FROM rooms WHERE id=:id");
  $roomStmt->execute([':id' => $roomId]);
  $newRoom = $roomStmt->fetch();
  if (!$newRoom) $errors[] = 'Kamar tidak ditemukan.';

  if (!$errors) {
    // aturan sederhana: jika status menginap, kamar tidak boleh maintenance
    if ($status === 'menginap' && $newRoom['status'] === 'maintenance' && $roomId !== (int)$stay['room_id']) {
      $errors[] = 'Kamar maintenance tidak bisa dipakai untuk menginap.';
    }
  }

  if (!$errors) {
    $pdo->beginTransaction();
    try {
      // update guest name
      $gUp = $pdo->prepare("UPDATE guests SET name=:n WHERE id=:id");
      $gUp->execute([':n' => $guestName, ':id' => (int)$stay['guest_id']]);

      // update stay
      $sUp = $pdo->prepare("UPDATE stays SET room_id=:rid, check_in=:ci, check_out=:co, status=:st WHERE id=:id");
      $sUp->execute([':rid'=>$roomId, ':ci'=>$checkIn, ':co'=>$checkOut, ':st'=>$status, ':id'=>$id]);

      $oldRoomId = (int)$stay['room_id'];
      $newRoomId = $roomId;

      // jika pindah kamar, set status kamar lama menjadi available jika tidak ada stay aktif lain
      if ($oldRoomId !== $newRoomId) {
        $activeOld = $pdo->prepare("SELECT COUNT(*) AS c FROM stays WHERE room_id=:rid AND status='menginap'");
        $activeOld->execute([':rid' => $oldRoomId]);
        $cOld = (int)$activeOld->fetch()['c'];
        if ($cOld <= 0) {
          $pdo->prepare("UPDATE rooms SET status='available' WHERE id=:id AND status<>'maintenance'")->execute([':id' => $oldRoomId]);
        }

        // jika status menginap, kamar baru jadi occupied
        if ($status === 'menginap') {
          $pdo->prepare("UPDATE rooms SET status='occupied' WHERE id=:id")->execute([':id' => $newRoomId]);
        }
      } else {
        // kamar sama: update status kamar sesuai status stay
        if ($status === 'menginap') {
          $pdo->prepare("UPDATE rooms SET status='occupied' WHERE id=:id")->execute([':id' => $oldRoomId]);
        } else {
          // selesai -> available (kecuali maintenance)
          $pdo->prepare("UPDATE rooms SET status='available' WHERE id=:id AND status<>'maintenance'")->execute([':id' => $oldRoomId]);
        }
      }

      // update transaksi (jika ada): jika selesai -> checkout
      if ($status === 'selesai') {
        $pdo->prepare("UPDATE transactions SET status='checkout' WHERE stay_id=:sid")->execute([':sid' => $id]);
      } else {
        $pdo->prepare("UPDATE transactions SET status='menginap' WHERE stay_id=:sid")->execute([':sid' => $id]);
      }

      $pdo->commit();
      set_flash('success', 'Data tamu berhasil di-update.');
      redirect('guests.php');
    } catch (Throwable $e) {
      $pdo->rollBack();
      $errors[] = 'Gagal update data. Coba lagi.';
    }
  }
}

$pageTitle = "Edit Tamu - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'topbar';
$heroTitle = 'Manajemen Tamu';
$heroSubtitle = 'Edit data check in / check out tamu.';
$heroActionHtml = '<a class="btn btn-muted" href="guests.php">Kembali</a>';

include __DIR__ . '/layout/header.php';
?>

<section class="section">
  <div class="panel">
    <h2 style="margin:0 0 12px;">Edit Tamu</h2>

    <?php if ($errors): ?>
      <div class="flash flash-error" style="margin: 0 0 16px;">
        <?= e(implode(" ", $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <div class="form-row">
        <div>
          <label style="font-weight:800;">Nama Tamu</label>
          <input class="input" name="guest_name" value="<?= e($stay['guest_name']) ?>" />
        </div>
        <div>
          <label style="font-weight:800;">Nomor Kamar</label>
          <select class="input" name="room_id">
            <?php foreach ($rooms as $r): ?>
              <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id']===(int)$stay['room_id']?'selected':'' ?>>
                <?= e($r['room_number']) ?> (<?= e($r['status']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row" style="margin-top:14px;">
        <div>
          <label style="font-weight:800;">Check-in</label>
          <input class="input" type="date" name="check_in" value="<?= e($stay['check_in']) ?>" />
        </div>
        <div>
          <label style="font-weight:800;">Check-out</label>
          <input class="input" type="date" name="check_out" value="<?= e($stay['check_out']) ?>" />
        </div>
      </div>

      <div style="margin-top:14px;">
        <label style="font-weight:800;">Status</label>
        <select class="input" name="status">
          <option value="menginap" <?= $stay['status']==='menginap'?'selected':'' ?>>Menginap</option>
          <option value="selesai" <?= $stay['status']==='selesai'?'selected':'' ?>>Selesai</option>
        </select>
      </div>

      <div style="margin-top:18px; display:flex; gap:12px; justify-content:flex-end;">
        <a class="btn btn-muted" href="guests.php">Batal</a>
        <button class="btn" type="submit">Update</button>
      </div>
    </form>

    <div style="margin-top:18px; color:#666; font-weight:700;">
      Catatan: Jika status diubah menjadi <b>Selesai</b>, kamar akan otomatis menjadi <b>Tersedia</b> (kecuali maintenance) dan transaksi akan berubah menjadi <b>Checkout</b>.
    </div>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
