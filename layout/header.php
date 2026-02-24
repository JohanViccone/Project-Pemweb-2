<?php
// layout/header.php
if (!isset($pageTitle)) $pageTitle = $config['app_name'] ?? 'Hotel The Peak';
if (!isset($layout)) $layout = 'topbar'; // topbar | sidebar
if (!isset($activeMenu)) $activeMenu = '';

require_once __DIR__ . '/icons.php';
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="assets/css/style.css?v=1" />
</head>
<body class="<?= $layout === 'sidebar' ? 'layout-sidebar' : 'layout-topbar' ?>">
<?php if ($layout === 'sidebar') { include __DIR__ . '/sidebar.php'; } ?>
<div class="app">
  <?php include __DIR__ . '/topbar.php'; ?>
  <main class="content">
    <?php if (isset($heroTitle)) { include __DIR__ . '/hero.php'; } ?>
    <?php include __DIR__ . '/flash.php'; ?>
