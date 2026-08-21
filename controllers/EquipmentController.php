<?php

declare(strict_types=1);
namespace Controllers;
use Models\AuditLog;use Models\Equipment;
class EquipmentController
{
    private const STATUSES=['available','reserved','borrowed','maintenance','inactive'];
    public static function index():array{return Equipment::all();}public static function show(int $id):?array{return Equipment::find($id);}
    private static function validate(array $data,?int $id=null):array{$name=trim((string)($data['name']??''));$code=trim((string)($data['patrimony_code']??''));if($name===''||$code==='')return ['success'=>false,'message'=>'Nome e código patrimonial são obrigatórios.'];if(Equipment::patrimonyExists($code,$id))return ['success'=>false,'message'=>'Este código patrimonial já está cadastrado.'];if(!in_array($data['status']??'available',self::STATUSES,true))return ['success'=>false,'message'=>'Status inválido.'];if(($data['purchase_value']??'')!==''&&!is_numeric($data['purchase_value']))return ['success'=>false,'message'=>'Valor de compra inválido.'];return ['success'=>true];}
    public static function store(array $data):array{$v=self::validate($data);if(!$v['success'])return $v;if(in_array($data['status']??'available',['borrowed','reserved'],true))return ['success'=>false,'message'=>'Os status Reservado e Emprestado são definidos automaticamente pelo módulo de empréstimos.'];$id=Equipment::create($data);AuditLog::record('create','equipment',$id,$data['name'].' / '.$data['patrimony_code']);return ['success'=>true,'equipment_id'=>$id];}
    public static function update(int $id,array $data):array{$current=Equipment::find($id);if(!$current)return ['success'=>false,'message'=>'Equipamento não encontrado.'];$v=self::validate($data,$id);if(!$v['success'])return $v;if(Equipment::hasOpenLoan($id)&&$data['status']!=$current['status'])return ['success'=>false,'message'=>'O status não pode ser alterado manualmente enquanto houver empréstimo aberto.'];if(!Equipment::hasOpenLoan($id)&&in_array($data['status'],['borrowed','reserved'],true))return ['success'=>false,'message'=>'Use o módulo de empréstimos para definir o equipamento como reservado ou emprestado.'];Equipment::update($id,$data);AuditLog::record('update','equipment',$id,$data['name']);return ['success'=>true];}
    public static function destroy(int $id):array{$eq=Equipment::find($id);if(!$eq)return ['success'=>false,'message'=>'Equipamento não encontrado.'];if(Equipment::hasOpenLoan($id))return ['success'=>false,'message'=>'Não é possível arquivar um equipamento emprestado.'];Equipment::archive($id);AuditLog::record('archive','equipment',$id,$eq['name']);return ['success'=>true];}
}
