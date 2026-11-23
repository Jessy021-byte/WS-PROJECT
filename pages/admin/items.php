<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$pdo = db();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_check();
    $id = (int)$_POST['delete_id'];
    $pdo->prepare('DELETE FROM items WHERE id = :id')->execute([':id' => $id]);
    flash('success', 'Item deleted by admin.');
    redirect('pages/admin/items.php');
}

$items = $pdo->query('SELECT i.*, c.name AS category_name FROM items i JOIN categories c ON c.id = i.category_id ORDER BY i.created_at DESC')->fetchAll();

$pageTitle = 'Manage Items · CrossRent';
require __DIR__ . '/../../includes/header.php';
?>
<div class="form-container" style="padding-top: 20px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
    <h2>All Items (<?= count($items) ?>)</h2>
    <a href="<?= url('pages/item_form.php') ?>" class="btn-primary" style="width:auto; padding:10px 15px; text-decoration:none;"><i class="fa-solid fa-plus"></i> Add</a>
  </div>
  
  <?php if (empty($items)): ?>
    <p class="text-muted">No items posted yet.</p>
  <?php else: foreach ($items as $it): ?>
    <div class="info-card" style="margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <strong style="font-size: 16px;"><?= e($it['name']) ?></strong><br>
        <span class="text-muted" style="font-size: 12px;"><?= e($it['category_name']) ?> | <?= e(format_price($it['price'])) ?></span>
      </div>
      <form method="post" onsubmit="return confirm('Delete this item permanently?');" style="margin:0;">
        <?= csrf_field() ?>
        <input type="hidden" name="delete_id" value="<?= $it['id'] ?>">
        <button type="submit" class="btn-icon" style="color: var(--primary); border-color: var(--primary);"><i class="fa-solid fa-trash"></i></button>
      </form>
    </div>
  <?php endforeach; endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>