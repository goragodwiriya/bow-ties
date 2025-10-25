<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../models/Auth.php';

$auth = new Auth();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
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
        !empty($data->username) &&
        !empty($data->password)
    ) {
        // Attempt to login
        if ($auth->login($data->username, $data->password)) {
            // Set response code
            http_response_code(200);

            // Tell the user
            echo json_encode([
                "success" => true,
                "message" => "Login successful.",
                "csrfToken" => $auth->getCsrfToken()
            ]);
        } else {
            // Set response code
            http_response_code(401);

            // Tell the user
            echo json_encode([
                "success" => false,
                "message" => "Invalid username or password."
            ]);
        }
    } else {
        // Set response code
        http_response_code(400);

        // Tell the user
        echo json_encode([
            "success" => false,
            "message" => "Unable to login. Data is incomplete."
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
