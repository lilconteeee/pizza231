<?php
namespace Models;
use PDO;

class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function saveOrder(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO orders (order_id, user_id, user_email, fio, phone, delivery_type, email, address, products, total, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
        ");
        return $stmt->execute([
            $data['order_id'],
            $data['user_id'] ?? 0,
            $data['user_email'] ?? '',
            $data['fio'],
            $data['phone'],
            $data['delivery_type'] ?? 'email',
            $data['email'] ?? '',
            $data['address'] ?? '',
            json_encode($data['products'], JSON_UNESCAPED_UNICODE),
            $data['total']
        ]);
    }

    public function getUserOrders(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$o) {
            if (is_string($o['products'])) $o['products'] = json_decode($o['products'], true) ?: [];
        }
        return $orders;
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
    }
}