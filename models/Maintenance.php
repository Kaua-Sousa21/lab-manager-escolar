<?php

declare(strict_types=1);

namespace Models;

use Config\Database;

class Maintenance
{
    private static function selectBase(): string
    {
        return 'SELECT m.*, e.name AS equipment_name, e.patrimony_code, u.name AS technician_name FROM maintenance m JOIN equipment e ON e.id=m.equipment_id JOIN users u ON u.id=m.technician_id';
    }
    public static function all(): array { return Database::getInstance()->getConnection()->query(self::selectBase().' ORDER BY m.maintenance_date DESC, m.id DESC')->fetchAll(); }
    public static function find(int $id): ?array { $stmt=Database::getInstance()->getConnection()->prepare(self::selectBase().' WHERE m.id=?');$stmt->execute([$id]);return $stmt->fetch()?:null; }
    public static function create(array $data): int
    {
        $db=Database::getInstance()->getConnection();$db->beginTransaction();
        try{$stmt=$db->prepare('INSERT INTO maintenance (equipment_id, technician_id, type, maintenance_date, completion_date, description, cost, status, observations) VALUES (?,?,?,?,?,?,?,?,?)');$stmt->execute([(int)$data['equipment_id'],(int)$data['technician_id'],$data['type'],$data['maintenance_date'],$data['completion_date']?:null,$data['description'],$data['cost']!==''?$data['cost']:null,$data['status'],$data['observations']?:null]);$id=(int)$db->lastInsertId();if($data['status']!=='completed')Equipment::updateStatus((int)$data['equipment_id'],'maintenance');$db->commit();return $id;}catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
    public static function update(int $id,array $data): bool
    {
        $db=Database::getInstance()->getConnection();$db->beginTransaction();
        try{$stmt=$db->prepare('UPDATE maintenance SET technician_id=?, type=?, maintenance_date=?, completion_date=?, description=?, cost=?, status=?, observations=? WHERE id=?');$ok=$stmt->execute([(int)$data['technician_id'],$data['type'],$data['maintenance_date'],$data['completion_date']?:null,$data['description'],$data['cost']!==''?$data['cost']:null,$data['status'],$data['observations']?:null,$id]);$record=self::find($id);if($record){Equipment::updateStatus((int)$record['equipment_id'],$data['status']==='completed'?'available':'maintenance');}$db->commit();return $ok;}catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
    public static function hasOpenForEquipment(int $equipmentId, ?int $exceptId = null): bool
    {
        $sql="SELECT COUNT(*) FROM maintenance WHERE equipment_id=? AND status IN ('pending','in_progress')";$params=[$equipmentId];
        if($exceptId){$sql.=' AND id<>?';$params[]=$exceptId;}
        $stmt=Database::getInstance()->getConnection()->prepare($sql);$stmt->execute($params);return (int)$stmt->fetchColumn()>0;
    }
    public static function countInProgress(): int { return (int)Database::getInstance()->getConnection()->query("SELECT COUNT(*) FROM maintenance WHERE status IN ('pending','in_progress')")->fetchColumn(); }
}
