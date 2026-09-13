<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;
final class ProductionStandBackupDriver implements StandBackupDriver
{
 public function capture(StandRestoreAuthorization $a):array
 { $attestor=new ProductionStandRestoreDriver();if($attestor->preflight($a)!=='VERIFIED')throw new \RuntimeException();$password=trim(StandBackupFilesystem::regularBytes((string)$a->value('credential_files')['database']));$c=['docker','compose','--project-name',(string)$a->value('project'),'--file',(string)$a->value('compose_file')];$db=$this->run([...$c,'exec','-T','-e','MYSQL_PWD',(string)$a->value('services')['database'],'mariadb-dump','--single-transaction','--routines','--triggers','--events','--hex-blob','-u',(string)$a->value('database_user'),(string)$a->value('database')],'',['MYSQL_PWD'=>$password]);if($attestor->preflight($a)!=='VERIFIED')throw new \RuntimeException();$art=$this->archive($a,'artifacts','/state/fmonitor2/artifacts');if($attestor->preflight($a)!=='VERIFIED')throw new \RuntimeException();$sessions=$this->archive($a,'sessions','/state/fmonitor2/sessions');return['database.sql'=>$db,'artifacts.tar'=>$art,'sessions.json'=>$sessions]; }
 private function archive(StandRestoreAuthorization $a,string $role,string $root):string{return$this->run(['docker','run','--rm','--network','none','--mount','type=volume,source='.$a->value('volumes')[$role]['name'].',target=/state,readonly',(string)$a->value('image'),'tar','-cpf','-','-C',$root,'.']);}
 private function run(array$argv,string$stdin='',array$env=[]):string{$pipes=[];$p=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,array_merge($_ENV,$env));if(!is_resource($p))throw new \RuntimeException();fwrite($pipes[0],$stdin);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[2]);if(proc_close($p)!==0)throw new \RuntimeException();return$out;}
}
