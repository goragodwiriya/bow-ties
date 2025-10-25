<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../models/OrderModel.php';
require_once '../models/Notification.php';

 $orderModel = new OrderModel();
 $notification = new Notification();

// Get HTTP method
 $method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid JSON data."]);
        exit;
    }

    // Make sure data is not empty
    if (
        !empty($data->customer->name) &&
        !empty($data->customer->email) &&
        !empty($data->customer->phone) &&
        !empty($data->customer->address) &&
        !empty($data->customer->city) &&
        !empty($data->customer->postal) &&
        !empty($data->customer->country) &&
        !empty($data->items) &&
        !empty($data->shipping->method) &&
        !empty($data->payment->method)
    ) {
        // Generate order number
        $orderNumber = 'ORD' . date('Ymd') . rand(1000, 9999);

        // Sanitize customer data
        $customerName = htmlspecialchars(trim($data->customer->name), ENT_QUOTES, 'UTF-8');
        $customerEmail = filter_var(trim($data->customer->email), FILTER_SANITIZE_EMAIL);
        $customerPhone = htmlspecialchars(trim($data->customer->phone), ENT_QUOTES, 'UTF-8');
        $customerAddress = htmlspecialchars(trim($data->customer->address), ENT_QUOTES, 'UTF-8');
        $customerCity = htmlspecialchars(trim($data->customer->city), ENT_QUOTES, 'UTF-8');
        $customerPostal = htmlspecialchars(trim($data->customer->postal), ENT_QUOTES, 'UTF-8');
        $customerCountry = htmlspecialchars(trim($data->customer->country), ENT_QUOTES, 'UTF-8');

        // Validate email
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid email format."]);
            exit;
        }

        // Validate numeric values
        $subtotal = filter_var($data->subtotal, FILTER_VALIDATE_FLOAT);
        $total = filter_var($data->total, FILTER_VALIDATE_FLOAT);
        $shippingCost = filter_var($data->shipping->cost, FILTER_VALIDATE_FLOAT);

        if ($subtotal === false || $total === false || $shippingCost === false) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid numeric values."]);
            exit;
        }

        // Prepare order data
        $orderData = [
            'orderNumber' => $orderNumber,
            'customer' => [
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
                'address' => $customerAddress,
                'city' => $customerCity,
                'postal' => $customerPostal,
                'country' => $customerCountry
            ],
            'items' => $data->items,
            'shipping' => [
                'method' => htmlspecialchars($data->shipping->method, ENT_QUOTES, 'UTF-8'),
                'cost' => $shippingCost
            ],
            'payment' => [
                'method' => htmlspecialchars($data->payment->method, ENT_QUOTES, 'UTF-8'),
                'status' => 'pending'
            ],
            'subtotal' => $subtotal,
            'total' => $total,
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s')
        ];

        // Create the order
        $orderId = $orderModel->createOrder($orderData);

        if ($orderId) {
            // Get the complete order data
            $order = $orderModel->getOrderById($orderId);

            // Send Telegram notification
            $notification->sendNewOrderNotification($order);

            // Set response code
            http_response_code(201);

            // Tell the user
            echo json_encode([
                "success" => true,
                "message" => "Order was created.",
                "orderNumber" => $orderNumber,
                "orderId" => $orderId
            ]);
        } else {
            // Set response code
            http_response_code(503);

            // Tell the user
            echo json_encode(["success" => false, "message" => "Unable to create order."]);
        }
    } else {
        // Set response code
        http_response_code(400);

        // Tell the user
        echo json_encode(["success" => false, "message" => "Unable to create order. Data is incomplete."]);
    }
} else {
    // Set response code
    http_response_code(405);

    // Tell the user method not allowed
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
}