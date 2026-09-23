<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class NativeBitrixOrderDocumentDelivery
{
    private const MAX_EXPANDED_ITEMS=50000;
    private \Closure $transport;
    public function __construct(private readonly BitrixOrderDocumentDeliveryConfig $config, ?callable $transport=null)
    {
        $this->transport=$transport===null?self::curlTransport(...):\Closure::fromCallable($transport);
    }
    public function fetch(array $current=[]): BitrixOrderDocumentDeliveryResult
    {
        if (!$this->validConfig()) return BitrixOrderDocumentDeliveryResult::failed('CONFIGURATION_UNAVAILABLE');
        try {
            $known=$this->knownUrls($current);
            $folders=[];$start=0;$seen=0;$expectedTotal=null;$pages=0;
            while (true) {
                if(++$pages>$this->config->maxItems)throw new \DomainException('LIMIT_EXCEEDED');
                $data=$this->request('disk.folder.getchildren',['id'=>$this->config->rootFolderId,'start'=>$start]);
                if (!isset($data['result']) || !is_array($data['result']) || !array_is_list($data['result'])) throw new \DomainException('SCHEMA_INVALID');
                if(isset($data['total'])){if(!is_int($data['total'])||$data['total']<0||($expectedTotal!==null&&$expectedTotal!==$data['total']))throw new \DomainException('PAGINATION_INVALID');$expectedTotal=$data['total'];}
                foreach ($data['result'] as $row) {
                    $seen++;if($seen>$this->config->maxItems)throw new \DomainException('LIMIT_EXCEEDED');
                    if (!is_array($row) || !isset($row['ID'],$row['NAME']) || !is_scalar($row['ID']) || !is_string($row['NAME'])) throw new \DomainException('SCHEMA_INVALID');
                    if (($row['TYPE'] ?? 'folder') !== 'folder') continue;
                    $folders[]=['id'=>(string)$row['ID'],'name'=>$row['NAME']];
                    if (count($folders)>$this->config->maxItems) throw new \DomainException('LIMIT_EXCEEDED');
                }
                $next=$data['next']??null;
                if ($next===null) {if($expectedTotal!==null&&$seen!==$expectedTotal)throw new \DomainException('PAGINATION_INVALID');break;}
                if (!is_int($next) || $next<=$start) throw new \DomainException('PAGINATION_INVALID');
                $start=$next;
            }
            $resolved=[];$pending=[];
            foreach ($folders as $folder) {
                $this->orderNumbers($folder['name']);
                $key=$folder['id']."\0".$folder['name'];
                if(isset($known[$key]))$resolved[$key]=$known[$key];else$pending[]=$folder;
            }
            foreach(array_chunk($pending,50)as$batch){
                $commands=[];
                foreach($batch as$index=>$folder)$commands['folder_'.$index]='disk.folder.getExternalLink?'.http_build_query(['id'=>$folder['id']],'','&',PHP_QUERY_RFC3986);
                $data=$this->batch($commands);
                if(!isset($data['result'])||!is_array($data['result'])||!isset($data['result']['result'],$data['result']['result_error'])
                    ||!is_array($data['result']['result'])||!is_array($data['result']['result_error'])||$data['result']['result_error']!==[]
                    ||array_keys($data['result']['result'])!==array_keys($commands))throw new \DomainException('API_FAILED');
                foreach($batch as$index=>$folder){$url=$data['result']['result']['folder_'.$index]??null;if(!is_string($url)||!$this->validUrl($url))throw new \DomainException('URL_INVALID');$resolved[$folder['id']."\0".$folder['name']]=$url;}
            }
            $links=[];
            foreach ($folders as $folder) {
                $orders=$this->orderNumbers($folder['name']);
                $url=$resolved[$folder['id']."\0".$folder['name']]??null;
                if(!is_string($url))throw new \DomainException('SCHEMA_INVALID');
                foreach ($orders as $order) {
                    $links[]=['sourceFolderId'=>$folder['id'],'sourceFolderName'=>$folder['name'],'orderNumber'=>$order,'url'=>$url];
                    if (count($links)>$this->maxExpandedItems()) throw new \DomainException('LIMIT_EXCEEDED');
                }
            }
            return BitrixOrderDocumentDeliveryResult::complete($links);
        } catch (\DomainException $error) {
            return BitrixOrderDocumentDeliveryResult::failed($error->getMessage());
        } catch (\Throwable) {
            return BitrixOrderDocumentDeliveryResult::failed('TRANSPORT_FAILED');
        }
    }
    private function knownUrls(array$current):array
    {
        $known=[];
        foreach($current as$row){
            if(!is_array($row)||!isset($row['sourceFolderId'],$row['sourceFolderName'],$row['url'])||!is_scalar($row['sourceFolderId'])
                ||!is_string($row['sourceFolderName'])||!is_string($row['url'])||!$this->validUrl($row['url']))throw new \DomainException('SCHEMA_INVALID');
            $id=(string)$row['sourceFolderId'];$name=$row['sourceFolderName'];
            if(preg_match('/^[1-9][0-9]*$/D',$id)!==1||$name===''||str_contains($name,"\0"))throw new \DomainException('SCHEMA_INVALID');
            $key=$id."\0".$name;
            if(isset($known[$key])&&$known[$key]!==$row['url'])throw new \DomainException('SCHEMA_INVALID');
            $known[$key]=$row['url'];
        }
        return$known;
    }
    private function orderNumbers(string$name):array
    {
        if($name===''||str_contains($name,"\0"))throw new \DomainException('AMBIGUOUS_FOLDER_NAME');
        if(preg_match('/^[0-9]+\.[0-9]+-/D',$name)!==1)return[$name];
        return BitrixOrderDocumentFolderMapper::orderNumbers($name,$this->maxExpandedItems());
    }
    private function maxExpandedItems():int{return min($this->config->maxItems,intdiv(self::MAX_EXPANDED_ITEMS,2))*2;}
    private function request(string $method,array $query): array
    {
        $base=rtrim($this->config->origin,'/').'/rest/'.$this->config->webhookUserId.'/'.rawurlencode($this->config->token).'/'.$method.'.json';
        $query['limit']=$this->config->pageSize;$url=$base.'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986);
        $response=($this->transport)('GET',$url,['timeout'=>$this->config->timeoutSeconds,'caFile'=>$this->config->caFile,'maxBytes'=>$this->config->maxBytes]);
        if (!is_array($response) || !isset($response['status'],$response['body']) || !is_int($response['status']) || !is_string($response['body'])) throw new \DomainException('TRANSPORT_FAILED');
        if (strlen($response['body'])>$this->config->maxBytes) throw new \DomainException('LIMIT_EXCEEDED');
        if ($response['status']<200 || $response['status']>=300) throw new \DomainException('API_FAILED');
        try {$data=json_decode($response['body'],true,32,JSON_THROW_ON_ERROR);} catch (\JsonException) {throw new \DomainException('SCHEMA_INVALID');}
        if (!is_array($data) || isset($data['error'])) throw new \DomainException(isset($data['error'])?'API_FAILED':'SCHEMA_INVALID');
        return $data;
    }
    private function batch(array$commands):array
    {
        $base=rtrim($this->config->origin,'/').'/rest/'.$this->config->webhookUserId.'/'.rawurlencode($this->config->token).'/batch.json';
        return$this->decode(($this->transport)('POST',$base,['timeout'=>$this->config->timeoutSeconds,'caFile'=>$this->config->caFile,'maxBytes'=>$this->config->maxBytes,'form'=>['halt'=>1,'cmd'=>$commands]]));
    }
    private function decode(mixed$response):array
    {
        if (!is_array($response) || !isset($response['status'],$response['body']) || !is_int($response['status']) || !is_string($response['body'])) throw new \DomainException('TRANSPORT_FAILED');
        if (strlen($response['body'])>$this->config->maxBytes) throw new \DomainException('LIMIT_EXCEEDED');
        if ($response['status']<200 || $response['status']>=300) throw new \DomainException('API_FAILED');
        try {$data=json_decode($response['body'],true,32,JSON_THROW_ON_ERROR);} catch (\JsonException) {throw new \DomainException('SCHEMA_INVALID');}
        if (!is_array($data) || isset($data['error'])) throw new \DomainException(isset($data['error'])?'API_FAILED':'SCHEMA_INVALID');
        return$data;
    }
    private function validConfig(): bool
    {
        $parts=parse_url($this->config->origin);
        return is_array($parts) && ($parts['scheme']??null)==='https' && isset($parts['host']) && !isset($parts['user'],$parts['pass'],$parts['query'],$parts['fragment'])
            && $this->config->webhookUserId>0 && $this->config->rootFolderId>0 && $this->config->token!=='' && !str_contains($this->config->token,"\0")
            && (($parts['path']??'')===''||$parts['path']==='/') && str_starts_with($this->config->loginPath,'/') && $this->config->pageSize>0 && $this->config->maxItems>0
            && $this->config->maxBytes>0 && $this->config->timeoutSeconds>0;
    }
    private function validUrl(string $url): bool
    {
        $actual=parse_url($url);$origin=parse_url($this->config->origin);
        if(!is_array($actual)||!is_array($origin)||($actual['scheme']??null)!=='https'||isset($actual['user'],$actual['pass']))return false;
        $host=strtolower((string)($actual['host']??''));
        return($host===strtolower((string)$origin['host'])&&($actual['port']??null)===($origin['port']??null))
            ||($host==='bitrix24public.com'&&!isset($actual['port']));
    }
    private static function curlTransport(string $method,string $url,array $options): array
    {
        if (!function_exists('curl_init')) throw new \RuntimeException();
        $curl=curl_init($url);if($curl===false)throw new \RuntimeException();$body='';$max=(int)$options['maxBytes'];$exceeded=false;
        $settings=[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>(int)$options['timeout'],CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROXY=>'',CURLOPT_WRITEFUNCTION=>static function($handle,string$chunk)use(&$body,$max,&$exceeded):int{if(strlen($body)+strlen($chunk)>$max){$exceeded=true;return 0;}$body.=$chunk;return strlen($chunk);}];
        if($method==='POST'){$settings[CURLOPT_POST]=true;$settings[CURLOPT_POSTFIELDS]=http_build_query($options['form']??[],'','&',PHP_QUERY_RFC3986);}
        if (is_string($options['caFile']??null)) $settings[CURLOPT_CAINFO]=$options['caFile'];
        curl_setopt_array($curl,$settings);$ok=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);
        if($exceeded)throw new \DomainException('LIMIT_EXCEEDED');if($ok!==true||$error!==0)throw new \RuntimeException();return['status'=>$status,'headers'=>[],'body'=>$body];
    }
}
