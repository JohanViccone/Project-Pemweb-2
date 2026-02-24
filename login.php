<?php
require __DIR__ . '/init.php';

if (current_user()) {
  redirect('dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    $error = 'Username dan password wajib diisi.';
  } else {
    if (auth_login($username, $password)) {
      set_flash('success', 'Login berhasil.');
      redirect('dashboard.php');
    } else {
      $error = 'Username atau password salah.';
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login - <?= e($config['app_name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css?v=1" />
</head>
<body class="login-page">
  <div class="login-card">
    <h1>Admin Login</h1>
    <p>Silakan masuk untuk mengakses dashboard</p>

    <?php if ($error): ?>
      <div class="flash flash-error" style="margin: 0 0 16px;"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <label for="username">Username</label>
      <input class="input" id="username" name="username" type="text" autocomplete="username" />

      <label for="password">Pasword</label>
      <input class="input" id="password" name="password" type="password" autocomplete="current-password" />

      <button class="btn" type="submit">Login</button>
    </form>

    <div style="margin-top:14px;color:#666;font-weight:700;font-size:14px;">
      Default: <b>admin</b> / <b>admin123</b>
    </div>
  </div>
</body>
</html>
