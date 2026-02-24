<?php
require __DIR__ . '/init.php';
require_login();

$pdo = db();

$settings = $pdo->query("SELECT * FROM settings WHERE id=1")->fetch();
if (!$settings) {
  // buat default jika belum ada
  $pdo->prepare("INSERT INTO settings (id, store_name) VALUES (1, 'Hotel The Peak')")->execute();
  $settings = $pdo->query("SELECT * FROM settings WHERE id=1")->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $storeName = trim($_POST['store_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $owner = trim($_POST['owner_name'] ?? '');

  if ($storeName === '') $errors[] = 'Nama toko/hotel wajib diisi.';

  if (!$errors) {
    $stmt = $pdo->prepare("UPDATE settings SET store_name=:n, phone=:p, address=:a, owner_name=:o WHERE id=1");
    $stmt->execute([':n'=>$storeName, ':p'=>$phone, ':a'=>$address, ':o'=>$owner]);
    set_flash('success', 'Pengaturan berhasil di-update.');
    redirect('settings.php');
  }
}

$pageTitle = "Pengaturan Toko - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'sidebar';
$activeMenu = 'settings';
$heroTitle = 'Pengaturan Toko';

include __DIR__ . '/layout/header.php';
?>

<section class="section">
  <div class="panel" style="max-width: 980px; margin: 0 auto;">
    <?php if ($errors): ?>
      <div class="flash flash-error" style="margin: 0 0 16px;">
        <?= e(implode(" ", $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <div class="form-row">
        <div>
          <label style="font-weight:900; font-size:20px;">Nama Toko</label>
          <input class="input" name="store_name" value="<?= e($settings['store_name']) ?>" />
        </div>
        <div>
          <label style="font-weight:900; font-size:20px;">Kontak Telephone</label>
          <input class="input" name="phone" value="<?= e($settings['phone']) ?>" />
        </div>
      </div>

      <div class="form-row" style="margin-top:14px;">
        <div>
          <label style="font-weight:900; font-size:20px;">Alamat Toko</label>
          <input class="input" name="address" value="<?= e($settings['address']) ?>" />
        </div>
        <div>
          <label style="font-weight:900; font-size:20px;">Nama Pemilik Toko</label>
          <input class="input" name="owner_name" value="<?= e($settings['owner_name']) ?>" />
        </div>
      </div>

      <div style="margin-top:18px; display:flex; justify-content:center;">
        <button class="btn" type="submit">
          <span class="icon"><?= svg_icon('pencil', 20) ?></span>
          Update Data
        </button>
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
