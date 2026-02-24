<?php
// layout/hero.php
// Variabel yang bisa dipakai: $heroTitle, $heroSubtitle, $heroActionHtml
?>
<section class="hero">
  <div class="hero-inner">
    <div class="hero-text">
      <h1><?= e($heroTitle ?? '') ?></h1>
      <?php if (!empty($heroSubtitle)): ?>
        <p><?= e($heroSubtitle) ?></p>
      <?php endif; ?>
    </div>

    <?php if (!empty($heroActionHtml)): ?>
      <div class="hero-action">
        <?= $heroActionHtml ?>
      </div>
    <?php endif; ?>
  </div>
</section>
