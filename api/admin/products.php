<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../models/Auth.php';
require_once __DIR__.'/../models/ProductModel.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

$productModel = new ProductModel();

// GET - Get all products
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $products = $productModel->getAllProducts();
    echo json_encode(['products' => $products]);
    exit;
}

// POST - Create product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->validateCsrfToken();

    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit;
    }

    if (!$data || !isset($data['name']) || !isset($data['price'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $data['price'] = filter_var($data['price'], FILTER_VALIDATE_FLOAT);

    if ($data['price'] === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid price value']);
        exit;
    }

    if (isset($data['stock'])) {
        $data['stock'] = filter_var($data['stock'], FILTER_VALIDATE_INT);
        if ($data['stock'] === false || $data['stock'] < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid stock value']);
            exit;
        }
    } else {
        $data['stock'] = 0;
    }

    $productId = $productModel->createProduct($data);

    if ($productId) {
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'productId' => $productId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create product']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
