<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class StandRestoreAuthorization
{
    private function __construct(private array $value, private string $digest) {}

    public static function load(string $path, string $operation, string $bundle, string $target,?array $manifest=null): self
    {return self::parse($path,$operation,$bundle,$target,'disposable-stand-restore',$manifest);}
    public static function loadForBackup(string $path,string $operation,string $target,?array $manifest=null):self
    {return self::parse($path,$operation,null,$target,'disposable-stand-backup',$manifest);}
    private static function parse(string $path,string $operation,?string $bundle,string $target,string $scope,?array $manifest):self
    {
        $real=realpath($path); $stat=$real===false?false:@lstat($path);
        if($real===false||$real!==$path||is_link($path)||$stat===false||($stat['mode']&0170000)!==0100000)throw new \RuntimeException();
        $bytes=StandBackupFilesystem::regularBytes($path);$v=json_decode($bytes,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($v)||array_is_list($v)||StandBackupFilesystem::canonical($v)!==$bytes)throw new \RuntimeException();
        $base=['authorization_id','bundle_digest','compose_file','credential_files','database','disposable','evidence_root','expires_at','health','image','observed','operation_id','project','scope','services','source','target_digest','version','volumes'];$production=[...$base,'database_user','expected','golden','runtime'];$keys=array_keys($v);sort($keys);sort($base);sort($production);if($keys!==$base&&$keys!==$production)throw new \RuntimeException();
        if($v['version']!==1||$v['scope']!==$scope||$v['disposable']!==true||$v['operation_id']!==$operation||($bundle!==null&&$v['bundle_digest']!==$bundle)||$v['target_digest']!==$target||!StandBackupBundle::uuid($operation)||($bundle!==null&&!StandBackupBundle::hex($bundle))||!StandBackupBundle::hex($target)||!preg_match('/^[A-Za-z0-9._-]{8,120}$/D',(string)$v['authorization_id']))throw new \RuntimeException();
        if(!is_string($v['expires_at'])||strtotime($v['expires_at'])===false||strtotime($v['expires_at'])<=time()||!preg_match('/^[0-9a-f]{40}$/D',(string)$v['source'])||!preg_match('/^fmonitor2-runtime@sha256:[0-9a-f]{64}$/D',(string)$v['image']))throw new \RuntimeException();
        $compose=realpath((string)$v['compose_file']);$expected=realpath(dirname(__DIR__,2).'/deploy/runtime/compose.yaml');
        if($compose===false||$compose!==$v['compose_file']||$compose!==$expected||!str_starts_with((string)$v['project'],'fm2-disposable-')||!str_starts_with((string)$v['database'],'fm2_disposable_'))throw new \RuntimeException();
        if(!self::keys($v['observed'],['database_container_id','network_id','project_id'])||!self::keys($v['services'],['database','php','scheduler','web','worker'])||!self::keys($v['volumes'],['artifacts','database','secrets','sessions'])||!self::keys($v['credential_files'],['database'])||!self::keys($v['health'],['live','ready']))throw new \RuntimeException();
        foreach(['database','php','web','worker','scheduler']as$key)if(!is_string($v['services'][$key]??null)||$v['services'][$key]==='')throw new \RuntimeException();
        foreach(['database','artifacts','sessions','secrets']as$key)if(!is_array($v['volumes'][$key]??null)||!is_string($v['volumes'][$key]['name']??null)||!is_string($v['volumes'][$key]['observed_id']??null))throw new \RuntimeException();
        foreach($v['observed']as$x)if(!is_string($x)||$x==='')throw new \RuntimeException();
        foreach($v['credential_files']as$p){$r=is_string($p)?realpath($p):false;$s=$r===false?false:@lstat($p);if($r===false||$r!==$p||is_link($p)||$s===false||($s['mode']&0170000)!==0100000||($s['mode']&0077)!==0)throw new \RuntimeException();}
        foreach($v['health']as$url)if(!is_string($url)||preg_match('#^http://127\.0\.0\.1:[1-9][0-9]{0,4}/health/(live|ready)$#D',$url)!==1)throw new \RuntimeException();
        if($keys===$production&&!StandRuntimeConfiguration::validAuthorizationValue($v['runtime']??null))throw new \RuntimeException();
        if($keys===$production){if(preg_match('/^[A-Za-z0-9_]{1,64}$/D',(string)$v['database'])!==1||preg_match('/^[A-Za-z0-9_]{1,64}$/D',(string)$v['database_user'])!==1||!is_array($v['golden'])||count($v['golden'])<1||!self::keys($v['expected'],['artifact','history_rows','job_lease_rows','jobs_rows','next_id','outbox_rows','recovery_rows','schema_sha256','sentinel_rows','session']))throw new \RuntimeException();foreach($v['golden']as$g)if(!self::keys($g,['sha256','url'])||!StandBackupBundle::hex($g['sha256']??null)||!is_string($g['url'])||!str_starts_with($g['url'],'http://127.0.0.1:'))throw new \RuntimeException();foreach(['artifact','session']as$r){$x=$v['expected'][$r];$wanted=$r==='artifact'?['mode','path','sha256']:['path','sha256'];if(!self::keys($x,$wanted)||preg_match('#^[A-Za-z0-9._/-]+$#D',(string)$x['path'])!==1||str_contains($x['path'],'..')||!StandBackupBundle::hex($x['sha256']??null))throw new \RuntimeException();}foreach(['sentinel_rows','history_rows','jobs_rows','outbox_rows','job_lease_rows','recovery_rows']as$k)if(!is_string($v['expected'][$k]))throw new \RuntimeException();if(!is_int($v['expected']['next_id'])||$v['expected']['next_id']<1||!StandBackupBundle::hex($v['expected']['schema_sha256']))throw new \RuntimeException();}
        if($manifest!==null){if($v['source']!==$manifest['source']||$v['image']!==$manifest['image']||$v['compose_file']!==$manifest['compose_file']||$v['project']!==$manifest['project']||$v['database']!==$manifest['database']['name']||$v['evidence_root']!==$manifest['evidence_root'])throw new \RuntimeException();foreach(['database','artifacts','sessions']as$role)if($v['volumes'][$role]!==$manifest['volumes'][$role])throw new \RuntimeException();}
        return new self($v,StandBackupFilesystem::digest($bytes));
    }
    public function digest():string{return $this->digest;}
    public function value(string $key):mixed{return $this->value[$key]??null;}
    private static function keys(mixed$value,array$wanted):bool{if(!is_array($value)||array_is_list($value))return false;$keys=array_keys($value);sort($keys);sort($wanted);return$keys===$wanted;}
}
