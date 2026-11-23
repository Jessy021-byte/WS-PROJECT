<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo    = db();
$user   = current_user();
$errors = [];

$row = $pdo->prepare('SELECT full_name, profile_pic, id_number, program, course, created_at FROM users WHERE id = :id');
$row->execute([':id' => $user['id']]);
$dbUser = $row->fetch() ?: [];
$extra  = $dbUser + ['id_number' => '', 'program' => '', 'course' => '', 'created_at' => null];
// Sync session with fresh DB data
$user = array_merge($user, array_intersect_key($dbUser, array_flip(['full_name', 'profile_pic'])));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    // Handle Profile Update
    $name      = post_str('full_name');
    $id_number = post_str('id_number');
    $program   = post_str('program');
    $course    = post_str('course');
    if ($name === '') $errors['full_name'] = 'Name is required.';

    [$picPath, $picErr] = upload_image($_FILES['profile_pic'] ?? [], 'avatar');
    if ($picErr) $errors['profile_pic'] = $picErr;

    if (empty($errors)) {
        try {
            $sql = 'UPDATE users SET full_name = :n, id_number = :i, program = :pr, course = :c';
            $params = [':n' => $name, ':i' => $id_number, ':pr' => $program, ':c' => $course, ':id' => $user['id']];
            if ($picPath) {
                $sql .= ', profile_pic = :p';
                $params[':p'] = $picPath;
            }
            $sql .= ' WHERE id = :id';
            $pdo->prepare($sql)->execute($params);

            $extra = ['id_number' => $id_number, 'program' => $program, 'course' => $course];
            $_SESSION['user']['full_name'] = $name;
            if ($picPath) $_SESSION['user']['profile_pic'] = $picPath;
            
            flash('success', 'Profile updated.');
            redirect('pages/profile.php');
        } catch (PDOException $e) {
            log_error('profile_update: ' . $e->getMessage());
            $errors['general'] = 'Update failed.';
        }
    }
}

$openEdit = !empty($errors);
$pageTitle = 'My Profile · CrossRent';
require __DIR__ . '/../includes/header.php';
?>
<div class="form-container glass-panel" style="padding: 30px; margin-top: 20px;">

  <!-- PROFILE VIEW -->
  <div id="profileView" <?= $openEdit ? 'style="display:none"' : '' ?>>
    <div class="profile-header" style="margin-bottom: 24px;">
      <div class="avatar" style="width: 80px; height: 80px; font-size: 32px; border: 3px solid var(--white); box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/../' . $user['profile_pic'])): ?>
          <img src="<?= url($user['profile_pic']) ?>" alt="Profile">
        <?php else: ?>
          <span><?= e(strtoupper(substr($user['email'], 0, 1))) ?></span>
        <?php endif; ?>
      </div>
      <div style="flex:1;">
        <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;"><?= e($user['full_name']) ?></h1>
        <p class="text-muted"><?= e($user['email']) ?> <span class="badge" style="background:var(--dark); margin-left:5px;"><?= e(ucfirst($user['role'])) ?></span></p>
      </div>
      <button class="btn-secondary" style="padding: 8px 18px; font-size: 13px; flex-shrink:0;" onclick="toggleEdit(true)"><i class="fa-solid fa-pen" style="margin-right:6px;"></i>Edit Profile</button>
    </div>

    <div class="profile-info-grid">
      <div class="profile-info-item">
        <span class="profile-info-label">ID Number</span>
        <span class="profile-info-value"><?= $extra['id_number'] !== '' ? e($extra['id_number']) : '<span class="text-muted">—</span>' ?></span>
      </div>
      <div class="profile-info-item">
        <span class="profile-info-label">Program</span>
        <span class="profile-info-value"><?= $extra['program'] !== '' ? e($extra['program']) : '<span class="text-muted">—</span>' ?></span>
      </div>
      <div class="profile-info-item">
        <span class="profile-info-label">Course</span>
        <span class="profile-info-value"><?= $extra['course'] !== '' ? e($extra['course']) : '<span class="text-muted">—</span>' ?></span>
      </div>
      <div class="profile-info-item">
        <span class="profile-info-label">Member Since</span>
        <span class="profile-info-value text-muted"><?= e(date('F j, Y', strtotime($user['created_at'] ?? 'now'))) ?></span>
      </div>
    </div>

    <button class="btn-primary" style="margin-top: 28px; width: auto; padding: 12px 28px; display:none;">Edit Profile</button>
  </div>

  <!-- EDIT FORM -->
  <div id="profileEdit" <?= $openEdit ? '' : 'style="display:none"' ?>>
    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Edit Profile</h2>

    <?php if (!empty($errors['general'])): ?><div class="alert error"><?= e($errors['general']) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <label class="input-label">Full Name</label>
      <input type="text" name="full_name" class="input-box" value="<?= e($user['full_name']) ?>" required>
      <?php if (!empty($errors['full_name'])): ?><span class="field-error"><?= e($errors['full_name']) ?></span><?php endif; ?>

      <label class="input-label">ID Number</label>
      <input type="text" name="id_number" class="input-box" value="<?= e($extra['id_number']) ?>">

      <label class="input-label">Program</label>
      <input type="text" name="program" class="input-box" value="<?= e($extra['program']) ?>">

      <label class="input-label">Course</label>
      <input type="text" name="course" class="input-box" value="<?= e($extra['course']) ?>">

      <label class="input-label" style="margin-top: 20px;">Profile Picture (Optional)</label>
      <input type="file" name="profile_pic" accept="image/*" class="input-box" style="padding: 10px; background:#fff;">
      <?php if (!empty($errors['profile_pic'])): ?><span class="field-error"><?= e($errors['profile_pic']) ?></span><?php endif; ?>

      <div style="display:flex; gap:12px; margin-top:25px;">
        <button class="btn-primary" type="submit">Save Changes</button>
        <button type="button" class="btn-secondary" onclick="toggleEdit(false)">Cancel</button>
      </div>
    </form>
  </div>

</div>

<script>
  function toggleEdit(show) {
    document.getElementById('profileView').style.display = show ? 'none' : '';
    document.getElementById('profileEdit').style.display = show ? '' : 'none';
  }
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>