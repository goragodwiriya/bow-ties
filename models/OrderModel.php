<?php
require_once 'Database.php';

class OrderModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createOrder($data) {
        $query = "INSERT INTO orders (
                    order_number, customer_name, customer_email, customer_phone,
                    customer_address, customer_city, customer_postal, customer_country,
                    items, shipping_method, shipping_cost, payment_method, payment_status,
                    subtotal, total, status, order_date
                  ) VALUES (
                    :order_number, :customer_name, :customer_email, :customer_phone,
                    :customer_address, :customer_city, :customer_postal, :customer_country,
                    :items, :shipping_method, :shipping_cost, :payment_method, :payment_status,
                    :subtotal, :total, :status, :order_date
                  )";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $orderNumber = htmlspecialchars(strip_tags($data['orderNumber']));
        $customerName = htmlspecialchars(strip_tags($data['customer']['name']));
        $customerEmail = htmlspecialchars(strip_tags($data['customer']['email']));
        $customerPhone = htmlspecialchars(strip_tags($data['customer']['phone']));
        $customerAddress = htmlspecialchars(strip_tags($data['customer']['address']));
        $customerCity = htmlspecialchars(strip_tags($data['customer']['city']));
        $customerPostal = htmlspecialchars(strip_tags($data['customer']['postal']));
        $customerCountry = htmlspecialchars(strip_tags($data['customer']['country']));
        $items = json_encode($data['items']);
        $shippingMethod = htmlspecialchars(strip_tags($data['shipping']['method']));
        $shippingCost = htmlspecialchars(strip_tags($data['shipping']['cost']));
        $paymentMethod = htmlspecialchars(strip_tags($data['payment']['method']));
        $paymentStatus = htmlspecialchars(strip_tags($data['payment']['status']));
        $subtotal = htmlspecialchars(strip_tags($data['subtotal']));
        $total = htmlspecialchars(strip_tags($data['total']));
        $status = htmlspecialchars(strip_tags($data['status']));
        $orderDate = htmlspecialchars(strip_tags($data['date']));

        // Bind data
        $stmt->bindParam(':order_number', $orderNumber);
        $stmt->bindParam(':customer_name', $customerName);
        $stmt->bindParam(':customer_email', $customerEmail);
        $stmt->bindParam(':customer_phone', $customerPhone);
        $stmt->bindParam(':customer_address', $customerAddress);
        $stmt->bindParam(':customer_city', $customerCity);
        $stmt->bindParam(':customer_postal', $customerPostal);
        $stmt->bindParam(':customer_country', $customerCountry);
        $stmt->bindParam(':items', $items);
        $stmt->bindParam(':shipping_method', $shippingMethod);
        $stmt->bindParam(':shipping_cost', $shippingCost);
        $stmt->bindParam(':payment_method', $paymentMethod);
        $stmt->bindParam(':payment_status', $paymentStatus);
        $stmt->bindParam(':subtotal', $subtotal);
        $stmt->bindParam(':total', $total);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':order_date', $orderDate);

        // Execute query
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    public function getOrderById($id) {
        $query = "SELECT * FROM orders WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Decode JSON fields
            $order['items'] = json_decode($order['items'], true);
        }

        return $order;
    }

    public function getOrderByNumber($orderNumber) {
        $query = "SELECT * FROM orders WHERE order_number = :order_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_number', $orderNumber);
        $stmt->execute();

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Decode JSON fields
            $order['items'] = json_decode($order['items'], true);
        }

        return $order;
    }

    public function getAllOrders($limit = 50, $offset = 0, $status = null) {
        $query = "SELECT * FROM orders";

        if ($status) {
            $query .= " WHERE status = :status";
        }

        $query .= " ORDER BY order_date DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        if ($status) {
            $stmt->bindParam(':status', $status);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON fields
        foreach ($orders as &$order) {
            $order['items'] = json_decode($order['items'], true);
        }

        return $orders;
    }

    public function updateOrderStatus($id, $status) {
        $query = "UPDATE orders SET status = :status WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $status = htmlspecialchars(strip_tags($status));

        // Bind data
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function updatePaymentStatus($id, $paymentStatus) {
        $query = "UPDATE orders SET payment_status = :payment_status WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $paymentStatus = htmlspecialchars(strip_tags($paymentStatus));

        // Bind data
        $stmt->bindParam(':payment_status', $paymentStatus);
        $stmt->bindParam(':id', $id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function searchOrders($keyword, $limit = 50, $offset = 0) {
        $query = "SELECT * FROM orders
                  WHERE order_number LIKE :keyword
                  OR customer_name LIKE :keyword
                  OR customer_email LIKE :keyword
                  ORDER BY order_date DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        $keyword = "%{$keyword}%";

        $stmt->bindParam(':keyword', $keyword);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON fields
        foreach ($orders as &$order) {
            $order['items'] = json_decode($order['items'], true);
        }

        return $orders;
    }

    public function getOrderCount($status = null) {
        $query = "SELECT COUNT(*) as count FROM orders";

        if ($status) {
            $query .= " WHERE status = :status";
        }

        $stmt = $this->conn->prepare($query);

        if ($status) {
            $stmt->bindParam(':status', $status);
        }

        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'];
    }

    public function getSalesData($startDate = null, $endDate = null) {
        $query = "SELECT
                    DATE(order_date) as date,
                    COUNT(*) as orders,
                    SUM(total) as revenue
                  FROM orders";

        if ($startDate && $endDate) {
            $query .= " WHERE order_date BETWEEN :start_date AND :end_date";
        }

        $query .= " GROUP BY DATE(order_date) ORDER BY date DESC";

        $stmt = $this->conn->prepare($query);

        if ($startDate && $endDate) {
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}