<?php
declare(strict_types=1);
namespace FMonitor\Otiz;

final class OtizSettlementV2Math
{
 public static function newGrossCents(int $fund,int $confirmedBp,int $acceptedBp):int{return max(0,intdiv($fund*max(0,$confirmedBp-$acceptedBp),10000));}
 public static function deadlineReduction(int $gross,int $kssBp):array{$deadline=intdiv($gross*max(0,10000-$kssBp),10000);return['gross'=>$gross,'deadline'=>$deadline,'pool'=>$gross-$deadline];}
 public static function allocate(int $pool,array $recipients,array $excluded,array $personal):array
 {
  $all=[];$eligible=[];foreach($recipients as$r){$id=(string)$r['employeeId'];$all[$id]=0;if(!in_array($id,$excluded,true))$eligible[$id]=max(0,(int)$r['weight']);}
  if($pool>0&&array_sum($eligible)<=0)throw new \DomainException('NO_ELIGIBLE_RECIPIENT');$sum=array_sum($eligible);$used=0;$fractions=[];
  foreach($eligible as$id=>$weight){$numerator=$pool*$weight;$base=$sum?intdiv($numerator,$sum):0;$all[$id]=$base;$used+=$base;$fractions[$id]=$sum?$numerator%$sum:0;}
  uksort($fractions,static fn($a,$b)=>$fractions[$b]<=>$fractions[$a]?:strcmp((string)$a,(string)$b));foreach(array_keys($fractions)as$id){if($used++ >=$pool)break;$all[$id]++;}
  foreach($personal as$id=>$amount){if(!array_key_exists((string)$id,$all)||$amount<0||$amount>$all[(string)$id])throw new \DomainException('DEDUCTION_EXCEEDS_AVAILABLE');$all[(string)$id]-=(int)$amount;}
  ksort($all,SORT_STRING);return$all;
 }
 public static function parseRubles(string $value):int{$v=str_replace(["\xc2\xa0",' '],'',trim($value));if(!preg_match('/^\d+(?:[,.]\d{1,2})?$/D',$v))throw new \InvalidArgumentException('INVALID_MONEY');$parts=preg_split('/[,.]/',$v);return(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');}
}
