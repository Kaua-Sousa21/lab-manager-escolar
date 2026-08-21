<?php

declare(strict_types=1);

namespace Controllers;

use Models\AuditLog;
use Models\Equipment;
use Models\Loan;
use Models\Student;
use Models\User;

class LoanController
{
    public static function index(): array { return Loan::all(); }
    public static function show(int $id): ?array { return Loan::find($id); }
    public static function myLoans(int $id): array { return Loan::findByUser($id); }

    public static function store(array $data): array
    {
        $equipmentId = (int)($data['equipment_id'] ?? 0);
        $userId = (int)($data['user_id'] ?? 0);
        $studentId = !empty($data['student_id']) ? (int)$data['student_id'] : null;
        $withdrawal = $data['withdrawal_date'] ?? '';
        $due = $data['expected_return_date'] ?? '';
        $status = $data['status'] ?? 'borrowed';

        if (!$equipmentId || !$userId || !$withdrawal || !$due) return ['success'=>false,'message'=>'Preencha o equipamento, responsável, início e término do período.'];
        $equipment = Equipment::find($equipmentId);
        $user = User::find($userId);
        if (!$equipment || !$user || $user['status'] !== 'active') return ['success'=>false,'message'=>'Equipamento ou responsável inválido.'];
        if ($studentId && !Student::find($studentId)) return ['success'=>false,'message'=>'Aluno inválido.'];
        if (!in_array($status, ['reserved','borrowed'], true)) return ['success'=>false,'message'=>'Tipo de movimentação inválido.'];

        try {
            $w = new \DateTime($withdrawal);
            $d = new \DateTime($due);
            if ($d <= $w) return ['success'=>false,'message'=>'O término deve ser posterior ao início do agendamento.'];
            if ($status === 'reserved' && $w < new \DateTime('-5 minutes')) return ['success'=>false,'message'=>'O início do agendamento não pode estar no passado.'];
        } catch (\Throwable $e) {
            return ['success'=>false,'message'=>'Datas inválidas.'];
        }

        try {
            $id = Loan::create([
                'equipment_id'=>$equipmentId,
                'user_id'=>$userId,
                'student_id'=>$studentId,
                'withdrawal_date'=>$withdrawal,
                'expected_return_date'=>$due,
                'status'=>$status,
                'observations'=>trim((string)($data['observations'] ?? '')),
            ]);
            AuditLog::record('create','loan',$id,$status === 'reserved' ? 'Agendamento criado' : 'Empréstimo registrado');
            return ['success'=>true,'loan_id'=>$id];
        } catch (\RuntimeException $e) {
            return ['success'=>false,'message'=>$e->getMessage()];
        } catch (\Throwable $e) {
            error_log('[LabManager] Loan create failed: '.$e->getMessage());
            return ['success'=>false,'message'=>'Não foi possível registrar o agendamento/empréstimo.'];
        }
    }

    public static function storeForTeacher(array $data, int $teacherId): array
    {
        $data['user_id'] = $teacherId;
        $data['student_id'] = null;
        $data['status'] = 'reserved';
        return self::store($data);
    }

    public static function update(int $id, array $data): array
    {
        $loan = Loan::find($id);
        if (!$loan) return ['success'=>false,'message'=>'Agendamento/empréstimo não encontrado.'];
        if (!in_array($loan['status'], ['reserved','borrowed','overdue'], true)) return ['success'=>false,'message'=>'Somente registros em aberto podem ser editados.'];

        $withdrawal = $loan['status'] === 'reserved' ? ($data['withdrawal_date'] ?? $loan['withdrawal_date']) : $loan['withdrawal_date'];
        $due = $data['expected_return_date'] ?? '';
        if (!$due) return ['success'=>false,'message'=>'Informe o término previsto.'];

        try {
            $w = new \DateTime($withdrawal);
            $d = new \DateTime($due);
            if ($d <= $w) return ['success'=>false,'message'=>'O término deve ser posterior ao início.'];
            if ($loan['status'] === 'reserved' && $w < new \DateTime('-5 minutes')) return ['success'=>false,'message'=>'O início do agendamento não pode estar no passado.'];
        } catch (\Throwable $e) {
            return ['success'=>false,'message'=>'Data inválida.'];
        }

        if ($loan['status'] === 'reserved' && Loan::hasConflict((int)$loan['equipment_id'], $withdrawal, $due, $id)) {
            return ['success'=>false,'message'=>'Esse novo horário conflita com outro agendamento ou empréstimo do equipamento.'];
        }

        Loan::updateSchedule($id, $withdrawal, $due, trim((string)($data['observations'] ?? '')));
        AuditLog::record('update','loan',$id,$loan['status'] === 'reserved' ? 'Agendamento atualizado' : 'Prazo/observações atualizados');
        return ['success'=>true];
    }

    public static function checkout(int $id, ?string $date=null): array
    {
        $loan=Loan::find($id);
        if(!$loan) return ['success'=>false,'message'=>'Reserva não encontrada.'];
        if($loan['status']!=='reserved') return ['success'=>false,'message'=>'Somente reservas podem ter a retirada confirmada.'];
        try {
            if(!Loan::checkout($id,$date)) return ['success'=>false,'message'=>'Não foi possível confirmar a retirada.'];
        } catch (\RuntimeException $e) {
            return ['success'=>false,'message'=>$e->getMessage()];
        }
        AuditLog::record('checkout','loan',$id,'Retirada de reserva confirmada');
        return ['success'=>true];
    }

    public static function return(int $id, ?string $date=null): array
    {
        $loan=Loan::find($id);
        if(!$loan) return ['success'=>false,'message'=>'Empréstimo não encontrado.'];
        if(!in_array($loan['status'],['borrowed','overdue'],true)) return ['success'=>false,'message'=>'Somente itens retirados podem ser devolvidos.'];
        if($date){
            try {
                if(new \DateTime($date)<new \DateTime($loan['withdrawal_date'])) return ['success'=>false,'message'=>'A devolução não pode ocorrer antes da retirada.'];
            } catch(\Throwable $e) {
                return ['success'=>false,'message'=>'Data de devolução inválida.'];
            }
        }
        if(!Loan::return($id,$date)) return ['success'=>false,'message'=>'Não foi possível registrar a devolução.'];
        AuditLog::record('return','loan',$id,'Devolução registrada');
        return ['success'=>true];
    }

    public static function cancel(int $id): array
    {
        $loan=Loan::find($id);
        if(!$loan) return ['success'=>false,'message'=>'Agendamento não encontrado.'];
        if(!Loan::cancel($id)) return ['success'=>false,'message'=>'Somente agendamentos ainda reservados podem ser cancelados.'];
        AuditLog::record('cancel','loan',$id,'Agendamento cancelado');
        return ['success'=>true];
    }
}
