<?php

declare(strict_types=1);

namespace Models;

use Config\Database;

class Equipment
{
    private static function baseSelect(): string
    {
        return "SELECT e.*, c.name AS category_name, TRIM(CONCAT_WS(' • ', l.unit_name, NULLIF(l.room,''), NULLIF(l.laboratory,''))) AS location_name FROM equipment e LEFT JOIN categories c ON c.id=e.category_id LEFT JOIN locations l ON l.id=e.location_id";
    }

    public static function all(): array
    {
        return Database::getInstance()->getConnection()->query(self::baseSelect() . ' ORDER BY e.name')->fetchAll();
    }

    public static function findAvailable(): array
    {
        return Database::getInstance()->getConnection()->query(self::baseSelect() . " WHERE e.status='available' ORDER BY e.name")->fetchAll();
    }

    public static function findSchedulable(): array
    {
        return Database::getInstance()->getConnection()->query(self::baseSelect() . " WHERE e.status NOT IN ('maintenance','inactive') ORDER BY e.name")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt=Database::getInstance()->getConnection()->prepare(self::baseSelect() . ' WHERE e.id=?'); $stmt->execute([$id]); return $stmt->fetch() ?: null;
    }

    public static function patrimonyExists(string $code, ?int $exceptId=null): bool
    {
        $sql='SELECT COUNT(*) FROM equipment WHERE patrimony_code=?'; $params=[$code]; if($exceptId){$sql.=' AND id<>?';$params[]=$exceptId;} $stmt=Database::getInstance()->getConnection()->prepare($sql);$stmt->execute($params);return (int)$stmt->fetchColumn()>0;
    }

    public static function create(array $data): int
    {
        $db=Database::getInstance()->getConnection();
        $stmt=$db->prepare('INSERT INTO equipment (name, patrimony_code, category_id, location_id, status, acquisition_date, warranty_until, purchase_value, description) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$data['name'],$data['patrimony_code'],$data['category_id']?:null,$data['location_id']?:null,$data['status']??'available',$data['acquisition_date']?:null,$data['warranty_until']?:null,$data['purchase_value']!==''?$data['purchase_value']:null,$data['description']?:null]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt=Database::getInstance()->getConnection()->prepare('UPDATE equipment SET name=?, patrimony_code=?, category_id=?, location_id=?, status=?, acquisition_date=?, warranty_until=?, purchase_value=?, description=? WHERE id=?');
        return $stmt->execute([$data['name'],$data['patrimony_code'],$data['category_id']?:null,$data['location_id']?:null,$data['status'],$data['acquisition_date']?:null,$data['warranty_until']?:null,$data['purchase_value']!==''?$data['purchase_value']:null,$data['description']?:null,$id]);
    }

    public static function updateStatus(int $id,string $status): bool
    {
        $stmt=Database::getInstance()->getConnection()->prepare('UPDATE equipment SET status=? WHERE id=?'); return $stmt->execute([$status,$id]);
    }

    public static function archive(int $id): bool
    {
        return self::updateStatus($id,'inactive');
    }

    public static function hasOpenLoan(int $id): bool
    {
        $stmt=Database::getInstance()->getConnection()->prepare("SELECT COUNT(*) FROM loans WHERE equipment_id=? AND status IN ('reserved','borrowed','overdue')");$stmt->execute([$id]);return (int)$stmt->fetchColumn()>0;
    }

    public static function count(): int { return (int)Database::getInstance()->getConnection()->query('SELECT COUNT(*) FROM equipment')->fetchColumn(); }
    public static function countByStatus(string $status): int { $stmt=Database::getInstance()->getConnection()->prepare('SELECT COUNT(*) FROM equipment WHERE status=?');$stmt->execute([$status]);return (int)$stmt->fetchColumn(); }
}
