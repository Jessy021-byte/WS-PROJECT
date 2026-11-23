<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$pdo = db();

$sql = 'SELECT t.id, i.name AS item_name, o.email AS owner_email, r.email AS renter_email, 
               t.status, t.rent_period, t.rent_price, t.created_at
        FROM transactions t
        JOIN items i ON i.id = t.item_id
        JOIN users o ON o.id = t.owner_id
        JOIN users r ON r.id = t.renter_id
        ORDER BY t.created_at DESC';
$transactions = $pdo->query($sql)->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="CrossRent_Transactions_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Add column headers
fputcsv($output, ['Transaction ID', 'Item Name', 'Owner Email', 'Renter Email', 'Status', 'Rent Period', 'Price (PHP)', 'Date Requested']);

// Add data rows
foreach ($transactions as $row) {
    fputcsv($output, [
        $row['id'],
        $row['item_name'],
        $row['owner_email'],
        $row['renter_email'],
        ucfirst($row['status']),
        $row['rent_period'],
        number_format($row['rent_price'], 2),
        // FIXED: Using standard Y-m-d H:i:s format so Excel doesn't show #######
        date('Y-m-d H:i:s', strtotime($row['created_at'])) 
    ]);
}

fclose($output);
exit;