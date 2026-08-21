<?php

declare(strict_types=1);

namespace Controllers;

use Models\AuditLog;
use Models\Student;

class StudentController
{
    public static function index(): array { return Student::all(); }
    public static function show(int $id): ?array { return Student::find($id); }
    private static function validate(array $data,?int $id=null): array
    {
        foreach(['name','registration','grade','class_name'] as $field){if(trim((string)($data[$field]??''))==='')return ['success'=>false,'message'=>'Nome, matrícula, ano/série e turma são obrigatórios.'];}
        $registration=trim((string)$data['registration']);if(Student::registrationExists($registration,$id))return ['success'=>false,'message'=>'Já existe um aluno com esta matrícula.'];
        if(!in_array($data['status']??'active',['active','inactive'],true))return ['success'=>false,'message'=>'Status inválido.'];
        return ['success'=>true];
    }
    public static function store(array $data): array
    {
        $v=self::validate($data);if(!$v['success'])return $v;$id=Student::create($data);AuditLog::record('create','student',$id,'Aluno cadastrado: '.$data['name']);return ['success'=>true,'student_id'=>$id];
    }
    public static function update(int $id,array $data): array
    {
        if(!Student::find($id))return ['success'=>false,'message'=>'Aluno não encontrado.'];$v=self::validate($data,$id);if(!$v['success'])return $v;Student::update($id,$data);AuditLog::record('update','student',$id,'Cadastro do aluno atualizado');return ['success'=>true];
    }
    public static function destroy(int $id): array
    {
        $student=Student::find($id);if(!$student)return ['success'=>false,'message'=>'Aluno não encontrado.'];if(Student::hasOpenLoans($id))return ['success'=>false,'message'=>'Este aluno possui empréstimo em aberto. Registre a devolução antes de arquivá-lo.'];Student::archive($id);AuditLog::record('archive','student',$id,'Aluno arquivado: '.$student['name']);return ['success'=>true];
    }
}
