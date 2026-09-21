<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class ObjectDetailsReferenceCatalogue
{
 private const VALUES=[
  'pittype'=>['7'=>'7','40'=>'Глухая'],
  'pitmaterial'=>['9'=>'9','41'=>'Железобетон','72'=>'Железобетон и металл','86'=>'Кирпич','112'=>'Кирпич и металл','123'=>'Металлокаркас и стекло'],
  'lift_type'=>['1'=>'Пассажирский','2'=>'Грузовой'],
 ];
 public static function options(string$field):array{return self::VALUES[$field]??throw new \InvalidArgumentException('Unknown reference field.');}
 public static function display(string$field,string$code):string{return self::VALUES[$field][$code]??throw new \InvalidArgumentException('Unknown reference value.');}
}
