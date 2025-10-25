<?php
require_once __DIR__ . '/../config.php';

class OrderModel {
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create new order
     */
    public function createOrder($orderData) {
        try {
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

            $stmt->bindParam(':order_number', $orderData['orderNumber']);
            $stmt->bindParam(':customer_name', $orderData['customer']['name']);
            $stmt->bindParam(':customer_email', $orderData['customer']['email']);
            $stmt->bindParam(':customer_phone', $orderData['customer']['phone']);
            $stmt->bindParam(':customer_address', $orderData['customer']['address']);
            $stmt->bindParam(':customer_city', $orderData['customer']['city']);
            $stmt->bindParam(':customer_postal', $orderData['customer']['postal']);
            $stmt->bindParam(':customer_country', $orderData['customer']['country']);

            $items = json_encode($orderData['items']);
            $stmt->bindParam(':items', $items);

            $stmt->bindParam(':shipping_method', $orderData['shipping']['method']);
            $stmt->bindParam(':shipping_cost', $orderData['shipping']['cost']);
            $stmt->bindParam(':payment_method', $orderData['payment']['method']);
            $stmt->bindParam(':payment_status', $orderData['payment']['status']);
            $stmt->bindParam(':subtotal', $orderData['subtotal']);
            $stmt->bindParam(':total', $orderData['total']);
            $stmt->bindParam(':status', $orderData['status']);
            $stmt->bindParam(':order_date', $orderData['date']);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            return false;
        } catch(PDOException $e) {
            error_log("Create order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order by ID
     */
    public function getOrderById($id) {
        try {
            $query = "SELECT * FROM orders WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all orders
     */
    public function getAllOrders($limit = 50, $offset = 0, $status = null) {
        try {
            if ($status) {
                $query = "SELECT * FROM orders WHERE status = :status ORDER BY order_date DESC LIMIT :limit OFFSET :offset";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $status);
            } else {
                $query = "SELECT * FROM orders ORDER BY order_date DESC LIMIT :limit OFFSET :offset";
                $stmt = $this->conn->prepare($query);
            }

            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get orders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $status) {
        try {
            $query = "UPDATE orders SET payment_status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update payment status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($id, $status) {
        try {
            $query = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update order status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order count
     */
    public function getOrderCount($status = null) {
        try {
            if ($status) {
                $query = "SELECT COUNT(*) as count FROM orders WHERE status = :status";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $status);
            } else {
                $query = "SELECT COUNT(*) as count FROM orders";
                $stmt = $this->conn->prepare($query);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'];
        } catch(PDOException $e) {
            error_log("Get order count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Search orders
     */
    public function searchOrders($search, $limit = 50, $offset = 0) {
        try {
            $searchTerm = "%{$search}%";
            $query = "SELECT * FROM orders
                      WHERE order_number LIKE :search
                      OR customer_name LIKE :search
                      OR customer_phone LIKE :search
                      OR customer_email LIKE :search
                      ORDER BY order_date DESC
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':search', $searchTerm);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Search orders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete order
     */
    public function deleteOrder($id) {
        try {
            $query = "DELETE FROM orders WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Delete order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get sales statistics
     */
    public function getSalesStats($startDate = null, $endDate = null) {
        try {
            $query = "SELECT
                        COUNT(*) as total_orders,
                        SUM(total) as total_sales,
                        AVG(total) as avg_order_value,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
                      FROM orders";

            if ($startDate && $endDate) {
                $query .= " WHERE order_date BETWEEN :start_date AND :end_date";
            }

            $stmt = $this->conn->prepare($query);

            if ($startDate && $endDate) {
                $stmt->bindParam(':start_date', $startDate);
                $stmt->bindParam(':end_date', $endDate);
            }

            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get sales stats error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get daily sales for chart
     */
    public function getDailySales($days = 7) {
        try {
            $query = "SELECT
                        DATE(order_date) as date,
                        COUNT(*) as orders,
                        SUM(total) as sales
                      FROM orders
                      WHERE order_date >= DATE_SUB(NOW(), INTERVAL :days DAY)
                      GROUP BY DATE(order_date)
                      ORDER BY date ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get daily sales error: " . $e->getMessage());
            return [];
        }
    }
}
