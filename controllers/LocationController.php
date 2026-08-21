<?php

declare(strict_types=1);
namespace Controllers;
use Models\AuditLog;use Models\Location;
class LocationController
{
    public static function index():array{return Location::all();}public static function show(int $id):?array{return Location::find($id);}private static function valid(array $d):array{if(trim((string)($d['unit_name']??''))===''||trim((string)($d['city']??''))==='')return ['success'=>false,'message'=>'Nome da unidade e cidade são obrigatórios.'];return ['success'=>true];}public static function store(array $data):array{$v=self::valid($data);if(!$v['success'])return $v;$id=Location::create($data);AuditLog::record('create','location',$id,$data['unit_name']);return ['success'=>true,'location_id'=>$id];}public static function update(int $id,array $data):array{if(!Location::find($id))return ['success'=>false,'message'=>'Ambiente não encontrado.'];$v=self::valid($data);if(!$v['success'])return $v;Location::update($id,$data);AuditLog::record('update','location',$id,$data['unit_name']);return ['success'=>true];}public static function destroy(int $id):array{if(!Location::find($id))return ['success'=>false,'message'=>'Ambiente não encontrado.'];Location::delete($id);AuditLog::record('delete','location',$id);return ['success'=>true];}
}
