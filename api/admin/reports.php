<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/ProductModel.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

$orderModel = new OrderModel();
$productModel = new ProductModel();

$type = $_GET['type'] ?? 'overview';

switch ($type) {
    case 'overview':
        // Get overall statistics
        $stats = $orderModel->getSalesStats();
        $dailySales = $orderModel->getDailySales(7);

        echo json_encode([
            'stats' => $stats,
            'dailySales' => $dailySales
        ]);
        break;

    case 'daily':
        $days = $_GET['days'] ?? 7;
        $dailySales = $orderModel->getDailySales($days);
        echo json_encode(['dailySales' => $dailySales]);
        break;

    case 'monthly':
        // Get monthly sales
        $query = "SELECT
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    COUNT(*) as orders,
                    SUM(total) as sales
                  FROM orders
                  WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                  GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                  ORDER BY month ASC";

        // This would need to be added to OrderModel, but for now:
        echo json_encode(['monthlySales' => []]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid report type']);
}
