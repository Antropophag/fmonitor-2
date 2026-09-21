<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class ObjectDetailsFieldRegistry
{
    private const DEFINITIONS=[
        'address'=>['label'=>'Адрес','type'=>'required_string'],'entrance'=>['label'=>'Подъезд','type'=>'required_string'],
        'regnumber'=>['label'=>'Регистрационный номер','type'=>'nullable_string'],'zavnumber'=>['label'=>'Заводской номер','type'=>'nullable_string'],
        'floors'=>['label'=>'Этажность','type'=>'integer'],'weight'=>['label'=>'Грузоподъёмность','type'=>'integer'],
        'speed'=>['label'=>'Скорость','type'=>'decimal','unit'=>'м/с'],'pittype'=>['label'=>'Тип шахты','type'=>'reference'],
        'pitmaterial'=>['label'=>'Материал шахты','type'=>'reference'],'lift_type'=>['label'=>'Тип лифта','type'=>'reference'],
        'paired'=>['label'=>'Спаренный лифт','type'=>'boolean'],
    ];
    public function definitions():array{return self::DEFINITIONS;}
    public function normalizePatch(array $patch):array
    {
        if($patch===[])throw new \InvalidArgumentException('Empty patch.');$out=[];
        foreach($patch as$key=>$value){
            if(!is_string($key)||!isset(self::DEFINITIONS[$key])||(!is_string($value)&&!is_int($value)))throw new \InvalidArgumentException('Invalid field.');
            $s=is_string($value)?trim($value):(string)$value;if(str_contains($s,"\0"))throw new \InvalidArgumentException('Unsafe value.');$type=self::DEFINITIONS[$key]['type'];
            $out[$key]=match($type){
                'required_string'=>$s!==''?$s:throw new \InvalidArgumentException('Required value.'),
                'nullable_string'=>$s===''?null:$s,
                'integer'=>preg_match('/^[1-9]\d*$/D',$s)===1&&($n=(int)$s)>0&&$n<=100000?$n:throw new \InvalidArgumentException('Invalid integer.'),
                'decimal'=>preg_match('/^(?:\d+)(?:[.,]\d+)?$/D',$s)===1&&((float)str_replace(',','.',$s))>0?$this->decimal($s):throw new \InvalidArgumentException('Invalid decimal.'),
                'reference'=>array_key_exists($s,ObjectDetailsReferenceCatalogue::options($key))?$s:throw new \InvalidArgumentException('Invalid reference.'),
                'boolean'=>match($s){'0'=>false,'1'=>true,default=>throw new \InvalidArgumentException('Invalid boolean.')},
            };
            if($key==='zavnumber'&&$out[$key]!==null&&strlen($out[$key])>120)throw new \InvalidArgumentException('Factory number is too long.');
            $max=['address'=>500,'entrance'=>80,'regnumber'=>120][$key]??null;if($max!==null&&is_string($out[$key])&&strlen($out[$key])>$max)throw new \InvalidArgumentException('Value is too long.');
            if($key==='floors'&&$out[$key]>200)throw new \InvalidArgumentException('Floor count is out of range.');
            if($key==='speed'&&(float)$out[$key]>20)throw new \InvalidArgumentException('Speed is out of range.');
        }return$out;
    }
    private function decimal(string$value):string{$value=str_replace(',','.',$value);[$whole,$fraction]=array_pad(explode('.',$value,2),2,null);if($fraction===null)return(string)(int)$whole;$fraction=rtrim($fraction,'0');return (string)(int)$whole.'.'.($fraction===''?'0':$fraction);}
    public function snapshot(string$field,mixed$value,bool$validated=true):array{$d=self::DEFINITIONS[$field]??throw new \InvalidArgumentException();$display=$value===null?null:($d['type']==='reference'?($validated?ObjectDetailsReferenceCatalogue::display($field,(string)$value):(ObjectDetailsReferenceCatalogue::options($field)[(string)$value]??(string)$value)):(is_bool($value)?($value?'Да':'Нет'):(string)$value));return['value'=>$value,'raw'=>$value,'display'=>$display,'unit'=>$d['unit']??($field==='weight'?'кг':null),'referenceLabel'=>$d['type']==='reference'?$display:null];}
}
