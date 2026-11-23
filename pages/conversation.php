<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo  = db();
$user = current_user();
$id   = (int)get_str('id');

if ($id <= 0) { flash('error', 'Invalid conversation.'); redirect('pages/chat.php'); }

// Fetch conversation and verify access
try {
    $stmt = $pdo->prepare(
        'SELECT c.*, i.name AS item_name, 
                o.full_name AS owner_name, r.full_name AS renter_name
         FROM conversations c
         JOIN items i ON i.id = c.item_id
         JOIN users o ON o.id = c.owner_id
         JOIN users r ON r.id = c.renter_id
         WHERE c.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $conv = $stmt->fetch();
} catch (PDOException $e) {
    log_error('conv load: ' . $e->getMessage());
    $conv = false;
}

// Ensure the user is either the owner or the renter
if (!$conv || ((int)$conv['owner_id'] !== (int)$user['id'] && (int)$conv['renter_id'] !== (int)$user['id'])) {
    flash('error', 'Conversation not found or access denied.'); 
    redirect('pages/chat.php');
}

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $content = post_str('content');
    
    if ($content !== '') {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert the message (using 'body' column)
            $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (:c, :s, :t)');
            $stmt->execute([':c' => $id, ':s' => $user['id'], ':t' => $content]);
            
            // 2. Update the conversation's updated_at timestamp so it bumps to top of chat list
            $pdo->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')
                ->execute([':id' => $id]);
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_error('msg insert FAILED: ' . $e->getMessage());
            flash('error', 'Message failed to send. Check error log.');
        }
    }
    redirect('pages/conversation.php?id=' . $id);
}

// Fetch messages
$stmt = $pdo->prepare('
    SELECT m.*, u.full_name, u.profile_pic 
    FROM messages m 
    JOIN users u ON u.id = m.sender_id 
    WHERE conversation_id = :id 
    ORDER BY created_at ASC
');
$stmt->execute([':id' => $id]);
$messages = $stmt->fetchAll();

// Determine who the other person is for the header
$isOwner = (int)$conv['owner_id'] === (int)$user['id'];
$otherName = $isOwner ? $conv['renter_name'] : $conv['owner_name'];

$pageTitle = 'Chat · CrossRent';
require __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; height: calc(100vh - 60px); position: relative;">

<div class="convo-header">
  <a href="<?= url('pages/chat.php') ?>" class="header-icon" style="color: var(--white);"><i class="fa-solid fa-arrow-left"></i></a>
  <div class="avatar" style="width:32px; height:32px; background:#fff; color:var(--primary);">
      <?= e(strtoupper(substr($otherName, 0, 1))) ?>
  </div>
  <div>
    <span class="convo-user"><?= e($otherName) ?></span>
    <div style="font-size: 11px; opacity: 0.8;">Re: <?= e($conv['item_name']) ?></div>
  </div>
</div>

<div class="chat-window" id="chatWindow">
  <?php if (empty($messages)): ?>
    <div class="empty-state" style="text-align: center; color: #888; margin-top: 50px;">Say hi to start the conversation!</div>
  <?php else: foreach ($messages as $m): 
    $isMine = (int)$m['sender_id'] === (int)$user['id']; ?>
    
    <div class="msg <?= $isMine ? 'mine' : 'theirs' ?>">
      <?= nl2br(e($m['body'])) ?>
    </div>
    <span class="msg-time" style="align-self: <?= $isMine ? 'flex-end' : 'flex-start' ?>;"><?= date('g:i A · M j', strtotime($m['created_at'])) ?></span>
    
  <?php endforeach; endif; ?>
</div>

<form method="post" class="chat-input-bar">
  <?= csrf_field() ?>
  <input type="text" name="content" placeholder="Type message..." required autocomplete="off">
  <button type="submit" class="chat-send-btn"><i class="fa-solid fa-paper-plane"></i></button>
</form>

<script>
  // Auto scroll to bottom of chat
  const chatWindow = document.getElementById('chatWindow');
  if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;
</script>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>