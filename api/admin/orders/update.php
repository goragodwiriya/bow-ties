<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__.'/../../models/OrderModel.php';
require_once __DIR__.'/../../models/Auth.php';

$orderModel = new OrderModel();
$auth = new Auth();

// Require authentication
$auth->requireLogin();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $auth->validateCsrfToken();

    // Get posted data
    $payload = file_get_contents("php://input");
    $data = json_decode($payload);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON payload."
        ]);
        exit;
    }

    // Make sure data is not empty
    if (
        !empty($data->id) &&
        !empty($data->status)
    ) {
        $orderId = (int) $data->id;
        $status = strtolower(trim($data->status));
        $allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];

        if (!in_array($status, $allowedStatuses, true)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid order status value."
            ]);
            exit;
        }

        // Update order status
        if ($orderModel->updateOrderStatus($orderId, $status)) {
            // Set response code
            http_response_code(200);

            // Tell the user
            echo json_encode([
                "success" => true,
                "message" => "Order status updated successfully."
            ]);
        } else {
            // Set response code
            http_response_code(503);

            // Tell the user
            echo json_encode([
                "success" => false,
                "message" => "Unable to update order status."
            ]);
        }
    } else {
        // Set response code
        http_response_code(400);

        // Tell the user
        echo json_encode([
            "success" => false,
            "message" => "Unable to update order status. Data is incomplete."
        ]);
    }
} else {
    // Set response code
    http_response_code(405);

    // Tell the user method not allowed
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);
}
