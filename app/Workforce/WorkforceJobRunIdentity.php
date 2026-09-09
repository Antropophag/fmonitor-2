<?php
declare(strict_types=1);
namespace FMonitor2\Workforce;

final class WorkforceJobRunIdentity
{
    public static function forAttempt(string $jobIdentity, int $attempt): string
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $jobIdentity) !== 1
            || $attempt < 1 || $attempt > 5) throw new \InvalidArgumentException('INVALID_JOB_IDENTITY');
        if ($attempt === 1) return $jobIdentity;
        $bytes = substr(hash('sha256', "workforce-job-attempt-v1\0{$jobIdentity}\0{$attempt}", true), 0, 16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20);
    }
}
