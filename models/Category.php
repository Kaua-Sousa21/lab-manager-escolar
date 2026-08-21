<?php

declare(strict_types=1);

namespace Models;

use Config\Database;

class Category
{
    public static function all(): array
    {
        return Database::getInstance()->getConnection()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }
    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->getConnection()->prepare('SELECT * FROM categories WHERE id=?'); $stmt->execute([$id]); return $stmt->fetch() ?: null;
    }
    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection(); $stmt=$db->prepare('INSERT INTO categories (name, description) VALUES (?,?)'); $stmt->execute([$data['name'], $data['description'] ?: null]); return (int)$db->lastInsertId();
    }
    public static function update(int $id, array $data): bool
    {
        $stmt=Database::getInstance()->getConnection()->prepare('UPDATE categories SET name=?, description=? WHERE id=?'); return $stmt->execute([$data['name'], $data['description'] ?: null, $id]);
    }
    public static function delete(int $id): bool
    {
        $stmt=Database::getInstance()->getConnection()->prepare('DELETE FROM categories WHERE id=?'); return $stmt->execute([$id]);
    }
}
