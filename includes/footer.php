</main>

<?php 
// Hide nav on auth and specific detail pages
$hide_nav_pages = ['login.php', 'register.php', 'item_view.php', 'conversation.php'];
if (is_logged_in() && !in_array($page, $hide_nav_pages)): 
?>
<!-- Floating Bottom Nav -->
<div class="floating-nav-container">
  <nav class="floating-nav">
    
    <!-- HOME -->
    <a href="<?= url('pages/home.php') ?>" class="nav-item <?= $page === 'home.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-house"></i> 
      <?php if ($page === 'home.php'): ?><span>Home</span><?php endif; ?>
    </a>
    
    <!-- CHATS -->
    <a href="<?= url('pages/chat.php') ?>" class="nav-item <?= $page === 'chat.php' ? 'active' : '' ?>">
      <i class="fa-regular fa-comment-dots"></i> 
      <?php if ($page === 'chat.php'): ?><span>Chats</span><?php endif; ?>
    </a>

    <?php if (is_admin()): ?>
    <!-- ADMIN: Transact replaced by Admin Panel, no Profile -->
    <a href="<?= url('pages/admin/dashboard.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : '' ?>">
      <i class="fa-solid fa-shield-halved"></i>
      <?php if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false): ?><span>Admin</span><?php endif; ?>
    </a>
    <?php else: ?>
    <!-- USER: Transact + Profile -->
    <a href="<?= url('pages/transactions.php') ?>" class="nav-item <?= $page === 'transactions.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-arrow-right-arrow-left"></i> 
      <?php if ($page === 'transactions.php'): ?><span>Transactions</span><?php endif; ?>
    </a>
    <a href="<?= url('pages/profile.php') ?>" class="nav-item <?= $page === 'profile.php' ? 'active' : '' ?>">
      <i class="fa-regular fa-user"></i> 
      <?php if ($page === 'profile.php'): ?><span>Profile</span><?php endif; ?>
    </a>
    <?php endif; ?>
    
  </nav>
  
  <!-- Only Admins see the Add button -->
  <?php if (is_admin()): ?>
    <a href="<?= url('pages/item_form.php') ?>" class="nav-fab">
      <i class="fa-solid fa-plus"></i>
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<script src="<?= url('assets/js/app.js') ?>"></script>
<script>
  function toggleDrawer() {
    const drawer = document.getElementById('sideDrawer');
    const overlay = document.getElementById('drawerOverlay');
    if (drawer && overlay) {
      drawer.classList.toggle('open');
      overlay.classList.toggle('open');
    }
  }
</script>
</body>
</html>