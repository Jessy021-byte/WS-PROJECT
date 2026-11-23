<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$pdo = db();

// Quick Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalItems = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$pendingTx  = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'requested'")->fetchColumn();

$pageTitle = 'Admin Dashboard · CrossRent';
require __DIR__ . '/../../includes/header.php';
?>

<div class="home-container">
  <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 20px;">Dashboard Overview</h2>
  
  <!-- Stats Row -->
  <div style="display: flex; gap: 15px; margin-bottom: 30px;">
    <div class="info-card" style="flex: 1; text-align: center; padding: 20px 10px; margin: 0;">
      <h3 style="font-size: 28px; color: var(--primary); margin: 0 0 5px 0;"><?= $totalUsers ?></h3>
      <p class="text-muted" style="font-size: 13px; font-weight: 600;">Users</p>
    </div>
    
    <div class="info-card" style="flex: 1; text-align: center; padding: 20px 10px; margin: 0;">
      <h3 style="font-size: 28px; color: var(--dark); margin: 0 0 5px 0;"><?= $totalItems ?></h3>
      <p class="text-muted" style="font-size: 13px; font-weight: 600;">Items</p>
    </div>
    
    <div class="info-card" style="flex: 1; text-align: center; padding: 20px 10px; margin: 0; border: 2px solid #E65100;">
      <h3 style="font-size: 28px; color: #E65100; margin: 0 0 5px 0;"><?= $pendingTx ?></h3>
      <p class="text-muted" style="font-size: 13px; font-weight: 600;">Pending</p>
    </div>
  </div>

  <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 15px;">Management</h3>
  
  <!-- Menu List -->
  <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 30px;">
<a href="<?= url('pages/admin/transactions.php') ?>" class="info-card" style="display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: var(--dark); padding: 20px;">
      <div style="display: flex; align-items: center; gap: 15px; font-weight: 600;">
        <i class="fa-solid fa-arrow-right-arrow-left text-dark"></i> Manage Rent Requests
      </div>
      <i class="fa-solid fa-chevron-right text-muted"></i>
    </a>
    
    <a href="<?= url('pages/admin/categories.php') ?>" class="info-card" style="display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: var(--dark); padding: 20px;">
      <div style="display: flex; align-items: center; gap: 15px; font-weight: 600;">
        <i class="fa-solid fa-tags text-dark"></i> Manage Categories
      </div>
      <i class="fa-solid fa-chevron-right text-muted"></i>
    </a>
    
    <a href="<?= url('pages/admin/users.php') ?>" class="info-card" style="display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: var(--dark); padding: 20px;">
      <div style="display: flex; align-items: center; gap: 15px; font-weight: 600;">
        <i class="fa-solid fa-users-gear text-dark"></i> Manage Users
      </div>
      <i class="fa-solid fa-chevron-right text-muted"></i>
    </a>
  </div>

  <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 15px;">Data Export</h3>
  
  <a href="<?= url('pages/admin/export.php') ?>" class="info-card" style="display: flex; justify-content: center; align-items: center; gap: 10px; text-decoration: none; color: var(--success); font-weight: 700; padding: 20px; border: 2px solid var(--success); background: #FFF;">
    <i class="fa-solid fa-file-csv"></i> Download CSV Report
  </a>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>