<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;
interface StandBackupDriver { public function capture(StandRestoreAuthorization $authorization):array; }
