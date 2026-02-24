<?php
require __DIR__ . '/init.php';
require_login();

$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;

$data = [
  'room_number' => '',
  'type' => '',
  'price_per_day' => '',
  'status' => 'available',
];

if ($editing) {
  $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=:id");
  $stmt->execute([':id' => $id]);
  $row = $stmt->fetch();
  if (!$row) {
    set_flash('error', 'Data kamar tidak ditemukan.');
    redirect('rooms.php');
  }
  $data = array_merge($data, $row);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $data['room_number'] = trim($_POST['room_number'] ?? '');
  $data['type'] = trim($_POST['type'] ?? '');
  $data['price_per_day'] = (int)preg_replace('/[^0-9]/', '', ($_POST['price_per_day'] ?? '0'));
  $data['status'] = $_POST['status'] ?? 'available';

  if ($data['room_number'] === '') $errors[] = 'Nomor kamar wajib diisi.';
  if ($data['type'] === '') $errors[] = 'Tipe kamar wajib diisi.';
  if ($data['price_per_day'] <= 0) $errors[] = 'Harga per hari harus lebih dari 0.';
  if (!in_array($data['status'], ['available','occupied','maintenance'], true)) $errors[] = 'Status tidak valid.';

  // Cek unik room_number
  $check = $pdo->prepare("SELECT id FROM rooms WHERE room_number=:rn " . ($editing ? "AND id<>:id" : ""));
  $params = [':rn' => $data['room_number']];
  if ($editing) $params[':id'] = $id;
  $check->execute($params);
  if ($check->fetch()) $errors[] = 'Nomor kamar sudah digunakan.';

  if (!$errors) {
    if ($editing) {
      $stmt = $pdo->prepare("UPDATE rooms SET room_number=:rn, type=:t, price_per_day=:p, status=:s WHERE id=:id");
      $stmt->execute([
        ':rn' => $data['room_number'],
        ':t' => $data['type'],
        ':p' => $data['price_per_day'],
        ':s' => $data['status'],
        ':id' => $id,
      ]);
      set_flash('success', 'Data kamar berhasil di-update.');
    } else {
      $stmt = $pdo->prepare("INSERT INTO rooms (room_number, type, price_per_day, status) VALUES (:rn,:t,:p,:s)");
      $stmt->execute([
        ':rn' => $data['room_number'],
        ':t' => $data['type'],
        ':p' => $data['price_per_day'],
        ':s' => $data['status'],
      ]);
      set_flash('success', 'Kamar berhasil ditambahkan.');
    }
    redirect('rooms.php');
  }
}

$pageTitle = ($editing ? 'Edit Kamar' : 'Tambah Kamar') . ' - ' . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'topbar';
$heroTitle = 'Manajemen Kamar';
$heroSubtitle = $editing ? 'Edit data kamar.' : 'Tambah data kamar baru.';
$heroActionHtml = '<a class="btn btn-muted" href="rooms.php">Kembali</a>';

include __DIR__ . '/layout/header.php';
?>

<section class="section">
  <div class="panel">
    <h2 style="margin:0 0 12px;"><?= e($editing ? 'Edit Kamar' : 'Tambah Kamar') ?></h2>

    <?php if ($errors): ?>
      <div class="flash flash-error" style="margin: 0 0 16px;">
        <?= e(implode(" ", $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <div class="form-row">
        <div>
          <label style="font-weight:800;">Nomor Kamar</label>
          <input class="input" name="room_number" value="<?= e($data['room_number']) ?>" />
        </div>
        <div>
          <label style="font-weight:800;">Tipe</label>
          <input class="input" name="type" value="<?= e($data['type']) ?>" placeholder="Deluxe / Superior" />
        </div>
      </div>

      <div class="form-row" style="margin-top:14px;">
        <div>
          <label style="font-weight:800;">Harga / hari</label>
          <input class="input" name="price_per_day" value="<?= e((string)$data['price_per_day']) ?>" placeholder="1200000" />
          <div style="color:#666;font-weight:700;margin-top:6px;">Isi angka saja (tanpa titik/koma).</div>
        </div>
        <div>
          <label style="font-weight:800;">Status</label>
          <select class="input" name="status">
            <option value="available" <?= $data['status']==='available'?'selected':'' ?>>Tersedia</option>
            <option value="occupied" <?= $data['status']==='occupied'?'selected':'' ?>>Terisi</option>
            <option value="maintenance" <?= $data['status']==='maintenance'?'selected':'' ?>>Maintence</option>
          </select>
        </div>
      </div>

      <div style="margin-top:18px; display:flex; gap:12px; justify-content:flex-end;">
        <a class="btn btn-muted" href="rooms.php">Batal</a>
        <button class="btn" type="submit"><?= e($editing ? 'Update' : 'Simpan') ?></button>
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
