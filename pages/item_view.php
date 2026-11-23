<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo  = db();
$user = current_user();
$id   = (int)get_str('id');
if ($id <= 0) { flash('error', 'Invalid item.'); redirect('pages/home.php'); }

try {
    $stmt = $pdo->prepare(
        'SELECT i.*, c.name AS category_name,
                u.full_name AS owner_name, u.email AS owner_email, u.id AS owner_id
         FROM items i
         JOIN categories c ON c.id = i.category_id
         JOIN users u      ON u.id = i.owner_id
         WHERE i.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();
} catch (PDOException $e) {
    log_error('item_view: ' . $e->getMessage());
    $item = false;
}
if (!$item) { flash('error', 'Item not found.'); redirect('pages/home.php'); }

$isAdmin = is_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post_str('action');
    try {
        if ($action === 'request' && !$isAdmin) {
            if ((int)$user['id'] === (int)$item['owner_id']) {
                flash('error', 'You cannot rent your own item.');
            } elseif ($item['status'] !== 'Available') {
                flash('error', 'This item is not available right now.');
            } else {
                // 1. Create the transaction
                $pdo->prepare(
                    'INSERT INTO transactions (item_id, owner_id, renter_id, status, rent_period, rent_price)
                     VALUES (:item,:owner,:renter,"requested",:period,:price)'
                )->execute([
                    ':item' => $item['id'], ':owner' => $item['owner_id'],
                    ':renter' => $user['id'], ':period' => $item['rental_period'],
                    ':price' => $item['price'],
                ]);

                // 2. Send the automated message
                send_system_message($pdo, $item['id'], $item['owner_id'], $user['id'], $user['id'], 'New Rental Request', [
                    'item_name' => $item['name'],
                    'renter_email' => $user['email']
                ]);

                flash('success', 'Rental request sent to the owner.');
            }
        } elseif ($action === 'message' && !$isAdmin) {
            if ((int)$user['id'] === (int)$item['owner_id']) {
                flash('error', 'You cannot message yourself.');
            } else {
                // Verify user still exists in DB
                $chk = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
                $chk->execute([':id' => $user['id']]);
                if (!$chk->fetch()) {
                    flash('error', 'Your session is invalid. Please sign in again.');
                    redirect('pages/login.php');
                }
                $stmt = $pdo->prepare('SELECT id FROM conversations WHERE item_id = :item AND renter_id = :r LIMIT 1');
                $stmt->execute([':item' => $item['id'], ':r' => $user['id']]);
                $conv = $stmt->fetch();
                if ($conv) {
                    $convId = $conv['id'];
                } else {
                    $pdo->prepare('INSERT INTO conversations (item_id, owner_id, renter_id) VALUES (:i,:o,:r)')
                        ->execute([':i' => $item['id'], ':o' => $item['owner_id'], ':r' => $user['id']]);
                    $convId = (int)$pdo->lastInsertId();
                }
                redirect('pages/conversation.php?id=' . $convId);
            }
        }
    } catch (PDOException $e) {
        log_error('item_view action: ' . $e->getMessage());
        flash('error', 'Something went wrong. Please try again.');
    }
    redirect('pages/item_view.php?id=' . $item['id']);
}

$pageTitle = $item['name'] . ' · CrossRent';
require __DIR__ . '/../includes/header.php';
?>
<div class="item-view-wrap">
  <div class="item-hero">
    <?php if ($item['image_path']): ?>
      <img src="<?= url($item['image_path']) ?>" alt="<?= e($item['name']) ?>">
    <?php else: ?>
      <div class="listing-placeholder" style="height:100%; display:flex; align-items:center; justify-content:center;">No image</div>
    <?php endif; ?>
  </div>
  
  <div class="item-info">
    <div class="item-meta">
      <span class="listing-category" style="font-size:12px;"><?= e($item['category_name']) ?></span>
      <span class="badge <?= e(strtolower($item['status'])) ?>"><?= e($item['status']) ?></span>
    </div>
    
    <h1 class="item-title"><?= e($item['name']) ?></h1>
    <p class="item-price"><?= e(format_price($item['price'])) ?> <span>/ <?= e(strtolower($item['rental_period'])) ?></span></p>

    <div class="info-card">
      <p class="info-label">Owner</p>
      <div class="owner-row">
        <div class="owner-avatar"><?= e(strtoupper(substr($item['owner_name'], 0, 1))) ?></div>
        <div>
          <span class="info-value" style="display:block; font-weight: 600;"><?= e($item['owner_name']) ?></span>
          <span class="text-muted" style="font-size: 12px;"><?= e($item['owner_email']) ?></span>
        </div>
      </div>
    </div>

    <div class="info-card">
      <p class="info-label">Description</p>
      <p class="info-value"><?= nl2br(e($item['description'])) ?></p>
    </div>

    <div class="info-card">
      <p class="info-label">Location</p>
      <p class="info-value"><i class="fa-solid fa-location-dot text-red"></i> <?= e($item['location'] ?? 'HCDC Campus') ?></p>
    </div>
    
    <div class="info-card" style="margin-bottom: 100px;">
      <p class="info-label">Posted On</p>
      <p class="info-value"><i class="fa-regular fa-clock text-red"></i> <?= date('M j, Y', strtotime($item['created_at'])) ?></p>
    </div>
  </div>
</div>

<div class="action-bar">
  <?php if ($isAdmin): ?>
    <!-- Admin View -->
    <a href="<?= url('pages/item_form.php?id=' . $item['id']) ?>" class="btn-rent" style="background:#F5F5F5; color:var(--dark); border: 1px solid #E5E7EB; text-decoration: none;">Edit Listing</a>
    <form method="post" action="<?= url('pages/item_delete.php') ?>" onsubmit="return confirm('Delete this listing permanently?');" style="flex:1; display:flex;">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
      <button class="btn-rent" style="background:var(--primary); width:100%;" type="submit">Delete</button>
    </form>
  <?php else: ?>
    <!-- User View -->
    <form method="post" style="display:flex; width:100%; gap:12px;">
      <?= csrf_field() ?>
      
      <button type="submit" name="action" value="message" class="btn-icon" title="Message Owner">
        <i class="fa-regular fa-comment-dots"></i>
      </button>
      
      <button type="submit" name="action" value="request" class="btn-rent" <?= $item['status'] !== 'Available' ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
        <i class="fa-solid fa-cart-shopping"></i> Rent Now
      </button>
      
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>