<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class StandRestoreReconciliationAuthorization
{
    private function __construct(private array $value, private string $digest) {}

    public static function load(string $path,string $reconciliationId,string $priorOperationId,array $target):self
    {
        $real=realpath($path);$stat=$real===false?false:@lstat($path);
        if($real===false||$real!==$path||is_link($path)||$stat===false||($stat['mode']&0170000)!==0100000)throw new \RuntimeException();
        $bytes=StandBackupFilesystem::regularBytes($path);$value=json_decode($bytes,true,512,JSON_THROW_ON_ERROR);
        $keys=['authorization_id','bundle_digest','disposable','expires_at','image','lease_digest','observed','prior_operation_id','reconciliation_id','rollback_bundle_digest','runtime','scope','source','target_digest','unknown_record_digest','version'];
        if(!self::keys($value,$keys)||StandBackupFilesystem::canonical($value)!==$bytes||$value['version']!==1||$value['scope']!=='disposable-stand-restore-reconcile-unknown'||!is_string($value['authorization_id'])||preg_match('/^[A-Za-z0-9._-]{8,120}$/D',$value['authorization_id'])!==1||$value['disposable']!==true)throw new \RuntimeException();
        if(getenv('FMONITOR_STAND_RECONCILE_TEST_MODE')==='1'&&$value['authorization_id']!=='owner-test-reconcile-exact')throw new \RuntimeException();
        if($value['reconciliation_id']!==$reconciliationId||$value['prior_operation_id']!==$priorOperationId||!StandBackupBundle::uuid($reconciliationId)||!StandBackupBundle::uuid($priorOperationId))throw new \RuntimeException();
        foreach(['target_digest','bundle_digest','rollback_bundle_digest','lease_digest','unknown_record_digest']as$key)if(!StandBackupBundle::hex($value[$key]??null))throw new \RuntimeException();
        if($value['target_digest']!==$target['target_digest']||$value['source']!==$target['manifest']['source']||$value['image']!==$target['manifest']['image'])throw new \RuntimeException();
        if(!is_string($value['expires_at'])||strtotime($value['expires_at'])===false||strtotime($value['expires_at'])<=time())throw new \RuntimeException();
        if(!self::keys($value['runtime'],['artifact_volume_path','process_table_prefix','session_volume_path'])||!StandRuntimeConfiguration::validAuthorizationValue($value['runtime'])||!self::keys($value['observed'],['database_id','network_id','project_id','volumes']))throw new \RuntimeException();
        if(getenv('FMONITOR_STAND_RECONCILE_TEST_MODE')==='1'&&$value['runtime']!==['artifact_volume_path'=>'artifacts','process_table_prefix'=>'test_','session_volume_path'=>'yii-sessions'])throw new \RuntimeException();
        return new self($value,StandBackupFilesystem::digest($bytes));
    }
    public function value(string$key):mixed{return$this->value[$key]??null;}
    public function digest():string{return$this->digest;}
    private static function keys(mixed$value,array$wanted):bool{if(!is_array($value)||array_is_list($value))return false;$keys=array_keys($value);sort($keys);sort($wanted);return$keys===$wanted;}
}
