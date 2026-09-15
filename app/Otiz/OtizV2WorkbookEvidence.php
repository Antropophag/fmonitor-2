<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

final class OtizV2WorkbookEvidence
{
 public static function rows(array$objects):array
 {
  $rows=[];$labels=['paid'=>'Выплачено ранее','remaining'=>'Остаток до штрафа','penalty'=>'Штраф за просрочку'];
  foreach($objects as$object){$input=json_decode((string)$object['inputs_json'],true);$calc=$input['premiumCalculation']??null;if(!is_array($calc))continue;$rows[]=['Премиальный фонд, руб.',(int)$calc['amounts']['fundCents']/100];$rows[]=['Начислено за прогресс, руб.',(int)$calc['amounts']['progressAmountCents']/100];$rows[]=['Новый пул, руб.',(int)$calc['amounts']['poolCents']/100];foreach($calc['formulaTrace']as$step)if(isset($labels[$step['step']]))$rows[]=[$labels[$step['step']],(int)$step['resultCents']/100];$certificate=$input['sourceEvidence']['certificate']??null;if(is_array($certificate)){$rows[]=['Справка о переносе срока',(string)$certificate['source']['label']];$rows[]=['Источник справки',(string)$certificate['source']['locator']];$rows[]=['Срок по справке',(string)$certificate['newDeadline']];}$original=$input['sourceEvidence']['originalDeadline']??null;if(is_array($original))$rows[]=['Исходный плановый срок',(string)$original['value']];$completion=$input['sourceEvidence']['completion']['root']??null;$rows[]=['Акт ПТО',is_array($completion)?(string)$calc['operandEvidence']['completionDate']['value']:'ПТО отсутствует'];}
  return$rows;
 }
}
