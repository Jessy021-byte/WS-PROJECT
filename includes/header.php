<?php
require_once __DIR__ . '/auth.php';
$user       = current_user();
$page       = current_page();
$successMsg = take_flash('success');
$errorMsg   = take_flash('error');
$theme      = ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?= e($pageTitle ?? 'CrossRent') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="theme-<?= $theme ?>">

<?php if ($user && $page !== 'login.php' && $page !== 'register.php'): ?>
<!-- Side Drawer -->
<div class="drawer-overlay" id="drawerOverlay" onclick="toggleDrawer()"></div>
<div class="drawer" id="sideDrawer">
  <div class="drawer-header">
    <h2>Cross<span>Rent</span></h2>
    <button class="close-drawer" onclick="toggleDrawer()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="drawer-menu">
    <a href="<?= url('pages/profile.php') ?>"><i class="fa-regular fa-user"></i> Profile</a>
    
    <a href="<?= url('pages/guidelines.php') ?>"><i class="fa-solid fa-circle-info"></i> Guidelines</a>
    <hr>
    <a href="<?= url('pages/logout.php') ?>" class="text-red"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
  </div>
</div>

<!-- Top Header -->
<header class="app-header">
  <div class="header-inner">
    <?php if ($page === 'item_view.php'): ?>
      <a href="<?= url('pages/home.php') ?>" class="header-icon" style="color: var(--dark);"><i class="fa-solid fa-arrow-left"></i></a>
      <h1 class="header-title" style="color: var(--dark);">Item Details</h1>
      <div style="width: 24px;"></div>
      
    <?php elseif ($page === 'item_form.php'): ?>
      <a href="<?= url('pages/admin/dashboard.php') ?>" class="header-icon"><i class="fa-solid fa-xmark"></i></a>
      <h1 class="header-title">List an Item</h1>
      <button type="submit" form="itemForm" class="header-action">Post</button>
      
    <?php elseif (strpos($_SERVER['PHP_SELF'], '/admin/') !== false): ?>
      <a href="<?= url('pages/admin/dashboard.php') ?>" class="header-icon"><i class="fa-solid fa-arrow-left"></i></a>
      <h1 class="header-title">Admin Panel</h1>
      <div style="width: 24px;"></div>
      
    <?php else: ?>
      <button class="header-icon" onclick="toggleDrawer()"><i class="fa-solid fa-bars"></i></button>
      <a class="logo" href="<?= url('pages/home.php') ?>">Cross<span>Rent</span></a>
      <a class="avatar" href="<?= url('pages/profile.php') ?>">
        <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/../' . $user['profile_pic'])): ?>
          <img src="<?= url($user['profile_pic']) ?>" alt="Profile">
        <?php else: ?>
          <span><?= e(strtoupper(substr($user['email'], 0, 1))) ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>
  </div>
</header>
<?php endif; ?>

<main class="app-main <?= ($page === 'item_form.php') ? 'pt-form' : '' ?>">
<?php if ($successMsg): ?><div class="flash success"><?= e($successMsg) ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="flash error"><?= e($errorMsg) ?></div><?php endif; ?>