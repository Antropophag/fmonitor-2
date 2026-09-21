<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Persists the Monday 09:00 Europe/Moscow slot with its durable job. */
final class MariaDbWeeklyFkrScheduler
{
    private const KEY_PREFIX = 'weekly-fkr-report/v1/';
    private \mysqli|\Closure $owner;
    private array $fixtureWeeks = [];

    public function __construct(\mysqli|callable $owner, private string $prefix = '')
    {
        $this->owner = $owner instanceof \mysqli ? $owner : \Closure::fromCallable($owner);
        if ($owner instanceof \mysqli) JobValues::text($prefix, '/^[A-Za-z0-9_]{0,25}$/D');
    }

    public function tick(string $nowUtc): array
    {
        $utc = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s.u\Z', $nowUtc, new \DateTimeZone('UTC'));
        if (!$utc || $utc->format('Y-m-d\TH:i:s.u\Z') !== $nowUtc) throw new \InvalidArgumentException('INVALID_INSTANT');
        $local = $utc->setTimezone(new \DateTimeZone('Europe/Moscow'));
        if ($local->format('N') !== '1' || $local->format('H:i:s.u') < '09:00:00.000000') return ['due' => false];
        $week = $local->format('Y-m-d');
        return $this->owner instanceof \mysqli ? $this->persist($week, $nowUtc, $local) : $this->fixture($week, $nowUtc, $local);
    }

    private function persist(string $week, string $nowUtc, \DateTimeImmutable $local): array
    {
        $session = new MariaDbJobsSession($this->owner, $this->prefix, static fn(): string => $nowUtc);
        return $session->transaction(function () use ($session, $week, $nowUtc, $local): array {
            $key = self::KEY_PREFIX.$week;
            $slots = $session->table('fm2_scheduler_slots');
            $stored = $session->rows("SELECT * FROM {$slots} WHERE schedule_key=? FOR UPDATE", [$key])[0] ?? null;
            if ($stored !== null) return $this->result($week, $local, (int)$stored['job_id'], false);
            $identity = $this->uuid($key);
            $enqueue = (new MariaDbJobEnqueue($session, ['weekly-fkr-report.generate' => [1]]))->command([
                'jobType' => 'weekly-fkr-report.generate', 'payloadVersion' => 1,
                'payload' => ['reportWeek' => $week, 'generatedAtUtc' => $nowUtc],
                'availableAtUtc' => $nowUtc, 'idempotencyKey' => $identity,
                'actor' => ['type' => 'system', 'id' => 'weekly-fkr-report-v1'],
            ]);
            $job = $enqueue();
            if ($job['status'] === 'conflict') throw new \RuntimeException('SCHEDULER_JOB_CONFLICT');
            $session->execute("INSERT INTO {$slots}(schedule_key,due_at_utc,enqueued_at_utc,skipped_slots,job_id) VALUES(?,?,?,?,?)", [$key, $nowUtc, $nowUtc, 0, $job['jobId']]);
            return $this->result($week, $local, $job['jobId'], true);
        });
    }

    private function fixture(string $week, string $nowUtc, \DateTimeImmutable $local): array
    {
        if (isset($this->fixtureWeeks[$week])) return array_replace($this->fixtureWeeks[$week], ['created' => false]);
        $job=($this->owner)([
            'jobType'=>'weekly-fkr-report.generate','payloadVersion'=>1,
            'idempotencyKey'=>self::KEY_PREFIX.$week,
            'payload'=>['reportWeek'=>$week,'generatedAtUtc'=>$nowUtc],
        ]);
        return $this->fixtureWeeks[$week] = $this->result($week, $local, (int)$job['jobId'], (bool)$job['created']);
    }

    private function result(string $week, \DateTimeImmutable $local, int $jobId, bool $created): array
    {
        return ['due' => true, 'reportWeek' => $week, 'planStart' => $week,
            'planEnd' => $local->modify('+6 days')->format('Y-m-d'),
            'progressStart' => $local->modify('-7 days')->format('Y-m-d'),
            'progressEnd' => $local->modify('-1 day')->format('Y-m-d'),
            'jobId' => $jobId, 'created' => $created];
    }

    private function uuid(string $key): string
    {
        $hex = substr(hash('sha256', $key), 0, 32); $hex[12] = '4'; $hex[16] = dechex((hexdec($hex[16]) & 3) | 8);
        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);
    }
}
