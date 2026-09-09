<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Native direct connection shared by deployment CLI compositions, not by child processes. */
final class MariaDbJobsConnection
{
    public static function open(JobsRuntimeConfiguration $config): \mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=mysqli_init();
        try{
            $db->options(MYSQLI_OPT_CONNECT_TIMEOUT,3);$db->options(MYSQLI_OPT_READ_TIMEOUT,5);
            @$db->real_connect($config->value('FMONITOR_DB_HOST'),$config->value('FMONITOR_DB_USER'),
                $config->value('FMONITOR_DB_PASSWORD'),$config->value('FMONITOR_DB_NAME'),$config->port());
            $db->set_charset('utf8mb4');return $db;
        }catch(\Throwable){try{$db->close();}catch(\Throwable){}throw new \RuntimeException('JOBS_UNAVAILABLE');}
    }
}
