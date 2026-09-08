<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderApplicationSchemaMigration
{
    public static function apply(\mysqli $db,string $prefix='',?AssignmentOrderApplicationSchemaObserver $observer=null):array
    {
        self::prefix($prefix);$database=self::configuration($db);$name='fm2_aoas_'.substr(hash('sha256',$database."\0".$prefix),0,48);
        $owned=false;$failed=false;$result=null;
        try{$pre=self::scalar($db,'SELECT IS_USED_LOCK(?) n',[$name]);if($pre['n']!==null&&(int)$pre['n']===$db->thread_id)throw new \RuntimeException();
            if((string)self::scalar($db,'SELECT GET_LOCK(?,5) n',[$name])['n']!=='1')throw new \RuntimeException();$owned=true;
            $observer?->observe(AssignmentOrderApplicationSchemaPhase::LOCK_ACQUIRED);$state=self::state($db,$prefix);
            if($state==='conflict')$result=['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT'];
            else{$collation=(string)self::scalar($db,'SELECT DEFAULT_COLLATION_NAME n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')['n'];$applied=false;
                if($state==='absent'){if(!$db->query(AssignmentOrderApplicationDefinitionSchemaMigration::applications($prefix,$collation)))throw new \RuntimeException();$applied=true;$observer?->observe(AssignmentOrderApplicationSchemaPhase::APPLICATIONS_CREATED);}
                if($state==='absent'||$state==='prefix'){if(!$db->query(AssignmentOrderApplicationDefinitionSchemaMigration::attempts($prefix,$collation)))throw new \RuntimeException();$applied=true;$observer?->observe(AssignmentOrderApplicationSchemaPhase::ATTEMPTS_CREATED);}
                if(self::state($db,$prefix)!=='ready')throw new \RuntimeException();$observer?->observe(AssignmentOrderApplicationSchemaPhase::FAMILY_VERIFIED);$result=['applied'=>$applied];}
        }catch(\Throwable){$failed=true;}finally{if($owned)try{if((string)self::scalar($db,'SELECT RELEASE_LOCK(?) n',[$name])['n']!=='1')$failed=true;}catch(\Throwable){$failed=true;}}
        if($failed||$result===null)throw new AssignmentOrderApplicationSchemaUnavailable();return$result;
    }
    public static function isReady(\mysqli $db,string $prefix=''):bool
    { self::prefix($prefix);try{self::configuration($db);return self::state($db,$prefix)==='ready';}catch(\Throwable){return false;} }
    private static function state(\mysqli$db,string$p):string
    {
        $a=self::exists($db,$p.'fm2_assignment_order_applications');$b=self::exists($db,$p.'fm2_assignment_application_attempts');
        if(!$a&&!$b)return'absent';if(!$a&&$b)return'conflict';if(!self::shape($db,$p.'fm2_assignment_order_applications',22))return'conflict';
        if(!$b){$count=(int)self::scalar($db,'SELECT COUNT(*) n FROM `'.$p.'fm2_assignment_order_applications`')['n'];return$count===0?'prefix':'conflict';}
        return self::shape($db,$p.'fm2_assignment_application_attempts',9)?'ready':'conflict';
    }
    private static function configuration(\mysqli$db):string
    {if($db->character_set_name()!=='utf8mb4'||(string)self::scalar($db,'SELECT @@in_transaction n')['n']!=='0'||(string)self::scalar($db,'SELECT @@autocommit n')['n']!=='1')throw new \RuntimeException();$n=self::scalar($db,'SELECT DATABASE() n')['n'];if(!is_string($n)||$n==='')throw new \RuntimeException();return$n;}
    private static function shape(\mysqli$db,string$t,int$n):bool
    {$r=self::scalar($db,'SELECT COUNT(*) n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$t]);$p=self::scalar($db,'SELECT ENGINE n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$t]);return(int)$r['n']===$n&&$p['n']==='InnoDB';}
    private static function exists(\mysqli$db,string$t):bool{return(string)self::scalar($db,'SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$t])['n']==='1';}
    private static function scalar(\mysqli$db,string$q,array$v=[]):array{$s=$db->prepare($q);if(!$s||!$s->execute($v))throw new \RuntimeException();try{$r=$s->get_result();$x=$r?->fetch_assoc();if(!$x)throw new \RuntimeException();return$x;}finally{$s->close();}}
    private static function prefix(string$p):void{if(PHP_INT_SIZE!==8||preg_match('/^[A-Za-z0-9_]{0,25}$/D',$p)!==1)throw new AssignmentOrderApplicationSchemaUnavailable();}
}
