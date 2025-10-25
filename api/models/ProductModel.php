<?php
require_once __DIR__.'/../config.php';

class ProductModel
{
    /**
     * @var mixed
     */
    private $conn;

    public function __construct()
    {
        try {
            $this->conn = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Connection error: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all products
     */
    public function getAllProducts()
    {
        try {
            $query = "SELECT * FROM products ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get products error: ".$e->getMessage());
            return [];
        }
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory($category)
    {
        try {
            $query = "SELECT * FROM products WHERE category = :category ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category', $category);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get products by category error: ".$e->getMessage());
            return [];
        }
    }

    /**
     * Get product by ID
     */
    public function getProductById($id)
    {
        try {
            $query = "SELECT * FROM products WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get product error: ".$e->getMessage());
            return false;
        }
    }

    /**
     * Create product
     */
    public function createProduct($data)
    {
        try {
            $query = "INSERT INTO products (name, description, price, category, image, stock, created_at)
                      VALUES (:name, :description, :price, :category, :image, :stock, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':category', $data['category']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':stock', $data['stock']);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create product error: ".$e->getMessage());
            return false;
        }
    }

    /**
     * Update product
     */
    public function updateProduct($id, $data)
    {
        try {
            $query = "UPDATE products SET
                      name = :name,
                      description = :description,
                      price = :price,
                      category = :category,
                      image = :image,
                      stock = :stock
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':category', $data['category']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':stock', $data['stock']);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update product error: ".$e->getMessage());
            return false;
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct($id)
    {
        try {
            $query = "DELETE FROM products WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete product error: ".$e->getMessage());
            return false;
        }
    }
}
