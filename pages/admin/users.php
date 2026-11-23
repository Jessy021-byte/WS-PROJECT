<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$pdo = db();
$currentAdminId = current_user()['id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $targetId = (int)post_str('user_id');
    $action   = post_str('action');

    if ($targetId === $currentAdminId) {
        flash('error', 'You cannot modify or delete your own admin account.');
    } else {
        try {
            if ($action === 'toggle_role') {
                $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
                $stmt->execute([':id' => $targetId]);
                $role = $stmt->fetchColumn();
                $newRole = ($role === 'admin') ? 'user' : 'admin';
                $pdo->prepare('UPDATE users SET role = :r WHERE id = :id')->execute([':r' => $newRole, ':id' => $targetId]);
                flash('success', 'User role updated to ' . $newRole . '.');
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $targetId]);
                flash('success', 'User deleted.');
            }
        } catch (PDOException $e) {
            log_error('admin users: ' . $e->getMessage());
            flash('error', 'Action failed.');
        }
    }
    redirect('pages/admin/users.php');
}

$users = $pdo->query('SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Manage Users · CrossRent';
require __DIR__ . '/../../includes/header.php';
?>
<div class="form-container" style="padding-top: 20px;">
  <h2 style="margin-bottom: 20px;">Users (<?= count($users) ?>)</h2>
  
  <?php foreach ($users as $u): ?>
    <div class="info-card" style="margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <strong style="font-size: 16px;"><?= e($u['full_name']) ?> <?= $u['role'] === 'admin' ? '👑' : '' ?></strong><br>
        <span class="text-muted" style="font-size: 12px;"><?= e($u['email']) ?> | Role: <?= e($u['role']) ?></span>
      </div>
      <?php if ($u['id'] !== $currentAdminId): ?>
      <div style="display: flex; gap: 8px;">
        <form method="post" style="margin:0;">
          <?= csrf_field() ?>
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button type="submit" name="action" value="toggle_role" class="btn-icon" title="Toggle Role"><i class="fa-solid fa-user-shield"></i></button>
        </form>
        <form method="post" onsubmit="return confirm('Delete this user?');" style="margin:0;">
          <?= csrf_field() ?>
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button type="submit" name="action" value="delete" class="btn-icon" style="color: var(--primary); border-color: var(--primary);"><i class="fa-solid fa-trash"></i></button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>