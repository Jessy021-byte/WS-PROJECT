<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo  = db();
$user = current_user();

try {
    // This query gets ALL conversations for the user (whether they are owner or renter)
    $sql = 'SELECT c.*, i.name AS item_name, 
                   IF(c.owner_id = :uid1, r.full_name, o.full_name) AS other_name,
                   IF(c.owner_id = :uid2, r.email, o.email) AS other_email,
                   (SELECT body FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_msg
            FROM conversations c
            JOIN items i ON i.id = c.item_id
            JOIN users o ON o.id = c.owner_id
            JOIN users r ON r.id = c.renter_id
            WHERE c.owner_id = :uid3 OR c.renter_id = :uid4
            ORDER BY c.updated_at DESC';
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid1' => $user['id'], 
        ':uid2' => $user['id'],
        ':uid3' => $user['id'],
        ':uid4' => $user['id']
    ]);
    $chats = $stmt->fetchAll();
} catch (PDOException $e) {
    log_error('chat list: ' . $e->getMessage());
    $chats = [];
}

$pageTitle = 'Chats · CrossRent';
require __DIR__ . '/../includes/header.php';
?>

<div class="home-container" style="padding-bottom: 100px;">
  <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 20px;">Messages</h2>

  <div class="chat-list" style="margin-top: 20px;">
    <?php if (empty($chats)): ?>
      <div class="empty-state" style="text-align: center; margin-top: 50px; color: #888;">
        <i class="fa-regular fa-comment-dots" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;"></i>
        <p>No messages yet.</p>
      </div>
    <?php else: foreach ($chats as $c): ?>
      <a href="<?= url('pages/conversation.php?id=' . $c['id']) ?>" class="chat-item glass-panel" style="margin-bottom: 12px; border-radius: 16px; padding: 15px; display: flex; gap: 15px; text-decoration: none; color: inherit;">
        <div class="avatar" style="width: 50px; height: 50px; font-size: 20px; background: var(--dark); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
          <span><?= e(strtoupper(substr($c['other_email'], 0, 1))) ?></span>
        </div>
        <div class="chat-content" style="flex: 1; overflow: hidden;">
          <div class="chat-name-row" style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
            <strong class="chat-name" style="font-size: 16px; font-weight: 600; color: var(--dark);"><?= e($c['other_name']) ?></strong>
          </div>
          <p class="text-muted" style="font-size: 11px; margin-bottom: 4px;">Re: <span style="color: var(--primary); font-weight: 600;"><?= e($c['item_name']) ?></span></p>
          <p class="chat-preview" style="font-size: 14px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($c['last_msg'] ?: 'No messages yet.') ?></p>
        </div>
      </a>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>