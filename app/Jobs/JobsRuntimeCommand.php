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
        $instance=in_array($request->mode,['worker','scheduler','health','process-health'],true)?$config->instance():null;
        if($instance!==null){$config->erpEquipmentFacts();$config->erpEquipmentFactsHmacKey();}
        $db=MariaDbJobsConnection::open($config);
        try{
            $prefix=$config->prefix();if(!JobsSchemaMigration::isReady($db,$prefix))throw new \RuntimeException('JOBS_UNAVAILABLE');
            $session=new MariaDbJobsSession($db,$prefix);$clock=$session->now(...);
            $queue=new MariaDbJobQueue($db,$prefix,null,null,['installer-utilization.capture'=>[1],'workforce.sync'=>[1],'outbox.dispatch'=>[1],'bitrix.order-document-links.sync'=>[1],'weekly-fkr-report.generate'=>[1],'erp.equipment-facts.sync'=>[1]]);
            $operator=new MariaDbOperatorJobs($db,$prefix,static fn(string $authority): bool=>$authority==='deployment-operator');
            $options=$request->options;
            switch($request->mode){
                case 'schedule-once':$result=(new MariaDbWorkforceScheduler($db,$prefix))->tick($options['now-utc']);(new MariaDbInstallerUtilizationScheduler($db,$prefix))->tick($options['now-utc']);return[0,$result];
                case 'list-failed':return [0,$operator->listFailed(['authority'=>'deployment-operator','page'=>$options['page'],'limit'=>$options['limit']])];
                case 'retry':
                    $result=$operator->retry(['authority'=>'deployment-operator','jobId'=>$options['job-id'],'operationId'=>$options['operation-id'],'nowUtc'=>$options['now-utc']]);
                    return [match($result['status']){'created'=>0,'busy'=>75,'forbidden'=>77,default=>65},$result];
                case 'health':
                    if($config->optionalValue('FMONITOR_RUNTIME_ENV')==='production')SmtpConfiguration::fromEnvironment(getenv());
                    $result=(new MariaDbJobsHealth($db,$prefix,$clock,[
                        'workerHeartbeatId'=>'worker:'.$instance,'schedulerHeartbeatId'=>'scheduler:'.$instance,
                        'workerFreshSeconds'=>120,'schedulerFreshSeconds'=>120,'readyMaxAgeSeconds'=>300,
                    ]))->read();return [$result['ok']?0:70,$result];
                case 'process-health':
                    $result=(new MariaDbJobsHealth($db,$prefix,$clock,[
                        'workerHeartbeatId'=>'worker:'.$instance,'schedulerHeartbeatId'=>'scheduler:'.$instance,
                        'workerFreshSeconds'=>120,'schedulerFreshSeconds'=>120,'readyMaxAgeSeconds'=>300,
                    ]))->readProcess();return [$result['ok']?0:70,$result];
                case 'scheduler':
                    $result=(new JobsSchedulerProcess(new MariaDbWorkforceScheduler($db,$prefix),
                        new OutboxDispatchScheduler(new MariaDbOutbox($db,$prefix),$queue),
                        new MariaDbOrderDocumentLinksScheduler($db,$prefix),
                        new MariaDbWorkerHeartbeat($db,$prefix),$clock,'scheduler:'.$instance,
                        new MariaDbWeeklyFkrScheduler($db,$prefix),new MariaDbEquipmentFactsScheduler($db,$prefix),new MariaDbInstallerUtilizationScheduler($db,$prefix)))->run();
                    return [$result['exitCode'],$result];
                case 'worker':
                    $command=static fn(array $job): array=>[PHP_BINARY,dirname(__DIR__,2).'/bin/fmonitor2-job-handler.php'];
                    $registry=['installer-utilization.capture'=>[1=>$command],'workforce.sync'=>[1=>$command],'outbox.dispatch'=>[1=>$command],'bitrix.order-document-links.sync'=>[1=>$command],'weekly-fkr-report.generate'=>[1=>$command],'erp.equipment-facts.sync'=>[1=>$command]];
                    $result=(new JobWorkerProcess($queue,new MariaDbWorkerHeartbeat($db,$prefix),$registry,$clock,
                        'worker:'.$instance,1,1_000_000,55))->run();return [$result['exitCode'],$result];
            }
            throw new \LogicException('Unsupported Jobs operation.');
        }finally{$db->close();}
    }
}
