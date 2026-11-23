<?php
require_once __DIR__ . '/../includes/auth.php';
require_guest();

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = post_str('email');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $errors = validate_required(['email' => 'Email', 'password' => 'Password']);
    if (!isset($errors['email']) && !validate_hcdc_email($email)) {
        $errors['email'] = 'Please use a valid @hcdc.edu.ph email.';
    }

    if (empty($errors)) {
        try {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
            $stmt->execute([':e' => $email]);
            $row = $stmt->fetch();
            if ($row && password_verify($password, $row['password_hash'])) {
                login_user($row);
                if ($remember) setcookie('remember_email', $email, time() + 60*60*24*30, '/');
                else          setcookie('remember_email', '', time() - 3600, '/');
                redirect('pages/home.php');
            } else {
                $errors['general'] = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            log_error('login: ' . $e->getMessage());
            $errors['general'] = 'Login is temporarily unavailable.';
        }
    }
}
$pageTitle = 'Sign In · CrossRent';
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-page-wrapper">
  <div class="auth-card-container">
    
    <!-- LEFT SIDE: HCDC BRANDING -->
    <div class="auth-brand-side">
      <img src="<?= url('assets/images/hcdc_logo.png') ?>" alt="HCDC Logo">
      <h2>Cross<span style="color:#FFD700;">Rent</span></h2>

    </div>

    <!-- RIGHT SIDE: LOGIN FORM -->
    <div class="auth-form-side">
      <div class="auth-card" style="width: 100%; max-width: 380px;">
        <div class="logo-large">Cross<span>Rent</span></div>
        <h1>Welcome Back</h1>
        <p class="subtitle">Exclusive for Holy Cross of Davao College users.</p>

        <?php if (!empty($errors['general'])): ?><div class="alert error"><?= e($errors['general']) ?></div><?php endif; ?>

        <form method="post" novalidate autocomplete="off">
          <?= csrf_field() ?>

          <div class="floating-label">
            <!-- ADDED readonly and onfocus TRICK -->
            <input type="email" name="email" id="email" value="<?= e($email) ?>" placeholder=" " required 
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
            <label for="email">EMAIL ADDRESS</label>
            <?php if (!empty($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
          </div>

          <div class="floating-label">
            <!-- ADDED readonly and onfocus TRICK -->
            <input type="password" name="password" id="password" placeholder=" " required 
                   autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')">
            <label for="password">PASSWORD</label>
            <button type="button" class="toggle-pass" onclick="togglePassword('password')"><i class="fa-regular fa-eye"></i></button>
            <?php if (!empty($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
          </div>

          <label class="checkbox-row">
            <input type="checkbox" name="remember"> Remember my email
          </label>

          <button class="btn-primary" type="submit">Sign In</button>
        </form>

        <p class="auth-switch">Don't have an account? <a href="<?= url('pages/register.php') ?>">Sign up here.</a></p>
      </div>
    </div>
    
  </div>
</div>

<script>
  function togglePassword(id) {
    var input = document.getElementById(id);
    var icon = event.currentTarget.querySelector('i');
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