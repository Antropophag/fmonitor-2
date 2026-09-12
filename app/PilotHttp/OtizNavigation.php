<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class OtizNavigation
{
 public static function currentUserCanAccess():bool
 {
  $prefix=(string)\getenv('FMONITOR_PROCESS_TABLE_PREFIX');$id=\filter_var($_SERVER['FMONITOR_AUTH_USER_ID']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(\preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1||$id===false)return false;
  try{$db=new \mysqli(\getenv('FMONITOR_DB_HOST')?:'127.0.0.1',(string)\getenv('FMONITOR_DB_USER'),(string)\getenv('FMONITOR_DB_PASSWORD'),(string)\getenv('FMONITOR_DB_NAME'),(int)(\getenv('FMONITOR_DB_PORT')?:3306));$db->set_charset('utf8mb4');$allowed=AccessPolicy::grants(AccessPolicy::forUser($db,$prefix,(int)$id),AccessPolicy::OTIZ_MANAGE);$db->close();return$allowed;}catch(\Throwable){return false;}
 }
 public static function decorate(string$html,bool$active):string
 {
  $current=$active?' aria-current="page"':'';$icon='<svg class="fm2-nav-icon fm2-nav-icon--shlz" viewBox="0 0 24 25" aria-hidden="true"><use href="/pilot/assets/shlz-icons.svg#shlz-icon-bar-chart-square-plus"/></svg>';
  return \preg_replace('#<span class="fm2-nav-group">Управление</span><span class="fm2-nav-item fm2-nav-item--muted" aria-disabled="true"><svg[^>]*>.*?</svg><span class="fm2-nav-text">Расчёты ОТиЗ</span></span>#s','<span class="fm2-nav-group">ОТиЗ</span><a class="fm2-nav-item" href="/pilot/otiz"'.$current.'>'.$icon.'<span class="fm2-nav-text">Расчёты ОТиЗ</span></a><span class="fm2-nav-group">Управление</span>',$html)??$html;
 }
}
