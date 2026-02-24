<?php
// config/helpers.php

function e(?string $str): string {
  return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
  header("Location: {$path}");
  exit;
}

function set_flash(string $type, string $message): void {
  $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
  if (!isset($_SESSION['flash'])) return null;
  $flash = $_SESSION['flash'];
  unset($_SESSION['flash']);
  return $flash;
}

function format_rupiah($amount): string {
  if ($amount === null || $amount === '') return 'Rp.0';
  $n = (float)$amount;
  return 'Rp.' . number_format($n, 0, ',', '.');
}

function days_between(string $start, string $end): int {
  $d1 = new DateTime($start);
  $d2 = new DateTime($end);
  $diff = $d1->diff($d2);
  $days = (int)$diff->days;
  return max(1, $days);
}

function format_date_id(?string $dateYmd): string {
  if (!$dateYmd) return '';
  // Format: 16 Maret 2026
  $dt = DateTime::createFromFormat('Y-m-d', $dateYmd);
  if (!$dt) return $dateYmd;

  $bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
  ];
  $m = (int)$dt->format('n');
  return $dt->format('j') . ' ' . ($bulan[$m] ?? $dt->format('F')) . ' ' . $dt->format('Y');
}

function format_day_date_id(DateTime $dt): string {
  // Format: Sabtu, 21 Februari 2026
  $hari = [
    'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu',
    'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu',
  ];
  $bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
  ];
  $h = $hari[$dt->format('D')] ?? $dt->format('l');
  $m = (int)$dt->format('n');
  return $h . ', ' . $dt->format('j') . ' ' . ($bulan[$m] ?? $dt->format('F')) . ' ' . $dt->format('Y');
}


function first_char(string $s): string {
  $s = trim($s);
  if ($s === '') return '';
  if (function_exists('mb_substr')) {
    return mb_substr($s, 0, 1);
  }
  return substr($s, 0, 1);
}

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf'];
}

function verify_csrf(): void {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
  $token = $_POST['csrf'] ?? '';
  if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(403);
    echo "CSRF token tidak valid.";
    exit;
  }
}
