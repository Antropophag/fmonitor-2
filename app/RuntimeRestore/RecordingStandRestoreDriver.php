<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RecordingStandRestoreDriver implements StandRestoreDriver
{
    private string $scenario='';
    public function __construct(private string $scenarioPath,private string $tracePath){}
    public function preflight(StandRestoreAuthorization $authorization):string
    {
        $this->scenario=(string)(json_decode(StandBackupFilesystem::regularBytes($this->scenarioPath),true,512,JSON_THROW_ON_ERROR)['scenario']??'');
        $this->event('attest:preflight');return $this->scenario==='drift-pre'?'TARGET_INVALID':'VERIFIED';
    }
    public function restore(StandRestoreAuthorization $authorization,array $payloads):array
    {
        $credential=(string)$authorization->value('credential_files')['database'];
        if(is_string(getenv('FMONITOR_CREDENTIAL_READ_TRACE'))&&getenv('FMONITOR_CREDENTIAL_READ_TRACE')!=='')StandBackupFilesystem::append(getenv('FMONITOR_CREDENTIAL_READ_TRACE'),"read\n");
        StandBackupFilesystem::regularBytes($credential);$this->event('credential:read');
        foreach([['before-db','database'],['before-artifacts','artifacts'],['before-sessions','sessions'],['before-restart','restart']]as[$attest,$effect]){$this->event('attest:'.$attest);$this->event('effect:'.$effect);if($this->scenario==='drift-after-db'&&$effect==='database')return['outcome'=>'OUTCOME_UNKNOWN','evidence'=>[]];}
        foreach(['observe:live','observe:ready','observe:integrity']as$event)$this->event($event);
        $evidence=array_fill_keys(['database','schema','auto_increment','history','jobs','artifacts','sessions','live','ready','golden'],true);
        if($this->scenario==='evidence-mismatch')$evidence['golden']=false;
        return['outcome'=>'RESTORE_VERIFIED','evidence'=>$evidence];
    }
    private function event(string $event):void{StandBackupFilesystem::append($this->tracePath,StandBackupFilesystem::canonical(['event'=>$event]));}
}
