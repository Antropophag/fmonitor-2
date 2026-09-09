<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Closed, side-effect-free parser for the worker-to-handler process boundary. */
final class JobHandlerClaim
{
    public static function decode(string $input): array
    {
        if(strlen($input)>65535)throw new \InvalidArgumentException();
        try{$job=json_decode($input,true,32,JSON_THROW_ON_ERROR);}catch(\Throwable){throw new \InvalidArgumentException();}
        if(!is_array($job))throw new \InvalidArgumentException();JobValues::keys($job,['jobId','jobIdentity','jobType','payloadVersion','payload','attempt','leaseToken','leasedAtUtc','leaseExpiresAtUtc']);
        JobValues::number($job['jobId']);JobValues::text($job['jobIdentity'],'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D');JobValues::number($job['attempt'],1,5);JobValues::text($job['leaseToken'],'/^[0-9a-f]{64}$/D');JobValues::date($job['leasedAtUtc']);JobValues::date($job['leaseExpiresAtUtc']);
        if($job['jobType']==='workforce.sync'&&$job['payloadVersion']===1){$payload=$job['payload'];if(!is_array($payload))throw new \InvalidArgumentException();JobValues::keys($payload,['scheduleSlot','dueAtUtc','runIdentity']);JobValues::text($payload['scheduleSlot'],'/^\d{4}-\d{2}-\d{2}T\d{2}$/D');JobValues::date($payload['dueAtUtc']);JobValues::text($payload['runIdentity'],'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D');return$job;}
        if($job['jobType']==='outbox.dispatch'&&$job['payloadVersion']===1&&is_array($job['payload'])){JobValues::keys($job['payload'],['intentId']);JobValues::number($job['payload']['intentId']);return$job;}
        throw new \InvalidArgumentException();
    }
}
