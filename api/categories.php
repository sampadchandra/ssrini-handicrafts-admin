<?php

/**
 * Ssrini Handicrafts
 * Categories API
 *
 * Returns active categories from the database.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            slug,
            description
        FROM categories
        WHERE status = 'active'
        ORDER BY name ASC
    ");

    $stmt->execute();

    $categories = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load categories.'
    ]);
}