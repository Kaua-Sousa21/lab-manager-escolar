<?php

declare(strict_types=1);

namespace Models;

use Config\Database;

class Location
{
    public static function all(): array
    {
        return Database::getInstance()->getConnection()->query("SELECT *, TRIM(CONCAT_WS(' • ', unit_name, NULLIF(room,''), NULLIF(laboratory,''))) AS display_name FROM locations ORDER BY unit_name, room, laboratory")->fetchAll();
    }
    public static function find(int $id): ?array
    {
        $stmt=Database::getInstance()->getConnection()->prepare('SELECT * FROM locations WHERE id=?'); $stmt->execute([$id]); return $stmt->fetch() ?: null;
    }
    public static function create(array $data): int
    {
        $db=Database::getInstance()->getConnection(); $stmt=$db->prepare('INSERT INTO locations (unit_name, city, street, number, block_name, floor, room, laboratory, observations) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$data['unit_name'],$data['city'],$data['street']?:null,$data['number']?:null,$data['block_name']?:null,$data['floor']?:null,$data['room']?:null,$data['laboratory']?:null,$data['observations']?:null]); return (int)$db->lastInsertId();
    }
    public static function update(int $id, array $data): bool
    {
        $stmt=Database::getInstance()->getConnection()->prepare('UPDATE locations SET unit_name=?, city=?, street=?, number=?, block_name=?, floor=?, room=?, laboratory=?, observations=? WHERE id=?');
        return $stmt->execute([$data['unit_name'],$data['city'],$data['street']?:null,$data['number']?:null,$data['block_name']?:null,$data['floor']?:null,$data['room']?:null,$data['laboratory']?:null,$data['observations']?:null,$id]);
    }
    public static function delete(int $id): bool
    {
        $stmt=Database::getInstance()->getConnection()->prepare('DELETE FROM locations WHERE id=?'); return $stmt->execute([$id]);
    }
}
