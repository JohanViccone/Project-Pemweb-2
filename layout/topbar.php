<?php
$now = new DateTime();
?>
<header class="topbar">
  <div class="topbar-left">
    <?php if ($layout === 'topbar'): ?>
      <a class="brand" href="dashboard.php" title="<?= e($config['app_name']) ?>">
        <span class="brand-logo" aria-hidden="true"></span>
        <span class="brand-text">
          <span class="brand-the">The</span> <span class="brand-peak">Peak</span>
        </span>
      </a>
    <?php else: ?>
      <div class="brand brand--simple">
        <span class="brand-text big">
          <span class="brand-the">Hotel The</span> <span class="brand-peak">Peak</span>
        </span>
      </div>
    <?php endif; ?>
  </div>

  <div class="topbar-right">
    <?php if ($layout === 'topbar'): ?>
      <div class="topbar-date">
        <span class="icon"><?= svg_icon('calendar', 22) ?></span>
        <span><?= e(format_day_date_id($now)) ?></span>
      </div>
    <?php endif; ?>

    <a class="topbar-user" href="profile.php" title="Profil">
      <span class="icon"><?= svg_icon('user', 22) ?></span>
    </a>
  </div>
</header>
