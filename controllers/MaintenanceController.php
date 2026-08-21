<?php

declare(strict_types=1);
namespace Controllers;
use Models\AuditLog;use Models\Equipment;use Models\Maintenance;use Models\User;
class MaintenanceController
{
    public static function index():array{return Maintenance::all();}public static function show(int $id):?array{return Maintenance::find($id);}private static function validate(array $d):array{if(empty($d['equipment_id'])||empty($d['technician_id'])||empty($d['maintenance_date'])||trim((string)($d['description']??''))==='')return ['success'=>false,'message'=>'Equipamento, responsável, data e descrição são obrigatórios.'];if(!in_array($d['type']??'', ['preventive','corrective'],true)||!in_array($d['status']??'', ['pending','in_progress','completed'],true))return ['success'=>false,'message'=>'Tipo ou status inválido.'];if(!Equipment::find((int)$d['equipment_id'])||!User::find((int)$d['technician_id']))return ['success'=>false,'message'=>'Equipamento ou responsável inválido.'];return ['success'=>true];}
    public static function store(array $data):array{$v=self::validate($data);if(!$v['success'])return $v;if(Equipment::hasOpenLoan((int)$data['equipment_id']))return ['success'=>false,'message'=>'Registre a devolução do equipamento antes de enviá-lo para manutenção.'];if(Maintenance::hasOpenForEquipment((int)$data['equipment_id']))return ['success'=>false,'message'=>'Já existe uma manutenção em aberto para este equipamento.'];$id=Maintenance::create($data);AuditLog::record('create','maintenance',$id,'Manutenção registrada');return ['success'=>true,'maintenance_id'=>$id];}
    public static function update(int $id,array $data):array{if(!Maintenance::find($id))return ['success'=>false,'message'=>'Manutenção não encontrada.'];$v=self::validate($data);if(!$v['success'])return $v;if(Maintenance::hasOpenForEquipment((int)$data['equipment_id'],$id))return ['success'=>false,'message'=>'Já existe outra manutenção em aberto para este equipamento.'];Maintenance::update($id,$data);AuditLog::record('update','maintenance',$id,'Manutenção atualizada');return ['success'=>true];}
}
