<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class NativeErpEquipmentFactsDelivery
{
    private \Closure$transport;
    public function __construct(private ErpEquipmentFactsDeliveryConfig$config,callable$transport){$this->transport=\Closure::fromCallable($transport);}
    public function fetch(array $candidates):array
    {
        $orders=[];
        foreach($candidates as$candidate){if(!is_string($candidate))return['status'=>'failed','reason'=>'SOURCE_INVALID'];$candidate=trim($candidate);if($candidate===''||$candidate==='0')continue;if(strlen($candidate)>120)return['status'=>'failed','reason'=>'SOURCE_INVALID'];$orders[$candidate]=true;}
        $orders=array_keys($orders);sort($orders,SORT_STRING);$records=[];$options=['maxRows'=>$this->config->maxRows,'timeoutSeconds'=>$this->config->timeoutSeconds,'readOnly'=>true];
        try{foreach(array_chunk($orders,$this->config->chunkSize)as$chunk){$a=($this->transport)(MariaDbErpEquipmentFactsSource::ordersQuery(count($chunk)),$chunk,$options);$b=($this->transport)(MariaDbErpEquipmentFactsSource::shipmentsQuery(count($chunk)),$chunk,$options);$normalized=$this->normalize($a,$b);if(($normalized['status']??null)!=='complete')return$normalized;foreach($normalized['records']as$record){$number=$record['sourceOrderNumber'];if(isset($records[$number]))return['status'=>'failed','reason'=>'SOURCE_INVALID'];$records[$number]=$record;}}}catch(\Throwable){return['status'=>'failed','reason'=>'SOURCE_UNAVAILABLE'];}
        return['status'=>'complete','records'=>array_values($records)];
    }
    private function normalize(mixed$orders,mixed$shipments):array
    {
        if(!is_array($orders)||!is_array($shipments)||count($orders)>$this->config->maxRows||count($shipments)>$this->config->maxRows)return['status'=>'failed','reason'=>'SOURCE_INVALID'];$out=[];
        foreach($orders as$row){if(!is_array($row)||array_keys($row)!==['sourceOrderNumber','readinessDate','fullShipmentDate']||!is_string($row['sourceOrderNumber']))return['status'=>'failed','reason'=>'SOURCE_INVALID'];$order=trim($row['sourceOrderNumber']);if($order===''||strlen($order)>120||isset($out[$order])||!$this->nullableDate($row['readinessDate'])||!$this->nullableDate($row['fullShipmentDate']))return['status'=>'failed','reason'=>'SOURCE_INVALID'];$out[$order]=['sourceOrderNumber'=>$order,'readinessDate'=>$row['readinessDate'],'firstShipmentDate'=>null,'fullShipmentDate'=>$row['fullShipmentDate']];}
        foreach($shipments as$row){if(!is_array($row)||array_keys($row)!==['sourceOrderNumber','shipmentDate']||!is_string($row['sourceOrderNumber']))return['status'=>'failed','reason'=>'SOURCE_INVALID'];$order=trim($row['sourceOrderNumber']);if(!isset($out[$order]))continue;$date=$row['shipmentDate'];if($date==='0001-01-01'||$date===null)continue;if(!$this->nullableDate($date))return['status'=>'failed','reason'=>'SOURCE_INVALID'];if($out[$order]['firstShipmentDate']===null||$date<$out[$order]['firstShipmentDate'])$out[$order]['firstShipmentDate']=$date;}
        return['status'=>'complete','records'=>array_values($out)];
    }
    private function nullableDate(mixed$v):bool{if($v===null)return true;if(!is_string($v)||preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)!==1||$v==='0001-01-01')return false;$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v);return$d!==false&&$d->format('Y-m-d')===$v;}
}
