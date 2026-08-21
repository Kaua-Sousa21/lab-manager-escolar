<?php

declare(strict_types=1);

namespace Models;

use Config\Database;

class Student
{
    public static function all(): array
    {
        return Database::getInstance()->getConnection()->query('SELECT * FROM students ORDER BY name')->fetchAll();
    }

    public static function active(): array
    {
        return Database::getInstance()->getConnection()->query("SELECT * FROM students WHERE status='active' ORDER BY grade, class_name, name")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->getConnection()->prepare('SELECT * FROM students WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function registrationExists(string $registration, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM students WHERE registration = ?';
        $params = [$registration];
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
        $stmt = $db->prepare('INSERT INTO students (name, registration, grade, class_name, birth_date, guardian_name, guardian_phone, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['name'], $data['registration'], $data['grade'], $data['class_name'],
            $data['birth_date'] ?: null, $data['guardian_name'] ?: null, $data['guardian_phone'] ?: null,
            $data['status'] ?? 'active', $data['notes'] ?: null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::getInstance()->getConnection()->prepare('UPDATE students SET name=?, registration=?, grade=?, class_name=?, birth_date=?, guardian_name=?, guardian_phone=?, status=?, notes=? WHERE id=?');
        return $stmt->execute([
            $data['name'], $data['registration'], $data['grade'], $data['class_name'],
            $data['birth_date'] ?: null, $data['guardian_name'] ?: null, $data['guardian_phone'] ?: null,
            $data['status'], $data['notes'] ?: null, $id,
        ]);
    }

    public static function archive(int $id): bool
    {
        $stmt = Database::getInstance()->getConnection()->prepare("UPDATE students SET status='inactive' WHERE id=?");
        return $stmt->execute([$id]);
    }

    public static function countActive(): int
    {
        return (int) Database::getInstance()->getConnection()->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
    }

    public static function hasOpenLoans(int $id): bool
    {
        $stmt = Database::getInstance()->getConnection()->prepare("SELECT COUNT(*) FROM loans WHERE student_id=? AND status IN ('reserved','borrowed','overdue')");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
