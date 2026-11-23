<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$pdo = db();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post_str('action');

    try {
        if ($action === 'add') {
            $name = post_str('name');
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            if ($name !== '') {
                $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:n, :s)')->execute([':n' => $name, ':s' => $slug]);
                flash('success', 'Category added.');
            }
        } elseif ($action === 'delete') {
            $id = (int)post_str('id');
            // Check if category has items
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE category_id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                flash('error', 'Cannot delete category: it contains items.');
            } else {
                $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
                flash('success', 'Category deleted.');
            }
        }
    } catch (PDOException $e) {
        log_error('admin categories: ' . $e->getMessage());
        flash('error', 'Action failed.');
    }
    redirect('pages/admin/categories.php');
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();

$pageTitle = 'Manage Categories · CrossRent';
require __DIR__ . '/../../includes/header.php';
?>
<div class="form-container" style="padding-top: 20px;">
  <h2 style="margin-bottom: 20px;">Categories</h2>

  <form method="post" class="info-card" style="display: flex; gap: 10px; align-items: center; margin-bottom: 30px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <input type="text" name="name" class="input-box" style="margin:0; flex:1;" placeholder="New category name..." required>
    <button type="submit" class="btn-primary" style="width:auto; padding: 12px 20px;">Add</button>
  </form>
  
  <?php foreach ($categories as $c): ?>
    <div class="info-card" style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <strong style="font-size: 16px;"><?= e($c['name']) ?></strong><br>
        <span class="text-muted" style="font-size: 12px;">Slug: <?= e($c['slug']) ?></span>
      </div>
      <form method="post" onsubmit="return confirm('Delete category?');" style="margin:0;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button type="submit" class="btn-icon" style="color: var(--primary); border-color: var(--primary);"><i class="fa-solid fa-trash"></i></button>
      </form>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>