<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class MariaDbBitrixOrderDocumentLinksRead
{
    public function __construct(private \mysqli$db,private string$prefix=''){MariaDbSchemaInspector::validateTablePrefix($prefix);}
    public function forOrder(?string$orderNumber):array
    {
        if($orderNumber===null||$orderNumber==='')return['status'=>'order_number_missing','links'=>[]];
        $q=$this->db->prepare("SELECT DISTINCT source_folder_name,url FROM `{$this->prefix}fm2_bitrix_order_document_links` WHERE BINARY order_number=BINARY ? ORDER BY source_folder_name,url");$q->bind_param('s',$orderNumber);$q->execute();
        $links=array_map(static fn(array$row):array=>['name'=>$row['source_folder_name'],'url'=>$row['url']],$q->get_result()->fetch_all(MYSQLI_ASSOC));
        return['status'=>$links===[]?'empty':'available','links'=>$links];
    }
    public function forObject(int$objectId):array
    {
        if($objectId<1)throw new \InvalidArgumentException();$effective=(new MariaDbEffectiveObjectDetails($this->db,$this->prefix,$this->prefix))->read($objectId);$number=$effective['zavnumber']['value']??null;return$this->forOrder(is_string($number)?$number:null);
    }
}
