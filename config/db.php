<?php
// config/db.php
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $config = require __DIR__ . '/config.php';
  $db = $config['db'];

  $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";

  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
  } catch (Throwable $e) {
    http_response_code(500);
    echo "<h1>Koneksi DB gagal</h1>";
    echo "<p>Periksa config/config.php dan pastikan database sudah di-import.</p>";
    // Untuk debugging lokal, kamu bisa uncomment baris berikut:
    // echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
  }

  return $pdo;
}
