<?php

declare(strict_types=1);

namespace FMonitor2\Otiz;

require_once __DIR__ . '/OtizSettlementV2Math.php';

use FMonitor\Otiz\OtizSettlementV2Math;
use yii\db\Connection;

/** Read-only current ledger summary for one calendar month. */
final readonly class MariaDbOtizMonthlyTotals
{
    public function __construct(private Connection $db, private string $prefix)
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('INVALID_TABLE_PREFIX');
        }
    }

    /**
     * @param list<int> $objectIds
     * @return array{month:string,rows:list<array<string,int|string>>,objects:list<array<string,int>>,incomplete:bool,issues:list<string>}
     */
    public function read(int $actor, string $month, array $objectIds): array
    {
        $this->authorize($actor);
        [$from, $to] = $this->monthRange($month);
        $ids = $this->objectIds($objectIds);
        if ($ids === []) return ['month'=>$month, 'rows'=>[], 'objects'=>[], 'incomplete'=>false, 'issues'=>[]];

        if ($this->db->getTransaction()?->isActive) throw new \RuntimeException('Monthly totals require an independent read snapshot.');
        $this->db->createCommand('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ')->execute();
        $transaction = $this->db->beginTransaction();
        try {
            $rows = [];
            $issues = [];
            $this->paid($ids, $from, $to, $rows, $issues);
            $this->deductions($ids, $from, $to, $rows, $issues);

            ksort($rows, SORT_STRING);
            $resultRows = [];
            $objects = [];
            foreach ($rows as $row) {
                $row['totalCents'] = $row['paidCents'] + $row['disciplineCents'] + $row['deadlineCents'];
                $resultRows[] = $row;
                $objectId = $row['objectId'];
                $objects[$objectId] ??= ['objectId'=>$objectId, 'paidCents'=>0, 'disciplineCents'=>0, 'deadlineCents'=>0, 'totalCents'=>0];
                foreach (['paidCents','disciplineCents','deadlineCents','totalCents'] as $field) {
                    $objects[$objectId][$field] += $row[$field];
                }
            }
            ksort($objects, SORT_NUMERIC);
            $issues = array_values(array_unique($issues));
            $result = ['month'=>$month, 'rows'=>$resultRows, 'objects'=>array_values($objects), 'incomplete'=>$issues !== [], 'issues'=>$issues];
            $transaction->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($transaction->isActive) $transaction->rollBack();
            throw $error;
        }
    }

    private function paid(array $ids, string $from, string $to, array &$totals, array &$issues): void
    {
        [$in, $params] = $this->in($ids, 'paidObject');
        $payments = $this->db->createCommand(
            "SELECT p.id,p.calculation_id,p.amount_cents,p.payment_date FROM `{$this->prefix}fm2_otiz_payment_facts` p "
            . "JOIN `{$this->prefix}fm2_otiz_calculation_revisions` c ON c.id=p.calculation_id AND c.status='accepted' "
            . "WHERE p.payment_date>=:paidFrom AND p.payment_date<:paidTo "
            . "AND NOT EXISTS(SELECT 1 FROM `{$this->prefix}fm2_otiz_payment_reversals` r WHERE r.payment_id=p.id) "
            . "AND EXISTS(SELECT 1 FROM `{$this->prefix}fm2_otiz_recipient_obligations` o WHERE o.calculation_id=p.calculation_id AND o.object_id IN({$in})) "
            . 'ORDER BY p.calculation_id,p.id',
            $params + [':paidFrom'=>$from, ':paidTo'=>$to],
        )->queryAll();
        $byCalculation = [];
        foreach ($payments as $payment) $byCalculation[(int) $payment['calculation_id']][] = $payment;
        foreach ($byCalculation as $calculation => $calculationPayments) {
            if (count($calculationPayments) !== 1) {
                $issues[] = "Расчёт {$calculation}: несколько действующих отметок оплаты.";
                continue;
            }
            $payment = $calculationPayments[0];
            $obligations = $this->db->createCommand(
                "SELECT object_id,employee_id,tab_number,full_name,amount_cents FROM `{$this->prefix}fm2_otiz_recipient_obligations` WHERE calculation_id=:calculation ORDER BY object_id,BINARY employee_id",
                [':calculation'=>$calculation],
            )->queryAll();
            $saved = array_sum(array_map(static fn(array $row): int => (int) $row['amount_cents'], $obligations));
            if ($saved <= 0 || $saved !== (int) $payment['amount_cents']) {
                $issues[] = "Расчёт {$calculation}: сумма оплаты не подтверждена сохранёнными обязательствами.";
                continue;
            }
            foreach ($obligations as $obligation) {
                $objectId = (int) $obligation['object_id'];
                if (!isset($ids[$objectId])) continue;
                $row =& $this->recipient($totals, $objectId, (string) $obligation['employee_id'], (string) $obligation['tab_number'], (string) $obligation['full_name'], $issues);
                $row['paidCents'] += (int) $obligation['amount_cents'];
                unset($row);
            }
        }
    }

    private function deductions(array $ids, string $from, string $to, array &$totals, array &$issues): void
    {
        $calculations = $this->db->createCommand(
            "SELECT c.id,c.projection_json FROM `{$this->prefix}fm2_otiz_calculation_revisions` c "
            . "WHERE c.status='accepted' AND c.report_date>=:reportFrom AND c.report_date<:reportTo "
            . 'ORDER BY c.id',
            [':reportFrom'=>$from, ':reportTo'=>$to],
        )->queryAll();
        foreach ($calculations as $calculation) {
            $calculationId = (int) $calculation['id'];
            try {
                $projection = json_decode((string) $calculation['projection_json'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $issues[] = "Расчёт {$calculationId}: сохранённая проекция повреждена.";
                continue;
            }
            if (!is_array($projection) || !is_array($projection['objects'] ?? null) || !is_array($projection['recipients'] ?? null)) {
                $issues[] = "Расчёт {$calculationId}: отсутствуют сохранённые данные распределения.";
                continue;
            }
            $deductions = $this->db->createCommand(
                "SELECT object_id,employee_id,amount_cents FROM `{$this->prefix}fm2_otiz_deductions` WHERE calculation_id=:calculation AND active=1 ORDER BY id",
                [':calculation'=>$calculationId],
            )->queryAll();
            $common = [];
            $personal = [];
            foreach ($deductions as $deduction) {
                $objectId = (int) $deduction['object_id'];
                if (!isset($ids[$objectId])) continue;
                if ($deduction['employee_id'] === null) $common[$objectId] = ($common[$objectId] ?? 0) + (int) $deduction['amount_cents'];
                else $personal[$objectId][(string) $deduction['employee_id']] = ($personal[$objectId][(string) $deduction['employee_id']] ?? 0) + (int) $deduction['amount_cents'];
            }
            $objectPenalties = [];
            foreach ($projection['objects'] as $object) {
                if (!is_array($object)) continue;
                $objectId = filter_var($object['objectId'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                if ($objectId === false || !isset($ids[(int) $objectId])) continue;
                $deadline = $object['deadlineCents'] ?? null;
                if (!is_int($deadline) || $deadline < 0) {
                    $issues[] = "Расчёт {$calculationId}, объект {$objectId}: не доказана сумма удержания за сроки.";
                    continue;
                }
                $objectPenalties[(int) $objectId] = $deadline;
            }
            $recipients = [];
            $invalidWeights = [];
            foreach ($projection['recipients'] as $recipient) {
                if (!is_array($recipient)) continue;
                $objectId = filter_var($recipient['objectId'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                $employee = (string) ($recipient['employeeId'] ?? '');
                $weight = $recipient['originalWeight'] ?? $recipient['weight'] ?? null;
                if ($objectId === false || !isset($ids[(int) $objectId]) || $employee === '') continue;
                if (!is_int($weight) || $weight <= 0) {
                    $issues[] = "Расчёт {$calculationId}, объект {$objectId}: нет положительного исходного веса получателя {$employee}.";
                    $invalidWeights[(int) $objectId] = true;
                    continue;
                }
                $recipients[(int) $objectId][$employee] = [
                    'employeeId'=>$employee,
                    'weight'=>$weight,
                    'tabNumber'=>(string) ($recipient['tabNumber'] ?? ''),
                    'name'=>(string) ($recipient['name'] ?? ''),
                ];
                $row =& $this->recipient($totals, (int) $objectId, $employee, (string) ($recipient['tabNumber'] ?? ''), (string) ($recipient['name'] ?? ''), $issues);
                unset($row);
            }
            $objectIds = array_unique(array_merge(array_keys($objectPenalties), array_keys($common), array_keys($personal)));
            foreach ($objectIds as $objectId) {
                $crew = array_values($recipients[$objectId] ?? []);
                $sharedDeadline = (int) ($objectPenalties[$objectId] ?? 0);
                $sharedDiscipline = (int) ($common[$objectId] ?? 0);
                $sharedIncomplete = ($sharedDeadline > 0 || $sharedDiscipline > 0) && ($crew === [] || isset($invalidWeights[$objectId]));
                if ($sharedIncomplete) {
                    $issues[] = "Расчёт {$calculationId}, объект {$objectId}: общие удержания нельзя распределить без сохранённых исходных весов.";
                }
                $deadlineAllocation = !$sharedIncomplete && $sharedDeadline > 0 ? OtizSettlementV2Math::allocate($sharedDeadline, $crew, [], []) : [];
                $disciplineAllocation = !$sharedIncomplete && $sharedDiscipline > 0 ? OtizSettlementV2Math::allocate($sharedDiscipline, $crew, [], []) : [];
                foreach ($crew as $recipient) {
                    $employee = $recipient['employeeId'];
                    $row =& $this->recipient($totals, (int) $objectId, $employee, $recipient['tabNumber'], $recipient['name'], $issues);
                    $row['deadlineCents'] += (int) ($deadlineAllocation[$employee] ?? 0);
                    $row['disciplineCents'] += (int) ($disciplineAllocation[$employee] ?? 0) + (int) ($personal[$objectId][$employee] ?? 0);
                    unset($row);
                    unset($personal[$objectId][$employee]);
                }
                foreach ($personal[$objectId] ?? [] as $employee => $amount) {
                    $issues[] = "Расчёт {$calculationId}, объект {$objectId}: личное удержание {$employee} не связано с сохранённым получателем.";
                }
            }
        }
    }

    private function &recipient(array &$rows, int $objectId, string $employeeId, string $tabNumber, string $name, array &$issues): array
    {
        if ($employeeId === '' || $tabNumber === '' || $name === '') throw new \RuntimeException('Malformed saved recipient identity.');
        $key = $objectId . "\0" . $employeeId;
        if (!isset($rows[$key])) {
            $rows[$key] = ['objectId'=>$objectId, 'employeeId'=>$employeeId, 'tabNumber'=>$tabNumber, 'name'=>$name, 'paidCents'=>0, 'disciplineCents'=>0, 'deadlineCents'=>0];
        } elseif ($rows[$key]['tabNumber'] !== $tabNumber || $rows[$key]['name'] !== $name) {
            $issues[] = "Объект {$objectId}, получатель {$employeeId}: сохранённые реквизиты расходятся.";
        }
        return $rows[$key];
    }

    /** @return array{0:string,1:string} */
    private function monthRange(string $month): array
    {
        if (preg_match('/^(\d{4})-(\d{2})$/D', $month, $match) !== 1 || (int) $match[1] < 1 || (int) $match[2] < 1 || (int) $match[2] > 12) {
            throw new \DomainException('INVALID_MONTH');
        }
        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01', new \DateTimeZone('Europe/Moscow'));
        if ($from === false) throw new \DomainException('INVALID_MONTH');
        return [$from->format('Y-m-d'), $from->modify('+1 month')->format('Y-m-d')];
    }

    /** @return array<int,true> */
    private function objectIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            if (!is_int($id) || $id < 1 || isset($out[$id])) throw new \DomainException('INVALID_OBJECT_ID');
            $out[$id] = true;
        }
        return $out;
    }

    /** @return array{0:string,1:array<string,int>} */
    private function in(array $ids, string $stem): array
    {
        $marks = [];
        $params = [];
        foreach (array_keys($ids) as $index => $id) {
            $mark = ':' . $stem . $index;
            $marks[] = $mark;
            $params[$mark] = $id;
        }
        return [implode(',', $marks), $params];
    }

    private function authorize(int $actor): void
    {
        $allowed = $this->db->createCommand(
            "SELECT 1 FROM `{$this->prefix}fm2_pilot_users` u "
            . "JOIN `{$this->prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id "
            . "JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id "
            . "JOIN `{$this->prefix}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id "
            . "WHERE u.user_id=:actor AND u.status=1 AND BINARY u.activation_state='active' "
            . "AND r.status=1 AND BINARY rp.permission='otiz.manage' LIMIT 1",
            [':actor'=>$actor],
        )->queryScalar();
        if ($allowed === false) throw new \DomainException('FORBIDDEN');
    }
}
