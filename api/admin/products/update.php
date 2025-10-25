<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../models/Auth.php';
require_once __DIR__.'/../../models/ProductModel.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$auth->validateCsrfToken();

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product ID']);
    exit;
}

$data['id'] = filter_var($data['id'], FILTER_VALIDATE_INT);

if ($data['id'] === false || $data['id'] <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

if (isset($data['price'])) {
    $data['price'] = filter_var($data['price'], FILTER_VALIDATE_FLOAT);
    if ($data['price'] === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid price value']);
        exit;
    }
}

if (isset($data['stock'])) {
    $data['stock'] = filter_var($data['stock'], FILTER_VALIDATE_INT);
    if ($data['stock'] === false || $data['stock'] < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid stock value']);
        exit;
    }
}

$productModel = new ProductModel();
$success = $productModel->updateProduct($data['id'], $data);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update product']);
}
