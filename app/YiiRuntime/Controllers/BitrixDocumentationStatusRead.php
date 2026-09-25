<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;

final class BitrixDocumentationStatusRead
{
    public const PAGE_SIZE = 25;
    private const JOB_TYPE = 'bitrix.order-document-links.sync';
    private const OUTCOMES = ['completed', 'retry_scheduled', 'dead', 'expired'];
    private const FAILURE_CODES = [
        'BITRIX_ORDER_DOCUMENT_LINKS_SYNC_FAILED',
        'LEASE_EXPIRED',
        'TERMINAL_FAILURE',
        'FIRST_ATTEMPT_FAILED',
        'SECOND_ATTEMPT_FAILED',
    ];

    public function __construct(private readonly Connection $db, private readonly string $prefix)
    {
        if (preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \RuntimeException('Invalid table prefix.');
        }
    }

    public function read(int $requestedPage): array
    {
        $jobs = $this->table('fm2_jobs');
        $events = $this->table('fm2_job_events');
        $counts = (new Query())->select(['status', 'amount' => new Expression('COUNT(*)')])->from($jobs)->where(['job_type' => self::JOB_TYPE])->groupBy('status')->all($this->db);
        $summary = ['ready' => 0, 'leased' => 0, 'completed' => 0, 'dead' => 0];
        foreach ($counts as $row) {
            if (array_key_exists((string) $row['status'], $summary)) $summary[(string) $row['status']] = (int) $row['amount'];
        }

        $latest = (new Query())->select(['job_id','status','attempt','available_at_utc','lease_expires_at_utc','completed_at_utc','failure_code'])->from($jobs)->where(['job_type' => self::JOB_TYPE])->orderBy(['job_id' => SORT_DESC])->limit(1)->one($this->db);
        $queue = is_array($latest) ? $this->queueState($latest, $events) : null;
        $latestClaim = (new Query())->select(['e.event_id','e.job_id','e.attempt','started_at' => 'e.occurred_at_utc'])->from(['e' => $events])->innerJoin(['j' => $jobs], '[[j.job_id]] = [[e.job_id]]')->where(['j.job_type' => self::JOB_TYPE, 'e.event_type' => 'claimed'])->orderBy(['e.occurred_at_utc' => SORT_DESC, 'e.event_id' => SORT_DESC])->limit(1)->one($this->db);
        $latestAttempt = is_array($latestClaim) ? $this->attempt($latestClaim, $events) : null;

        $published = "CASE WHEN JSON_VALID([[j.result_json]]) THEN JSON_EXTRACT([[j.result_json]], '$.published') ELSE NULL END";
        $success = (new Query())->select(['j.job_id','finished_at' => 'e.occurred_at_utc','published' => new Expression("CAST(JSON_UNQUOTE($published) AS UNSIGNED)")])->from(['e' => $events])->innerJoin(['j' => $jobs], '[[j.job_id]] = [[e.job_id]] AND [[j.attempt]] = [[e.attempt]]')->where(['j.job_type' => self::JOB_TYPE, 'j.status' => 'completed', 'e.event_type' => 'completed'])->andWhere(new Expression("JSON_TYPE($published) = 'INTEGER' AND $published >= 0"))->orderBy(['e.occurred_at_utc' => SORT_DESC, 'e.event_id' => SORT_DESC])->limit(1)->one($this->db);

        $claims = (new Query())->from(['e' => $events])->innerJoin(['j' => $jobs], '[[j.job_id]] = [[e.job_id]]')->where(['j.job_type' => self::JOB_TYPE, 'e.event_type' => 'claimed']);
        $total = (int) $claims->count('*', $this->db);
        $page = min($requestedPage, max(1, (int) ceil($total / self::PAGE_SIZE)));
        $rows = (new Query())->select(['e.event_id','e.job_id','e.attempt','started_at' => 'e.occurred_at_utc'])->from(['e' => $events])->innerJoin(['j' => $jobs], '[[j.job_id]] = [[e.job_id]]')->where(['j.job_type' => self::JOB_TYPE, 'e.event_type' => 'claimed'])->orderBy(['e.occurred_at_utc' => SORT_DESC, 'e.event_id' => SORT_DESC])->limit(self::PAGE_SIZE)->offset(($page - 1) * self::PAGE_SIZE)->all($this->db);
        $outcomes = $this->outcomes($rows, $events);
        foreach ($rows as &$row) {
            $key = $row['job_id'].':'.$row['attempt'];
            $outcome = $outcomes[$key] ?? null;
            $row['outcome'] = $outcome['event_type'] ?? null;
            $row['finished_at'] = $outcome['occurred_at_utc'] ?? null;
            $row['failure_code'] = $this->failureCode($outcome['details_json'] ?? null);
        }
        unset($row);

        return ['available' => true, 'summary' => $summary, 'queue' => $queue, 'attempt' => $latestAttempt, 'success' => is_array($success) ? ['finished_at' => $success['finished_at'], 'published' => (int) $success['published']] : null, 'rows' => $rows, 'total' => $total, 'page' => $page];
    }

    private function queueState(array $job, string $events): array
    {
        $claim = (new Query())->select(['occurred_at_utc'])->from($events)->where(['job_id' => $job['job_id'], 'event_type' => 'claimed', 'attempt' => $job['attempt']])->orderBy(['occurred_at_utc' => SORT_DESC, 'event_id' => SORT_DESC])->limit(1)->one($this->db);
        $status = 'unknown';
        if ($job['status'] === 'ready') {
            if ((int) $job['attempt'] === 0) {
                $status = 'queued';
            } else {
                $retry = (new Query())->select(['event_id'])->from($events)->where(['job_id' => $job['job_id'], 'event_type' => 'retry_scheduled', 'attempt' => (int) $job['attempt'] - 1])->orderBy(['event_id' => SORT_DESC])->limit(1)->one($this->db);
                $status = is_array($retry) ? 'retry' : 'unknown';
            }
        }
        elseif ($job['status'] === 'completed') $status = 'completed';
        elseif ($job['status'] === 'dead') $status = 'dead';
        elseif ($job['status'] === 'leased' && is_array($claim) && is_string($job['lease_expires_at_utc']) && strtotime($job['lease_expires_at_utc']) > time()) $status = 'running';
        return ['status' => $status, 'started_at' => is_array($claim) ? $claim['occurred_at_utc'] : null, 'failure_code' => $job['status'] === 'dead' && in_array($job['failure_code'], self::FAILURE_CODES, true) ? $job['failure_code'] : null];
    }

    private function attempt(array $claim, string $events): array
    {
        $outcome = (new Query())->select(['event_type','occurred_at_utc','details_json'])->from($events)->where(['job_id' => $claim['job_id'], 'attempt' => $claim['attempt'], 'event_type' => self::OUTCOMES])->andWhere(['>', 'event_id', $claim['event_id']])->orderBy(['occurred_at_utc' => SORT_DESC, 'event_id' => SORT_DESC])->limit(1)->one($this->db);
        return [
            'job_id' => (int) $claim['job_id'],
            'attempt' => (int) $claim['attempt'],
            'started_at' => $claim['started_at'],
            'outcome' => is_array($outcome) ? $outcome['event_type'] : null,
            'finished_at' => is_array($outcome) ? $outcome['occurred_at_utc'] : null,
            'failure_code' => $this->failureCode(is_array($outcome) ? $outcome['details_json'] : null),
        ];
    }

    private function outcomes(array $claims, string $events): array
    {
        if ($claims === []) return [];
        $result = [];
        foreach ($claims as $claim) {
            $row = (new Query())->select(['job_id','attempt','event_type','occurred_at_utc','details_json'])->from($events)->where(['job_id' => $claim['job_id'], 'attempt' => $claim['attempt'], 'event_type' => self::OUTCOMES])->andWhere(['>', 'event_id', $claim['event_id']])->orderBy(['occurred_at_utc' => SORT_DESC, 'event_id' => SORT_DESC])->limit(1)->one($this->db);
            if (is_array($row)) $result[$claim['job_id'].':'.$claim['attempt']] = $row;
        }
        return $result;
    }

    private function failureCode(mixed $json): ?string
    {
        if (!is_string($json)) return null;
        try { $details = json_decode($json, true, 8, JSON_THROW_ON_ERROR); } catch (\Throwable) { return null; }
        $code = is_array($details) ? ($details['failureCode'] ?? null) : null;
        return is_string($code) && in_array($code, self::FAILURE_CODES, true) ? $code : null;
    }

    private function table(string $name): string { return $this->prefix.$name; }
}
