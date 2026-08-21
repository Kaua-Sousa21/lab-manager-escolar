<?php

declare(strict_types=1);
namespace Controllers;
use Models\AuditLog;use Models\Category;
class CategoryController
{
    public static function index():array{return Category::all();}public static function show(int $id):?array{return Category::find($id);}public static function store(array $data):array{$name=trim((string)($data['name']??''));if($name==='')return ['success'=>false,'message'=>'Nome da categoria é obrigatório.'];try{$id=Category::create(['name'=>$name,'description'=>trim((string)($data['description']??''))]);AuditLog::record('create','category',$id,$name);return ['success'=>true,'category_id'=>$id];}catch(\Throwable){return ['success'=>false,'message'=>'Já existe uma categoria com esse nome ou os dados são inválidos.'];}}public static function update(int $id,array $data):array{if(!Category::find($id))return ['success'=>false,'message'=>'Categoria não encontrada.'];$name=trim((string)($data['name']??''));if($name==='')return ['success'=>false,'message'=>'Nome é obrigatório.'];try{Category::update($id,['name'=>$name,'description'=>trim((string)($data['description']??''))]);AuditLog::record('update','category',$id,$name);return ['success'=>true];}catch(\Throwable){return ['success'=>false,'message'=>'Não foi possível atualizar a categoria.'];}}public static function destroy(int $id):array{if(!Category::find($id))return ['success'=>false,'message'=>'Categoria não encontrada.'];Category::delete($id);AuditLog::record('delete','category',$id);return ['success'=>true];}
}
