<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/autoload.php';

use FMonitor2\IdentityAccess\MariaDbWeeklyFkrRecipientDirectory;
use FMonitor2\InstallationProcess\MariaDbWeeklyFkrReportSource;
use FMonitor2\Jobs\{JobsRuntimeConfiguration,MariaDbJobsConnection,MariaDbWeeklyFkrOpeningEligibility,SmtpConfiguration,SmtpTransport,WeeklyFkrReportBuilder,WeeklyFkrReportRenderer};

if(PHP_SAPI!=='cli'||$argc!==2||$argv[1]!=='--send'){
    fwrite(STDERR,"Explicit --send is required.\n");exit(64);
}
$identity=getenv('FMONITOR_WEEKLY_TEST_USER_ID');
if(!is_string($identity)||preg_match('/^[1-9]\d*$/D',$identity)!==1){fwrite(STDERR,"FMONITOR_WEEKLY_TEST_USER_ID is required.\n");exit(64);}
$config=JobsRuntimeConfiguration::fromEnvironment();$db=MariaDbJobsConnection::open($config);
try{
    $directory=new MariaDbWeeklyFkrRecipientDirectory($db,$config->prefix());
    $source=new MariaDbWeeklyFkrReportSource($db,$config->prefix(),$config->value('FMONITOR_LEGACY_TABLE_PREFIX'));
    $instant=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
    $report=(new WeeklyFkrReportBuilder($directory,$source,new MariaDbWeeklyFkrOpeningEligibility($db,$config->prefix())))->build($identity,$instant,$config->value('FMONITOR_PUBLIC_BASE_URL'));
    $message=(new WeeklyFkrReportRenderer())->render($report);$message['recipientIdentity']=$identity;
    $result=(new SmtpTransport(SmtpConfiguration::fromEnvironment(getenv()),$directory->recipient(...)))->send($message);
    echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),"\n";exit($result['status']==='delivered'?0:70);
}finally{$db->close();}
