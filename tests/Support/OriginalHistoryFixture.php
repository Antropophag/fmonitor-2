<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

/** Synthetic accepted facts come exclusively from existing native public commands. */
final class OriginalHistoryFixture
{
    public readonly SelectedOriginalFixture $base;
    public readonly O\AssignmentOrderOriginalResult $first;
    public readonly O\AssignmentOrderOriginalResult $second;
    public function __construct(public readonly string $prefix='')
    {
        $this->base=new SelectedOriginalFixture($prefix);
        try {
            \assertSameValue('selected',$this->base->selection->app()->selectAssignmentOrderComposition(SelectionNativeFixture::command())->status()->value,'native selection setup');
            $this->first=$this->base->app()->submitAssignmentOrderOriginal(SelectedOriginalFixture::command(new SelectedOriginalInput(SelectedOriginalFixture::pdf())));
            \assertSameValue(['accepted',327,'78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62'],[$this->first->status()->value,$this->first->byteSize(),$this->first->sha256()],'independent PDF1.7 receipt');
            \assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',hash('sha256',self::otherPdf()),'independent PDF1.4 fixture');
            $this->second=$this->correct($this->first,self::otherPdf());
            \assertSameValue(['accepted',2,327,'2026-09-03'],[$this->second->status()->value,$this->second->revisionNumber(),$this->second->byteSize(),$this->second->documentDate()],'second native accepted original');
        }catch(\Throwable $error){$this->close();throw $error;}
    }
    public static function otherPdf():string {return base64_decode(AssignmentOrderOriginalRemainingMatrix::POSITIVE_PDF_BASE64,true);}
    public function correct(O\AssignmentOrderOriginalResult $previous,string $bytes,int $request=2,string $date='2026-09-03'):O\AssignmentOrderOriginalResult
    {
        return $this->base->app()->submitAssignmentOrderOriginal(new O\SubmitAssignmentOrderOriginalCommand(sprintf('33333333-3333-4333-8333-%012d',$request),O\AssignmentOrderOriginalMode::CORRECTION,4512,81,18,$date,true,$previous->rootOriginalId(),$previous->currentRevisionId(),$previous->currentRevisionId(),'Уточнена дата и файл',new O\AssignmentOrderOriginalUpload(new SelectedOriginalInput($bytes),'signed.pdf','application/pdf')));
    }
    public function reader(?string $root=null):object
    {
        \assertSameValue(true,is_callable([O\AssignmentOrderOriginalHistoryReaderFactory::class,'create']),'INTENDED_RED: production original history reader missing after two healthy native accepted PDFs');
        return O\AssignmentOrderOriginalHistoryReaderFactory::create($this->base->selection->db,$root??$this->base->privateRoot,$this->prefix);
    }
    public function revision(bool $second):array
    {
        return ['revisionId'=>$second?$this->second->currentRevisionId():$this->first->currentRevisionId(),'revisionNumber'=>$second?2:1,
            'previousRevisionId'=>$second?$this->first->currentRevisionId():null,'documentDate'=>$second?'2026-09-03':'2026-09-04',
            'uploadedAt'=>'2026-09-05T09:00:00Z','actorUserId'=>18,'sha256'=>$second?'4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784':'78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62','byteSize'=>327,'correctionReason'=>$second?'Уточнена дата и файл':null];
    }
    public function context(bool $history):array
    {
        $context=['objectId'=>4512,'caseId'=>4512,'orderId'=>81,'orderVersion'=>1,'rootOriginalId'=>$this->first->rootOriginalId()];
        if($history)$context['currentRevisionId']=$this->second->currentRevisionId();
        return $context+['compositionIdentity'=>'composition-81-v1','compositionSha256'=>'5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a',
            'composition'=>['installers'=>[['tabId'=>7001,'fullName'=>'Монтажник 7001','position'=>'Монтажник']],'engineer'=>['userId'=>73,'fullName'=>'Инженер теста','position'=>'Инженер строительного контроля']]];
    }
    public function files():array
    {
        $out=[];$root=$this->base->privateRoot;
        $walk=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::SELF_FIRST);
        foreach($walk as $file){$path=$file->getPathname();clearstatcache(true,$path);$stat=lstat($path);$out[substr($path,strlen($root)+1)]=[$stat['mode'],$stat['uid'],$stat['nlink'],is_link($path)?readlink($path):($file->isFile()?hash_file('sha256',$path):null)];}
        clearstatcache(true,$root);$rootStat=lstat($root);$out['.']=[$rootStat['mode'],$rootStat['uid'],$rootStat['nlink'],null];ksort($out);return $out;
    }
    public function observe(callable $read):mixed
    {
        $state=$this->base->selection->schema->state();$files=$this->files();$streams=count(get_resources('stream'));
        $result=$read();
        \assertSameValue('0',$this->base->selection->db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],'owned snapshot released before result');
        \assertSameValue($streams,count(get_resources('stream')),'native stream descriptors released before result');
        \assertSameValue($state,$this->base->selection->schema->state(),'reader leaves all DB rows and DDL unchanged');
        \assertSameValue($files,$this->files(),'reader leaves private file names/hash/mode/owner/link count unchanged');return $result;
    }
    public function close():void {$this->base->close();}
}
