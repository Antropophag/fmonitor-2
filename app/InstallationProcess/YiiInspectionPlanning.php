<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final readonly class YiiInspectionPlanning
{
    public function __construct(private MariaDbYiiInspectionPlanning $store, private \Closure $clock)
    {
    }

    public function scheduleInspection(int $actorId, int $objectId, string $inspectionDate): array
    {
        $transaction = $this->store->begin();
        try {
            $this->store->assertReady();
            if (!$this->store->actorCanSchedule($actorId)) {
                $transaction->rollBack();
                return ['status' => 'access_denied'];
            }
            $now = $this->moscowNow();
            if (!$this->validDate($inspectionDate, $now)) {
                $transaction->rollBack();
                return ['status' => 'invalid_date'];
            }
            $case = $this->store->lockEligibleCase($objectId);
            if ($case === null) {
                $transaction->rollBack();
                return ['status' => 'ineligible'];
            }
            $existing = $this->store->findSchedule($case['caseId'], $case['engineerId'], $inspectionDate);
            if ($existing !== null) {
                $transaction->commit();
                return ['status' => 'scheduled', 'scheduleId' => $existing];
            }
            $stamp = $now->format(DATE_ATOM);
            $scheduleId = $this->store->appendSchedule($case, $objectId, $inspectionDate, $actorId, $stamp);
            $this->store->appendEvent($scheduleId, $case, $inspectionDate, $actorId, $stamp);
            $transaction->commit();
            return ['status' => 'scheduled', 'scheduleId' => $scheduleId];
        } catch (\yii\db\IntegrityException $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            $case = $case ?? null;
            $id = is_array($case) ? $this->store->findSchedule($case['caseId'], $case['engineerId'], $inspectionDate) : null;
            if ($id !== null) {
                return ['status' => 'scheduled', 'scheduleId' => $id];
            }
            throw $error;
        } catch (\Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    private function moscowNow(): \DateTimeImmutable
    {
        $value = ($this->clock)();
        if (!$value instanceof \DateTimeInterface) {
            throw new \RuntimeException('Clock unavailable.');
        }
        return (new \DateTimeImmutable($value->format(DATE_ATOM)))->setTimezone(new \DateTimeZone('Europe/Moscow'));
    }

    private function validDate(string $value, \DateTimeImmutable $now): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('Europe/Moscow'));
        return $date !== false && $date->format('Y-m-d') === $value && $value >= $now->format('Y-m-d');
    }
}
