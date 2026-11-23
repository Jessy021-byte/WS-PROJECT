<?php
require_once __DIR__ . '/../includes/auth.php';
require_guest();

$errors = [];
$old    = ['full_name' => '', 'email' => '', 'id_number' => '', 'program' => '', 'course' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old['full_name'] = post_str('full_name');
    $old['email']     = post_str('email');
    $old['id_number'] = post_str('id_number');
    $old['program']   = post_str('program');
    $old['course']    = post_str('course');
    $password         = $_POST['password'] ?? '';
    $confirm          = $_POST['confirm_password'] ?? '';

    $errors = validate_required([
        'full_name' => 'Full name', 'email' => 'Email',
        'id_number' => 'ID number', 'program' => 'Program', 'course' => 'Course',
        'password' => 'Password', 'confirm_password' => 'Confirm password',
    ]);
    if (!isset($errors['email']) && !validate_hcdc_email($old['email'])) {
        $errors['email'] = 'Only @hcdc.edu.ph emails are allowed.';
    }
    if (!isset($errors['password']) && ($m = validate_password($password))) {
        $errors['password'] = $m;
    }
    if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

    if (empty($errors)) {
        try {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
            $stmt->execute([':e' => $old['email']]);
            if ($stmt->fetch()) {
                $errors['email'] = 'This email is already registered.';
            } else {
                db()->prepare('INSERT INTO users (email, password_hash, full_name, id_number, program, course, role) VALUES (:e,:h,:n,:i,:pr,:c,"user")')
                    ->execute([
                        ':e'  => $old['email'],
                        ':h'  => password_hash($password, PASSWORD_DEFAULT),
                        ':n'  => $old['full_name'],
                        ':i'  => $old['id_number'],
                        ':pr' => $old['program'],
                        ':c'  => $old['course'],
                    ]);
                log_activity('register', 'New user: ' . $old['email']);
                flash('success', 'Account created. Please sign in.');
                redirect('pages/login.php');
            }
        } catch (PDOException $e) {
            log_error('register: ' . $e->getMessage());
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}
$pageTitle = 'Sign Up · CrossRent';
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-page-wrapper">
  <div class="auth-card-container">
    
    <!-- LEFT SIDE: HCDC BRANDING -->
    <div class="auth-brand-side">
      <img src="<?= url('assets/images/hcdc_logo.png') ?>" alt="HCDC Logo">
      <h2>Join Cross<span style="color:#FFD700;">Rent</span></h2>

    </div>

    <!-- RIGHT SIDE: REGISTER FORM -->
    <div class="auth-form-side">
      <div class="auth-card" style="width: 100%; max-width: 380px;">
        <div class="logo-large">Cross<span>Rent</span></div>
        <h1>Get Started</h1>
        <p class="subtitle">Exclusive for Holy Cross of Davao College users.</p>

        <?php if (!empty($errors['general'])): ?><div class="alert error"><?= e($errors['general']) ?></div><?php endif; ?>

        <form method="post" novalidate autocomplete="off">
          <?= csrf_field() ?>

          <div class="floating-label">
            <input type="text" name="full_name" id="full_name" value="<?= e($old['full_name']) ?>" placeholder=" " required 
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
            <label for="full_name">FULL NAME</label>
            <?php if (!empty($errors['full_name'])): ?><span class="field-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
          </div>

          <div class="floating-label">
            <input type="text" name="id_number" id="id_number" value="<?= e($old['id_number']) ?>" placeholder=" " required
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
            <label for="id_number">ID NUMBER</label>
            <?php if (!empty($errors['id_number'])): ?><span class="field-error"><?= e($errors['id_number']) ?></span><?php endif; ?>
          </div>

          <div class="floating-label">
            <input type="text" name="program" id="program" value="<?= e($old['program']) ?>" placeholder=" " required
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
            <label for="program">PROGRAM</label>
            <?php if (!empty($errors['program'])): ?><span class="field-error"><?= e($errors['program']) ?></span><?php endif; ?>
          </div>

          <div class="floating-label">
            <input type="text" name="course" id="course" value="<?= e($old['course']) ?>" placeholder=" " required
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
            <label for="course">COURSE</label>
            <?php if (!empty($errors['course'])): ?><span class="field-error"><?= e($errors['course']) ?></span><?php endif; ?>
          </div>

          <div class="floating-label">
            <input type="email" name="email" id="email" value="<?= e($old['email']) ?>" placeholder=" " required 
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
            <label for="email">EMAIL ADDRESS</label>
            <?php if (!empty($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
          </div>

          <div class="floating-label">
            <input type="password" name="password" id="password" placeholder=" " required 
                   autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')">
            <label for="password">PASSWORD</label>
            <!-- PASSING 'this' TO THE FUNCTION -->
            <button type="button" class="toggle-pass" onclick="togglePassword('password', this)"><i class="fa-regular fa-eye"></i></button>
            <?php if (!empty($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
          </div>

          <div class="floating-label">
            <input type="password" name="confirm_password" id="confirm_password" placeholder=" " required 
                   autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')">
            <label for="confirm_password">CONFIRM PASSWORD</label>
            <!-- PASSING 'this' TO THE FUNCTION -->
            <button type="button" class="toggle-pass" onclick="togglePassword('confirm_password', this)"><i class="fa-regular fa-eye"></i></button>
            <?php if (!empty($errors['confirm_password'])): ?><span class="field-error"><?= e($errors['confirm_password']) ?></span><?php endif; ?>
          </div>

          <button class="btn-primary" type="submit">Create Account</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="<?= url('pages/login.php') ?>">Sign in here.</a></p>
      </div>
    </div>
    
  </div>
</div>

<script>
  // The function now receives the button element directly as 'btn'
  function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    
    if (input.type === "password") {
      input.type = "text";
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    } else {
      input.type = "password";
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    }
  }
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>