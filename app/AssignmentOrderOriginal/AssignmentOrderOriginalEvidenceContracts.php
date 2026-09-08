<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

final readonly class AssignmentOrderOriginalEvidenceReaderConfig
{
    public function __construct(
        public string $databaseHost, public int $databasePort, public string $databaseName,
        public string $databaseUser, public string $databasePasswordFile, public string $tablePrefix,
        public string $privateStorageRoot, public string $safeLogFile,
    ) {}
}
final class AssignmentOrderOriginalEvidenceUnavailable extends \RuntimeException
{
    public function __construct() {parent::__construct('Assignment-order original evidence unavailable.',0,null);}
}
interface AssignmentOrderOriginalEvidenceReader
{
    public function domainCanonicalJson(int $caseId,int $orderId):string;
    public function requestsCanonicalJson():string;
    public function fingerprintsCanonicalJson():string;
    public function eventsCanonicalJson():string;
    public function safeAuditsCanonicalJson():string;
    public function maintenanceRequestsCanonicalJson():string;
    public function maintenanceAuditsCanonicalJson():string;
    public function unchangedProcessCanonicalJson(int $caseId,int $orderId):string;
    public function privateBlobsCanonicalJson():string;
    public function safeLogsCanonicalJson():string;
    public function close():void;
}
