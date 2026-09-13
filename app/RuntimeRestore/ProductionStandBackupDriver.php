<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;
final class ProductionStandBackupDriver implements StandBackupDriver
{
 public function __construct(private ?StandRuntimeConfiguration$config=null,private ?StandProcess$process=null){$this->config??=StandRuntimeConfiguration::fromEnvironment();$this->process??=new NativeStandProcess();}
 public function capture(StandRestoreAuthorization $a):array
 { $attestor=new ProductionStandRestoreDriver($this->config,$this->process);if($attestor->preflight($a)!=='VERIFIED')throw new \RuntimeException();$password=trim(StandBackupFilesystem::regularBytes((string)$a->value('credential_files')['database']));$c=['docker','compose','--project-name',(string)$a->value('project'),'--file',(string)$a->value('compose_file')];$db=$this->process->run([...$c,'exec','-T','-e','MYSQL_PWD',(string)$a->value('services')['database'],'mariadb-dump','--single-transaction','--routines','--triggers','--events','--hex-blob','-u',(string)$a->value('database_user'),(string)$a->value('database')],'',['MYSQL_PWD'=>$password]);if($attestor->preflight($a)!=='VERIFIED')throw new \RuntimeException();$art=$this->archive($a,'artifacts',$this->config->artifactVolumePath());if($attestor->preflight($a)!=='VERIFIED')throw new \RuntimeException();$sessions=$this->archive($a,'sessions',$this->config->sessionVolumePath());return['database.sql'=>$db,'artifacts.tar'=>$art,'sessions.json'=>$sessions]; }
 private function archive(StandRestoreAuthorization $a,string $role,string $path):string{return$this->process->run(['docker','run','--rm','--network','none','--mount','type=volume,source='.$a->value('volumes')[$role]['name'].',target=/state,readonly',(string)$a->value('image'),'tar','-cpf','-','-C','/state/'.$path,'.']);}
}
