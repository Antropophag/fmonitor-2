<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class EngineerSnapshot
{ public function __construct(public int $userId,public string $fio,public string $position) {} }
