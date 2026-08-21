<?php
require_once __DIR__.'/../../includes/session.php';
$title = 'Agendamentos e empréstimos - '.APP_NAME;

use Controllers\AuthController;
use Models\Loan;
use Models\Equipment;
use Models\Student;
use Models\User;

AuthController::requireAuth();
$canManage = can('manage_loans');
$canSchedule = can('schedule_equipment');
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$loans = $canManage ? Loan::all() : Loan::findByUser($currentUserId);
$equipments = ($canManage || $canSchedule) ? Equipment::findSchedulable() : [];
$students = $canManage ? Student::active() : [];
$staff = $canManage ? User::activeStaff() : [];
$schedule = Loan::upcomingSchedule(14, 100);
$api = appUrl('/api/loans.php');
$csrf = csrfToken();
$preselectedEquipment = (int)($_GET['equipment_id'] ?? 0);
require_once __DIR__.'/../partials/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $canManage ? 'Agendamentos e empréstimos' : 'Meus agendamentos' ?></h1>
        <p class="page-subtitle"><?= $canManage ? 'Gerencie reservas, retiradas e devoluções dos equipamentos da escola.' : 'Reserve equipamentos para suas aulas sem precisar solicitar o lançamento à administração.' ?></p>
    </div>
    <?php if($canSchedule): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loanModal" onclick="newLoan()"><i data-lucide="calendar-plus"></i> <?= $canManage ? 'Novo agendamento' : 'Agendar equipamento' ?></button>
    <?php endif; ?>
</div>

<?php if($canManage && Loan::countOverdue()>0): ?>
<div class="alert alert-danger alert-overdue"><strong><i data-lucide="triangle-alert"></i> Há <?=Loan::countOverdue()?> empréstimo(s) atrasado(s).</strong> Priorize a devolução desses itens.</div>
<?php endif; ?>

<div class="alert alert-info border-0 shadow-sm">
    <div class="d-flex gap-2 align-items-start"><i data-lucide="calendar-check" class="mt-1"></i><div><strong>Agenda inteligente por horário.</strong> O mesmo equipamento pode ser reservado por professores diferentes em períodos distintos. Se houver sobreposição de horário, o sistema bloqueia o novo agendamento automaticamente.</div></div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div><h2 class="section-title mb-1">Agenda dos próximos 14 dias</h2><div class="help-text">Consulte os horários já ocupados antes de reservar.</div></div>
        <span class="badge text-bg-light border"><?=count($schedule)?> compromisso(s)</span>
    </div>
    <div class="card-body p-0 p-md-3">
        <div class="table-responsive">
            <table class="table mobile-cards">
                <thead><tr><th>Equipamento</th><th>Início</th><th>Término</th><th>Situação</th><th>Responsável</th></tr></thead>
                <tbody>
                <?php foreach($schedule as $item): $mine=(int)$item['user_id']===$currentUserId; ?>
                    <tr>
                        <td data-label="Equipamento"><strong><?=e($item['equipment_name'])?></strong><small class="d-block muted"><?=e($item['patrimony_code'])?></small></td>
                        <td data-label="Início"><?=e(formatDateTime($item['withdrawal_date']))?></td>
                        <td data-label="Término"><?=e(formatDateTime($item['expected_return_date']))?></td>
                        <td data-label="Situação"><?=getStatusBadge($item['status'])?></td>
                        <td data-label="Responsável"><?php if($canManage): ?><?=e($item['borrower_name'])?><?php elseif($mine): ?><strong>Você</strong><?php else: ?><span class="muted">Horário ocupado</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if(!$schedule): ?><div class="empty-state"><i data-lucide="calendar-check-2"></i><div>Nenhum equipamento agendado para os próximos dias.</div></div><?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="toolbar">
            <div class="search-box"><i data-lucide="search"></i><input class="form-control" placeholder="Buscar equipamento ou responsável" data-search-table="#loansTable"></div>
            <span class="ms-auto small muted"><?=count($loans)?> registros</span>
        </div>
    </div>
    <div class="card-body p-0 p-md-3">
        <div class="table-responsive">
            <table class="table mobile-cards" id="loansTable">
                <thead><tr><th><?= $canManage ? 'Destinatário' : 'Professor' ?></th><th>Equipamento</th><th>Início / retirada</th><th>Término / devolução</th><th>Status</th><?php if($canManage || $canSchedule):?><th class="text-end">Ações</th><?php endif;?></tr></thead>
                <tbody>
                <?php foreach($loans as $l): $isOwnReservation=$l['status']==='reserved' && (int)$l['user_id']===$currentUserId && $l['student_id']===null; ?>
                    <tr>
                        <td data-label="Destinatário"><strong><?=e($l['borrower_name'])?></strong><?php if($l['student_id']):?><small class="d-block muted">Aluno • <?=e($l['student_grade'])?> <?=e($l['student_class'])?> • resp. <?=e($l['user_name'])?></small><?php else:?><small class="d-block muted"><?=e(roleLabel($l['user_role']??'teacher'))?></small><?php endif;?></td>
                        <td data-label="Equipamento"><strong><?=e($l['equipment_name'])?></strong><small class="d-block muted"><?=e($l['patrimony_code'])?></small></td>
                        <td data-label="Início"><?=e(formatDateTime($l['withdrawal_date']))?></td>
                        <td data-label="Término"><?=e(formatDateTime($l['expected_return_date']))?></td>
                        <td data-label="Status"><?=getStatusBadge($l['status'])?></td>
                        <?php if($canManage || $canSchedule): ?>
                        <td data-label="Ações"><div class="table-actions">
                            <?php if($canManage && $l['status']==='reserved'): ?>
                                <button class="btn btn-primary btn-sm" onclick="checkoutLoan(<?=$l['id']?>)"><i data-lucide="package-check"></i> Confirmar retirada</button>
                                <button class="btn btn-outline-primary btn-sm" onclick="editLoan(<?=$l['id']?>)"><i data-lucide="calendar-pen"></i> Editar</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="cancelLoan(<?=$l['id']?>)"><i data-lucide="x"></i></button>
                            <?php elseif(!$canManage && $isOwnReservation): ?>
                                <button class="btn btn-outline-primary btn-sm" onclick="editLoan(<?=$l['id']?>)"><i data-lucide="calendar-pen"></i> Editar</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="cancelLoan(<?=$l['id']?>)"><i data-lucide="x"></i> Cancelar</button>
                            <?php elseif($canManage && in_array($l['status'],['borrowed','overdue'],true)): ?>
                                <button class="btn btn-outline-primary btn-sm" onclick="editLoan(<?=$l['id']?>)"><i data-lucide="calendar-pen"></i></button>
                                <button class="btn btn-success btn-sm" onclick="returnLoan(<?=$l['id']?>)"><i data-lucide="undo-2"></i> Devolver</button>
                            <?php else: ?><span class="muted small">—</span><?php endif; ?>
                        </div></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if(!$loans):?><div class="empty-state"><i data-lucide="calendar-days"></i><div><?= $canManage ? 'Nenhum agendamento ou empréstimo registrado.' : 'Você ainda não possui agendamentos.' ?></div></div><?php endif;?>
    </div>
</div>

<?php if($canSchedule): ?>
<div class="modal fade" id="loanModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><form id="loanForm">
    <div class="modal-header"><div><h2 class="modal-title fs-5"><?= $canManage ? 'Novo agendamento / empréstimo' : 'Agendar equipamento' ?></h2><div class="help-text"><?= $canManage ? 'Você pode reservar para o futuro ou registrar uma retirada imediata.' : 'Escolha o equipamento e o período em que você pretende utilizá-lo.' ?></div></div><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><?=csrfField()?><input type="hidden" name="action" value="create">
        <?php if(!$canManage): ?><input type="hidden" name="user_id" value="<?=$currentUserId?>"><input type="hidden" name="status" value="reserved"><?php endif; ?>
        <div class="row g-3">
            <?php if($canManage): ?>
            <div class="col-md-6"><label class="form-label">Aluno (opcional)</label><select class="form-select" name="student_id" id="loanStudent"><option value="">Empréstimo para servidor/professor</option><?php foreach($students as $s):?><option value="<?=$s['id']?>"><?=e($s['name'].' • '.$s['grade'].' '.$s['class_name'].' • '.$s['registration'])?></option><?php endforeach;?></select></div>
            <div class="col-md-6"><label class="form-label">Responsável / destinatário *</label><select class="form-select" name="user_id" id="loanUser" required><?php foreach($staff as $u):?><option value="<?=$u['id']?>" <?=$u['id']==$currentUserId?'selected':''?>><?=e($u['name'].' • '.roleLabel($u['role']))?></option><?php endforeach;?></select></div>
            <?php endif; ?>
            <div class="col-12"><label class="form-label">Equipamento *</label><select class="form-select" name="equipment_id" id="loanEquipment" required><option value="">Selecione...</option><?php foreach($equipments as $eq):?><option value="<?=$eq['id']?>" <?=$preselectedEquipment===$eq['id']?'selected':''?>><?=e($eq['name'].' • '.$eq['patrimony_code'].($eq['location_name']?' • '.$eq['location_name']:''))?></option><?php endforeach;?></select><div class="help-text">Equipamentos em manutenção ou inativos não aparecem aqui. Conflitos de horário são validados ao salvar.</div></div>
            <div class="col-md-6"><label class="form-label"><?= $canManage ? 'Início / retirada *' : 'Início da reserva *' ?></label><input class="form-control" type="datetime-local" name="withdrawal_date" id="withdrawalDate" required></div>
            <div class="col-md-6"><label class="form-label"><?= $canManage ? 'Término / devolução prevista *' : 'Fim da reserva *' ?></label><input class="form-control" type="datetime-local" name="expected_return_date" id="expectedDate" required></div>
            <?php if($canManage): ?><div class="col-md-5"><label class="form-label">Tipo</label><select class="form-select" name="status"><option value="reserved">Agendamento futuro</option><option value="borrowed">Empréstimo imediato</option></select></div><?php endif; ?>
            <div class="col-12"><label class="form-label">Finalidade / observações</label><textarea class="form-control" rows="3" name="observations" placeholder="Ex.: aula de Ciências, apresentação, atividade no 7º A..."></textarea></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary"><i data-lucide="calendar-check"></i> <?= $canManage ? 'Salvar' : 'Confirmar agendamento' ?></button></div>
</form></div></div></div>

<div class="modal fade" id="editLoanModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form id="editLoanForm">
    <div class="modal-header"><h2 class="modal-title fs-5">Editar período</h2><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><?=csrfField()?><input type="hidden" name="action" value="update"><input type="hidden" name="id" id="editLoanId">
        <div class="mb-3" id="editStartWrap"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="withdrawal_date" id="editStart"></div>
        <div class="mb-3"><label class="form-label">Término / devolução prevista</label><input class="form-control" type="datetime-local" name="expected_return_date" id="editDue" required></div>
        <div><label class="form-label">Finalidade / observações</label><textarea class="form-control" rows="3" name="observations" id="editNotes"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar alteração</button></div>
</form></div></div></div>

<?php if($canManage): ?>
<div class="modal fade" id="returnModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form id="returnForm"><div class="modal-header"><h2 class="modal-title fs-5">Registrar devolução</h2><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><?=csrfField()?><input type="hidden" name="action" value="return"><input type="hidden" name="id" id="returnLoanId"><label class="form-label">Data e hora da devolução</label><input class="form-control" type="datetime-local" name="actual_return_date" id="returnDate" required></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-success">Confirmar devolução</button></div></form></div></div></div>
<?php endif; ?>

<script>
const loanApi=<?=json_encode($api)?>,loanCsrf=<?=json_encode($csrf)?>,preselectedEquipment=<?=json_encode((string)$preselectedEquipment)?>;
function localInput(d){const p=n=>String(n).padStart(2,'0');return d.getFullYear()+'-'+p(d.getMonth()+1)+'-'+p(d.getDate())+'T'+p(d.getHours())+':'+p(d.getMinutes())}
function newLoan(){
    loanForm.reset();
    const now=new Date(); now.setMinutes(Math.ceil(now.getMinutes()/10)*10,0,0);
    const end=new Date(now.getTime()+60*60*1000);
    withdrawalDate.value=localInput(now); expectedDate.value=localInput(end);
    <?php if($canManage): ?>loanUser.value=<?=json_encode((string)$currentUserId)?>;<?php endif; ?>
    if(preselectedEquipment && document.getElementById('loanEquipment')) loanEquipment.value=preselectedEquipment;
}
async function editLoan(id){try{const r=await fetch(loanApi+'?action=show&id='+id),d=await r.json();if(!d.success)throw new Error(d.message);editLoanId.value=d.data.id;editStart.value=(d.data.withdrawal_date||'').replace(' ','T').slice(0,16);editDue.value=(d.data.expected_return_date||'').replace(' ','T').slice(0,16);editNotes.value=d.data.observations||'';editStart.disabled=d.data.status!=='reserved';document.getElementById('editStartWrap').classList.toggle('opacity-50',d.data.status!=='reserved');new bootstrap.Modal(document.getElementById('editLoanModal')).show()}catch(e){notifyError(e)}}
<?php if($canManage): ?>
async function checkoutLoan(id){if(!confirm('Confirmar que o equipamento foi retirado agora?'))return;try{await apiPost(loanApi,{action:'checkout',id,withdrawal_date:localInput(new Date()),_csrf:loanCsrf});location.reload()}catch(e){notifyError(e)}}
function returnLoan(id){returnLoanId.value=id;returnDate.value=localInput(new Date());new bootstrap.Modal(document.getElementById('returnModal')).show()}
returnForm.addEventListener('submit',async e=>{e.preventDefault();try{await apiPost(loanApi,new FormData(e.target));location.reload()}catch(err){notifyError(err)}});
<?php endif; ?>
async function cancelLoan(id){if(!confirm('Cancelar este agendamento?'))return;try{await apiPost(loanApi,{action:'cancel',id,_csrf:loanCsrf});location.reload()}catch(e){notifyError(e)}}
loanForm.addEventListener('submit',async e=>{e.preventDefault();try{await apiPost(loanApi,new FormData(e.target));location.reload()}catch(err){notifyError(err)}});
editLoanForm.addEventListener('submit',async e=>{e.preventDefault();try{await apiPost(loanApi,new FormData(e.target));location.reload()}catch(err){notifyError(err)}});
<?php if(isset($_GET['new'])):?>window.addEventListener('load',()=>{newLoan();new bootstrap.Modal(document.getElementById('loanModal')).show()});<?php endif;?>
</script>
<?php endif; ?>
<?php require_once __DIR__.'/../partials/footer.php';?>
