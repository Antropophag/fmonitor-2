<?php
declare(strict_types=1);

use FMonitor2\InstallationProcess\PilotCaseCommitOutcomeUnknown;
use FMonitor2\InstallationProcess\PilotCaseImporter;
use FMonitor2\InstallationProcess\PilotCaseSchemaUnavailable;

spl_autoload_register(static function(string $class):void{
    $prefix='FMonitor2\\InstallationProcess\\';
    if(!str_starts_with($class,$prefix)) {
        return;
    }
    $path=dirname(__DIR__).'/app/InstallationProcess/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_file($path)) {
        require $path;
    }
});

final class PilotCaseImportCliRunner
{
    private const ENV_NAMES=['FMONITOR_DB_HOST','FMONITOR_DB_PORT','FMONITOR_DB_NAME','FMONITOR_DB_USER','FMONITOR_DB_PASSWORD','FMONITOR_PROCESS_TABLE_PREFIX','FMONITOR_LEGACY_TABLE_PREFIX'];

    /** @param list<string> $arguments */
    public function run(array $arguments):never
    {
        $environment=$this->environment();
        $selected=$this->selectedIds($arguments);

        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        try{$connection=$this->connect($environment);}
        catch(Throwable){self::finish(['ok'=>false,'reason'=>'DATABASE_UNAVAILABLE'],69);}

        $timestamp=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d\TH:i:sP');
        $importer=new PilotCaseImporter($connection,$environment['FMONITOR_PROCESS_TABLE_PREFIX'],$environment['FMONITOR_LEGACY_TABLE_PREFIX']);
        try{$importer->assertSchemaAvailable();}
        catch(Throwable){self::close($connection);self::finish(['ok'=>false,'reason'=>'SCHEMA_UNAVAILABLE'],78);}

        for($attempt=0;$attempt<3;$attempt++){
            try{
                $result=$importer->import($selected,$timestamp);
                self::close($connection);
                if(isset($result['rejected'])) {
                    self::finish(['ok'=>false,'reason'=>'PILOT_CASES_NOT_ELIGIBLE','rejected'=>$result['rejected']],2);
                }
                self::finish(['ok'=>true,'selected'=>$selected,'imported'=>$result['imported'],'alreadyPresent'=>$result['alreadyPresent']],0);
            }catch(PilotCaseSchemaUnavailable){
                self::close($connection);self::finish(['ok'=>false,'reason'=>'SCHEMA_UNAVAILABLE'],78);
            }catch(PilotCaseCommitOutcomeUnknown $unknown){
                self::close($connection);$this->finishUnknownCommit($environment,$selected,$timestamp,$unknown);
            }catch(mysqli_sql_exception $error){
                if(in_array($error->getCode(),[1062,1205,1213],true)&&$attempt<2)continue;
                self::close($connection);self::finish(['ok'=>false,'reason'=>'IMPORT_FAILED'],70);
            }catch(Throwable){
                self::close($connection);self::finish(['ok'=>false,'reason'=>'IMPORT_FAILED'],70);
            }
        }
        self::close($connection);self::finish(['ok'=>false,'reason'=>'IMPORT_FAILED'],70);
    }

    /** @return array<string,string> */
    private function environment():array
    {
        $environment=[];
        foreach(self::ENV_NAMES as $name){
            $value=getenv($name);
            if($value===false) {
                self::finish(['ok'=>false,'reason'=>'CONFIGURATION_INVALID'],64);
            }
            $environment[$name]=$value;
        }
        foreach(['FMONITOR_DB_HOST','FMONITOR_DB_NAME','FMONITOR_DB_USER'] as $name){
            if($environment[$name]==='') {
                self::finish(['ok'=>false,'reason'=>'CONFIGURATION_INVALID'],64);
            }
        }
        $port=$environment['FMONITOR_DB_PORT'];
        if(preg_match('/^[1-9][0-9]*$/D',$port)!==1||(int)$port>65535
            ||!self::validPrefix($environment['FMONITOR_PROCESS_TABLE_PREFIX'])
            ||!self::validPrefix($environment['FMONITOR_LEGACY_TABLE_PREFIX'])){
            self::finish(['ok'=>false,'reason'=>'CONFIGURATION_INVALID'],64);
        }
        return $environment;
    }

    /** @param list<string> $arguments @return list<int> */
    private function selectedIds(array $arguments):array
    {
        $selected=[];
        foreach($arguments as $argument){
            if(preg_match('/^--object-id=([1-9][0-9]*)$/D',$argument,$match)!==1
                ||strlen($match[1])>19
                ||(strlen($match[1])===19&&strcmp($match[1],'9223372036854775807')>0)){
                self::finish(['ok'=>false,'reason'=>'CONFIGURATION_INVALID'],64);
            }
            $id=(int)$match[1];
            if(in_array($id,$selected,true)) {
                self::finish(['ok'=>false,'reason'=>'CONFIGURATION_INVALID'],64);
            }
            $selected[]=$id;
        }
        if($selected===[]||count($selected)>100) {
            self::finish(['ok'=>false,'reason'=>'CONFIGURATION_INVALID'],64);
        }
        return $selected;
    }

    /** @param array<string,string> $environment */
    private function connect(array $environment):mysqli
    {
        $connection=@new mysqli($environment['FMONITOR_DB_HOST'],$environment['FMONITOR_DB_USER'],$environment['FMONITOR_DB_PASSWORD'],$environment['FMONITOR_DB_NAME'],(int)$environment['FMONITOR_DB_PORT']);
        if(!$connection->set_charset('utf8mb4')) {
            throw new RuntimeException();
        }
        return $connection;
    }

    /** @param array<string,string> $environment @param list<int> $selected */
    private function finishUnknownCommit(array $environment,array $selected,string $timestamp,PilotCaseCommitOutcomeUnknown $unknown):never
    {
        $reconciled=false;$connection=null;
        if($unknown->expectedNewIds!==null){
            try{
                $connection=$this->connect($environment);
                $importer=new PilotCaseImporter($connection,$environment['FMONITOR_PROCESS_TABLE_PREFIX'],$environment['FMONITOR_LEGACY_TABLE_PREFIX']);
                $reconciled=$importer->reconciles($unknown->expectedNewIds,$timestamp);
            }catch(Throwable){$reconciled=false;}
            if($connection instanceof mysqli) {
                self::close($connection);
            }
        }
        if($reconciled) {
            self::finish(['ok'=>true,'selected'=>$selected,'imported'=>$unknown->expectedNewIds,'alreadyPresent'=>$unknown->alreadyPresent],0);
        }
        self::finish(['ok'=>false,'reason'=>'IMPORT_OUTCOME_UNKNOWN'],75);
    }

    private static function validPrefix(string $prefix):bool
    {
        return strlen($prefix)<=32&&preg_match('/^[A-Za-z0-9_]*$/D',$prefix)===1;
    }

    private static function close(mysqli $connection):void
    {
        try {
            $connection->close();
        } catch(Throwable) {
        }
    }

    private static function finish(array $result,int $exitCode):never
    {
        echo json_encode($result,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";
        exit($exitCode);
    }
}

(new PilotCaseImportCliRunner())->run(array_slice($argv,1));
