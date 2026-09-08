<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

interface AssignmentOrderOriginalByteStreamFactory
{
    public function fromBase64(string $base64):AssignmentOrderOriginalByteStream;
}
final readonly class AssignmentOrderOriginalWorkerConfig
{
    public function __construct(
        public string $databaseDsn, public string $databaseUser, public string $databasePasswordFile,
        public string $tablePrefix, public string $privateStorageRoot, public string $safeLogFile,
        public string $clockUtc, public string $rootIdSequenceCsv, public string $revisionIdSequenceCsv,
        public string $inspectorMode, public ?string $faultPoint, public string $barrierEvent,
    ) {}
}
final class AssignmentOrderOriginalBase64StreamFactory implements AssignmentOrderOriginalByteStreamFactory
{
    public function __construct(private ?AssignmentOrderOriginalWorkerFaults $faults=null) {}
    public function fromBase64(string $base64):AssignmentOrderOriginalByteStream
    {
        if(strlen($base64)%4!==0||preg_match('/^[A-Za-z0-9+\/]*={0,2}$/D',$base64)!==1) throw new \InvalidArgumentException('Invalid worker base64.');
        $bytes=base64_decode($base64,true);
        if($bytes===false||base64_encode($bytes)!==$base64) throw new \InvalidArgumentException('Invalid worker base64.');
        return new AssignmentOrderOriginalMemoryStream($bytes,$this->faults);
    }
}
