<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin(); 

$pdo        = db();
$user       = current_user();
$categories = get_categories($pdo);
$id         = (int)get_str('id');
$isEdit     = $id > 0;
$errors     = [];

// Find the ID of the "Others" category to use for the JS toggle
$others_id = 0;
foreach ($categories as $c) {
    if (strtolower($c['name']) === 'others') {
        $others_id = $c['id'];
        break;
    }
}

// Initialize item array
$item = [
    'name' => '', 'category_id' => '', 'description' => '',
    'price' => '', 'rental_period' => 'Day', 'status' => 'Available',
    'location' => '', 'image_path' => null, 'owner_id' => $user['id'],
];

if ($isEdit) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM items WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { flash('error', 'Item not found.'); redirect('pages/home.php'); }
        if ((int)$row['owner_id'] !== (int)$user['id'] && !is_admin()) {
            http_response_code(403); die('You cannot edit this listing.');
        }
        $item = $row;
    } catch (PDOException $e) {
        log_error('item_form load: ' . $e->getMessage());
        flash('error', 'Could not load item.'); redirect('pages/home.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $item['name']          = post_str('name');
    $item['category_id']   = post_str('category_id');
    $item['description']   = post_str('description');
    $item['price']         = post_str('price');
    $item['rental_period'] = post_str('rental_period');
    $item['status']        = post_str('status');
    $item['location']      = post_str('location');
    $custom_category       = post_str('custom_category');

    if ($item['name'] === '')                              $errors['name'] = 'Item name is required.';
    elseif (mb_strlen($item['name']) > 150)                $errors['name'] = 'Name is too long.';
    if (!ctype_digit($item['category_id']))                $errors['category_id'] = 'Choose a category.';
    if ($item['description'] === '')                       $errors['description'] = 'Description is required.';
    if ($m = validate_number($item['price'], 1))           $errors['price'] = 'Price: ' . $m;
    if (!in_array($item['rental_period'], ['Hour','Day','Week','Month'], true))
        $errors['rental_period'] = 'Invalid rental period.';
    if (!in_array($item['status'], ['Available','Reserved','Rented'], true))
        $errors['status'] = 'Invalid status.';
    if ($item['location'] === '')                          $errors['location'] = 'Location is required.';

    // Validation for custom category
    if ($others_id && (int)$item['category_id'] === $others_id && $custom_category === '') {
        $errors['category_id'] = 'Please type your specific category.';
    }

    [$imagePath, $imageErr] = upload_image($_FILES['image'] ?? []);
    if ($imageErr) $errors['image'] = $imageErr;

    if (empty($errors)) {
        try {
            // If user typed a custom category, create it in the database first
            if ($others_id && (int)$item['category_id'] === $others_id && $custom_category !== '') {
                // Check if the custom category already exists
                $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = :n');
                $stmt->execute([':n' => $custom_category]);
                $new_cat_id = $stmt->fetchColumn();

                if (!$new_cat_id) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $custom_category)));
                    $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:n, :s)')->execute([':n' => $custom_category, ':s' => $slug]);
                    $new_cat_id = $pdo->lastInsertId();
                }
                $item['category_id'] = $new_cat_id;
            }

            if ($isEdit) {
                $sql = 'UPDATE items SET name=:name, category_id=:cat, description=:desc,
                        price=:price, rental_period=:period, status=:status, location=:loc'
                     . ($imagePath ? ', image_path=:img' : '') . ' WHERE id=:id';
                $params = [
                    ':name' => $item['name'], ':cat' => $item['category_id'],
                    ':desc' => $item['description'], ':price' => $item['price'],
                    ':period' => $item['rental_period'], ':status' => $item['status'],
                    ':loc' => $item['location'], ':id' => $id,
                ];
                if ($imagePath) $params[':img'] = $imagePath;
                $pdo->prepare($sql)->execute($params);
                log_activity('item_update', "Item $id edited");
                flash('success', 'Listing updated.');
            } else {
                $pdo->prepare(
                    'INSERT INTO items (owner_id, category_id, name, description, price, rental_period, status, location, image_path)
                     VALUES (:owner,:cat,:name,:desc,:price,:period,:status,:loc,:img)'
                )->execute([
                    ':owner' => $user['id'], ':cat' => $item['category_id'],
                    ':name' => $item['name'], ':desc' => $item['description'],
                    ':price' => $item['price'], ':period' => $item['rental_period'],
                    ':status' => $item['status'], ':loc' => $item['location'], ':img' => $imagePath,
                ]);
                log_activity('item_create', 'New listing: ' . $item['name']);
                flash('success', 'Listing created.');
            }
            redirect('pages/home.php');
        } catch (PDOException $e) {
            log_error('item_form save: ' . $e->getMessage());
            $errors['general'] = 'Could not save listing.';
        }
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'List') . ' an Item · CrossRent';
require __DIR__ . '/../includes/header.php';
?>
<div class="form-container">
  <?php if (!empty($errors['general'])): ?><div class="alert error"><?= e($errors['general']) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="itemForm" novalidate>
    <?= csrf_field() ?>

    <label class="input-label">Item Name</label>
    <input type="text" name="name" class="input-box" value="<?= e($item['name']) ?>" placeholder="e.g. Scientific Calculator" required>
    <?php if (!empty($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>

    <label class="input-label">Category</label>
    <div class="pill-group">
      <?php foreach ($categories as $c): ?>
        <label class="pill-radio">
          <input type="radio" name="category_id" value="<?= $c['id'] ?>" 
                 <?= $item['category_id'] == $c['id'] ? 'checked' : '' ?>
                 onchange="toggleCustomCategory(this.value, <?= $others_id ?>)">
          <span><?= e($c['name']) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($errors['category_id'])): ?><span class="field-error"><?= e($errors['category_id']) ?></span><?php endif; ?>

    <!-- Hidden Custom Category Field -->
    <div id="customCategoryWrapper" style="display: <?= ($item['category_id'] == $others_id) ? 'block' : 'none' ?>; margin-top: 15px;">
        <input type="text" name="custom_category" id="customCategoryInput" class="input-box" placeholder="Type specific category here...">
    </div>

    <label class="input-label">Price (₱)</label>
    <input type="number" step="0.01" name="price" class="input-box" value="<?= e($item['price']) ?>" placeholder="0.00" required>
    <?php if (!empty($errors['price'])): ?><span class="field-error"><?= e($errors['price']) ?></span><?php endif; ?>

    <label class="input-label">Rental Duration</label>
    <div class="pill-group">
      <?php foreach (['Hour','Day','Week','Month'] as $p): ?>
        <label class="pill-radio">
          <input type="radio" name="rental_period" value="<?= $p ?>" <?= $item['rental_period'] === $p ? 'checked' : '' ?>>
          <span><?= $p ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <label class="input-label">Location</label>
    <input type="text" name="location" class="input-box" value="<?= e($item['location'] ?? '') ?>" placeholder="e.g. J605 or any specific location" required>
    <?php if (!empty($errors['location'])): ?><span class="field-error"><?= e($errors['location']) ?></span><?php endif; ?>

    <label class="input-label">Status</label>
    <div class="pill-group">
      <?php foreach (['Available','Reserved','Rented'] as $s): ?>
        <label class="pill-radio">
          <input type="radio" name="status" value="<?= $s ?>" <?= $item['status'] === $s ? 'checked' : '' ?>>
          <span><?= $s ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <label class="input-label">Description</label>
    <textarea name="description" class="input-box" rows="4" placeholder="Describe condition..." required><?= e($item['description']) ?></textarea>
    <?php if (!empty($errors['description'])): ?><span class="field-error"><?= e($errors['description']) ?></span><?php endif; ?>

    <label class="input-label">Item Image (Optional)</label>
    <input type="file" name="image" accept="image/*" class="input-box" style="padding: 10px; background:#fff;">
    <?php if (!empty($errors['image'])): ?><span class="field-error"><?= e($errors['image']) ?></span><?php endif; ?>
    
    <?php if (!empty($item['image_path'])): ?>
      <img src="<?= url($item['image_path']) ?>" class="img-preview" alt="Item Preview">
    <?php endif; ?>

  </form>
</div>

<script>
  function toggleCustomCategory(selectedId, othersId) {
    const wrapper = document.getElementById('customCategoryWrapper');
    const input = document.getElementById('customCategoryInput');
    
    // If the selected radio button is the "Others" category ID
    if (parseInt(selectedId) === parseInt(othersId) && othersId !== 0) {
      wrapper.style.display = 'block';
      input.required = true;
      input.focus();
    } else {
      wrapper.style.display = 'none';
      input.required = false;
    }
  }
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>