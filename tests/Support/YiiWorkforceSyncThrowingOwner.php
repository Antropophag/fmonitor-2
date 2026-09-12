<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class MariaDbWorkforceSynchronization
{
    public function __construct(\mysqli $connection,string $prefix){}
    public function run(object $delivery,string $run):array{throw new \RuntimeException('PRIVATE_WORKFORCE_THROWABLE SELECT private_sql');}
}
