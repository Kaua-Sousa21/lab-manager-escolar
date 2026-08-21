<?php

declare(strict_types=1);

namespace Models;

use Config\Database;

class AuditLog
{
    public static function record(string $action, string $entity, ?int $entityId = null, ?string $details = null): void
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $action,
                $entity,
                $entityId,
                $details ? mb_substr($details, 0, 500) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('[LabManager] Audit log failed: ' . $e->getMessage());
        }
    }

    public static function latest(int $limit = 8): array
    {
        $db = Database::getInstance()->getConnection();
        $limit = max(1, min(30, $limit));
        $stmt = $db->query("SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT {$limit}");
        return $stmt->fetchAll();
    }
}
