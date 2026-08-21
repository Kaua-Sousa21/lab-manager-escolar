<?php
require_once __DIR__ . '/../includes/session.php';

use Controllers\AuthController;
use Controllers\LoanController;

header('Content-Type: application/json; charset=utf-8');
AuthController::requireAuth();
$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfForPost(true);
}

try {
    switch ($action) {
        case 'index':
            $data = can('view_all_loans') ? LoanController::index() : LoanController::myLoans((int)$_SESSION['user_id']);
            jsonResponse(['success'=>true,'data'=>$data]);

        case 'show':
            $loan = LoanController::show((int)($_GET['id'] ?? 0));
            if (!$loan) jsonResponse(['success'=>false,'message'=>'Agendamento/empréstimo não encontrado.'],404);
            if (!can('view_all_loans') && (int)$loan['user_id'] !== (int)$_SESSION['user_id']) {
                jsonResponse(['success'=>false,'message'=>'Permissão negada.'],403);
            }
            jsonResponse(['success'=>true,'data'=>$loan]);

        case 'create':
            if (can('manage_loans')) {
                $result = LoanController::store($_POST);
            } elseif (can('schedule_equipment')) {
                $result = LoanController::storeForTeacher($_POST, (int)$_SESSION['user_id']);
            } else {
                jsonResponse(['success'=>false,'message'=>'Você não possui permissão para agendar equipamentos.'],403);
            }
            jsonResponse($result,$result['success']?201:400);

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            $loan = LoanController::show($id);
            if (!$loan) jsonResponse(['success'=>false,'message'=>'Registro não encontrado.'],404);
            $ownsReservation = can('schedule_equipment') && $loan['status'] === 'reserved' && (int)$loan['user_id'] === (int)$_SESSION['user_id'] && $loan['student_id'] === null;
            if (!can('manage_loans') && !$ownsReservation) jsonResponse(['success'=>false,'message'=>'Permissão negada.'],403);
            $result = LoanController::update($id,$_POST);
            jsonResponse($result,$result['success']?200:400);

        case 'cancel':
            $id = (int)($_POST['id'] ?? 0);
            $loan = LoanController::show($id);
            if (!$loan) jsonResponse(['success'=>false,'message'=>'Registro não encontrado.'],404);
            $ownsReservation = can('schedule_equipment') && $loan['status'] === 'reserved' && (int)$loan['user_id'] === (int)$_SESSION['user_id'] && $loan['student_id'] === null;
            if (!can('manage_loans') && !$ownsReservation) jsonResponse(['success'=>false,'message'=>'Permissão negada.'],403);
            $result = LoanController::cancel($id);
            jsonResponse($result,$result['success']?200:400);

        case 'checkout':
            if (!can('manage_loans')) jsonResponse(['success'=>false,'message'=>'Somente a equipe responsável pode confirmar retiradas.'],403);
            $result=LoanController::checkout((int)($_POST['id']??0),$_POST['withdrawal_date']??null);
            jsonResponse($result,$result['success']?200:400);

        case 'return':
            if (!can('manage_loans')) jsonResponse(['success'=>false,'message'=>'Somente a equipe responsável pode registrar devoluções.'],403);
            $result=LoanController::return((int)($_POST['id']??0),$_POST['actual_return_date']??null);
            jsonResponse($result,$result['success']?200:400);

        default:
            jsonResponse(['success'=>false,'message'=>'Ação inválida.'],400);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    jsonResponse(['success'=>false,'message'=>'Erro interno ao processar a solicitação.'],500);
}
