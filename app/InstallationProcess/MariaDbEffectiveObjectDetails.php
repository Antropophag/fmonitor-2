<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class MariaDbEffectiveObjectDetails
{
    public function __construct(private \mysqli $db,private string $prefix,private string $legacyPrefix){foreach([$prefix,$legacyPrefix]as$p)if(strlen($p)>28||preg_match('/^[A-Za-z0-9_]*$/D',$p)!==1)throw new \InvalidArgumentException();}
    public function read(int$objectId):array
    {
        $s=$this->db->prepare("SELECT ordadr_address,entrance,regnumber,zavnumber FROM `{$this->legacyPrefix}fm_maintable` WHERE id=? LIMIT 2");$s->execute([$objectId]);$legacy=$s->get_result()->fetch_all(MYSQLI_ASSOC);if(count($legacy)!==1)throw new \RuntimeException('Object details unavailable.');
        $s=$this->db->prepare("SELECT payload_json FROM `{$this->prefix}fm2_pilot_object_details` WHERE object_id=? LIMIT 2");$s->execute([$objectId]);$row=$s->get_result()->fetch_assoc();$fields=[];if($row){$payload=json_decode($row['payload_json'],true,flags:JSON_THROW_ON_ERROR);$fields=$payload['fields']??[];}
        $result=[];$map=['address'=>'ordadr_address','entrance'=>'entrance','regnumber'=>'regnumber','zavnumber'=>'zavnumber'];foreach($map as$name=>$column)$result[$name]=['value'=>$legacy[0][$column],'raw'=>$legacy[0][$column],'display'=>$legacy[0][$column],'source'=>'import'];
        foreach(['floors','weight','speed','pittype','pitmaterial','lift_type','paired']as$name){$field=$fields[$name]??[];$result[$name]=['value'=>$field['raw']??$field['display']??null,'raw'=>$field['raw']??null,'display'=>$field['display']??$field['raw']??null,'source'=>'import'];}
        $s=$this->db->prepare("SELECT revision,values_json FROM `{$this->prefix}fm2_object_detail_edits` WHERE object_id=?");$s->execute([$objectId]);$edit=$s->get_result()->fetch_assoc();if($edit){foreach(json_decode($edit['values_json'],true,flags:JSON_THROW_ON_ERROR)as$name=>$value){$display=in_array($name,['pittype','pitmaterial','lift_type'],true)&&$value!==null?ObjectDetailsReferenceCatalogue::display($name,(string)$value):(is_bool($value)?($value?'Да':'Нет'):$value);$result[$name]=['value'=>$value,'raw'=>$value,'display'=>$display,'source'=>'manual'];}}
        $result['_revision']=(int)($edit['revision']??0);return$result;
    }
}
