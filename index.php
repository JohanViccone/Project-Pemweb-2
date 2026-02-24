<?php
require __DIR__ . '/init.php';
if (current_user()) {
  redirect('dashboard.php');
}
redirect('login.php');
