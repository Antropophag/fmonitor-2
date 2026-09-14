<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;
final class RecordingStandBackupDriver implements StandBackupDriver{public function capture(StandRestoreAuthorization $authorization):array{return['database.sql'=>'','artifacts.tar'=>'','sessions.json'=>''];}}
