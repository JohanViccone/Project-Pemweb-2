<?php
$items = [
  ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'home'],
  ['key' => 'data_master', 'label' => 'Data Master', 'href' => 'data_master.php', 'icon' => 'file'],
  ['key' => 'transaksi', 'label' => 'Transaksi', 'href' => 'transaction_create.php', 'icon' => 'monitor'],
  ['key' => 'settings', 'label' => 'Pengaturan Toko', 'href' => 'settings.php', 'icon' => 'settings'],
];
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-circle" title="The Peak"></div>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($items as $it): ?>
      <a class="sidebar-link <?= $activeMenu === $it['key'] ? 'active' : '' ?>" href="<?= e($it['href']) ?>">
        <span class="sidebar-icon"><?= svg_icon($it['icon'], 28) ?></span>
        <span class="sidebar-label"><?= e($it['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
