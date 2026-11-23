<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin(); // <--- Changed from require_login()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id   = (int)post_str('id');
    $pdo  = db();

    try {
        $stmt = $pdo->prepare('SELECT image_path FROM items WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();

        if ($item) {
            if ($item['image_path'] && file_exists(__DIR__ . '/../' . $item['image_path'])) {
                @unlink(__DIR__ . '/../' . $item['image_path']);
            }
            $pdo->prepare('DELETE FROM items WHERE id = :id')->execute([':id' => $id]);
            log_activity('item_delete', "Admin deleted item $id");
            flash('success', 'Listing deleted.');
        }
    } catch (PDOException $e) {
        log_error('item_delete: ' . $e->getMessage());
        flash('error', 'Could not delete item.');
    }
}
redirect('pages/admin/items.php');