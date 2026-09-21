<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class EquipmentFactsApplication
{
    private MariaDbEquipmentFacts$store;
    public function __construct(\mysqli$db,string$prefix,private string$hmacKey){if(strlen($hmacKey)<32)throw new \InvalidArgumentException('EQUIPMENT_FACTS_CONFIGURATION_UNAVAILABLE');$this->store=MariaDbEquipmentFacts::create($db,$prefix);}
    public function execute(array$command):array
    {
        $actor=$command['actor']??null;if(!is_array($actor)||($actor['type']??null)!=='system'||($actor['id']??null)!=='erp-equipment-facts-hourly-v1')return['status'=>'rejected','reason'=>'UNAUTHORIZED'];
        if(!$this->valid($command))return['status'=>'rejected','reason'=>'BATCH_INVALID'];
        $canonical=json_encode($command,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);return$this->store->apply($command,hash_hmac('sha256',$canonical,$this->hmacKey),$this->hmacKey);
    }
    private function valid(array$c):bool
    {
        $keys=($c['kind']??null)==='failed'?['actor','kind','runId','observedAtUtc','reason']:['actor','kind','runId','observedAtUtc','records'];if(array_keys($c)!==$keys)return false;
        if(($c['actor']??null)!==['type'=>'system','id'=>'erp-equipment-facts-hourly-v1']||!in_array($c['kind']??null,['complete','failed'],true)||!is_string($c['runId']??null)||preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$c['runId'])!==1||!self::time($c['observedAtUtc']??null))return false;
        if($c['kind']==='failed')return in_array($c['reason']??null,['SOURCE_UNAVAILABLE','SOURCE_INVALID'],true);
        if(!is_array($c['records']))return false;$seen=[];foreach($c['records']as$r){if(!is_array($r)||array_keys($r)!==['sourceOrderNumber','readinessDate','firstShipmentDate','fullShipmentDate']||!is_string($r['sourceOrderNumber'])||$r['sourceOrderNumber']===''||trim($r['sourceOrderNumber'])!==$r['sourceOrderNumber']||strlen($r['sourceOrderNumber'])>120||isset($seen[$r['sourceOrderNumber']]))return false;$seen[$r['sourceOrderNumber']]=true;foreach(['readinessDate','firstShipmentDate','fullShipmentDate']as$k)if($r[$k]!==null&&!self::date($r[$k]))return false;}return true;
    }
    private static function date(mixed$v):bool{if(!is_string($v)||preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)!==1||$v==='0001-01-01')return false;$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v);return$d!==false&&$d->format('Y-m-d')===$v;}
    private static function time(mixed$v):bool{if(!is_string($v)||preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/D',$v)!==1)return false;$d=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s.u\Z',$v,new \DateTimeZone('UTC'));return$d!==false&&$d->format('Y-m-d\TH:i:s.u\Z')===$v;}
}
