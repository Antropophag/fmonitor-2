<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/MariaDbOriginalSql.php';
require_once __DIR__.'/AssignmentOrderOriginalCommitValues.php';
require_once __DIR__.'/AssignmentOrderOriginalFreshConnection.php';
require_once __DIR__.'/MariaDbOriginalFreshTerminalReader.php';

final class AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory implements AssignmentOrderOriginalFreshTerminalReaderFactory
{
    public function __construct(private readonly AssignmentOrderOriginalFreshReaderConfig $config,
        private readonly ?AssignmentOrderOriginalPersistenceObserver $observer = null) {}

    public function open(): AssignmentOrderOriginalFreshTerminalReaderOpenResult
    {
        $db = null;
        try {
            $host = AssignmentOrderOriginalFreshConnection::host($this->config);
            $password = AssignmentOrderOriginalFreshConnection::password($this->config->databasePasswordFile);
            $db = mysqli_init();
            if (!$db instanceof \mysqli || !@$db->real_connect($host, $this->config->databaseUser, $password,
                $this->config->databaseName, $this->config->databasePort) || !$db->set_charset('utf8mb4')) AssignmentOrderOriginalSql::fail();
            return AssignmentOrderOriginalFreshTerminalReaderOpenResult::opened(
                new AssignmentOrderOriginalMariaDbFreshTerminalReader($db, $this->config->tablePrefix, $this->observer));
        } catch (\Throwable) {
            if ($db instanceof \mysqli) { try { $db->close(); } catch (\Throwable) {} }
            return AssignmentOrderOriginalFreshTerminalReaderOpenResult::unavailable();
        }
    }
}
