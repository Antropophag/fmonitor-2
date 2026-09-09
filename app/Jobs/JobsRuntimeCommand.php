<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
use FMonitor2\InstallationProcess\JobsSchemaMigration;

/** Deployment CLI composition. HTTP callers cannot provide an authority override. */
final class JobsRuntimeCommand
{
    public static function execute(array $arguments): array
    {
        if(PHP_SAPI!=='cli')throw new \InvalidArgumentException('Jobs deployment commands require CLI.');
        $request=JobsRuntimeRequest::parse($arguments);$config=JobsRuntimeConfiguration::fromEnvironment();
        $instance=in_array($request->mode,['worker','scheduler','health'],true)?$config->instance():null;
        $db=MariaDbJobsConnection::open($config);
        try{
            $prefix=$config->prefix();if(!JobsSchemaMigration::isReady($db,$prefix))throw new \RuntimeException('JOBS_UNAVAILABLE');
            $session=new MariaDbJobsSession($db,$prefix);$clock=$session->now(...);
            $queue=new MariaDbJobQueue($db,$prefix,null,null,['workforce.sync'=>[1],'outbox.dispatch'=>[1]]);
            $operator=new MariaDbOperatorJobs($db,$prefix,static fn(string $authority): bool=>$authority==='deployment-operator');
            $options=$request->options;
            switch($request->mode){
                case 'schedule-once':return [0,(new MariaDbWorkforceScheduler($db,$prefix))->tick($options['now-utc'])];
                case 'list-failed':return [0,$operator->listFailed(['authority'=>'deployment-operator','page'=>$options['page'],'limit'=>$options['limit']])];
                case 'retry':
                    $result=$operator->retry(['authority'=>'deployment-operator','jobId'=>$options['job-id'],'operationId'=>$options['operation-id'],'nowUtc'=>$options['now-utc']]);
                    return [match($result['status']){'created'=>0,'busy'=>75,'forbidden'=>77,default=>65},$result];
                case 'health':
                    $result=(new MariaDbJobsHealth($db,$prefix,$clock,[
                        'workerHeartbeatId'=>'worker:'.$instance,'schedulerHeartbeatId'=>'scheduler:'.$instance,
                        'workerFreshSeconds'=>120,'schedulerFreshSeconds'=>120,'readyMaxAgeSeconds'=>300,
                    ]))->read();return [$result['ok']?0:70,$result];
                case 'scheduler':
                    $result=(new JobsSchedulerProcess(new MariaDbWorkforceScheduler($db,$prefix),
                        new OutboxDispatchScheduler(new MariaDbOutbox($db,$prefix),$queue),
                        new MariaDbWorkerHeartbeat($db,$prefix),$clock,'scheduler:'.$instance))->run();
                    return [$result['exitCode'],$result];
                case 'worker':
                    $command=static fn(array $job): array=>[PHP_BINARY,dirname(__DIR__,2).'/bin/fmonitor2-job-handler.php'];
                    $registry=['workforce.sync'=>[1=>$command],'outbox.dispatch'=>[1=>$command]];
                    $result=(new JobWorkerProcess($queue,new MariaDbWorkerHeartbeat($db,$prefix),$registry,$clock,
                        'worker:'.$instance,1,1_000_000,55))->run();return [$result['exitCode'],$result];
            }
            throw new \LogicException('Unsupported Jobs operation.');
        }finally{$db->close();}
    }
}
