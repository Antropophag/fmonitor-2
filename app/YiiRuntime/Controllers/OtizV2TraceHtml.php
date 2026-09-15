<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

final class OtizV2TraceHtml
{
 public static function render(array$o):string
 {
  $input=json_decode((string)$o['inputs_json'],true);$calc=$input['premiumCalculation']??null;if(!is_array($calc))return'';
  $labels=['fund'=>'Премиальный фонд','progress'=>'Начислено за прогресс','deadline'=>'С учётом сроков','paid'=>'Выплачено ранее','remaining'=>'Остаток до штрафа','penalty'=>'Штраф за просрочку','pool'=>'К выплате'];
  $excluded=$calc['exclusions']??[];$h='<details class="fm2-otiz-trace"><summary>Как рассчитана сумма</summary><p>'.self::e($excluded===[]?'Расчёт допущен к распределению.':'Исключено: '.implode(', ',array_column($excluded,'code'))).'</p><ol>';
  foreach($calc['formulaTrace']??[]as$step){$key=(string)($step['step']??'');$value=isset($step['resultCents'])?self::rub((int)$step['resultCents']):number_format((int)($step['resultBp']??0)/100,0,',',' ').'%';$h.='<li>'.self::e($labels[$key]??'Этап расчёта').': '.$value.'</li>';}$h.='</ol>';
  $sources=$input['sourceEvidence']??[];$original=$sources['originalDeadline']??null;$certificate=$sources['certificate']??null;$completion=$sources['completion']??null;
  if(is_array($original))$h.='<p>'.self::e((string)$original['source']['label']).': '.self::e((string)$original['value']).'</p>';
  if(is_array($certificate))$h.='<p>'.self::e((string)$certificate['source']['label']).': '.self::e((string)$certificate['newDeadline']).' <a href="/pilot/objects/'.(int)$o['object_id'].'/deadline-certificates/'.(int)$certificate['id'].'/pdf">PDF</a></p>';
  $pto=is_array($completion)&&is_array($completion['root']??null)?'ПТО: '.self::e((string)($calc['operandEvidence']['completionDate']['value']??'')):'ПТО отсутствует';return$h.'<p>'.$pto.'</p></details>';
 }
 private static function e(string$v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_HTML5,'UTF-8');}
 private static function rub(int$c):string{return($c<0?'−':'').number_format(abs($c)/100,2,',',' ').' ₽';}
}
