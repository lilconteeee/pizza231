<?php
namespace Models;
use PDO;

class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function loadData(): array
    {
        $stmt = $this->db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id");
        $products = $stmt->fetchAll();
        foreach ($products as &$p) {
            if (is_string($p['features'])) $p['features'] = json_decode($p['features'], true) ?: [];
        }
        return $products;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if ($p && is_string($p['features'])) $p['features'] = json_decode($p['features'], true) ?: [];
        return $p ?: null;
    }

    public function getBasketData(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $basket = $_SESSION['basket'] ?? [];
        $result = [];
        foreach ($basket as $productId => $item) {
            $product = $this->getById((int)$productId);
            if ($product) {
                $result[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'] ?? 1,
                    'subtotal' => $product['price'] * ($item['quantity'] ?? 1)
                ];
            }
        }
        return $result;
    }

    public function updateProduct(array $data): bool
    {
        $features = json_encode($data['features'] ?? [], JSON_UNESCAPED_UNICODE);
        $stmt = $this->db->prepare("UPDATE products SET name=?, description=?, price=?, period=?, image=?, coverage=?, features=? WHERE id=?");
        return $stmt->execute([
            $data['name'], $data['description'], $data['price'],
            $data['period'], $data['image'], $data['coverage'],
            $features, $data['id']
        ]);
    }

    public function addProduct(array $data): bool
    {
        $features = json_encode($data['features'] ?? [], JSON_UNESCAPED_UNICODE);
        $stmt = $this->db->prepare("INSERT INTO products (name, description, price, period, image, coverage, features) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'], $data['description'], $data['price'],
            $data['period'], $data['image'], $data['coverage'], $features
        ]);
    }
}