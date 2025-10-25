<?php
require_once '../api/config.php';
require_once '../api/models/Auth.php';

$auth = new Auth();

// Return user info as JSON if requested
if (isset($_GET['json'])) {
    header('Content-Type: application/json');

    if (!$auth->isLoggedIn()) {
        echo json_encode(['authenticated' => false]);
        exit;
    }

    echo json_encode([
        'authenticated' => true,
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'csrfToken' => $auth->getCsrfToken()
    ]);
    exit;
}

// Check if user is logged in for HTML requests
if (!$auth->isLoggedIn()) {
    header('Location: login.html');
    exit;
}
