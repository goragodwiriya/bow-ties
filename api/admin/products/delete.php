<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../models/Auth.php';
require_once __DIR__.'/../../models/ProductModel.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$auth->validateCsrfToken();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product ID']);
    exit;
}

$productModel = new ProductModel();
$success = $productModel->deleteProduct($id);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete product']);
}
