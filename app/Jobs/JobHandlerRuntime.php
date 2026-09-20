<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
use FMonitor2\YiiRuntime\WorkforceSyncConsole;
use FMonitor2\YiiRuntime\Commands\BitrixOrderDocumentLinksSyncController;

final class JobHandlerRuntime
{
    public static function handle(array $job, JobsRuntimeConfiguration $config, ?callable $documentSync=null): array
    {
        if ($job['jobType'] === 'outbox.dispatch') return self::deliverOutbox($job, $config);
        if ($job['jobType'] === 'weekly-fkr-report.generate') return self::generateWeekly($job, $config);
        if ($job['jobType'] === 'bitrix.order-document-links.sync') {
            try { $result=$documentSync===null?BitrixOrderDocumentLinksSyncController::runJob($config):$documentSync(); }
            catch (\Throwable) { return ['status'=>'retryable','failureCode'=>'BITRIX_ORDER_DOCUMENT_LINKS_SYNC_FAILED']; }
            if (($result['status'] ?? null) === 'published') return ['status'=>'completed','result'=>['published'=>(int)($result['count']??0)]];
            return ['status'=>'retryable','failureCode'=>'BITRIX_ORDER_DOCUMENT_LINKS_SYNC_FAILED'];
        }
        $result=WorkforceSyncConsole::runJob($job,$config);
        if ($result['status']==='completed') return ['status'=>'completed','result'=>['delivered'=>(int)$result['delivered']]];
        $code=(string)($result['failureCode']??'WORKFORCE_SYNC_FAILED');
        return ['status'=>'retryable','failureCode'=>preg_match('/^[A-Z][A-Z0-9_]{0,63}$/D',$code)===1?$code:'WORKFORCE_SYNC_FAILED'];
    }

    public static function mapEmailTransportOutcome(array $result): array
    {
        return match($result['status']??null){
            'delivered'=>['status'=>'delivered','providerReference'=>$result['providerReference']??null],
            'permanent'=>['status'=>'permanent','failureCode'=>$result['failureCode']??'TRANSPORT_REJECTED'],
            'transient'=>['status'=>'retryable','failureCode'=>$result['failureCode']??'TRANSPORT_UNAVAILABLE'],
            'unknown'=>['status'=>'permanent','failureCode'=>'UNKNOWN_DELIVERY'],
            default=>['status'=>'ambiguous_retryable','failureCode'=>'TRANSPORT_RESULT_INVALID'],
        };
    }

    private static function generateWeekly(array $job,JobsRuntimeConfiguration $config): array
    {
        $db=MariaDbJobsConnection::open($config);
        try {
            $prefix=$config->prefix();
            $directory=new MariaDbWeeklyFkrRecipientDirectory($db,$prefix);
            $source=new MariaDbWeeklyFkrReportSource($db,$prefix,$config->value('FMONITOR_LEGACY_TABLE_PREFIX'));
            $builder=new WeeklyFkrReportBuilder($directory,$source,new MariaDbWeeklyFkrOpeningEligibility($db,$prefix));
            $renderer=new WeeklyFkrReportRenderer();$outbox=new MariaDbOutbox($db,$prefix);$created=0;
            foreach ($directory->eligibleIdentities() as $identity) {
                $report=$builder->build($identity,$job['payload']['generatedAtUtc'],$config->value('FMONITOR_PUBLIC_BASE_URL'));
                $message=$renderer->render($report);$db->begin_transaction();
                try {
                    $result=$outbox->append([
                        'eventId'=>'weekly-fkr-report/v1/'.$job['payload']['reportWeek'].'/'.$identity,
                        'channel'=>'email','template'=>'weekly-fkr-report','version'=>1,
                        'data'=>['recipientIdentity'=>$identity]+$message,
                    ]);
                    $db->commit();if($result['status']==='created')$created++;
                } catch (\Throwable $error) { $db->rollback();throw $error; }
            }
            return ['status'=>'completed','result'=>['intentsCreated'=>$created]];
        } catch (\Throwable) { return ['status'=>'retryable','failureCode'=>'WEEKLY_FKR_REPORT_FAILED']; }
        finally { $db->close(); }
    }

    private static function deliverOutbox(array $job,JobsRuntimeConfiguration $config): array
    {
        try { $smtp=SmtpConfiguration::fromEnvironment(getenv()); }
        catch (\RuntimeException) { return ['status'=>'permanent','failureCode'=>'OUTBOX_TRANSPORT_UNCONFIGURED']; }
        $db=MariaDbJobsConnection::open($config);
        try {
            $directory=new MariaDbWeeklyFkrRecipientDirectory($db,$config->prefix());
            $transport=new SmtpTransport($smtp,$directory->recipient(...));
            $handler=new OutboxDeliveryHandler(new MariaDbOutbox($db,$config->prefix()),static function(array $intent) use ($transport): array {
                if ($intent['channel']!=='email'||$intent['template']!=='weekly-fkr-report') return ['status'=>'permanent','failureCode'=>'TRANSPORT_REJECTED'];
                return self::mapEmailTransportOutcome($transport->send($intent['data']));
            },(new MariaDbJobsSession($db,$config->prefix()))->now(...));
            $result=$handler->handle($job);
            if ($result['status']==='delivered') return ['status'=>'completed','result'=>['outcome'=>'delivered','providerReference'=>$result['providerReference']??null]];
            return ['status'=>$result['status']==='permanent'?'permanent':'retryable','failureCode'=>$result['failureCode']??'TRANSPORT_UNAVAILABLE'];
        } catch (\Throwable) { return ['status'=>'retryable','failureCode'=>'TRANSPORT_UNAVAILABLE']; }
        finally { $db->close(); }
    }
}
