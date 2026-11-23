<?php
// Run once from the browser: http://localhost/crossrent/database/seed.php
require_once __DIR__ . '/../config/database.php';

$pdo = db();

$users = [
    ['admin@hcdc.edu.ph', 'admin123', 'CrossRent Admin', 'admin'],
    ['owner@hcdc.edu.ph', 'owner123', 'Juan Dela Cruz', 'user'],
    ['renter@hcdc.edu.ph', 'renter123', 'Maria Santos', 'user'],
];

foreach ($users as $u) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute([':email' => $u[0]]);
    if (!$stmt->fetch()) {
        $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (:e, :h, :n, :r)')
            ->execute([':e' => $u[0], ':h' => password_hash($u[1], PASSWORD_DEFAULT), ':n' => $u[2], ':r' => $u[3]]);
        echo "Created user {$u[0]}<br>";
    }
}

// sample items for owner
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = "owner@hcdc.edu.ph"');
$stmt->execute();
$ownerId = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT id, slug FROM categories');
$stmt->execute();
$cats = [];
foreach ($stmt->fetchAll() as $c) { $cats[$c['slug']] = $c['id']; }

$items = [
    ['Scientific Calculator', 'electronics', 'A Casio fx-991EX scientific calculator, perfect for engineering students.', 50, 'Day', 'Available'],
    ['Engineering Math Book', 'books', 'Stewart Calculus 8th edition, lightly used.', 30, 'Week', 'Available'],
    ['Study Lamp', 'others', 'LED desk lamp with adjustable brightness.', 25, 'Day', 'Available'],
];

foreach ($items as $it) {
    $stmt = $pdo->prepare('SELECT id FROM items WHERE name = :n');
    $stmt->execute([':n' => $it[0]]);
    if (!$stmt->fetch()) {
        $pdo->prepare('INSERT INTO items (owner_id, category_id, name, description, price, rental_period, status)
                       VALUES (:o, :c, :n, :d, :p, :rp, :s)')
            ->execute([':o' => $ownerId, ':c' => $cats[$it[1]], ':n' => $it[0], ':d' => $it[2], ':p' => $it[3], ':rp' => $it[4], ':s' => $it[5]]);
        echo "Created item {$it[0]}<br>";
    }
}

echo "<br>Seed complete. You can delete this file.";