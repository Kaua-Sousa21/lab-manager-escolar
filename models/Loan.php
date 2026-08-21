<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use RuntimeException;

class Loan
{
    private static function selectBase(): string
    {
        return "SELECT l.*, e.name AS equipment_name, e.patrimony_code, u.name AS user_name, u.email AS user_email, u.role AS user_role, s.name AS student_name, s.registration AS student_registration, s.grade AS student_grade, s.class_name AS student_class, CASE WHEN l.student_id IS NOT NULL THEN s.name ELSE u.name END AS borrower_name FROM loans l JOIN equipment e ON e.id=l.equipment_id JOIN users u ON u.id=l.user_id LEFT JOIN students s ON s.id=l.student_id";
    }

    public static function all(): array
    {
        self::checkOverdue();
        return Database::getInstance()->getConnection()->query(self::selectBase() . " ORDER BY CASE WHEN l.status='overdue' THEN 0 WHEN l.status='borrowed' THEN 1 WHEN l.status='reserved' THEN 2 ELSE 3 END, l.withdrawal_date ASC, l.created_at DESC")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        self::checkOverdue();
        $stmt = Database::getInstance()->getConnection()->prepare(self::selectBase() . ' WHERE l.id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUser(int $userId): array
    {
        self::checkOverdue();
        $stmt = Database::getInstance()->getConnection()->prepare(self::selectBase() . ' WHERE l.user_id=? ORDER BY l.withdrawal_date DESC, l.created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function upcomingByUser(int $userId, int $limit = 12): array
    {
        self::checkOverdue();
        $limit = max(1, min(50, $limit));
        $stmt = Database::getInstance()->getConnection()->prepare(self::selectBase() . " WHERE l.user_id=? AND l.status IN ('reserved','borrowed','overdue') AND (l.status='overdue' OR l.expected_return_date>=NOW()) ORDER BY CASE WHEN l.status='overdue' THEN 0 ELSE 1 END, l.withdrawal_date ASC LIMIT {$limit}");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function upcomingSchedule(int $days = 14, int $limit = 80): array
    {
        self::checkOverdue();
        $days = max(1, min(60, $days));
        $limit = max(1, min(200, $limit));
        $sql = self::selectBase() . " WHERE l.status IN ('reserved','borrowed','overdue') AND (l.status='overdue' OR (l.expected_return_date>=NOW() AND l.withdrawal_date<=DATE_ADD(NOW(), INTERVAL {$days} DAY))) ORDER BY CASE WHEN l.status='overdue' THEN 0 ELSE 1 END, l.withdrawal_date ASC LIMIT {$limit}";
        return Database::getInstance()->getConnection()->query($sql)->fetchAll();
    }

    public static function hasConflict(int $equipmentId, string $start, string $end, ?int $exceptId = null): bool
    {
        self::checkOverdue();
        $sql = "SELECT COUNT(*) FROM loans WHERE equipment_id=? AND status IN ('reserved','borrowed','overdue') AND (status='overdue' OR (withdrawal_date < ? AND expected_return_date > ?))";
        $params = [$equipmentId, $end, $start];
        if ($exceptId) {
            $sql .= ' AND id<>?';
            $params[] = $exceptId;
        }
        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $equipmentId = (int) $data['equipment_id'];
            $lock = $db->prepare('SELECT status FROM equipment WHERE id=? FOR UPDATE');
            $lock->execute([$equipmentId]);
            $equipmentStatus = $lock->fetchColumn();
            if ($equipmentStatus === false) {
                throw new RuntimeException('Equipamento não encontrado.');
            }
            if (in_array($equipmentStatus, ['maintenance', 'inactive'], true)) {
                throw new RuntimeException('Este equipamento está indisponível para agendamento.');
            }

            $loanStatus = $data['status'] ?? 'borrowed';
            if (!in_array($loanStatus, ['reserved', 'borrowed'], true)) {
                $loanStatus = 'borrowed';
            }
            if ($loanStatus === 'borrowed' && !in_array($equipmentStatus, ['available', 'reserved'], true)) {
                throw new RuntimeException('Este equipamento não está disponível para retirada agora.');
            }
            if (self::hasConflict($equipmentId, $data['withdrawal_date'], $data['expected_return_date'])) {
                throw new RuntimeException('Já existe um agendamento ou empréstimo que ocupa este equipamento nesse horário. Escolha outro período.');
            }

            $stmt = $db->prepare('INSERT INTO loans (user_id, student_id, equipment_id, withdrawal_date, expected_return_date, status, observations) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([
                (int) $data['user_id'],
                !empty($data['student_id']) ? (int) $data['student_id'] : null,
                $equipmentId,
                $data['withdrawal_date'],
                $data['expected_return_date'],
                $loanStatus,
                $data['observations'] ?: null,
            ]);
            $id = (int) $db->lastInsertId();

            // Reservas futuras não alteram o estado físico do equipamento. Assim o mesmo
            // equipamento pode possuir vários agendamentos em horários não conflitantes.
            if ($loanStatus === 'borrowed') {
                Equipment::updateStatus($equipmentId, 'borrowed');
            }

            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function updateSchedule(int $id, string $withdrawalDate, string $expectedReturnDate, ?string $observations): bool
    {
        $stmt = Database::getInstance()->getConnection()->prepare("UPDATE loans SET withdrawal_date=?, expected_return_date=?, observations=?, status=CASE WHEN status='overdue' AND ? >= NOW() THEN 'borrowed' WHEN status='borrowed' AND ? < NOW() THEN 'overdue' ELSE status END WHERE id=? AND status IN ('reserved','borrowed','overdue')");
        return $stmt->execute([$withdrawalDate, $expectedReturnDate, $observations ?: null, $expectedReturnDate, $expectedReturnDate, $id]);
    }

    public static function checkout(int $id, ?string $withdrawalDate = null): bool
    {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $loan = self::find($id);
            if (!$loan || $loan['status'] !== 'reserved') {
                $db->rollBack();
                return false;
            }
            $lock = $db->prepare('SELECT status FROM equipment WHERE id=? FOR UPDATE');
            $lock->execute([(int) $loan['equipment_id']]);
            $equipmentStatus = $lock->fetchColumn();
            if (!in_array($equipmentStatus, ['available', 'reserved'], true)) {
                $db->rollBack();
                throw new RuntimeException('O equipamento não está disponível para retirada neste momento.');
            }
            $date = $withdrawalDate ?: date('Y-m-d H:i:s');
            $stmt = $db->prepare("UPDATE loans SET status='borrowed', withdrawal_date=? WHERE id=?");
            $stmt->execute([$date, $id]);
            Equipment::updateStatus((int) $loan['equipment_id'], 'borrowed');
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function return(int $id, ?string $actualReturnDate = null): bool
    {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $loan = self::find($id);
            if (!$loan || !in_array($loan['status'], ['borrowed', 'overdue'], true)) {
                $db->rollBack();
                return false;
            }
            $date = $actualReturnDate ?: date('Y-m-d H:i:s');
            $stmt = $db->prepare("UPDATE loans SET status='returned', actual_return_date=? WHERE id=?");
            $stmt->execute([$date, $id]);
            $eq = $db->prepare("UPDATE equipment SET status='available' WHERE id=? AND status='borrowed'");
            $eq->execute([(int) $loan['equipment_id']]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function cancel(int $id): bool
    {
        $stmt = Database::getInstance()->getConnection()->prepare("UPDATE loans SET status='cancelled' WHERE id=? AND status='reserved'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function checkOverdue(): void
    {
        $db = Database::getInstance()->getConnection();
        $db->exec("UPDATE loans SET status='overdue' WHERE status='borrowed' AND expected_return_date < NOW()");
        // Compatibilidade com versões anteriores, nas quais uma reserva alterava o
        // equipamento para 'reserved'. Uma reserva futura agora é apenas agenda.
        $db->exec("UPDATE equipment e SET e.status='available' WHERE e.status='reserved' AND NOT EXISTS (SELECT 1 FROM loans l WHERE l.equipment_id=e.id AND l.status IN ('borrowed','overdue'))");
    }

    public static function countActive(): int { self::checkOverdue(); return (int) Database::getInstance()->getConnection()->query("SELECT COUNT(*) FROM loans WHERE status IN ('reserved','borrowed','overdue')")->fetchColumn(); }
    public static function countOverdue(): int { self::checkOverdue(); return (int) Database::getInstance()->getConnection()->query("SELECT COUNT(*) FROM loans WHERE status='overdue'")->fetchColumn(); }
    public static function countDueToday(): int { self::checkOverdue(); return (int) Database::getInstance()->getConnection()->query("SELECT COUNT(*) FROM loans WHERE status='borrowed' AND DATE(expected_return_date)=CURDATE()")->fetchColumn(); }
    public static function overdue(int $limit=10): array { self::checkOverdue(); $limit=max(1,min(50,$limit)); return Database::getInstance()->getConnection()->query(self::selectBase()." WHERE l.status='overdue' ORDER BY l.expected_return_date ASC LIMIT {$limit}")->fetchAll(); }
    public static function dueSoon(int $days=3,int $limit=10): array { self::checkOverdue(); $days=max(1,min(14,$days)); $limit=max(1,min(50,$limit)); return Database::getInstance()->getConnection()->query(self::selectBase()." WHERE l.status='borrowed' AND l.expected_return_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL {$days} DAY) ORDER BY l.expected_return_date ASC LIMIT {$limit}")->fetchAll(); }
}
