<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM portfolio_items ORDER BY created_at DESC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group items by category if needed
    $grouped_items = [];
    foreach ($items as $item) {
        $category = $item['category'] ?? 'general';
        if (!isset($grouped_items[$category])) {
            $grouped_items[$category] = [];
        }
        $grouped_items[$category][] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $grouped_items
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch portfolio items'
    ]);
} 