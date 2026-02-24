<?php
// init.php - dipanggil di semua halaman setelah login.
session_start();

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/helpers.php';
require __DIR__ . '/config/auth.php';

$config = require __DIR__ . '/config/config.php';
date_default_timezone_set('Asia/Jakarta');

// Buat folder upload jika belum ada
$uploadDir = __DIR__ . '/assets/uploads';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0777, true);
}

require_once __DIR__ . '/layout/icons.php';
