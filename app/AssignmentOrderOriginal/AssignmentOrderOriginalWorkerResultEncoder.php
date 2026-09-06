<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

final class AssignmentOrderOriginalWorkerEncodingUnavailable extends \RuntimeException
{
    public function __construct(){parent::__construct('Assignment-order original worker encoding unavailable.',0,null);}
}
final class AssignmentOrderOriginalWorkerResultEncoder
{
    public static function encode(AssignmentOrderOriginalResult $result):string
    {
        try {
            $line=json_encode(['status'=>$result->status()->value,'reasonCode'=>$result->reasonCode()?->value,
                'retryable'=>$result->retryable(),'requestId'=>$result->requestId(),'rootOriginalId'=>$result->rootOriginalId(),
                'currentRevisionId'=>$result->currentRevisionId(),'revisionNumber'=>$result->revisionNumber(),
                'documentDate'=>$result->documentDate(),'sha256'=>$result->sha256(),'byteSize'=>$result->byteSize(),
                'uploadedAt'=>$result->uploadedAt()],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
            if(strlen($line)>16384)throw new \RuntimeException();
            return $line;
        } catch(\Throwable){throw new AssignmentOrderOriginalWorkerEncodingUnavailable();}
    }
}
