<?php
namespace Models;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(string $name, string $email, string $passwordHash): ?int
    {
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, verified) VALUES (?, ?, ?, 'user', 0)");
        $stmt->execute([$name, $email, $passwordHash]);
        return (int)$this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, card_last4, role, verified, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAllUsers(): array
    {
        return $this->db->query("SELECT id, name, email, phone, card_last4, role, verified, created_at FROM users ORDER BY id DESC")->fetchAll();
    }

    public function deleteUser(int $id): bool
    {
        return $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }

    public function setRole(int $id, string $role): bool
    {
        return $this->db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $id]);
    }

    public function verifyUser(int $id): bool
    {
        return $this->db->prepare("UPDATE users SET verified = 1 WHERE id = ?")->execute([$id]);
    }

    public function updatePhone(int $id, string $phone): bool
    {
        return $this->db->prepare("UPDATE users SET phone = ? WHERE id = ?")->execute([$phone, $id]);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        return $this->db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $id]);
    }

    public function updateCard(int $id, string $cardNumber): bool
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $last4 = strlen($cardNumber) >= 4 ? substr($cardNumber, -4) : '';
        return $this->db->prepare("UPDATE users SET card_last4 = ? WHERE id = ?")->execute([$last4, $id]);
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row && password_verify($password, $row['password']);
    }
}