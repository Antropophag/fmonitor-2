<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

/** Public read seam for the object-economics register. */
final readonly class ObjectRegister
{
    private MariaDbObjectRegister $store;
    public function __construct(\mysqli $db,string $prefix,string $legacyPrefix)
    { $this->store=new MariaDbObjectRegister($db,$prefix,$legacyPrefix); }
    public function read(int $actorId,array $query): array
    { return $this->store->read($actorId,$query); }

    public static function query(array $input): array
    {
        $out=['q'=>'','state'=>'','sort'=>'default','page'=>1,'pageSize'=>50];
        foreach($out as $key=>$default){
            if(!array_key_exists($key,$input))continue;
            $value=$input[$key];
            if(is_int($default)){
                if(!(is_int($value)||(is_string($value)&&preg_match('/^[1-9][0-9]{0,6}$/D',$value)===1))||$value<1||$value>1000000)throw new \DomainException('REGISTER_QUERY_INVALID');
                $out[$key]=(int)$value;
            }else{
                if(!is_string($value))throw new \DomainException('REGISTER_QUERY_INVALID');
                $out[$key]=$value;
            }
        }
        $out['q']=trim($out['q']);
        if(!mb_check_encoding($out['q'],'UTF-8')||mb_strlen($out['q'],'UTF-8')>120
            ||!in_array($out['state'],['','planned','ready','blocked','no_new_amount','completed','missing_norm'],true)
            ||!in_array($out['sort'],['default','regnumber_asc','regnumber_desc'],true)
            ||!in_array($out['pageSize'],[25,50,100],true))throw new \DomainException('REGISTER_QUERY_INVALID');
        return $out;
    }
}
