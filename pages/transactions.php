<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo  = db();
$user = current_user();

// STRICT TAB VALIDATION: If the URL says 'lending' or anything else, force it to 'borrowing'
$allowed_tabs = ['borrowing', 'completed', 'returned'];
$requested_tab = get_str('tab');
$tab = in_array($requested_tab, $allowed_tabs) ? $requested_tab : 'borrowing';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $txId   = (int)post_str('tx_id');
    $action = post_str('action');
    
    try {
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id');
        $stmt->execute([':id' => $txId]);
        $tx = $stmt->fetch();
        
        if ($tx) {
            $isOwner  = (int)$tx['owner_id'] === (int)$user['id'];
            $isRenter = (int)$tx['renter_id'] === (int)$user['id'];
            
            if (($isOwner || $isRenter) && $action === 'cancel') {
                $pdo->prepare('UPDATE transactions SET status = "cancelled" WHERE id = :id')->execute([':id' => $txId]);
                $pdo->prepare('UPDATE items SET status = "Available" WHERE id = :item')->execute([':item' => $tx['item_id']]);
                flash('success', 'Transaction cancelled.');
            } elseif ($isOwner && $action === 'complete') {
                $pdo->prepare('UPDATE transactions SET status = "completed" WHERE id = :id')->execute([':id' => $txId]);
                $pdo->prepare('UPDATE items SET status = "Available" WHERE id = :item')->execute([':item' => $tx['item_id']]);
                flash('success', 'Item marked as returned.');
            }
        }
    } catch (PDOException $e) {
        log_error('transaction update: ' . $e->getMessage());
        flash('error', 'Action failed.');
    }
    redirect('pages/transactions.php?tab=' . $tab);
}

// Fetch Transactions
try {
    $sql = 'SELECT t.*, i.name AS item_name, 
                   u.full_name AS other_name, u.email AS other_email
            FROM transactions t
            JOIN items i ON i.id = t.item_id
            JOIN users u ON u.id = ' . ($tab === 'completed' ? 't.renter_id' : 't.owner_id') . '
            WHERE ' . ($tab === 'completed' ? 't.owner_id' : 't.renter_id') . ' = :uid';
            
    if ($tab === 'borrowing') {
        $sql .= ' AND t.status IN ("requested", "rented")';
    } elseif ($tab === 'completed') {
        $sql .= ' AND t.status = "completed"';
    } elseif ($tab === 'returned') {
        $sql .= ' AND t.status = "cancelled"';
    }
    $sql .= ' ORDER BY t.created_at DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $user['id']]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    log_error('transactions list: ' . $e->getMessage());
    $transactions = [];
}

$pageTitle = 'Transactions · CrossRent';
require __DIR__ . '/../includes/header.php';
?>

<div class="home-container" style="padding-bottom: 100px;">
  
  <!-- ONLY 3 TABS -->
  <div class="tabs" style="margin-bottom: 20px; background: transparent;">
    <a href="?tab=borrowing" class="<?= $tab === 'borrowing' ? 'active' : '' ?>">Borrowing</a>
    <a href="?tab=completed" class="<?= $tab === 'completed' ? 'active' : '' ?>">Completed</a>
    <a href="?tab=returned" class="<?= $tab === 'returned' ? 'active' : '' ?>">Cancelled</a>
  </div>

  <div class="list-container" style="padding: 0;">
    <?php if (empty($transactions)): ?>
      <div class="empty-state" style="text-align: center; margin-top: 50px; color: #888;">
        <i class="fa-regular fa-receipt" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;"></i>
        <p>No records found in <?= e($tab) ?>.</p>
      </div>
    <?php else: foreach ($transactions as $tx): ?>
      
      <div class="info-card glass-panel" style="margin-bottom: 20px; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
          <h3 style="font-size: 18px; margin: 0; color: var(--dark); font-weight: 700;"><?= e($tx['item_name']) ?></h3>
          <span class="badge <?= e(strtolower($tx['status'])) ?>"><?= e(ucfirst($tx['status'])) ?></span>
        </div>
        
        <div style="background: rgba(255,255,255,0.5); padding: 12px; border-radius: 12px; margin-bottom: 15px;">
            <p class="text-muted" style="font-size: 13px; margin-bottom: 6px;">
              Owner: <strong style="color: var(--dark);"><?= e($tx['other_name']) ?></strong>
            </p>
            <p class="text-muted" style="font-size: 13px; margin-bottom: 6px;">
              Period: <strong style="color: var(--dark);"><?= e($tx['rent_period']) ?></strong> | Price: <strong style="color: var(--dark);"><?= format_price($tx['rent_price']) ?></strong>
            </p>
            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">
              <i class="fa-regular fa-clock"></i> <?= date('M j, Y g:i A', strtotime($tx['created_at'])) ?>
            </p>
        </div>
        
        <div style="display: flex; gap: 10px;">
          <?php if ($tab === 'borrowing' && $tx['status'] === 'requested'): ?>
            <form method="post" style="flex: 1;">
              <?= csrf_field() ?>
              <input type="hidden" name="tx_id" value="<?= $tx['id'] ?>">
              <button name="action" value="cancel" class="btn-primary" style="background: var(--primary) !important; padding: 12px; font-size: 14px; width: 100%; border-radius: 12px;">Cancel Request</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

    <?php endforeach; endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>