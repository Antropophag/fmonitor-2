<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class NativeBitrixOrderDocumentDelivery
{
    private \Closure $transport;
    public function __construct(private readonly BitrixOrderDocumentDeliveryConfig $config, ?callable $transport=null)
    {
        $this->transport=$transport===null?self::curlTransport(...):\Closure::fromCallable($transport);
    }
    public function fetch(): BitrixOrderDocumentDeliveryResult
    {
        if (!$this->validConfig()) return BitrixOrderDocumentDeliveryResult::failed('CONFIGURATION_UNAVAILABLE');
        try {
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
            $links=[];
            foreach ($folders as $folder) {
                $orders=BitrixOrderDocumentFolderMapper::orderNumbers($folder['name'],$this->config->maxItems);
                $data=$this->request('disk.folder.getExternalLink',['id'=>$folder['id']]);
                if (!isset($data['result']) || !is_string($data['result']) || !$this->validUrl($data['result'])) throw new \DomainException('URL_INVALID');
                foreach ($orders as $order) {
                    $links[]=['sourceFolderId'=>$folder['id'],'sourceFolderName'=>$folder['name'],'orderNumber'=>$order,'url'=>$data['result']];
                    if (count($links)>$this->config->maxItems) throw new \DomainException('LIMIT_EXCEEDED');
                }
            }
            return BitrixOrderDocumentDeliveryResult::complete($links);
        } catch (\DomainException $error) {
            return BitrixOrderDocumentDeliveryResult::failed($error->getMessage());
        } catch (\Throwable) {
            return BitrixOrderDocumentDeliveryResult::failed('TRANSPORT_FAILED');
        }
    }
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
        return is_array($actual)&&is_array($origin)&&($actual['scheme']??null)==='https'&&!isset($actual['user'],$actual['pass'])
            && strtolower((string)($actual['host']??''))===strtolower((string)$origin['host'])
            && ($actual['port']??null)===($origin['port']??null);
    }
    private static function curlTransport(string $method,string $url,array $options): array
    {
        if (!function_exists('curl_init')) throw new \RuntimeException();
        $curl=curl_init($url);if($curl===false)throw new \RuntimeException();$body='';$max=(int)$options['maxBytes'];$exceeded=false;
        $settings=[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>(int)$options['timeout'],CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROXY=>'',CURLOPT_WRITEFUNCTION=>static function($handle,string$chunk)use(&$body,$max,&$exceeded):int{if(strlen($body)+strlen($chunk)>$max){$exceeded=true;return 0;}$body.=$chunk;return strlen($chunk);}];
        if (is_string($options['caFile']??null)) $settings[CURLOPT_CAINFO]=$options['caFile'];
        curl_setopt_array($curl,$settings);$ok=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        if($exceeded)throw new \DomainException('LIMIT_EXCEEDED');if($ok!==true||$error!==0)throw new \RuntimeException();return['status'=>$status,'headers'=>[],'body'=>$body];
    }
}
