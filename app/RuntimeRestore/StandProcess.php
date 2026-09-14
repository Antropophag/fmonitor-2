<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

interface StandProcess
{
    public function run(array $argv, string $stdin='', array $env=[]): string;
}
