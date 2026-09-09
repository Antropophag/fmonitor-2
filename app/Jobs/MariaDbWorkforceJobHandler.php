<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
use FMonitor2\InstallationProcess\MariaDbWorkforceSynchronization;
use FMonitor2\Workforce\BitrixWorkforceDeliveryClient;

final class MariaDbWorkforceJobHandler
{
    public function __construct(private MariaDbWorkforceSynchronization $owner, private BitrixWorkforceDeliveryClient $delivery) {}
    public function handle(array $job): array
    {
        if (($job['jobType']??null)!=='workforce.sync'||($job['payloadVersion']??null)!==1||!is_string($job['jobIdentity']??null)||!is_int($job['attempt']??null))return['status'=>'permanent','failureCode'=>'JOB_PAYLOAD_INVALID'];
        $result=$this->owner->runForJob($this->delivery,$job['jobIdentity'],$job['attempt']);
        if(($result['status']??null)==='completed')return$result;
        return['status'=>'retryable','failureCode'=>(string)($result['reason']??'WORKFORCE_SYNC_FAILED')];
    }
}
