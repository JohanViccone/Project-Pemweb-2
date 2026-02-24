<?php
require __DIR__ . '/init.php';
auth_logout();
set_flash('success', 'Kamu sudah logout.');
redirect('login.php');
