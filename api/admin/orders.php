<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../models/OrderModel.php';
require_once '../models/Auth.php';

$orderModel = new OrderModel();
$auth = new Auth();

// Require authentication
$auth->requireLogin();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get query parameters
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;

    if ($search) {
        // Search orders
        $orders = $orderModel->searchOrders($search, $limit, $offset);
    } else {
        // Get all orders with optional status filter
        $orders = $orderModel->getAllOrders($limit, $offset, $status);
    }

    // Get total count
    $totalCount = $orderModel->getOrderCount($status);

    // Set response code
    http_response_code(200);

    // Show orders in JSON format
    echo json_encode([
        "orders" => $orders,
        "totalCount" => $totalCount,
        "limit" => $limit,
        "offset" => $offset
    ]);
} else {
    // Set response code
    http_response_code(405);

    // Tell the user method not allowed
    echo json_encode(["message" => "Method not allowed."]);
}
