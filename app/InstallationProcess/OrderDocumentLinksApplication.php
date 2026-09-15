<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class OrderDocumentLinksApplication
{
    private MariaDbBitrixOrderDocumentLinks $store;
    public function __construct(\mysqli $db,string $prefix='') {$this->store=MariaDbBitrixOrderDocumentLinks::for($db,$prefix);}
    public function replace(array $links): array
    {
        $valid=[];
        foreach($links as $link){
            if(!is_array($link)||array_keys($link)!==['sourceFolderId','sourceFolderName','orderNumber','url'])throw new \DomainException('BATCH_INVALID');
            $id=(string)$link['sourceFolderId'];$name=$link['sourceFolderName'];$order=$link['orderNumber'];$url=$link['url'];
            if(preg_match('/^[1-9][0-9]{0,19}$/D',$id)!==1||!is_string($name)||$name===''||strlen($name)>255||str_contains($name,"\0")
                ||!is_string($order)||$order===''||strlen($order)>120||str_contains($order,"\0")||!is_string($url)||strlen($url)>2048||!self::safeUrl($url))throw new \DomainException('BATCH_INVALID');
            $key=$id."\0".$order;$row=[$id,$name,$order,$url];if(isset($valid[$key])&&$valid[$key]!==$row)throw new \DomainException('BATCH_INVALID');$valid[$key]=$row;
        }
        $this->store->replaceCurrent(array_values($valid));
        return['status'=>'published','count'=>count($valid)];
    }
    private static function safeUrl(string$url):bool{$p=parse_url($url);return is_array($p)&&($p['scheme']??null)==='https'&&isset($p['host'])&&!isset($p['user'],$p['pass']);}
}
