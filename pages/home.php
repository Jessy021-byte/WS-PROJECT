<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo          = db();
$search       = get_str('q');
$categorySlug = get_str('category');
$myListings   = get_str('my_listings'); // Added for drawer link
$items        = get_items($pdo, $search, $categorySlug);
$categories   = get_categories($pdo);

$pageTitle = 'Home · CrossRent';
require __DIR__ . '/../includes/header.php';
?>
<div class="home-container">
  <div class="greeting-header">
    <div class="hcdc-dropdown">HCDC <i class="fa-solid fa-chevron-down"></i></div>
    <h1 class="greeting-text">Find what you need, Crossian.</h1>
  </div>

  <form class="search-bar" method="get" action="<?= url('pages/home.php') ?>">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search items for rent...">
  </form>

  <h3 class="section-label">Categories</h3>
  <div class="category-row">
    <a href="<?= url('pages/home.php?q=' . urlencode($search)) ?>" class="chip <?= empty($categorySlug) ? 'active' : '' ?>">
      <i class="fa-solid fa-border-all"></i> All
    </a>
    <?php foreach ($categories as $c): ?>
      <a href="<?= url('pages/home.php?category=' . urlencode($c['slug']) . '&q=' . urlencode($search)) ?>" class="chip <?= $categorySlug === $c['slug'] ? 'active' : '' ?>">
        <i class="fa-solid fa-book"></i> <?= e($c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <h3 class="section-label"><?= $search !== '' ? 'Search Results' : 'All Items' ?></h3>
  
  <div class="listings-grid">
    <?php if (empty($items)): ?>
      <div class="empty-state">No listings found.</div>
    <?php else: foreach ($items as $it): ?>
      <a class="listing-card" href="<?= url('pages/item_view.php?id=' . $it['id']) ?>">
        <div class="listing-image">
          <?php if ($it['image_path']): ?>
            <img src="<?= url($it['image_path']) ?>" alt="<?= e($it['name']) ?>">
          <?php else: ?>
            <div class="listing-placeholder">No image</div>
          <?php endif; ?>
        </div>
        <div class="listing-body">
          <div class="listing-top-row">
            <span class="listing-category"><?= e($it['category_name']) ?></span>
            <span class="badge <?= e(strtolower($it['status'])) ?>"><?= e($it['status']) ?></span>
          </div>
          <p class="listing-name"><?= e($it['name']) ?></p>
          <p class="listing-price"><?= e(format_price($it['price'])) ?> <span>/ <?= e(strtolower($it['rental_period'])) ?></span></p>
        </div>
      </a>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>