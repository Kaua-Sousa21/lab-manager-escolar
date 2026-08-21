<?php

declare(strict_types=1);

namespace Controllers;

use Models\AuditLog;
use Models\User;

class UserController
{
    private const ROLES=['admin','coordinator','technician','teacher','common'];
    private const STATUSES=['active','inactive'];
    public static function index(): array { return User::all(); }
    public static function show(int $id): ?array { return User::find($id); }
    public static function store(array $data): array
    {
        $name=trim((string)($data['name']??''));$email=trim((string)($data['email']??''));$password=(string)($data['password']??'');$role=$data['role']??'teacher';$status=$data['status']??'active';
        if(mb_strlen($name)<3) return ['success'=>false,'message'=>'Informe o nome completo do usuário.'];
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) return ['success'=>false,'message'=>'Informe um e-mail válido.'];
        if(strlen($password)<8) return ['success'=>false,'message'=>'A senha deve ter pelo menos 8 caracteres.'];
        if(!in_array($role,self::ROLES,true)||!in_array($status,self::STATUSES,true)) return ['success'=>false,'message'=>'Perfil ou status inválido.'];
        if(User::emailExists($email)) return ['success'=>false,'message'=>'Este e-mail já está cadastrado.'];
        $id=User::create(['name'=>$name,'email'=>$email,'password'=>$password,'role'=>$role,'status'=>$status]);AuditLog::record('create','user',$id,'Usuário criado: '.$name);return ['success'=>true,'user_id'=>$id];
    }
    public static function update(int $id,array $data): array
    {
        $user=User::find($id);if(!$user)return ['success'=>false,'message'=>'Usuário não encontrado.'];
        $name=trim((string)($data['name']??''));$email=trim((string)($data['email']??''));$role=$data['role']??'';$status=$data['status']??'';
        if(mb_strlen($name)<3||!filter_var($email,FILTER_VALIDATE_EMAIL))return ['success'=>false,'message'=>'Revise o nome e o e-mail.'];
        if(!in_array($role,self::ROLES,true)||!in_array($status,self::STATUSES,true))return ['success'=>false,'message'=>'Perfil ou status inválido.'];
        if(User::emailExists($email,$id))return ['success'=>false,'message'=>'Este e-mail já está em uso.'];
        if($id===(int)($_SESSION['user_id']??0)&&($status!=='active'||$role!=='admin'))return ['success'=>false,'message'=>'Você não pode desativar ou remover o próprio acesso administrativo.'];
        if(!empty($data['password'])&&strlen((string)$data['password'])<8)return ['success'=>false,'message'=>'A nova senha deve ter pelo menos 8 caracteres.'];
        User::update($id,['name'=>$name,'email'=>$email,'role'=>$role,'status'=>$status,'password'=>$data['password']??'']);AuditLog::record('update','user',$id,'Usuário atualizado: '.$name);return ['success'=>true];
    }
    public static function destroy(int $id): array
    {
        if($id===(int)($_SESSION['user_id']??0))return ['success'=>false,'message'=>'Você não pode desativar sua própria conta.'];
        $user=User::find($id);if(!$user)return ['success'=>false,'message'=>'Usuário não encontrado.'];
        User::setStatus($id,'inactive');AuditLog::record('archive','user',$id,'Usuário desativado: '.$user['name']);return ['success'=>true];
    }
}
