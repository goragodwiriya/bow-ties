<?php
require_once __DIR__.'/Database.php';

class ProductModel
{
    /** @var PDO */
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();

        if (!$this->conn instanceof PDO) {
            throw new RuntimeException('Unable to establish database connection for ProductModel');
        }
    }

    /**
     * @return mixed
     */
    public function getAllProducts()
    {
        $query = "SELECT * FROM products ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getProductById($id)
    {
        $query = "SELECT * FROM products WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param $category
     * @return mixed
     */
    public function getProductsByCategory($category)
    {
        $query = "SELECT * FROM products WHERE category = :category ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function createProduct($data)
    {
        $query = "INSERT INTO products (name, price, category, description, image, images, details)
                  VALUES (:name, :price, :category, :description, :image, :images, :details)";

        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($data['name']));
        $price = htmlspecialchars(strip_tags($data['price']));
        $category = htmlspecialchars(strip_tags($data['category']));
        $description = htmlspecialchars(strip_tags($data['description']));
        $image = htmlspecialchars(strip_tags($data['image']));
        $images = htmlspecialchars(strip_tags($data['images']));
        $details = htmlspecialchars(strip_tags($data['details']));

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':images', $images);
        $stmt->bindParam(':details', $details);

        return $stmt->execute();
    }

    /**
     * @param $id
     * @param $data
     * @return mixed
     */
    public function updateProduct($id, $data)
    {
        $query = "UPDATE products
                  SET name = :name, price = :price, category = :category, description = :description,
                      image = :image, images = :images, details = :details
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($data['name']));
        $price = htmlspecialchars(strip_tags($data['price']));
        $category = htmlspecialchars(strip_tags($data['category']));
        $description = htmlspecialchars(strip_tags($data['description']));
        $image = htmlspecialchars(strip_tags($data['image']));
        $images = htmlspecialchars(strip_tags($data['images']));
        $details = htmlspecialchars(strip_tags($data['details']));

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':images', $images);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function deleteProduct($id)
    {
        $query = "DELETE FROM products WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
