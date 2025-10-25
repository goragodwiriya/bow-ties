<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../../models/OrderModel.php';
require_once '../../models/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

$orderModel = new OrderModel();
$auth = new Auth();

// Require authentication
$auth->requireLogin();
$auth->validateCsrfToken();

// Get order ID from URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Order ID not provided."]);
    exit;
}

// Delete the order
if ($orderModel->deleteOrder($id)) {
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Order deleted successfully."]);
} else {
    http_response_code(503);
    echo json_encode(["success" => false, "message" => "Unable to delete order."]);
}
