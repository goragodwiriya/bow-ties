<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../models/OrderModel.php';
require_once '../models/Notification.php';
require_once '../models/Auth.php';

$orderModel = new OrderModel();
$notification = new Notification();
$auth = new Auth();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Get order ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($method === 'GET') {
    $auth->requireLogin();

    if ($id) {
        // Get single order
        $order = $orderModel->getOrderById($id);

        if ($order) {
            // Set response code
            http_response_code(200);

            // Show order in JSON format
            echo json_encode($order);
        } else {
            // Set response code
            http_response_code(404);

            // Tell the user order does not exist
            echo json_encode(["message" => "Order does not exist."]);
        }
    } else {
        // Get all orders
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : null;

        $orders = $orderModel->getAllOrders($limit, $offset, $status);

        // Set response code
        http_response_code(200);

        // Show orders in JSON format
        echo json_encode($orders);
    }
} else if ($method === 'POST') {
    $payload = file_get_contents("php://input");
    $data = json_decode($payload);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid JSON payload."]);
        exit;
    }

    if ($id) {
        // Get the order
        $order = $orderModel->getOrderById($id);

        if (!$order) {
            // Set response code
            http_response_code(404);

            // Tell the user order does not exist
            echo json_encode(["success" => false, "message" => "Order does not exist."]);
            exit;
        }

        // Check action
        if (isset($data->action) && $data->action === 'confirm_payment') {
            $customerEmail = isset($data->email) ? trim($data->email) : '';

            if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || strcasecmp($customerEmail, $order['customer_email']) !== 0) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Verification failed."]);
                exit;
            }

            if ($order['payment_status'] === 'paid') {
                http_response_code(200);
                echo json_encode(["success" => true, "message" => "Payment already confirmed."]);
                exit;
            }

            $success = $orderModel->updatePaymentStatus($id, 'paid');
            if ($success) {
                $orderModel->updateOrderStatus($id, 'processing');
                $order['payment_status'] = 'paid';
                $order['status'] = 'processing';
                $notification->sendPaymentConfirmationNotification($order);

                http_response_code(200);
                echo json_encode(["success" => true, "message" => "Payment confirmed successfully."]);
            } else {
                http_response_code(503);
                echo json_encode(["success" => false, "message" => "Failed to confirm payment."]);
            }
        } else {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Action not permitted."]);
        }
    } else {
        // Set response code
        http_response_code(400);

        // Tell the user
        echo json_encode(["success" => false, "message" => "Order ID not provided."]);
    }
} else {
    // Set response code
    http_response_code(405);

    // Tell the user method not allowed
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
}
