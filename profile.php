<?php
require __DIR__ . '/init.php';
require_login();

$pdo = db();
$user = current_user();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$stmt->execute([':id' => (int)$user['id']]);
$userRow = $stmt->fetch();
if (!$userRow) {
  auth_logout();
  redirect('login.php');
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $form = $_POST['form'] ?? '';

  if ($form === 'photo') {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Upload foto gagal.';
    } else {
      $tmp = $_FILES['photo']['tmp_name'];
      $name = $_FILES['photo']['name'];
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
        $errors[] = 'Format foto harus jpg/png/webp.';
      } else {
        $safeName = 'user_' . (int)$userRow['id'] . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/assets/uploads/' . $safeName;
        if (!move_uploaded_file($tmp, $dest)) {
          $errors[] = 'Gagal menyimpan file.';
        } else {
          $pdo->prepare("UPDATE users SET photo=:p WHERE id=:id")->execute([':p' => $safeName, ':id' => (int)$userRow['id']]);
          set_flash('success', 'Foto berhasil diubah.');
          redirect('profile.php');
        }
      }
    }
  }

  if ($form === 'password') {
    $newPass = $_POST['new_password'] ?? '';
    if (strlen($newPass) < 6) {
      $errors[] = 'Password baru minimal 6 karakter.';
    } else {
      $hash = password_hash($newPass, PASSWORD_BCRYPT);
      $pdo->prepare("UPDATE users SET password_hash=:h WHERE id=:id")->execute([':h' => $hash, ':id' => (int)$userRow['id']]);
      set_flash('success', 'Password berhasil diubah.');
      redirect('profile.php');
    }
  }

  if ($form === 'profile') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') $errors[] = 'Nama wajib diisi.';

    if (!$errors) {
      $pdo->prepare("UPDATE users SET name=:n, email=:e, phone=:p, address=:a WHERE id=:id")->execute([
        ':n' => $name,
        ':e' => $email,
        ':p' => $phone,
        ':a' => $address,
        ':id' => (int)$userRow['id'],
      ]);

      // update session name/email
      $_SESSION['user']['name'] = $name;
      $_SESSION['user']['email'] = $email;

      set_flash('success', 'Profil berhasil di-update.');
      redirect('profile.php');
    }
  }
}

$photoUrl = $userRow['photo'] ? ('assets/uploads/' . $userRow['photo']) : null;

$pageTitle = "Profil Pengguna - " . ($config['app_name'] ?? 'Hotel The Peak');
$layout = 'sidebar';
$activeMenu = ''; // bukan menu utama
$heroTitle = 'Profil Pengguna Aplikasi';

include __DIR__ . '/layout/header.php';
?>

<section class="section">
  <?php if ($errors): ?>
    <div class="flash flash-error" style="max-width: 1100px; margin: 0 auto 14px;">
      <?= e(implode(" ", $errors)) ?>
    </div>
  <?php endif; ?>

  <div class="profile-top">
    <div class="profile-photo-area">
      <div class="profile-photo-circle" style="<?= $photoUrl ? 'background-image:url(' . e($photoUrl) . ');' : '' ?>">
        <?php if (!$photoUrl): ?>
          <span style="font-size:42px; font-weight:900; color:#444;"><?= e(strtoupper(first_char($userRow['name']))) ?></span>
        <?php endif; ?>
      </div>

      <form method="post" enctype="multipart/form-data" style="margin-top: 14px; text-align:center;">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="form" value="photo" />
        <input class="input" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" style="max-width: 360px;" />
        <div style="margin-top:10px;">
          <button class="btn btn-sm" type="submit">
            <span class="icon"><?= svg_icon('pencil', 18) ?></span>Ganti Foto
          </button>
        </div>
      </form>
    </div>

    <div class="profile-pass">
      <div class="profile-box">
        <div class="profile-box-head">
          <span class="icon"><?= svg_icon('lock', 22) ?></span>
          <span>Ganti Password</span>
        </div>

        <div class="profile-box-body">
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
            <input type="hidden" name="form" value="password" />

            <label>Username</label>
            <input class="input" value="<?= e($userRow['username']) ?>" disabled />

            <label>Password Baru</label>
            <input class="input" type="password" name="new_password" />

            <div style="margin-top:14px; display:flex; justify-content:center;">
              <button class="btn btn-sm" type="submit">
                <span class="icon"><?= svg_icon('pencil', 18) ?></span>Ubah Pasword
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="profile-bottom">
    <div class="profile-box" style="max-width: 1100px; margin: 0 auto;">
      <div class="profile-box-head">
        <span class="icon"><?= svg_icon('user', 22) ?></span>
        <span>Kelola Pengguna</span>
      </div>

      <div class="profile-box-body">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
          <input type="hidden" name="form" value="profile" />

          <div class="form-row">
            <div>
              <label>Nama</label>
              <input class="input" name="name" value="<?= e($userRow['name']) ?>" />
            </div>
            <div>
              <label>Alamat</label>
              <textarea class="input" name="address" rows="5"><?= e($userRow['address']) ?></textarea>
            </div>
          </div>

          <div class="form-row" style="margin-top:14px;">
            <div>
              <label>Email</label>
              <input class="input" name="email" value="<?= e($userRow['email']) ?>" />
            </div>
            <div>
              <label>Telephone</label>
              <input class="input" name="phone" value="<?= e($userRow['phone']) ?>" />
            </div>
          </div>

          <div style="margin-top:18px; display:flex; justify-content:center;">
            <button class="btn" type="submit">
              <span class="icon"><?= svg_icon('pencil', 20) ?></span>Ubah Profil
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div style="max-width:1100px;margin:18px auto 0; display:flex; justify-content:flex-end; gap:12px;">
    <a class="btn btn-muted" href="dashboard.php">Kembali ke Dashboard</a>
    <a class="btn btn-muted" href="logout.php">Logout</a>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
