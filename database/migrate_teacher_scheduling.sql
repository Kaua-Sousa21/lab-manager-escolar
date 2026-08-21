-- Migração para quem já está usando a versão "LabManager Escolar corrigido".
-- Faça backup do banco antes de executar.
USE lab_manager;

-- Na versão anterior, uma reserva futura mudava o status físico do equipamento
-- para "reserved". Agora reservas são controladas pelo horário e não bloqueiam
-- o equipamento durante o dia inteiro.
UPDATE equipment e
SET e.status = 'available'
WHERE e.status = 'reserved'
  AND NOT EXISTS (
      SELECT 1
      FROM loans l
      WHERE l.equipment_id = e.id
        AND l.status IN ('borrowed','overdue')
  );

-- Índice para acelerar a verificação de conflitos de agenda.
ALTER TABLE loans
    ADD INDEX idx_loans_schedule (equipment_id, status, withdrawal_date, expected_return_date);
