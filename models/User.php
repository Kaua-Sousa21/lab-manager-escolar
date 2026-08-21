<?php

declare(strict_types=1);

namespace Models;

use Config\Database;

class User
{
    public static function all(): array
    {
        return Database::getInstance()->getConnection()->query('SELECT id, name, email, role, status, created_at, updated_at FROM users ORDER BY name')->fetchAll();
    }

    public static function activeStaff(): array
    {
        return Database::getInstance()->getConnection()->query("SELECT id, name, email, role FROM users WHERE status='active' ORDER BY name")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->getConnection()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::getInstance()->getConnection()->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(?)';
        $params = [$email];
        if ($exceptId) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['name'],
            strtolower($data['email']),
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'teacher',
            $data['status'] ?? 'active',
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = ['name = ?', 'email = ?', 'role = ?', 'status = ?'];
        $values = [$data['name'], strtolower($data['email']), $data['role'], $data['status']];
        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $values[] = $id;
        $stmt = Database::getInstance()->getConnection()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public static function setStatus(int $id, string $status): bool
    {
        $stmt = Database::getInstance()->getConnection()->prepare('UPDATE users SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    public static function count(): int
    {
        return (int) Database::getInstance()->getConnection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function countActive(): int
    {
        return (int) Database::getInstance()->getConnection()->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
    }
}
