<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$pdo = db();

// Define tabs: Lending (active), Completed, Cancelled
$allowed_tabs = ['lending', 'completed', 'cancelled'];
$requested_tab = get_str('tab');
$tab = in_array($requested_tab, $allowed_tabs) ? $requested_tab : 'lending';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tx_id'])) {
    csrf_check();
    $txId   = (int)$_POST['tx_id'];
    $action = post_str('action');
    
    $status = '';
    if ($action === 'approve') $status = 'rented';
    if ($action === 'decline') $status = 'cancelled';
    if ($action === 'complete') $status = 'completed';

    if ($status) {
        try {
            // Fetch tx details for the message
            $stmt = $pdo->prepare('SELECT t.*, i.name AS item_name, r.email AS renter_email FROM transactions t JOIN items i ON i.id=t.item_id JOIN users r ON r.id=t.renter_id WHERE t.id = :id');
            $stmt->execute([':id' => $txId]);
            $tx = $stmt->fetch();

            $pdo->prepare('UPDATE transactions SET status = :s WHERE id = :id')->execute([':s' => $status, ':id' => $txId]);
            
            if ($status === 'rented') {
                $pdo->prepare('UPDATE items SET status = "Rented" WHERE id = :id')->execute([':id' => $tx['item_id']]);
                // Send automated message
                send_system_message($pdo, $tx['item_id'], $tx['owner_id'], $tx['renter_id'], current_user()['id'], 'Rental Approved', [
                    'item_name' => $tx['item_name'], 'renter_email' => $tx['renter_email']
                ]);
            }
            if ($status === 'cancelled') {
                $pdo->prepare('UPDATE items SET status = "Available" WHERE id = :id')->execute([':id' => $tx['item_id']]);
                // Send automated message
                send_system_message($pdo, $tx['item_id'], $tx['owner_id'], $tx['renter_id'], current_user()['id'], 'Rental Declined', [
                    'item_name' => $tx['item_name'], 'renter_email' => $tx['renter_email']
                ]);
            }
            if ($status === 'completed') {
                $pdo->prepare('UPDATE items SET status = "Available" WHERE id = :id')->execute([':id' => $tx['item_id']]);
                // Send automated message
                send_system_message($pdo, $tx['item_id'], $tx['owner_id'], $tx['renter_id'], current_user()['id'], 'Rental Completed', [
                    'item_name' => $tx['item_name'], 'renter_email' => $tx['renter_email']
                ]);
            }
            
            flash('success', 'Transaction updated and notification sent.');
        } catch (PDOException $e) {
            log_error('admin tx update: ' . $e->getMessage());
            flash('error', 'Action failed.');
        }
    }
    redirect('pages/admin/transactions.php?tab=' . $tab);
}

// Fetch Transactions based on active tab
try {
    $sql = 'SELECT t.*, i.name AS item_name, r.email AS renter_email, o.email AS owner_email 
            FROM transactions t
            JOIN items i ON i.id = t.item_id
            JOIN users r ON r.id = t.renter_id
            JOIN users o ON o.id = t.owner_id';
            
    if ($tab === 'lending') {
        $sql .= ' WHERE t.status IN ("requested", "rented")';
    } elseif ($tab === 'completed') {
        $sql .= ' WHERE t.status = "completed"';
    } elseif ($tab === 'cancelled') {
        $sql .= ' WHERE t.status = "cancelled"';
    }
    
    $sql .= ' ORDER BY t.created_at DESC';
    $txs = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    log_error('admin tx list: ' . $e->getMessage());
    $txs = [];
}

$pageTitle = 'Manage Transactions · CrossRent';
require __DIR__ . '/../../includes/header.php';
?>

<div class="home-container" style="padding-bottom: 100px;">
  <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 20px;">Transactions</h2>
  
  <!-- TABS -->
  <div class="tabs" style="margin-bottom: 20px; background: transparent; display: flex;">
    <a href="?tab=lending" class="<?= $tab === 'lending' ? 'active' : '' ?>" style="flex: 1; text-align: center;">Lending</a>
    <a href="?tab=completed" class="<?= $tab === 'completed' ? 'active' : '' ?>" style="flex: 1; text-align: center;">Completed</a>
    <a href="?tab=cancelled" class="<?= $tab === 'cancelled' ? 'active' : '' ?>" style="flex: 1; text-align: center;">Cancelled</a>
  </div>

  <?php if (empty($txs)): ?>
    <div class="empty-state" style="text-align: center; margin-top: 50px; color: #888;">
      <i class="fa-regular fa-receipt" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;"></i>
      <p>No <?= e($tab) ?> transactions found.</p>
    </div>
  <?php else: foreach ($txs as $tx): ?>
    <div class="info-card glass-panel" style="margin-bottom: 15px; padding: 20px;">
      
      <!-- Card Header -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
        <strong style="font-size: 18px; color: var(--dark);"><?= e($tx['item_name']) ?></strong>
        <span class="badge-tx <?= e(strtolower($tx['status'])) ?>" style="padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase;
          <?php 
            if($tx['status'] === 'requested') echo 'background: #E65100; color: #FFF;';
            elseif($tx['status'] === 'rented') echo 'background: #27AE60; color: #FFF;';
            elseif($tx['status'] === 'completed') echo 'background: #1976D2; color: #FFF;';
            elseif($tx['status'] === 'cancelled') echo 'background: #AF0B01; color: #FFF;';
          ?>">
          <?= e(ucfirst($tx['status'])) ?>
        </span>
      </div>
      
      <!-- Card Details -->
      <div style="background: rgba(255,255,255,0.5); padding: 12px; border-radius: 12px; margin-bottom: 15px;">
        <p class="text-muted" style="font-size: 13px; margin-bottom: 6px;">
          Renter: <strong style="color: var(--dark);"><?= e($tx['renter_email']) ?></strong>
        </p>
        <p class="text-muted" style="font-size: 13px; margin-bottom: 6px;">
          Period: <strong style="color: var(--dark);"><?= e($tx['rent_period']) ?></strong> | Price: <strong style="color: var(--dark);"><?= format_price($tx['rent_price']) ?></strong>
        </p>
        <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">
          <i class="fa-regular fa-calendar"></i> <?= date('M j, Y · g:i A', strtotime($tx['created_at'])) ?>
        </p>
      </div>
      
      <!-- Action Buttons -->
      <div style="display: flex; gap: 10px;">
        <form method="post" style="display: flex; gap: 10px; width: 100%;">
          <?= csrf_field() ?>
          <input type="hidden" name="tx_id" value="<?= $tx['id'] ?>">
          
          <?php if ($tx['status'] === 'requested'): ?>
            <button name="action" value="approve" class="btn-primary" style="padding: 12px; font-size: 14px; flex: 1; background: var(--dark); border-radius: 12px;">Approve</button>
            <button name="action" value="decline" class="btn-primary" style="padding: 12px; font-size: 14px; flex: 1; background: var(--primary); border-radius: 12px;">Decline</button>
          
          <?php elseif ($tx['status'] === 'rented'): ?>
            <button name="action" value="complete" class="btn-primary" style="padding: 12px; font-size: 14px; flex: 1; background: var(--dark); border-radius: 12px;">Mark Returned</button>
          <?php endif; ?>
          
        </form>
      </div>

    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>