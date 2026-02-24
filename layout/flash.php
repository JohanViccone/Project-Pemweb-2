<?php
$flash = get_flash();
if (!$flash) return;
$type = $flash['type'] ?? 'info';
$msg = $flash['message'] ?? '';
?>
<div class="flash flash-<?= e($type) ?>">
  <?= e($msg) ?>
</div>
