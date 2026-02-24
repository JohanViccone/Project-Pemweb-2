<?php
// config/auth.php

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function require_login(): void {
  if (!current_user()) {
    redirect('login.php');
  }
}

function auth_login(string $username, string $password): bool {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
  $stmt->execute([':u' => $username]);
  $user = $stmt->fetch();
  if (!$user) return false;

  if (!password_verify($password, $user['password_hash'])) return false;

  // Simpan data minimal di session
  unset($user['password_hash']);
  $_SESSION['user'] = $user;
  return true;
}

function auth_logout(): void {
  unset($_SESSION['user']);
  session_regenerate_id(true);
}
