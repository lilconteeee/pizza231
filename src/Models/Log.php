<?php
namespace Models;
use PDO;

class Log
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function add(string $action, string $user = 'Система', array $details = []): bool
    {
        $stmt = $this->db->prepare("INSERT INTO logs (user, action, ip, details) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user, $action, $_SERVER['REMOTE_ADDR'] ?? 'unknown', json_encode($details, JSON_UNESCAPED_UNICODE)]);
    }

    public function getAll(int $limit = 100): array
    {
        $stmt = $this->db->prepare("SELECT * FROM logs ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();
        foreach ($logs as &$l) {
            if (is_string($l['details'])) $l['details'] = json_decode($l['details'], true) ?: [];
        }
        return $logs;
    }

    public function filter(string $action = '', string $user = '', string $dateFrom = '', int $limit = 100): array
    {
        $sql = "SELECT * FROM logs WHERE 1=1";
        $params = [];
        if ($action) { $sql .= " AND action LIKE ?"; $params[] = "%$action%"; }
        if ($user) { $sql .= " AND user LIKE ?"; $params[] = "%$user%"; }
        if ($dateFrom) { $sql .= " AND created_at >= ?"; $params[] = $dateFrom; }
        $sql .= " ORDER BY id DESC LIMIT $limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        foreach ($logs as &$l) {
            if (is_string($l['details'])) $l['details'] = json_decode($l['details'], true) ?: [];
        }
        return $logs;
    }

    public function getStats(): array
    {
        $logs = $this->getAll(1000);
        $stats = ['total' => count($logs), 'today' => 0, 'by_action' => [], 'by_user' => []];
        foreach ($logs as $log) {
            if (date('Y-m-d', strtotime($log['created_at'])) === date('Y-m-d')) $stats['today']++;
            $action = explode(' ', $log['action'])[0];
            $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;
            $stats['by_user'][$log['user']] = ($stats['by_user'][$log['user']] ?? 0) + 1;
        }
        arsort($stats['by_action']);
        arsort($stats['by_user']);
        return $stats;
    }

    public function clearOld(int $days = 30): int
    {
        $stmt = $this->db->prepare("DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    public function clearAll(): bool
    {
        return $this->db->exec("TRUNCATE TABLE logs") !== false;
    }
}