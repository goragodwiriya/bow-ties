<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../models/ProductModel.php';

try {
    $productModel = new ProductModel();
} catch (Throwable $e) {
    error_log('Product API initialization failed: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Unable to access product data at this time.']);
    exit;
}

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        if (isset($_GET['category'])) {
            $category = $_GET['category'];
            $products = $productModel->getProductsByCategory($category);
        } else {
            $products = $productModel->getAllProducts();
        }

        http_response_code(200);
        echo json_encode($products);
    } catch (Throwable $e) {
        error_log('Product API query failed: '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Unable to load product data.']);
    }
} else {
    // Set response code
    http_response_code(405);

    // Tell the user method not allowed
    echo json_encode(["message" => "Method not allowed."]);
}
