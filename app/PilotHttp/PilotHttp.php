<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final readonly class PilotHttpRequest { public function __construct(public string $method,public string $path,public string $host,public mixed $serverIdentity,public array $server=[],public string $body=''){} }
final readonly class PilotHttpResponse { public function __construct(public int $status,public array $headers,public string $body){} }
final readonly class HttpUser { public function __construct(public int $id,public string $displayName,public string $email){} }
final class InvalidServerIdentity extends \RuntimeException {}
final class CssAssetUnavailable extends \RuntimeException {}
final class CssDescriptorCloseFailed extends \RuntimeException { public function __construct(){parent::__construct('CSS descriptor close failed.',0,null);} }
final class PilotHttpInfrastructureUnavailable extends \RuntimeException {}
final class InvalidHttpRequest extends \RuntimeException {}

interface TrustedServerIdentity { public function resolve(mixed $serverIdentity):string; }
interface HttpUserDirectory { public function resolveActiveUser(string $principal):?HttpUser; }
interface PilotShellRenderer { public function render(HttpUser $user):string; }
interface CompatibilityPilotShellRenderer { public function renderCompatibility(HttpUser $user):string; }
interface ObjectCardRenderer { public function render(HttpUser $user,array $card):string; }
interface CompatibilityObjectCardRenderer { public function renderCompatibility(HttpUser $user,array $card):string; }
interface ObjectCardReader { public function read(int $installationObjectId):array|null; }
interface ObjectListRenderer { public function render(HttpUser $user,array $objects):string; }
interface CompatibilityObjectListRenderer { public function renderCompatibility(HttpUser $user,array $objects):string; }
interface ObjectListReader { public function read():array; }
interface PrepareFormRenderer { public function render(HttpUser $user,array $form):string; }
interface CompatibilityPrepareFormRenderer { public function renderCompatibility(HttpUser $user,array $form):string; }
interface PrepareFormReader { public function read(int $installationObjectId,string $processDate):array|null; }
interface CssAsset { public function readBytes():string; public function close():void; }
interface CssDescriptor { public function readBytes():string; public function close():void; }
interface CssDescriptorOpener { public function open(string $absolutePath):CssDescriptor; }
interface PhpStreamCloser { public function close(mixed $phpStream):void; }
interface PhpStreamClosePrimitive { public function close(mixed $phpStream):bool; }
interface EnvironmentSource { public function read(string $name):string|false; }
interface PilotHttpDependencies { public function css():CssAsset; public function users():HttpUserDirectory; public function close():void; }
interface ObjectCardReaderProvider { public function objectCards():ObjectCardReader; }
interface ObjectListReaderProvider { public function objectList():ObjectListReader; }
interface PrepareFormReaderProvider { public function prepareForms():PrepareFormReader; public function hasCapability(int $userId,string $capability):bool; public function processDate():string; }
interface ChecklistAccessProvider { public function canEditChecklist(int $userId):bool; }
interface PilotUiConfiguration { public function pilotUiConfigured():bool; public function prepareCommandConfigured():bool; public function pilotCss():CssAsset; }
interface ShlzAssetProvider { public function shlzAsset(string $relativePath):?CssAsset; }
interface UnexpectedFailureReporter { public function report(string $category,string $correlationId):void; }
interface CorrelationIdSource { public function nextId():string; }

final class RemoteUserIdentity implements TrustedServerIdentity
{
    public function resolve(mixed $value):string
    {
        if(!\is_string($value)||\strlen($value)<3||\strlen($value)>254||\preg_match("/^[A-Za-z0-9.!#$%&'*+\\/=?^_`{|}~-]+@[A-Za-z0-9.-]+$/D",$value)!==1)throw new InvalidServerIdentity();
        return $value;
    }
}

abstract class NamedCssAsset implements CssAsset
{
    private ?CssDescriptor $descriptor=null;private ?string $bytes=null;private bool $closeAttempted=false;
    protected function __construct(private readonly string $path,private readonly CssDescriptorOpener $descriptors,private readonly string $basename){}
    public function readBytes():string
    {
        if($this->bytes!==null)return $this->bytes;
        if($this->path===''||$this->path[0]!=='/'||\basename($this->path)!==$this->basename)throw new CssAssetUnavailable();
        $this->descriptor=$this->descriptors->open($this->path);return $this->bytes=$this->descriptor->readBytes();
    }
    public function close():void
    {
        if($this->closeAttempted)return;$this->closeAttempted=true;$descriptor=$this->descriptor;
        try{if($descriptor!==null)$descriptor->close();}finally{$this->descriptor=null;$this->bytes=null;}
    }
}

final class ShlzCssAsset extends NamedCssAsset
{
    public function __construct(string $path,CssDescriptorOpener $descriptors){parent::__construct($path,$descriptors,'shlz.css');}
}

final class PilotCssAsset extends NamedCssAsset
{
    public function __construct(string $path,CssDescriptorOpener $descriptors){parent::__construct($path,$descriptors,'pilot.css');}
}

final class ManifestCssAsset implements CssAsset
{
    private ?CssDescriptor $descriptor=null;private bool $closeAttempted=false;
    public function __construct(private readonly string $path,private readonly array $identity,private readonly CssDescriptorOpener $opener){}
    public function readBytes():string{self::validateComponents($this->path);\clearstatcache(true,$this->path);$before=@\lstat($this->path);if(!self::same($before,$this->identity))throw new CssAssetUnavailable();$this->descriptor=$this->opener->open($this->path);$bytes=$this->descriptor->readBytes();\clearstatcache(true,$this->path);$after=@\lstat($this->path);if(!self::same($after,$this->identity)||(int)$after['size']!==\strlen($bytes)||isset($this->identity['hash'])&&!\hash_equals($this->identity['hash'],\hash('sha256',$bytes)))throw new CssAssetUnavailable();return $bytes;}
    public function close():void{if($this->closeAttempted)return;$this->closeAttempted=true;if($this->descriptor!==null)$this->descriptor->close();$this->descriptor=null;}
    public static function validateComponents(string $path,bool $directory=false):void{$parts=\explode('/',\trim($path,'/'));if($path===''||$path[0]!=='/'||\str_contains($path,"\0")||$parts===[])throw new CssAssetUnavailable();$current='';foreach($parts as$i=>$part){$current.='/'.$part;$s=@\lstat($current);if(!\is_array($s)||\is_link($current))throw new CssAssetUnavailable();$last=$i===\count($parts)-1;$type=$s['mode']&0170000;if(($last&&!$directory&&$type!==0100000)||((!$last||$directory)&&$type!==0040000))throw new CssAssetUnavailable();if(!$last&&!\is_executable($current))throw new CssAssetUnavailable();}if(!\is_readable($path)||($directory&&!\is_executable($path)))throw new CssAssetUnavailable();\clearstatcache(true,$path);$final=@\lstat($path);if(!\is_array($final)||$final['dev']!==$s['dev']||$final['ino']!==$s['ino']||($final['mode']&0170000)!==($s['mode']&0170000))throw new CssAssetUnavailable();}
    public static function identity(string $path):array{\clearstatcache(true,$path);$s=@\lstat($path);if(!\is_array($s))throw new CssAssetUnavailable();return ['dev'=>$s['dev'],'ino'=>$s['ino'],'mode'=>$s['mode'],'uid'=>$s['uid'],'size'=>(int)$s['size'],'mtime'=>$s['mtime']];}
    private static function same(array|false $a,array $b):bool{return \is_array($a)&&($a['mode']&0170000)===0100000&&($b['mode']&0170000)===0100000&&$a['dev']===$b['dev']&&$a['ino']===$b['ino']&&$a['mode']===$b['mode']&&$a['uid']===$b['uid']&&(int)$a['size']===$b['size']&&$a['mtime']===$b['mtime'];}
}

final class CapturedCssAsset implements CssAsset
{
    public function __construct(private readonly string $bytes){}
    public function readBytes():string{return $this->bytes;}
    public function close():void{}
}

final class ShlzCssManifest
{
    private array $members=[];private array $assets=[];private array $directories=[];private array $handles=[];private int $trustedUid;
    public function __construct(string $entry,private readonly CssDescriptorOpener $opener){if($entry===''||$entry[0]!=='/'||\basename($entry)!=='shlz.css')throw new CssAssetUnavailable();$root=\dirname($entry);ManifestCssAsset::validateComponents($root,true);ManifestCssAsset::validateComponents($entry);$root=\realpath($root);$canonical=\realpath($entry);if($root===false||$canonical===false||\dirname($canonical)!==$root)throw new CssAssetUnavailable();$rootStat=ManifestCssAsset::identity($root);$euid=\function_exists('posix_geteuid')?\posix_geteuid():$rootStat['uid'];if($rootStat['uid']!==0&&$rootStat['uid']!==$euid)throw new CssAssetUnavailable();$this->trustedUid=$rootStat['uid'];$failure=null;try{$this->captureDirectory($root);$this->walk($root,'shlz.css',1,0);\ksort($this->members);$this->revalidate();}catch(\Throwable $e){$failure=$e;}try{$this->closeHandles();}catch(\Throwable $e){$failure??=$e;}if($failure!==null)throw $failure;}
    public function asset(string $relative):?CssAsset{if(!isset($this->members[$relative]))return null;if(isset($this->assets[$relative]))return $this->assets[$relative];return $this->assets[$relative]=new CapturedCssAsset($this->members[$relative]['bytes']);}
    public function relativePaths():array{return \array_keys($this->members);}
    public function close():void{$first=null;foreach($this->assets as$a)try{$a->close();}catch(\Throwable $e){$first??=$e;}$this->assets=[];if($first!==null)throw $first;}
    private function walk(string $root,string $relative,int $depth,int $total):int{if(isset($this->members[$relative]))return$total;if($depth>32||\count($this->members)>=256)throw new CssAssetUnavailable();$path=$root.'/'.$relative;ManifestCssAsset::validateComponents($path);$canonical=\realpath($path);if($canonical===false||!\str_starts_with($canonical,$root.'/'))throw new CssAssetUnavailable();$directory=\dirname($path);while(\str_starts_with($directory,$root.'/')){$this->captureDirectory($directory);$directory=\dirname($directory);}$identity=ManifestCssAsset::identity($path);if($identity['uid']!==$this->trustedUid||($identity['mode']&0400)===0||($identity['mode']&0022)!==0||($identity['mode']&0111)!==0)throw new CssAssetUnavailable();$handle=@\fopen($path,'rb');if($handle===false)throw new CssAssetUnavailable();$this->handles[$relative]=$handle;$opened=@\fstat($handle);\clearstatcache(true,$path);$after=@\lstat($path);if(!$this->sameFile($opened,$identity)||!$this->sameFile($after,$identity))throw new CssAssetUnavailable();$bytes='';while(\strlen($bytes)<$identity['size']){$chunk=@\fread($handle,$identity['size']-\strlen($bytes));if(!\is_string($chunk)||$chunk==='')throw new CssAssetUnavailable();$bytes.=$chunk;}$identity['hash']=\hash('sha256',$bytes);$total+=\strlen($bytes);if($total>8388608||\preg_match('//u',$bytes)!==1)throw new CssAssetUnavailable();$this->members[$relative]=['path'=>$path,'identity'=>$identity,'bytes'=>$bytes];foreach($this->imports($bytes)as$target){$resolved=$this->resolve($relative,$target);if($resolved==='pilot.css')throw new CssAssetUnavailable();$total=$this->walk($root,$resolved,$depth+1,$total);}return$total;}
    private function captureDirectory(string $path):void{$identity=ManifestCssAsset::identity($path);if(($identity['mode']&0170000)!==0040000||$identity['uid']!==$this->trustedUid||($identity['mode']&0500)!==0500||($identity['mode']&0022)!==0)throw new CssAssetUnavailable();if(isset($this->directories[$path])&&$this->directories[$path]!==$identity)throw new CssAssetUnavailable();$this->directories[$path]=$identity;}
    private function revalidate():void{foreach($this->directories as$path=>$identity)if(ManifestCssAsset::identity($path)!==$identity)throw new CssAssetUnavailable();foreach($this->members as$relative=>$member){$handle=$this->handles[$relative]??null;$opened=\is_resource($handle)?@\fstat($handle):false;\clearstatcache(true,$member['path']);$pathStat=@\lstat($member['path']);if(!$this->sameFile($opened,$member['identity'])||!$this->sameFile($pathStat,$member['identity'])||@\fseek($handle,0)!==0)throw new CssAssetUnavailable();$bytes='';while(\strlen($bytes)<$member['identity']['size']){$chunk=@\fread($handle,$member['identity']['size']-\strlen($bytes));if(!\is_string($chunk)||$chunk==='')throw new CssAssetUnavailable();$bytes.=$chunk;}$final=@\fstat($handle);if(!$this->sameFile($final,$member['identity'])||!\hash_equals($member['identity']['hash'],\hash('sha256',$bytes)))throw new CssAssetUnavailable();}}
    private function closeHandles():void{$failed=false;foreach($this->handles as$handle){$warning=false;\set_error_handler(static function()use(&$warning):bool{$warning=true;return true;});try{$ok=\fclose($handle);}finally{\restore_error_handler();}if($warning||$ok!==true)$failed=true;}$this->handles=[];if($failed)throw new CssAssetUnavailable();}
    private function sameFile(array|false $actual,array $expected):bool{return \is_array($actual)&&($actual['mode']&0170000)===0100000&&$actual['dev']===$expected['dev']&&$actual['ino']===$expected['ino']&&$actual['mode']===$expected['mode']&&$actual['uid']===$expected['uid']&&(int)$actual['size']===$expected['size']&&$actual['mtime']===$expected['mtime'];}
    private function imports(string $css):array{$out=[];$o=0;$n=\strlen($css);while($o<$n){if(\preg_match('/\G(?:\s+|\/\*.*?\*\/)+/As',$css,$m,0,$o)===1){$o+=\strlen($m[0]);continue;}if(\preg_match('/\G@charset\s+(?:"[^"]*"|\'[^\']*\')\s*;/Ai',$css,$m,0,$o)===1){$o+=\strlen($m[0]);continue;}if(\preg_match('/\G@layer\s+[A-Za-z0-9._-]+(?:\s*,\s*[A-Za-z0-9._-]+)*\s*;/A',$css,$m,0,$o)===1){$o+=\strlen($m[0]);continue;}if(\strncasecmp(\substr($css,$o,7),'@import',7)!==0)break;if(\preg_match('/\G@import\s+(?:"([^"]*)"|\'([^\']*)\'|url\(\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\)"\']+))\s*\))([^;{}]*);/Ai',$css,$m,0,$o)!==1||!$this->validSuffix($m[6]??''))throw new CssAssetUnavailable();$target='';for($i=1;$i<=5;$i++)if(isset($m[$i])&&$m[$i]!==''){$target=$m[$i];break;}if($target==='')throw new CssAssetUnavailable();$out[]=$target;$o+=\strlen($m[0]);}return$out;}
    private function validSuffix(string $suffix):bool{$s=\trim($suffix);if($s==='')return true;if(\preg_match('/[^A-Za-z0-9\s._,:\/%()#<>=+*~-]/',$s)===1)return false;if(\str_starts_with($s,'layer')){if(\preg_match('/^layer(?:\(\s*[A-Za-z_][A-Za-z0-9_.-]*\s*\))?(?=\s|$)/',$s,$m)!==1)return false;$s=\ltrim(\substr($s,\strlen($m[0])));}if(\str_starts_with($s,'supports')){$end=$this->functionEnd($s,'supports');if($end===null)return false;$s=\ltrim(\substr($s,$end));}if($s==='')return true;if(\preg_match('/^[A-Za-z][A-Za-z0-9_-]*(?:\s|$|\()/',$s)!==1)return false;return $this->balanced($s);}
    private function functionEnd(string $s,string $name):?int{$prefix=$name.'(';if(!\str_starts_with($s,$prefix))return null;$depth=0;$n=\strlen($s);for($i=\strlen($name);$i<$n;$i++){if($s[$i]==='(')$depth++;elseif($s[$i]===')'){if(--$depth===0)return$i+1;if($depth<0)return null;}}return null;}
    private function balanced(string $s):bool{$depth=0;for($i=0,$n=\strlen($s);$i<$n;$i++){if($s[$i]==='(')$depth++;elseif($s[$i]===')'&&--$depth<0)return false;}return$depth===0;}
    private function resolve(string $importer,string $target):string{if($target===''||$target[0]==='/'||\str_contains($target,'\\')||\preg_match('/[\x00-\x20\x7f%?#:]/',$target)===1||!\str_ends_with($target,'.css'))throw new CssAssetUnavailable();$stack=\dirname($importer)==='.'?[]:\explode('/',\dirname($importer));foreach(\explode('/',$target)as$i=>$p){if($p===''||($p==='.'&&$i!==0)||($p==='..'&&$i!==0)||($p!=='..'&&$p!=='.'&&\preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/D',$p)!==1))throw new CssAssetUnavailable();if($p==='.')continue;if($p==='..'){if($stack===[])throw new CssAssetUnavailable();\array_pop($stack);}else$stack[]=$p;}$r=\implode('/',$stack);if($r===''||!\str_ends_with($r,'.css'))throw new CssAssetUnavailable();return$r;}
}

final class PhpCssDescriptor implements CssDescriptor
{
    private mixed $resource;private bool $closeAttempted=false;
    public function __construct(mixed $resource,private readonly PhpStreamCloser $closer){if(!\is_resource($resource))throw new CssAssetUnavailable();$this->resource=$resource;}
    public function readBytes():string{$before=@\fstat($this->resource);if(!\is_array($before)||($before['mode']&0170000)!==0100000)throw new CssAssetUnavailable();$size=(int)$before['size'];$bytes='';while(\strlen($bytes)<$size){$chunk=@\fread($this->resource,$size-\strlen($bytes));if(!\is_string($chunk)||$chunk==='')throw new CssAssetUnavailable();$bytes.=$chunk;}$after=@\fstat($this->resource);if(!\is_array($after)||$before['dev']!==$after['dev']||$before['ino']!==$after['ino']||(int)$after['size']!==$size||\strlen($bytes)!==$size)throw new CssAssetUnavailable();return $bytes;}
    public function close():void{if($this->closeAttempted)return;$this->closeAttempted=true;$resource=$this->resource;$this->resource=null;$this->closer->close($resource);}
}

final class PhpCssDescriptorOpener implements CssDescriptorOpener
{
    public function __construct(private readonly PhpStreamCloser $closer){}
    public function open(string $path):CssDescriptor{$before=@\lstat($path);if(!self::same($before,$before)||\is_link($path)||!\is_readable($path))throw new CssAssetUnavailable();$resource=@\fopen($path,'rb');if($resource===false)throw new CssAssetUnavailable();try{$opened=@\fstat($resource);$after=@\lstat($path);if(!self::same($before,$opened)||!self::same($before,$after)||\is_link($path))throw new CssAssetUnavailable();return new PhpCssDescriptor($resource,$this->closer);}catch(\Throwable $e){try{$this->closer->close($resource);}catch(\Throwable){}throw $e;}}
    private static function same(array|false $a,array|false $b):bool{return \is_array($a)&&\is_array($b)&&($a['mode']&0170000)===0100000&&($b['mode']&0170000)===0100000&&$a['dev']===$b['dev']&&$a['ino']===$b['ino'];}
}

final class NativePhpFclosePrimitive implements PhpStreamClosePrimitive
{
    public function close(mixed $phpStream):bool{return \fclose($phpStream);}
}

final class NativePhpStreamCloser implements PhpStreamCloser
{
    public function __construct(private readonly PhpStreamClosePrimitive $primitive){}
    public function close(mixed $phpStream):void
    {
        $warning=false;\set_error_handler(static function()use(&$warning):bool{$warning=true;return true;});
        try{try{$result=$this->primitive->close($phpStream);}catch(\Throwable){throw new CssDescriptorCloseFailed();}if($warning||$result!==true)throw new CssDescriptorCloseFailed();}finally{\restore_error_handler();}
    }
}

final class PrepareFormUnavailable extends \RuntimeException {}

final class MariaDbPrepareFormReader implements PrepareFormReader
{
    public function __construct(private readonly \mysqli $connection,private readonly string $prefix,private readonly string $processPrefix=''){}
    public function read(int $id,string $processDate):array|null
    {
        try{
            if(self::date($processDate)!==$processDate)throw new PilotHttpInfrastructureUnavailable();$cases=$this->many('SELECT id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id FROM fm2_installation_cases WHERE legacy_installation_object_id=? LIMIT 2',$id);$legacy=$this->many("SELECT id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,plan_finish_date,workdatefinish,ptoactdate,responsstroicontrol FROM `{$this->prefix}fm_maintable` WHERE id=? LIMIT 2",$id);
            if($cases===[]&&$legacy===[])return null;if(\count($cases)!==1||\count($legacy)!==1)throw new PilotHttpInfrastructureUnavailable();$case=$cases[0];$l=$legacy[0];if((string)$case['process_state']!=='needs_assignment_order')throw new PrepareFormUnavailable();
            $address=\trim((string)$l['ordadr_address']);$entrance=\trim((string)$l['entrance']);$registration=\trim((string)$l['regnumber']);$start=self::datePrefix($l['workdatestart']);$finish=self::datePrefix($l['workdateendadjusted'])??self::datePrefix($l['plan_finish_date']);$completion=self::optionalLegacyDate($l['workdatefinish']);$pto=self::optionalLegacyDate($l['ptoactdate']);if(self::positiveId($case['id'])===null||self::positiveId($case['legacy_installation_object_id'])!==$id||self::positiveId($l['id'])!==$id||$address===''||$entrance===''||$registration===''||$start===null||$finish===null||$case['actual_start_date']!==null||$case['opened_at']!==null||$case['opened_by_user_id']!==null||$completion!==null||$pto!==null)throw new PilotHttpInfrastructureUnavailable();if($this->many('SELECT id FROM fm2_assignment_orders WHERE installation_case_id=? LIMIT 1',(int)$case['id'])!==[])throw new PilotHttpInfrastructureUnavailable();
            $historyColumns=$this->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fm2_workforce_catalog' AND COLUMN_NAME IN ('reconciliation_state','last_successful_sync_run_id','last_successful_sync_at')")->fetch_all(MYSQLI_ASSOC);if(\count($historyColumns)!==0&&\count($historyColumns)!==3)throw new PilotHttpInfrastructureUnavailable();$history=\count($historyColumns)===3;$runId=null;$syncAt=null;if($history){$metadata=$this->query('SELECT singleton_id,last_successful_run_id,last_successful_at FROM fm2_workforce_sync_metadata LIMIT 2')->fetch_all(MYSQLI_ASSOC);if(\count($metadata)!==1||self::positiveId($metadata[0]['singleton_id'])!==1||!\is_string($metadata[0]['last_successful_run_id'])||$metadata[0]['last_successful_run_id']===''||!self::rfc3339($metadata[0]['last_successful_at']))throw new PilotHttpInfrastructureUnavailable();$runId=$metadata[0]['last_successful_run_id'];$syncAt=$metadata[0]['last_successful_at'];$s=$this->connection->prepare($this->prefixed('SELECT run_id,status,completed_at FROM fm2_workforce_sync_runs WHERE run_id=? LIMIT 2'));$s->bind_param('s',$runId);$s->execute();$runs=$s->get_result()->fetch_all(MYSQLI_ASSOC);if(\count($runs)!==1||$runs[0]['status']!=='completed'||!self::rfc3339($runs[0]['completed_at'])||$runs[0]['completed_at']!==$syncAt)throw new PilotHttpInfrastructureUnavailable();}
            $fields='installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at'.($history?',reconciliation_state,last_successful_sync_run_id,last_successful_sync_at':'');$workforce=$this->query("SELECT {$fields} FROM fm2_workforce_catalog LIMIT 5002")->fetch_all(MYSQLI_ASSOC);$busy=[];$busyColumns=$this->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fm2_order_installers' AND COLUMN_NAME='valid_to'")->fetch_all(MYSQLI_ASSOC);$busyRows=$busyColumns===[]?[]:$this->query("SELECT installer_tab_id,MAX(valid_to) AS busy_until FROM fm2_order_installers WHERE valid_to IS NOT NULL GROUP BY installer_tab_id LIMIT 502")->fetch_all(MYSQLI_ASSOC);foreach($busyRows as $busyRow){$busyTab=self::positiveId($busyRow["installer_tab_id"]);$busyUntil=self::date($busyRow["busy_until"]);if($busyTab===null||$busyUntil===null||isset($busy[$busyTab]))throw new PilotHttpInfrastructureUnavailable();$busy[$busyTab]=$busyUntil;}$installers=[];$seen=[];foreach($workforce as $x){$tab=self::positiveId($x['installer_tab_id']);$fio=\trim((string)$x['fio']);$position=\trim((string)$x['position']);$source=\trim((string)$x['workforce_source']);$from=self::date($x['employed_from']);$to=$x['employed_to']===null?null:self::date($x['employed_to']);if($tab===null||isset($seen[$tab])||$fio===''||$position===''||$source===''||$from===null||($x['employed_to']!==null&&$to===null)||!self::rfc3339($x['workforce_source_updated_at']))throw new PilotHttpInfrastructureUnavailable();$seen[$tab]=true;$reconciliation='delivered';if($history){$provenance=[$x['reconciliation_state'],$x['last_successful_sync_run_id'],$x['last_successful_sync_at']];$legacy=\count(\array_filter($provenance,static fn(mixed $v):bool=>$v!==null))===0;if(!$legacy&&!\in_array($x['reconciliation_state'],['delivered','missing_from_delivery'],true))throw new PilotHttpInfrastructureUnavailable();if(!$legacy&&(!self::rfc3339($x['last_successful_sync_at'])||$x['last_successful_sync_run_id']!==$runId||$x['last_successful_sync_at']!==$syncAt))throw new PilotHttpInfrastructureUnavailable();$reconciliation=$legacy?'delivered':$x['reconciliation_state'];}if($reconciliation==='delivered'&&$x['employment_status']==='employed'&&$from<=$processDate&&($to===null||$to>=$finish))$installers[]=['tabId'=>$tab,'fio'=>$fio,'position'=>$position,'source'=>$source,'updatedAt'=>(string)$x['workforce_source_updated_at'],'busyUntil'=>$busy[$tab]??null];}if(\count($installers)>5000)throw new PilotHttpInfrastructureUnavailable();\usort($installers,static fn(array $a,array $b):int=>[$a['fio'],$a['tabId']]<=>[$b['fio'],$b['tabId']]);
            $engineer=$this->currentEngineer((int)$case['id'],self::positiveId($l['responsstroicontrol']));
            return ['id'=>$id,'registrationNumber'=>$registration,'address'=>$address,'entrance'=>$entrance,'plannedStartDate'=>$start,'plannedFinishDate'=>$finish,'installers'=>$installers,'engineer'=>$engineer];
        }catch(PrepareFormUnavailable|PilotHttpInfrastructureUnavailable $e){throw $e;}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    private function prefixed(string $sql):string{return \str_replace('fm2_',$this->processPrefix.'fm2_',$sql);}
    private function query(string $sql):\mysqli_result|bool{return $this->connection->query($this->prefixed($sql));}
    private function many(string $sql,int $id):array{$s=$this->connection->prepare($this->prefixed($sql));$s->bind_param('i',$id);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC);}
    private static function positiveId(mixed $v):?int{if(!\is_int($v)&&(!\is_string($v)||\preg_match('/^[1-9][0-9]*$/D',$v)!==1))return null;$n=\filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $n===false?null:$n;}
    private static function date(mixed $v):?string{if(!\is_string($v)||\preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$v,$m)!==1||!\checkdate((int)$m[2],(int)$m[3],(int)$m[1])||$m[1]==='0000')return null;return $v;}
    private static function optionalLegacyDate(mixed $v):?string{if($v===null)return null;$v=\trim((string)$v);if($v===''||\preg_match('/^0+$/D',$v)===1||\str_starts_with($v,'0000-00-00'))return null;$date=self::datePrefix($v);if($date===null)throw new PilotHttpInfrastructureUnavailable();return $date;}
    private static function datePrefix(mixed $v):?string{if(!\is_string($v)||\preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D',$v,$m)!==1||!\checkdate((int)$m[2],(int)$m[3],(int)$m[1])||$m[1]==='0000')return null;return $m[1].'-'.$m[2].'-'.$m[3];}
    private static function rfc3339(mixed $v):bool{if(!\is_string($v)||\preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/D',$v,$m)!==1)return false;$c=$m[1].($m[2]==='Z'?'+00:00':$m[2]);$d=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP',$c);$er=\DateTimeImmutable::getLastErrors();return $d!==false&&($er===false||($er['warning_count']===0&&$er['error_count']===0))&&$d->format('Y-m-d\TH:i:sP')===$c;}
    private function currentEngineer(int $caseId,?int $legacyUserId):?array
    {
        $events=$this->many("SELECT payload_json FROM fm2_process_events WHERE installation_case_id=? AND event_type='control_engineer_changed' ORDER BY id DESC LIMIT 1",$caseId);
        if($events!==[]){$payload=\json_decode((string)$events[0]['payload_json'],true,flags:JSON_THROW_ON_ERROR);$engineer=$payload['engineer']??null;if(!\is_array($engineer)||self::positiveId($engineer['userId']??null)===null||\trim((string)($engineer['fullName']??''))===''||\trim((string)($engineer['position']??''))==='')throw new PilotHttpInfrastructureUnavailable();return ['userId'=>(int)$engineer['userId'],'fio'=>(string)$engineer['fullName'],'position'=>(string)$engineer['position']];}
        if($legacyUserId===null)return null;
        $pilotTable=$this->processPrefix.'fm2_pilot_users';$table=$this->connection->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');$table->bind_param('s',$pilotTable);$table->execute();$pilotUsers=$table->get_result()->fetch_assoc()===null?[]:$this->query("SELECT full_name FROM fm2_pilot_users WHERE user_id={$legacyUserId} LIMIT 2")->fetch_all(MYSQLI_ASSOC);if(\count($pilotUsers)===1)return ['userId'=>$legacyUserId,'fio'=>(string)$pilotUsers[0]['full_name'],'position'=>'Строительный контроль'];
        $legacyUsers=$this->query("SELECT name FROM `{$this->prefix}users` WHERE id={$legacyUserId} LIMIT 2")->fetch_all(MYSQLI_ASSOC);return \count($legacyUsers)===1?['userId'=>$legacyUserId,'fio'=>(string)$legacyUsers[0]['name'],'position'=>'Инженер строительного контроля']:null;
    }
}

final class MariaDbObjectCardReader implements ObjectCardReader
{
    public function __construct(private readonly \mysqli $connection,private readonly string $prefix,private readonly string $processPrefix=''){}
    public function read(int $id):array|null
    {
        try{
            $pilotUserTable=$this->processPrefix.'fm2_pilot_users';$pilotTable=$this->connection->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');$pilotTable->bind_param('s',$pilotUserTable);$pilotTable->execute();$hasPilotUsers=$pilotTable->get_result()->fetch_assoc()!==null;
            $case=$this->one('SELECT id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id FROM fm2_installation_cases WHERE legacy_installation_object_id=? LIMIT 2',$id);
            $table=$this->prefix.'fm_maintable';$column=$this->connection->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME IN('ptoactdate','responsstroicontrol')");$column->bind_param('s',$table);$column->execute();$columns=\array_column($column->get_result()->fetch_all(MYSQLI_ASSOC),'COLUMN_NAME');$ptoSelect=\in_array('ptoactdate',$columns,true)?'ptoactdate':'NULL AS ptoactdate';$responsSelect=\in_array('responsstroicontrol',$columns,true)?'responsstroicontrol':'NULL AS responsstroicontrol';$legacy=$this->one("SELECT id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,plan_finish_date,{$ptoSelect},{$responsSelect} FROM `{$table}` WHERE id=? LIMIT 2",$id);
            if($case===null||$legacy===null)return null;
            $card=$this->legacyCard($legacy,$id);if($card===null)return null;
            $caseId=(int)$case['id'];
            $orders=$this->many('SELECT id,installation_case_id,version_no,status,order_date,registration_number,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at FROM fm2_assignment_orders WHERE installation_case_id=? ORDER BY version_no DESC,id DESC',$caseId);
            $order=null;
            $highestVersion=null;
            if($orders!==[]){
                $highestVersion=self::positiveId($orders[0]['version_no']);
                if($highestVersion===null)throw new PilotHttpInfrastructureUnavailable();
                $highest=\array_values(\array_filter($orders,static fn(array $candidate):bool=>(string)$candidate['version_no']===(string)$highestVersion));
                $active=\array_values(\array_filter($orders,static fn(array $candidate):bool=>\in_array($candidate['status'],['prepared','registered'],true)));
                if(\count($highest)!==1||\count($active)!==1||!\in_array($highest[0]['status'],['prepared','registered'],true))throw new PilotHttpInfrastructureUnavailable();
                $order=$highest[0];
            }
            $opened=$case['actual_start_date']!==null||$case['opened_at']!==null||$case['opened_by_user_id']!==null;
            $allOpening=$case['actual_start_date']!==null&&$case['opened_at']!==null&&$case['opened_by_user_id']!==null;
            if($opened!==$allOpening)throw new PilotHttpInfrastructureUnavailable();
            if($allOpening&&(self::date($case['actual_start_date'])!==(string)$case['actual_start_date']||!self::rfc3339($case['opened_at'])||self::positiveId($case['opened_by_user_id'])===null))throw new PilotHttpInfrastructureUnavailable();
            $state=(string)$case['process_state'];
            $status=match($state){
                'needs_assignment_order'=>(!$opened&&$order===null)?'Требуется распоряжение':throw new PilotHttpInfrastructureUnavailable(),
                'assignment_order_prepared'=>(!$opened&&$order!==null&&$order['status']==='prepared')?'Распоряжение подготовлено':((!$opened&&$order!==null&&$order['status']==='registered')?'Готов к открытию':throw new PilotHttpInfrastructureUnavailable()),
                'working'=>( $allOpening&&$order!==null&&$order['status']==='registered')?'В работе':throw new PilotHttpInfrastructureUnavailable(),
                'needs_assignment_change'=>($allOpening&&$order!==null&&$order['status']==='registered')?'Требуется изменение':throw new PilotHttpInfrastructureUnavailable(),
                default=>throw new PilotHttpInfrastructureUnavailable(),
            };
            $renderedOrder=null;
            if($order!==null){
                foreach(['object_address_snapshot'=>'address','entrance_snapshot'=>'entrance','object_registration_number_snapshot'=>'registrationNumber','planned_start_date_snapshot'=>'plannedStartDate','planned_finish_date_snapshot'=>'plannedFinishDate'] as $snapshot=>$live)if((string)$order[$snapshot]!==$card[$live])throw new PilotHttpInfrastructureUnavailable();
                $engineerId=self::positiveId($order['control_engineer_user_id']);
                $registrationNumber=$order['registration_number'];
                $registrationNumberValid=$order['status']==='prepared'
                    ?$registrationNumber===null
                    :\is_string($registrationNumber)&&$registrationNumber!==''&&\trim($registrationNumber)===$registrationNumber;
                if(!\in_array($order['organization_form'],['individual','brigade'],true)||self::date($order['order_date'])!==(string)$order['order_date']||!$registrationNumberValid||$engineerId===null||\trim((string)$order['control_engineer_fio_snapshot'])===''||\trim((string)$order['control_engineer_position_snapshot'])==='')throw new PilotHttpInfrastructureUnavailable();
                $installers=$this->many('SELECT assignment_order_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot FROM fm2_order_installers WHERE assignment_order_id=? ORDER BY installer_tab_id ASC',(int)$order['id']);
                if($installers===[])throw new PilotHttpInfrastructureUnavailable();
                $renderedInstallers=[];
                foreach($installers as $installer){$tabId=self::positiveId($installer['installer_tab_id']);if($tabId===null||\trim((string)$installer['fio_snapshot'])===''||\trim((string)$installer['position_snapshot'])===''||!\in_array($installer['employment_status_snapshot'],['employed','dismissed'],true))throw new PilotHttpInfrastructureUnavailable();$renderedInstallers[]=['tabId'=>$tabId,'fullName'=>(string)$installer['fio_snapshot'],'position'=>(string)$installer['position_snapshot'],'status'=>(string)$installer['employment_status_snapshot']];}
                if(!self::rfc3339($order['prepared_at']))throw new PilotHttpInfrastructureUnavailable();
                $artifactRows=$this->many('SELECT assignment_order_id,artifact_type,filename,media_type,byte_size,sha256 FROM fm2_order_artifacts WHERE assignment_order_id=? ORDER BY FIELD(artifact_type,\'order\',\'appendix\'),artifact_type',(int)$order['id']);
                $artifacts=[];
                foreach($artifactRows as $artifact){$type=(string)$artifact['artifact_type'];$filename=(string)$artifact['filename'];$media=(string)$artifact['media_type'];$size=(int)$artifact['byte_size'];if(!in_array($type,['order','appendix'],true)||$filename===''||!in_array($media,['application/pdf','text/html'],true)||$size<0)throw new PilotHttpInfrastructureUnavailable();$artifacts[]=['type'=>$type,'filename'=>$filename,'mediaType'=>$media,'size'=>$size];}
                $renderedOrder=['version'=>$highestVersion,'status'=>$order['status'],'orderDate'=>$order['order_date'],'preparedAt'=>$order['prepared_at'],'registrationNumber'=>$registrationNumber,'organizationType'=>$order['organization_form'],'engineer'=>['userId'=>$engineerId,'fullName'=>(string)$order['control_engineer_fio_snapshot'],'position'=>(string)$order['control_engineer_position_snapshot']],'installers'=>$renderedInstallers,'artifacts'=>$artifacts];
            }
            $eventFields=$hasPilotUsers?'id,installation_case_id,event_type,occurred_at,actor_user_id,payload_json':'id,installation_case_id,event_type,occurred_at,actor_user_id';$events=$this->many("SELECT {$eventFields} FROM fm2_process_events WHERE installation_case_id=? ORDER BY id DESC LIMIT ".($hasPilotUsers?'8':'3'),$caseId);
            $renderedEvents=[];
            $controlEngineer=null;foreach($events as $event){$actorId=self::positiveId($event['actor_user_id']);if($actorId===null||\trim((string)$event['event_type'])===''||!self::rfc3339($event['occurred_at']))throw new PilotHttpInfrastructureUnavailable();$rendered=['type'=>(string)$event['event_type'],'occurredAt'=>(string)$event['occurred_at'],'actorId'=>$actorId];if($hasPilotUsers&&$event['event_type']==='control_engineer_changed'){$payload=\json_decode((string)$event['payload_json'],true,flags:JSON_THROW_ON_ERROR);$engineer=$payload['engineer']??null;if(!\is_array($engineer)||self::positiveId($engineer['userId']??null)===null||\trim((string)($engineer['fullName']??''))===''||\trim((string)($engineer['position']??''))==='')throw new PilotHttpInfrastructureUnavailable();$snapshot=['userId'=>(int)$engineer['userId'],'fullName'=>(string)$engineer['fullName'],'position'=>(string)$engineer['position']];$controlEngineer??=$snapshot;$rendered['label']='Инженер стройконтроля: '.$snapshot['fullName'];}$renderedEvents[]=$rendered;}
            if($controlEngineer===null){$legacyEngineer=self::positiveId($legacy['responsstroicontrol']);if($legacyEngineer!==null)$controlEngineer=$this->engineerSnapshot($legacyEngineer);}
            $pto=self::optionalLegacyDate($legacy['ptoactdate']);return $card+['status'=>$status,'order'=>$renderedOrder,'controlEngineer'=>$controlEngineer,'opened'=>$allOpening,'actualStartDate'=>$case['actual_start_date'],'openedAt'=>$case['opened_at'],'openedByUserId'=>$allOpening?self::positiveId($case['opened_by_user_id']):null,'events'=>$renderedEvents,'hasPtoAct'=>$pto!==null];
        }catch(PilotHttpInfrastructureUnavailable $e){throw $e;}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    private function one(string $sql,int $id):array|null{$rows=$this->many($sql,$id);if(\count($rows)>1)throw new PilotHttpInfrastructureUnavailable();return $rows[0]??null;}
    private function many(string $sql,int $id):array{$s=$this->connection->prepare(\str_replace('fm2_',$this->processPrefix.'fm2_',$sql));$s->bind_param('i',$id);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC);}
    private function engineerSnapshot(int $id):?array{$pilotTable=$this->processPrefix.'fm2_pilot_users';$table=$this->connection->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');$table->bind_param('s',$pilotTable);$table->execute();if($table->get_result()->fetch_assoc()!==null){$s=$this->connection->prepare("SELECT full_name FROM `{$pilotTable}` WHERE user_id=? LIMIT 2");$s->bind_param('i',$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);if(\count($rows)===1)return ['userId'=>$id,'fullName'=>(string)$rows[0]['full_name'],'position'=>'Строительный контроль'];}$s=$this->connection->prepare("SELECT name FROM `{$this->prefix}users` WHERE id=? LIMIT 2");$s->bind_param('i',$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);return \count($rows)===1?['userId'=>$id,'fullName'=>(string)$rows[0]['name'],'position'=>'Инженер строительного контроля']:null;}
    private function legacyCard(array $r,int $id):array|null
    {
        $address=\trim((string)$r['ordadr_address']);$entrance=\trim((string)$r['entrance']);$registration=\trim((string)$r['regnumber']);
        $start=self::date($r['workdatestart']);$finish=self::date($r['workdateendadjusted'])??self::date($r['plan_finish_date']);
        if((int)$r['id']!==$id||$address===''||$entrance===''||$registration===''||$start===null||$finish===null)return null;
        return ['id'=>$id,'address'=>$address,'entrance'=>$entrance,'registrationNumber'=>$registration,'plannedStartDate'=>$start,'plannedFinishDate'=>$finish];
    }
    private static function date(mixed $value):?string{if(!\is_string($value)||\preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D',$value,$m)!==1||!\checkdate((int)$m[2],(int)$m[3],(int)$m[1])||$m[1]==='0000')return null;return $m[1].'-'.$m[2].'-'.$m[3];}
    private static function optionalLegacyDate(mixed $value):?string{if($value===null)return null;$value=\trim((string)$value);if($value===''||\preg_match('/^0+$/D',$value)===1||\str_starts_with($value,'0000-00-00'))return null;$date=self::date($value);if($date===null)throw new PilotHttpInfrastructureUnavailable();return $date;}
    private static function positiveId(mixed $value):?int{if(!\is_int($value)&&(!\is_string($value)||\preg_match('/^[1-9][0-9]*$/D',$value)!==1))return null;$id=\filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $id===false?null:$id;}
    private static function rfc3339(mixed $value):bool
    {
        if(!\is_string($value)||\preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/D',$value,$parts)!==1)return false;
        $canonical=$parts[1].($parts[2]==='Z'?'+00:00':$parts[2]);
        $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP',$canonical);
        $errors=\DateTimeImmutable::getLastErrors();
        return $parsed!==false&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0))&&$parsed->format('Y-m-d\TH:i:sP')===$canonical;
    }
}

final class MariaDbObjectListReader implements ObjectListReader
{
    public function __construct(private readonly \mysqli $connection,private readonly string $prefix,private readonly string $processPrefix=''){}
    public function read():array
    {
        try{$rows=$this->connection->query("SELECT c.id AS case_id,c.legacy_installation_object_id,l.id AS legacy_id,l.ordadr_address,l.entrance,l.regnumber,l.workdatestart,l.workdateendadjusted,l.plan_finish_date FROM `{$this->processPrefix}fm2_installation_cases` c LEFT JOIN `{$this->prefix}fm_maintable` l ON l.id=c.legacy_installation_object_id LIMIT 501")->fetch_all(MYSQLI_ASSOC);}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
        if(\count($rows)>500)throw new PilotHttpInfrastructureUnavailable();
        $objects=[];$caseIds=[];$objectIds=[];
        foreach($rows as $row){$caseId=self::positiveId($row['case_id']);$id=self::positiveId($row['legacy_installation_object_id']);$legacyId=self::positiveId($row['legacy_id']);$address=\trim((string)$row['ordadr_address']);$entrance=\trim((string)$row['entrance']);$registration=\trim((string)$row['regnumber']);$start=self::date($row['workdatestart']);$finish=self::date($row['workdateendadjusted'])??self::date($row['plan_finish_date']);if($caseId===null||$id===null||$legacyId!==$id||isset($caseIds[$caseId])||isset($objectIds[$id])||$address===''||$entrance===''||$registration===''||$start===null||$finish===null)throw new PilotHttpInfrastructureUnavailable();$caseIds[$caseId]=true;$objectIds[$id]=true;$objects[]=['id'=>$id,'registrationNumber'=>$registration,'address'=>$address,'entrance'=>$entrance,'plannedStartDate'=>$start,'plannedFinishDate'=>$finish];}
        \usort($objects,static fn(array $a,array $b):int=>[$a['plannedStartDate'],$a['id']]<=>[$b['plannedStartDate'],$b['id']]);return $objects;
    }
    private static function date(mixed $value):?string{if(!\is_string($value)||\preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D',$value,$m)!==1||!\checkdate((int)$m[2],(int)$m[3],(int)$m[1])||$m[1]==='0000')return null;return $m[1].'-'.$m[2].'-'.$m[3];}
    private static function positiveId(mixed $value):?int{if(!\is_int($value)&&(!\is_string($value)||\preg_match('/^[1-9][0-9]*$/D',$value)!==1))return null;$id=\filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $id===false?null:$id;}
}

class PilotHttpCoordinator
{
    public function __construct(private TrustedServerIdentity $identity,private PilotShellRenderer $shell,private PilotHttpDependencies $dependencies,private ?ObjectCardRenderer $cards=null,private ?ObjectCardReaderProvider $cardReaders=null,private ?ObjectListRenderer $lists=null,private ?ObjectListReaderProvider $listReaders=null,private ?PrepareFormRenderer $prepareForms=null,private ?PrepareFormReaderProvider $prepareReaders=null,private ?ProductionChecklistRenderer $checklists=null){}
    public function handle(PilotHttpRequest $r):PilotHttpResponse
    {
        $cardId=self::cardId($r->path);$prepareId=self::prepareId($r->path);$checklistId=self::checklistId($r->path);
        $shlzRelative=self::shlzRelative($r->path);$assetCandidate=$shlzRelative!==null;
        if(!\in_array($r->path,['/pilot','/pilot/','/pilot/assets/pilot.css','/pilot/assets/pilot-20260829-22.css','/pilot/assets/pilot-20260829-23.css','/pilot/assets/picker.js','/pilot/assets/users.js','/pilot/assets/checklist.js','/pilot/assets/checklist-sw.js','/pilot/assets/control-queue.js','/pilot/objects'],true)&&!$assetCandidate&&$cardId===null&&$prepareId===null&&$checklistId===null)return $this->response(404,"Not found.\n");
        if(!\in_array($r->method,['GET','HEAD'],true))return $this->response(405,"Method not allowed.\n",['Allow'=>'GET, HEAD'],$r->method);
        if($r->path==='/pilot')return $this->response(308,'',['Location'=>'/pilot/'],$r->method);
        if($assetCandidate){try{$asset=$this->dependencies instanceof ShlzAssetProvider?$this->dependencies->shlzAsset($shlzRelative):($shlzRelative==='shlz.css'?$this->dependencies->css():null);if($asset===null)return $this->response(404,"Not found.\n",[],$r->method);$body=$asset->readBytes();return $this->response(200,$body,['Content-Type'=>'text/css; charset=UTF-8'],$r->method);}catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        if(\in_array($r->path,['/pilot/assets/pilot.css','/pilot/assets/pilot-20260829-22.css','/pilot/assets/pilot-20260829-23.css'],true)){$assetConfigured=$this->dependencies instanceof PilotUiConfiguration&&$this->dependencies->pilotUiConfigured();if(!$assetConfigured)return $this->response(404,"Not found.\n",[],$r->method);try{$body=$this->dependencies->pilotCss()->readBytes();return $this->response(200,$body,['Content-Type'=>'text/css; charset=UTF-8'],$r->method);}catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        if($r->path==='/pilot/assets/picker.js'){try{$body=\file_get_contents(__DIR__.'/picker.js');if($body===false)throw new PilotHttpInfrastructureUnavailable();return $this->response(200,$body,['Content-Type'=>'text/javascript; charset=UTF-8'],$r->method);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        if($r->path==='/pilot/assets/users.js'){try{$body=\file_get_contents(__DIR__.'/users.js');if($body===false)throw new PilotHttpInfrastructureUnavailable();return $this->response(200,$body,['Content-Type'=>'text/javascript; charset=UTF-8'],$r->method);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        if($r->path==='/pilot/assets/checklist.js'){try{$body=\file_get_contents(__DIR__.'/checklist.js');if($body===false)throw new PilotHttpInfrastructureUnavailable();return $this->response(200,$body,['Content-Type'=>'text/javascript; charset=UTF-8'],$r->method);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        if($r->path==='/pilot/assets/control-queue.js'){try{$body=\file_get_contents(__DIR__.'/control-queue.js');if($body===false)throw new PilotHttpInfrastructureUnavailable();return $this->response(200,$body,['Content-Type'=>'text/javascript; charset=UTF-8'],$r->method);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        if($r->path==='/pilot/assets/checklist-sw.js'){try{$body=\file_get_contents(__DIR__.'/checklist-sw.js');if($body===false)throw new PilotHttpInfrastructureUnavailable();return $this->response(200,$body,['Content-Type'=>'text/javascript; charset=UTF-8','Service-Worker-Allowed'=>'/pilot/','Content-Security-Policy'=>"default-src 'self'; connect-src 'self'"],$r->method);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}
        $configured=$this->dependencies instanceof PilotUiConfiguration&&$this->dependencies->pilotUiConfigured();
        try{$this->dependencies->css()->readBytes();if($configured)$this->dependencies->pilotCss()->readBytes();$user=$this->dependencies->users()->resolveActiveUser($principal);if($user===null)return $this->response(403,"Access denied.\n",[],$r->method);if($checklistId!==null){if($this->cardReaders===null||$this->checklists===null)throw new PilotHttpInfrastructureUnavailable();$card=$this->cardReaders->objectCards()->read($checklistId);if($card===null)return $this->response(404,"Not found.\n",[],$r->method);$roleAccess=$this->dependencies instanceof ChecklistAccessProvider&&$this->dependencies->canEditChecklist($user->id);return $this->response(200,$this->checklists->render($user,$card,$roleAccess),['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}if($prepareId!==null){if($this->prepareReaders===null||$this->prepareForms===null)throw new PilotHttpInfrastructureUnavailable();if(!$this->prepareReaders->hasCapability($user->id,'assignment_order.prepare'))return $this->response(403,"Access denied.\n",[],$r->method);$form=$this->prepareReaders->prepareForms()->read($prepareId,$this->prepareReaders->processDate());if($form===null)return $this->response(404,"Not found.\n",[],$r->method);$html=!$configured&&$this->prepareForms instanceof CompatibilityPrepareFormRenderer?$this->prepareForms->renderCompatibility($user,$form):$this->prepareForms->render($user,$form);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}if($r->path==='/pilot/objects'){if($this->listReaders===null||$this->lists===null)throw new PilotHttpInfrastructureUnavailable();$objects=$this->listReaders->objectList()->read();$html=!$configured&&$this->lists instanceof CompatibilityObjectListRenderer?$this->lists->renderCompatibility($user,$objects):$this->lists->render($user,$objects);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}if($cardId!==null){if($this->cardReaders===null||$this->cards===null)throw new PilotHttpInfrastructureUnavailable();$card=$this->cardReaders->objectCards()->read($cardId);if($card===null)return $this->response(404,"Not found.\n",[],$r->method);$prepareConfigured=!($this->dependencies instanceof PilotUiConfiguration)||$this->dependencies->prepareCommandConfigured();$compatibility=$this->dependencies instanceof PilotUiConfiguration&&!$configured&&!$prepareConfigured;$hasPrepareCapability=false;if(!$compatibility){if($this->prepareReaders===null)throw new PilotHttpInfrastructureUnavailable();$hasPrepareCapability=$this->prepareReaders->hasCapability($user->id,'assignment_order.prepare');}$card['canPrepare']=$prepareConfigured&&$hasPrepareCapability;$html=!$configured&&$this->cards instanceof CompatibilityObjectCardRenderer?$this->cards->renderCompatibility($user,$card):$this->cards->render($user,$card);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}$html=!$configured&&$this->shell instanceof CompatibilityPilotShellRenderer?$this->shell->renderCompatibility($user):$this->shell->render($user);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}catch(PrepareFormUnavailable){return $this->response(409,"Формирование распоряжения недоступно для текущего состояния объекта монтажа.\n",[],$r->method);}catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}
    }
    private static function cardId(string $path):?int
    {
        if(\preg_match('#^/pilot/objects/([1-9][0-9]*)$#D',$path,$m)!==1)return null;
        $digits=$m[1];if(\strlen($digits)>19||(\strlen($digits)===19&&\strcmp($digits,'9223372036854775807')>0))return null;
        return (int)$digits;
    }
    private static function checklistId(string $path):?int
    {
        if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/checklist$#D',$path,$m)!==1)return null;$digits=$m[1];if(\strlen($digits)>19||(\strlen($digits)===19&&\strcmp($digits,'9223372036854775807')>0))return null;return (int)$digits;
    }
    private static function shlzRelative(string $path):?string{if(\preg_match('#^/pilot/assets/([A-Za-z0-9][A-Za-z0-9._-]*(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*\.css)$#D',$path,$m)!==1||$m[1]==='pilot.css')return null;return $m[1];}
    private static function prepareId(string $path):?int
    {
        if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/assignment-order/prepare$#D',$path,$m)!==1)return null;$digits=$m[1];if(\strlen($digits)>19||(\strlen($digits)===19&&\strcmp($digits,'9223372036854775807')>0))return null;return (int)$digits;
    }
    private function response(int $status,string $getBody,array $headers=[],string $method='GET'):PilotHttpResponse
    {
        $headers += [
            'Content-Type'=>'text/plain; charset=UTF-8',
            'X-Content-Type-Options'=>'nosniff',
            'Referrer-Policy'=>'no-referrer',
            'X-Frame-Options'=>'DENY',
            'Content-Security-Policy'=>(($headers['Content-Type']??'')==='text/html; charset=UTF-8'&&\str_contains($getBody,'data-checklist'))?"default-src 'none'; style-src 'self'; script-src 'self'; worker-src 'self'; connect-src 'self'; img-src 'self' blob:; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'":"default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",
            'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Opener-Policy'=>'same-origin',
            'Cache-Control'=>'no-store',
        ];
        $headers['Content-Length']=(string)\strlen($getBody);
        return new PilotHttpResponse($status,$headers,$method==='HEAD'?'':$getBody);
    }
}

final class PilotHttpRequestFactory
{
    public function fromServer(array $server):PilotHttpRequest
    {
        $host=PHP_SAPI==='cli-server'?($server['HTTP_HOST']??null):($server['FMONITOR_TRUSTED_REQUEST_HOST']??null);if(!self::validHost($host))throw new InvalidHttpRequest();
        $method=\strtoupper((string)($server['REQUEST_METHOD']??''));$uri=(string)($server['REQUEST_URI']??'');$raw=\explode('?',$uri,2)[0];$invalid=\preg_match('/%(?![0-9A-Fa-f]{2})/',$raw)===1||\preg_match('/%(?:2f|5c)/i',$raw)===1;$path=\rawurldecode($raw);$invalid=$invalid||\str_contains($path,"\0")||\str_contains($path,'\\')||\str_contains($path,'//')||(\str_starts_with($path,'/pilot/objects/')&&$raw!==$path);foreach(\explode('/',$path) as $s)if($s==='.'||$s==='..')$invalid=true;if($invalid){if(PHP_SAPI!=='cli-server')throw new InvalidHttpRequest();$path="\0invalid-path";}
        return new PilotHttpRequest($method,$path,$host,$server['REMOTE_USER']??null,$server,'');
    }

    private static function validHost(mixed $host):bool
    {
        if (!\is_string($host) || \strlen($host) < 1 || \strlen($host) > 253
            || \preg_match('/[\x00-\x20\x7f,\/\\\\@#?%]/', $host) === 1) return false;
        if (\str_starts_with($host, '[')) {
            if (\preg_match('/^\[([^\]]+)\](?::([0-9]+))?$/D', $host, $match) !== 1
                || \str_contains($match[1], '%') || @\inet_pton($match[1]) === false
                || \strlen((string)@\inet_pton($match[1])) !== 16) return false;
            return self::validPort($match[2] ?? null);
        }
        if (\substr_count($host, ':') > 1) return false;
        $parts=\explode(':',$host,2);$name=$parts[0];if(!self::validPort($parts[1]??null))return false;
        if(\preg_match('/^[0-9.]+$/D',$name)===1){$octets=\explode('.',$name);if(\count($octets)!==4)return false;foreach($octets as $octet)if(\preg_match('/^(?:0|[1-9][0-9]{0,2})$/D',$octet)!==1||(int)$octet>255)return false;return true;}
        foreach(\explode('.',$name) as $label)if(\preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/D',$label)!==1)return false;
        return true;
    }

    private static function validPort(?string $port):bool{return $port===null||(\preg_match('/^[1-9][0-9]*$/D',$port)===1&&(int)$port<=65535);}
}

final class ProcessEnvironmentSource implements EnvironmentSource
{
    public function read(string $name):string|false{return \getenv($name);}
}

final class ProductionPilotHttpDependencies implements PilotHttpDependencies,ObjectCardReaderProvider,ObjectListReaderProvider,PrepareFormReaderProvider,ChecklistAccessProvider,PilotUiConfiguration,ShlzAssetProvider
{
    private ?CssAsset $cssAsset=null;
    private ?ShlzCssManifest $shlzManifest=null;
    private ?CssAsset $pilotCssAsset=null;
    private ?HttpUserDirectory $userDirectory=null;
    private ?ObjectCardReader $objectCardReader=null;
    private ?ObjectListReader $objectListReader=null;
    private ?PrepareFormReader $prepareFormReader=null;
    private ?MariaDbInstallerDirectoryReader $installerDirectoryReader=null;
    private ?\mysqli $connection=null;
    private ?string $legacyTablePrefix=null;
    private string $processTablePrefix='';
    public function __construct(private readonly EnvironmentSource $environment,private readonly CssDescriptorOpener $cssDescriptors){}
    public function css():CssAsset
    {
        if($this->cssAsset!==null)return $this->cssAsset;$path=$this->environment->read('FMONITOR_SHLZ_CSS_PATH');if(!\is_string($path))throw new CssAssetUnavailable();return $this->cssAsset=new ShlzCssAsset($path,$this->cssDescriptors);
    }
    public function shlzAsset(string $relativePath):?CssAsset{if($this->shlzManifest===null){$path=$this->environment->read('FMONITOR_SHLZ_CSS_PATH');if(!\is_string($path))throw new CssAssetUnavailable();$this->shlzManifest=new ShlzCssManifest($path,$this->cssDescriptors);}return $this->shlzManifest->asset($relativePath);}
    public function pilotCss():CssAsset
    {
        if($this->pilotCssAsset!==null)return $this->pilotCssAsset;
        $configured=$this->environment->read('FMONITOR_PILOT_CSS_PATH');if(!\is_string($configured))throw new CssAssetUnavailable();
        return $this->pilotCssAsset=new PilotCssAsset($configured,$this->cssDescriptors);
    }
    public function users():HttpUserDirectory
    {
        if($this->userDirectory!==null)return $this->userDirectory;
        $names=['FMONITOR_DB_HOST','FMONITOR_DB_PORT','FMONITOR_DB_NAME','FMONITOR_DB_USER','FMONITOR_DB_PASSWORD','FMONITOR_LEGACY_TABLE_PREFIX'];$v=[];
        foreach($names as $name)$v[$name]=$this->environment->read($name);
        if(!\is_string($v['FMONITOR_DB_HOST'])||!\is_string($v['FMONITOR_DB_PORT'])||!\is_string($v['FMONITOR_DB_NAME'])||!\is_string($v['FMONITOR_DB_USER'])||!\is_string($v['FMONITOR_DB_PASSWORD'])||!\is_string($v['FMONITOR_LEGACY_TABLE_PREFIX']))throw new PilotHttpInfrastructureUnavailable();
        $port=\preg_match('/^[1-9][0-9]*$/D',$v['FMONITOR_DB_PORT'])===1?(int)$v['FMONITOR_DB_PORT']:0;
        if($port<1||$port>65535||$v['FMONITOR_DB_HOST']===''||$v['FMONITOR_DB_NAME']===''||$v['FMONITOR_DB_USER']===''||\strlen($v['FMONITOR_LEGACY_TABLE_PREFIX'])>32||\preg_match('/^[A-Za-z0-9_]*$/D',$v['FMONITOR_LEGACY_TABLE_PREFIX'])!==1)throw new PilotHttpInfrastructureUnavailable();
        $this->legacyTablePrefix=$v['FMONITOR_LEGACY_TABLE_PREFIX'];
        try{$this->connection=@new \mysqli($v['FMONITOR_DB_HOST'],$v['FMONITOR_DB_USER'],$v['FMONITOR_DB_PASSWORD'],$v['FMONITOR_DB_NAME'],$port);if(!$this->connection->set_charset('utf8mb4'))throw new \RuntimeException();return $this->userDirectory=new MariaDbHttpUserDirectory($this->connection,$this->legacyTablePrefix,$this->resolveProcessPrefix());}catch(\Throwable $e){if($this->connection instanceof \mysqli)try{$this->connection->close();}catch(\Throwable){}$this->connection=null;$this->legacyTablePrefix=null;throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    public function objectCards():ObjectCardReader
    {
        if($this->objectCardReader!==null)return $this->objectCardReader;
        $this->users();
        if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();
        return $this->objectCardReader=new MariaDbObjectCardReader($this->connection,$this->legacyTablePrefix,$this->resolveProcessPrefix());
    }
    public function objectList():ObjectListReader
    {
        if($this->objectListReader!==null)return $this->objectListReader;$this->users();if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();return $this->objectListReader=new MariaDbObjectListReader($this->connection,$this->legacyTablePrefix,$this->resolveProcessPrefix());
    }
    public function prepareForms():PrepareFormReader
    {
        if($this->prepareFormReader!==null)return $this->prepareFormReader;$this->users();if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();return $this->prepareFormReader=new MariaDbPrepareFormReader($this->connection,$this->legacyTablePrefix,$this->resolveProcessPrefix());
    }
    public function installerDirectory():MariaDbInstallerDirectoryReader
    {
        if($this->installerDirectoryReader!==null)return $this->installerDirectoryReader;$this->users();if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();return $this->installerDirectoryReader=new MariaDbInstallerDirectoryReader($this->connection,$this->resolveProcessPrefix(),$this->legacyTablePrefix);
    }
    public function hasCapability(int $userId,string $capability):bool
    {
        $this->users();$this->resolveProcessPrefix();try{$s=$this->connection->prepare("SELECT user_id FROM `{$this->processTablePrefix}fm2_process_user_capabilities` WHERE user_id=? AND capability=? LIMIT 2");$s->bind_param('is',$userId,$capability);$s->execute();return \count($s->get_result()->fetch_all(MYSQLI_ASSOC))===1;}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    public function canEditChecklist(int $userId):bool
    {
        $this->users();$this->resolveProcessPrefix();
        try{
            if($this->hasCapability($userId,'construction_control_engineer'))return true;
            $s=$this->connection->prepare("SELECT ur.user_id FROM `{$this->processTablePrefix}fm2_pilot_user_roles` ur JOIN `{$this->processTablePrefix}fm2_pilot_users` u ON u.user_id=ur.user_id JOIN `{$this->processTablePrefix}fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE ur.user_id=? AND u.status=1 AND r.status=1 AND (r.name='Строительный контроль' OR r.name='Администратор' OR r.name='Суперадминистратор' OR r.name LIKE 'Руководитель %' OR r.name LIKE 'Директор %') LIMIT 1");
            $s->bind_param('i',$userId);$s->execute();return $s->get_result()->fetch_assoc()!==null;
        }catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    private function resolveProcessPrefix():string{$configured=$this->environment->read('FMONITOR_PROCESS_TABLE_PREFIX');$this->processTablePrefix=\is_string($configured)?$configured:'';if(\strlen($this->processTablePrefix)>32||\preg_match('/^[A-Za-z0-9_]*$/D',$this->processTablePrefix)!==1)throw new PilotHttpInfrastructureUnavailable();return $this->processTablePrefix;}
    public function processDate():string{$v=$this->environment->read('FMONITOR_NOW');try{return new \DateTimeImmutable(\is_string($v)?$v:'now')->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d');}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}}
    public function commandResources():array
    {
        $this->users();if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();
        $processPrefix=$this->environment->read('FMONITOR_PROCESS_TABLE_PREFIX');$root=$this->environment->read('FMONITOR_ARTIFACT_STORAGE_ROOT');$now=$this->environment->read('FMONITOR_NOW');
        if(!\is_string($processPrefix)||!\is_string($root)||!\is_string($now))throw new PilotHttpInfrastructureUnavailable();
        return [$this->connection,$processPrefix,$this->legacyTablePrefix,$root,$now];
    }
    public function e2eConfigured():bool{return \is_string($this->environment->read('FMONITOR_ARTIFACT_STORAGE_ROOT'))&&\is_string($this->environment->read('FMONITOR_PROCESS_TABLE_PREFIX'))&&\is_string($this->environment->read('FMONITOR_NOW'));}
    public function pilotUiConfigured():bool{return $this->environment instanceof ProcessEnvironmentSource&&\is_string($this->environment->read('FMONITOR_PILOT_CSS_PATH'));}
    public function prepareCommandConfigured():bool{if(!$this->environment instanceof ProcessEnvironmentSource)return false;try{$this->users();$prefix=$this->environment->read('FMONITOR_PROCESS_TABLE_PREFIX');$table=(\is_string($prefix)?$prefix:'').'fm2_process_user_capabilities';$q=$this->connection->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');$q->bind_param('s',$table);$q->execute();return $q->get_result()->fetch_assoc()!==null;}catch(\Throwable){return false;}}
    public function close():void
    {
        $css=$this->cssAsset;$manifest=$this->shlzManifest;$pilotCss=$this->pilotCssAsset;$this->cssAsset=null;$this->shlzManifest=null;$this->pilotCssAsset=null;$connection=$this->connection;$this->connection=null;$this->legacyTablePrefix=null;$this->userDirectory=null;$this->objectCardReader=null;$this->objectListReader=null;$this->prepareFormReader=null;$this->installerDirectoryReader=null;$first=null;
        try{if($css!==null)$css->close();}catch(\Throwable $e){$first=$e;}try{if($manifest!==null)$manifest->close();}catch(\Throwable $e){$first??=$e;}
        try{if($pilotCss!==null)$pilotCss->close();}catch(\Throwable $e){$first??=$e;}
        try{if($connection instanceof \mysqli&&$connection->close()!==true)throw new \RuntimeException();}catch(\Throwable $e){$first??=$e;}
        if($first!==null)throw $first;
    }
}

final class MariaDbHttpUserDirectory implements HttpUserDirectory
{
    public function __construct(private readonly \mysqli $connection,private readonly string $prefix,private readonly string $processPrefix=''){}
    public function resolveActiveUser(string $principal):?HttpUser
    {
        try{
            $pilotTable=$this->processPrefix.'fm2_pilot_users';$table=$this->connection->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');$table->bind_param('s',$pilotTable);$table->execute();
            if($table->get_result()->fetch_assoc()!==null){$s=$this->connection->prepare("SELECT user_id AS id,full_name AS name,email FROM `{$pilotTable}` WHERE BINARY email=BINARY ? AND status=1 LIMIT 2");$s->bind_param('s',$principal);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);if($rows!==[])return $this->user($rows,$principal);}
            $s=$this->connection->prepare("SELECT u.id,u.name,u.email FROM `{$this->prefix}users` u JOIN `{$this->prefix}users_roles` r ON r.id=u.role_id WHERE BINARY u.email=BINARY ? AND u.status=1 AND r.status=1 LIMIT 2");$s->bind_param('s',$principal);$s->execute();return $this->user($s->get_result()->fetch_all(MYSQLI_ASSOC),$principal);
        }catch(\Throwable $e){if($e instanceof PilotHttpInfrastructureUnavailable)throw $e;throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    private function user(array $rows,string $principal):?HttpUser{if(\count($rows)!==1)return null;$row=$rows[0];$id=\filter_var($row['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false||\trim((string)$row['name'])===''||$row['email']!==$principal)throw new PilotHttpInfrastructureUnavailable();return new HttpUser($id,(string)$row['name'],(string)$row['email']);}
}

final class ErrorLogUnexpectedFailureReporter implements UnexpectedFailureReporter
{
    public function report(string $category,string $correlationId):void{\error_log($category.' '.$correlationId);}
}

final class RandomCorrelationIdSource implements CorrelationIdSource
{
    public function nextId():string{return \bin2hex(\random_bytes(16));}
}

class PilotHttpGateway
{
    public function __construct(private readonly PilotHttpRequestFactory $requests,private readonly PilotHttpCoordinator $application,private readonly PilotHttpDependencies $dependencies,private readonly CorrelationIdSource $correlationIds,private readonly UnexpectedFailureReporter $failures){}
    public function handle(array $server):PilotHttpResponse
    {
        $correlationId='correlation-unavailable';$reported=false;$correlationValid=false;
        try{$candidate=$this->correlationIds->nextId();if(\preg_match('/^[A-Za-z0-9._-]{1,128}$/D',$candidate)!==1)throw new \RuntimeException();$correlationId=$candidate;$correlationValid=true;}
        catch(\Throwable){$response=self::failure(503,"Service unavailable.\n",['Retry-After'=>'60']);$reported=true;$this->guardedReport('pilot_http_correlation_failure',$correlationId);}
        if($correlationValid){try{$response=$this->application->handle($this->requests->fromServer($server));}catch(InvalidHttpRequest){$response=self::failure(400,"Bad request.\n");}catch(\Throwable){$response=self::failure(503,"Service unavailable.\n",['Retry-After'=>'60']);$reported=true;$this->guardedReport('pilot_http_unexpected_failure',$correlationId);}}
        try{$this->dependencies->close();}catch(\Throwable){$response=self::failure(503,"Service unavailable.\n",['Retry-After'=>'60']);if(!$reported)$this->guardedReport('pilot_http_unexpected_failure',$correlationId);}
        return $response;
    }
    private function guardedReport(string $category,string $correlationId):void{try{$this->failures->report($category,$correlationId);}catch(\Throwable){}}
    private static function failure(int $status,string $body,array $extra=[]):PilotHttpResponse
    {
        $headers=['Content-Type'=>'text/plain; charset=UTF-8','X-Content-Type-Options'=>'nosniff','Referrer-Policy'=>'no-referrer','X-Frame-Options'=>'DENY','Content-Security-Policy'=>"default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()','Cross-Origin-Opener-Policy'=>'same-origin','Cache-Control'=>'no-store']+$extra;$headers['Content-Length']=(string)\strlen($body);return new PilotHttpResponse($status,$headers,$body);
    }
}

final class ProductionPilotHttpGatewayFactory
{
    public static function create(EnvironmentSource $environment):PilotHttpGateway
    {
        $closer=new NativePhpStreamCloser(new NativePhpFclosePrimitive());
        $dependencies=new ProductionPilotHttpDependencies($environment,new PhpCssDescriptorOpener($closer));
        $application=new PilotHttpCoordinator(new RemoteUserIdentity(),new ProductionPilotShellRenderer(),$dependencies,new ProductionObjectCardRenderer(),$dependencies,new ProductionObjectListRenderer(),$dependencies,new ProductionPrepareFormRenderer(),$dependencies,new ProductionChecklistRenderer());
        return new PilotHttpGateway(new PilotHttpRequestFactory(),$application,$dependencies,new RandomCorrelationIdSource(),new ErrorLogUnexpectedFailureReporter());
    }
}

require_once __DIR__.'/PilotView.php';
require_once __DIR__.'/PilotShellView.php';
require_once __DIR__.'/ObjectListView.php';
require_once __DIR__.'/ObjectCardView.php';
require_once __DIR__.'/PrepareFormView.php';
require_once __DIR__.'/InstallerDirectoryView.php';
require_once __DIR__.'/ChecklistView.php';
