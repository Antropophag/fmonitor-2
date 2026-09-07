<?php

declare(strict_types=1);
require_once dirname(__DIR__).'/app/InstallationProcess/MariaDbInstallationCompletion.php';
require_once dirname(__DIR__).'/app/InstallationProcess/InstallationCompletionDetailsSchemaMigration.php';
require_once dirname(__DIR__).'/app/InspectionEvidence/MariaDbChecklistProgress.php';

final class RapidPilotCompletionFlow
{
    public static function matches(string $path): bool
    {
        return preg_match('#^/pilot/objects/[1-9][0-9]*/completion$#D', $path) === 1;
    }

    public static function handle(string $path): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') self::plain(405, 'Метод не поддерживается.');
        preg_match('#^/pilot/objects/([1-9][0-9]*)/completion$#D', $path, $match);
        $objectId=(int)$match[1];
        $csrf=(string)($_POST['csrfToken']??'');
        if($csrf===''||!hash_equals((string)($_SERVER['FMONITOR_AUTH_CSRF']??''),$csrf))self::plain(403,'Недопустимый запрос.');
        $db=self::db();$prefix=self::prefix();self::assertHttpSchemaReady($db,$prefix);
        $actor=self::actorId($db,$prefix);$now=self::now();$owner=new \FMonitor2\InstallationProcess\MariaDbInstallationCompletion($db,$prefix);
        try{$action=(string)($_POST['action']??'');
            if($action==='record_pto'){
                $date=self::date((string)($_POST['ptoActDate']??''));if($date===null||$date>substr($now,0,10))self::fail($db,422,'Укажите дату акта ПТО не позже сегодняшней.');
                $owner->record($objectId,$actor,'pto_act',$date,'',$now);
            }elseif($action==='record_declaration'){
                $details=trim((string)($_POST['declarationDetails']??''));$date=self::date((string)($_POST['declarationDate']??''));
                if($details===''||mb_strlen($details)>500||$date===null||$date>substr($now,0,10))self::fail($db,422,'Укажите дату и реквизиты декларации.');
                $owner->record($objectId,$actor,'declaration',$date,$details,$now);
            }elseif($action==='correct_pto'||$action==='correct_declaration'){
                $type=$action==='correct_pto'?'pto_act':'declaration';$factId=(int)($_POST['factId']??0);$date=self::date((string)($_POST[$type==='pto_act'?'ptoActDate':'declarationDate']??''));$details=$type==='declaration'?trim((string)($_POST['declarationDetails']??'')):'';$reason=trim((string)($_POST['reason']??''));
                if($factId<1||$date===null||$reason===''||mb_strlen($reason)>1000)self::fail($db,422,'Укажите исправленные данные и причину.');
                $owner->correct($objectId,$actor,$factId,$type,$date,$details,$reason,$now);
            }else self::fail($db,422,'Неизвестное действие.');
        }catch(DomainException$error){self::domainFailure($db,$error);}
        header('Location: /pilot/objects/'.$objectId.'#completion',true,303);header('Cache-Control: no-store');exit;
    }

    public static function blocksLegacyCompletion(string $path): bool
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||preg_match('#^/pilot/(?:objects|construction-control/objects)/[1-9][0-9]*/checklist/operations$#D',$path)!==1)return false;
        $body=file_get_contents('php://input');if(!is_string($body))return false;$payload=json_decode($body,true);
        if(!is_array($payload)||($payload['itemId']??null)!==42)return false;
        http_response_code(409);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
        echo json_encode(['status'=>'rejected','message'=>'Последние 15% закрываются актом ПТО и декларацией в карточке объекта.'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);return true;
    }

    public static function enhanceCard(string $html,int $objectId):string
    {
        $db=self::db();$prefix=self::prefix();self::assertHttpSchemaReady($db,$prefix);
        try{$case=self::case($db,$prefix,$objectId,false);if($case===null||($case['process_state']??null)!=='working')return$html;$progress=self::installationProgress($db,$prefix,(int)$case['id']);$facts=self::facts($db,$prefix,(int)$case['id']);}
        catch(Throwable){return$html;}
        $complete=isset($facts['pto_act'],$facts['declaration']);$status=self::status($progress,$complete);
        $paint=$complete?'shlz-status--bright-green':($progress>=85?'shlz-status--orange':'shlz-status--blue');
        $html=preg_replace('#<span class="shlz-status [^"]*">(?:В работе|Изменяющее распоряжение подготовлено)</span>#','<span class="shlz-status '.$paint.'">'.$status.'</span>',$html,1)??$html;
        $html=preg_replace_callback('#<section class="fm2-next-action"[^>]*>.*?</section>#s',static function(array$match)use($objectId,$progress,$facts):string{
            preg_match('#<div class="fm2-action-stack">.*?</div>#s',$match[0],$actions);
            $current=$progress<85?$match[0]:self::currentAction($objectId,$progress,$facts,(string)($_SERVER['FMONITOR_AUTH_CSRF']??''),$actions[0]??'');
            return$current.self::panel($objectId,$progress,$facts,(string)($_SERVER['FMONITOR_AUTH_CSRF']??''));
        },$html,1,$replaced)??$html;
        if($replaced===0)$html=str_replace('</main>',self::currentAction($objectId,$progress,$facts,(string)($_SERVER['FMONITOR_AUTH_CSRF']??''),'').self::panel($objectId,$progress,$facts,(string)($_SERVER['FMONITOR_AUTH_CSRF']??'')).'</main>',$html);
        return$html;
    }

    public static function decorateQueue(array $objects,mysqli$db,string$prefix):array
    {
        if($objects===[])return[];self::assertSchemaReady($db,$prefix);$objectIds=array_map('intval',array_column($objects,'id'));$idList=implode(',',$objectIds);$cases=$db->query("SELECT id,legacy_installation_object_id FROM `{$prefix}fm2_installation_cases` WHERE legacy_installation_object_id IN({$idList})")->fetch_all(MYSQLI_ASSOC);$objectByCase=[];$caseIds=[];foreach($cases as$row){$case=(int)$row['id'];$caseIds[]=$case;$objectByCase[$case]=(int)$row['legacy_installation_object_id'];}if($caseIds===[])return$objects;$caseList=implode(',',$caseIds);
        $progress=(new \FMonitor2\InspectionEvidence\MariaDbChecklistProgress($db,$prefix))->forCases($caseIds);
        $factTypes=[];$factRows=$db->query("SELECT installation_case_id,fact_type FROM `{$prefix}fm2_pilot_completion_facts` WHERE installation_case_id IN({$caseList})")->fetch_all(MYSQLI_ASSOC);foreach($factRows as$row)$factTypes[(int)$row['installation_case_id']][(string)$row['fact_type']]=true;
        $state=[];foreach($objectByCase as$case=>$objectId){$value=$progress[$case]??0;$pto=isset($factTypes[$case]['pto_act']);$complete=$pto&&isset($factTypes[$case]['declaration']);$state[$objectId]=['status'=>self::status($value,$complete),'nextStep'=>$complete?'Монтаж закрыт актом ПТО и декларацией':($value<85?'Продолжить монтажные работы':($pto?'Добавить декларацию':'Зафиксировать дату акта ПТО'))];}
        foreach($objects as&$object)if(($object['status']??null)==='В работе'&&isset($state[(int)$object['id']]))$object=array_replace($object,$state[(int)$object['id']]);unset($object);return$objects;
    }

    public static function ensureQueueSchema(mysqli$db,string$prefix):void
    {
        self::assertSchemaReady($db,$prefix);
        (new \FMonitor2\PilotHttp\ChecklistSync($db,$prefix,'',''))->ensureSchema();
    }

    public static function assertSchemaReady(mysqli $db, string $prefix): void
    {
        if (!\FMonitor2\InstallationProcess\InstallationCompletionDetailsSchemaMigration::isCompleteCompatible($db, $prefix)) {
            throw new RuntimeException('Completion schema is unavailable.');
        }
    }

    public static function paintStatuses(string$html):string
    {
        $map=['Требуется распоряжение'=>'shlz-status--orange','Готов к открытию'=>'shlz-status--source-blue','Монтажные работы'=>'shlz-status--cyan','Документарное закрытие'=>'shlz-status--purple','Работы завершены'=>'shlz-status--bright-green','Требуется изменение'=>'shlz-status--pink'];
        return preg_replace_callback('#<span class="([^"]*\bshlz-status\b[^"]*)">([^<]+)</span>#u',static function(array$match)use($map):string{$label=trim(html_entity_decode($match[2],ENT_QUOTES|ENT_HTML5,'UTF-8'));$label=preg_replace('/\s+·\s+\d+%$/u','',$label)??$label;if(!isset($map[$label]))return$match[0];$classes=preg_replace('/\s*shlz-status--(?:green|bright-green|source-blue|blue|orange|purple|cyan|pink|neutral)\b/','',$match[1])??$match[1];return'<span class="'.trim($classes).' '.$map[$label].'">'.$match[2].'</span>';},$html)??$html;
    }

    public static function enhanceChecklist(string $html,int $objectId):string
    {
        $db=self::db();$prefix=self::prefix();self::assertHttpSchemaReady($db,$prefix);
        try{$case=self::case($db,$prefix,$objectId,false);if($case===null)return$html;$facts=self::facts($db,$prefix,(int)$case['id']);}
        catch(Throwable){return$html;}
        $pto=isset($facts['pto_act']);$complete=$pto&&isset($facts['declaration']);$final=$complete?100:85;
        $status=$complete?'Работы завершены':($pto?'Ожидается декларация':'Ожидается акт ПТО');$paint=$complete?'shlz-status--bright-green':'shlz-status--orange';$action=$complete?'Посмотреть документы':($pto?'Добавить декларацию':'Зафиксировать акт ПТО');
        $ptoMark=$pto?'is-done':'';$declarationMark=$complete?'is-done':'';
        $closeout='<section class="fm2-check-section fm2-check-section--completion" data-check-section="8"><div class="fm2-check-closeout"><div class="fm2-check-closeout__copy"><div><strong>Документарное закрытие</strong><span class="shlz-status '.$paint.'">'.$status.'</span></div><p>Последние 15% закрываются после монтажа двумя обязательными основаниями.</p><ul><li class="'.$ptoMark.'"><span></span>Дата акта ПТО</li><li class="'.$declarationMark.'"><span></span>Декларация</li></ul></div><a class="shlz-button '.($complete?'':'shlz-button--primary').'" href="/pilot/objects/'.$objectId.'#completion">'.$action.'</a></div></section>';
        $html=preg_replace('#<section class="fm2-check-section" data-check-section="8".*?</section>#s',$closeout,$html,1)??$html;
        $html=str_replace('<span data-total-progress>0</span>%','<span data-total-progress data-progress-cap="'.$final.'">0</span>%',$html);
        $html=str_replace('<span data-total-items>0</span> из 42 работ','<span data-total-items>0</span> из 41 монтажной работы',$html);
        return $html;
    }

    private static function currentAction(int$id,int$progress,array$facts,string$csrf,string$actions):string
    {
        $pto=$facts['pto_act']??null;$declaration=$facts['declaration']??null;$complete=$pto!==null&&$declaration!==null;$e=static fn(string$v):string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');
        $form=$complete?'':($pto===null?'<form method="post" action="/pilot/objects/'.$id.'/completion" class="fm2-completion-form"><input type="hidden" name="csrfToken" value="'.$e($csrf).'"><input type="hidden" name="action" value="record_pto"><label class="shlz-field"><span class="shlz-field__label">Дата акта ПТО</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="ptoActDate" max="'.substr(self::now(),0,10).'" required></span></label><button class="shlz-button shlz-button--primary">Зафиксировать акт ПТО</button></form>':'<form method="post" action="/pilot/objects/'.$id.'/completion" class="fm2-completion-form"><input type="hidden" name="csrfToken" value="'.$e($csrf).'"><input type="hidden" name="action" value="record_declaration"><label class="shlz-field"><span class="shlz-field__label">Дата декларации</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="declarationDate" max="'.substr(self::now(),0,10).'" required></span></label><label class="shlz-field fm2-completion-details"><span class="shlz-field__label">Реквизиты декларации</span><span class="shlz-field__control"><input class="shlz-input" name="declarationDetails" maxlength="500" placeholder="Номер, кем и когда выдана" required></span></label><button class="shlz-button shlz-button--primary">Завершить работы</button></form>');
        $heading=$complete?'Документы приняты':($pto===null?'Зафиксируйте акт ПТО':'Добавьте декларацию');
        $copy=$complete?'Акт ПТО и декларация подтверждены. Работы по объекту завершены.':($pto===null?'Монтажная часть выполнена. Укажите дату акта ПТО.':'Акт ПТО зафиксирован. Добавьте реквизиты декларации, чтобы завершить работы.');
        return'<section class="fm2-next-action fm2-completion-action" id="completion" aria-labelledby="completion-title"><div><h2 id="completion-title">'.$heading.'</h2><p>'.$copy.'</p></div>'.$form.$actions.'</section>';
    }

    private static function panel(int$id,int$progress,array$facts,string$csrf):string
    {
        $pto=$facts['pto_act']??null;$declaration=$facts['declaration']??null;$complete=$pto!==null&&$declaration!==null;$shown=$complete?100:min(85,$progress);$e=static fn(string$v):string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');
        $ptoState=$pto?'<strong>Акт ПТО от '.self::shortDate($pto['fact_date']).'</strong><span>Контрольная точка пройдена</span>':($progress>=85?'<strong>Зафиксируйте дату акта ПТО</strong><span>Монтажная часть выполнена на 85%</span>':'<strong>Завершите монтаж до 85%</strong><span>До акта ПТО осталось '.(85-$shown).'%</span>');
        $declarationState=$declaration?'<strong>'.$e($declaration['details']).'</strong><span>Декларация от '.self::shortDate($declaration['fact_date']).'</span>':($pto?'<strong>Добавьте декларацию</strong><span>Она закроет последние 15% работ</span>':'<strong>Ожидает акта ПТО</strong><span>Декларация фиксируется после контрольной точки</span>');
        $status=self::status($progress,$complete);$corrections='';
        foreach(['pto_act'=>'Акт ПТО','declaration'=>'Декларация']as$type=>$label)if(isset($facts[$type])){$fact=$facts[$type];$action=$type==='pto_act'?'correct_pto':'correct_declaration';$dateName=$type==='pto_act'?'ptoActDate':'declarationDate';$details=$type==='declaration'?'<label class="shlz-field"><span class="shlz-field__label">Исправленные реквизиты</span><span class="shlz-field__control"><input class="shlz-input" name="declarationDetails" maxlength="500" value="'.$e((string)$fact['details']).'" required></span></label>':'';$history=isset($fact['version_no'])?' · исправление '.(int)$fact['version_no']:'';$corrections.='<details class="fm2-completion-correction"><summary>Исправить: '.$label.$history.'</summary><form method="post" action="/pilot/objects/'.$id.'/completion" class="fm2-completion-form"><input type="hidden" name="csrfToken" value="'.$e($csrf).'"><input type="hidden" name="action" value="'.$action.'"><input type="hidden" name="factId" value="'.(int)$fact['id'].'"><label class="shlz-field"><span class="shlz-field__label">Исправленная дата</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="'.$dateName.'" value="'.$e((string)$fact['fact_date']).'" max="'.substr(self::now(),0,10).'" required></span></label>'.$details.'<label class="shlz-field"><span class="shlz-field__label">Причина исправления</span><span class="shlz-field__control"><textarea class="shlz-input" name="reason" maxlength="1000" required></textarea></span></label><button class="shlz-button">Сохранить исправление</button></form></details>';}
        return'<section class="fm2-completion" aria-labelledby="work-progress-title"><header><div><h2 id="work-progress-title">Ход работ</h2><p>Монтаж даёт 85%. Последние 15% закрываются актом ПТО и декларацией.</p></div><span class="shlz-status '.($complete?'shlz-status--bright-green':($progress>=85?'shlz-status--orange':'shlz-status--blue')).'">'.$status.' · '.$shown.'%</span></header><div class="fm2-completion-track" role="progressbar" aria-label="Готовность работ" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'.$shown.'"><progress class="fm2-completion-track__segment fm2-completion-track__segment--installation" max="85" value="'.min(85,$progress).'" aria-hidden="true"></progress><progress class="fm2-completion-track__segment fm2-completion-track__segment--documents" max="15" value="'.($complete?'15':'0').'" aria-hidden="true"></progress></div><ol class="fm2-completion-steps"><li class="'.($progress>=85?'is-done':'is-current').'"><span>1</span><div><strong>Чеклист монтажных работ</strong><small>'.min(85,$progress).' из 85%</small></div></li><li class="'.($pto?'is-done':($progress>=85?'is-current':'')).'"><span>2</span><div>'.$ptoState.'</div></li><li class="'.($declaration?'is-done':($pto?'is-current':'')).'"><span>3</span><div>'.$declarationState.'</div></li></ol>'.$corrections.'</section>';
    }

    private static function installationProgress(mysqli$db,string$p,int$caseId):int{return(new \FMonitor2\InspectionEvidence\MariaDbChecklistProgress($db,$p))->forCase($caseId);}
    private static function status(int$progress,bool$complete):string{return$complete?'Работы завершены':($progress>=85?'Документарное закрытие':'Монтажные работы');}
    private static function facts(mysqli$db,string$p,int$caseId):array{return(new \FMonitor2\InstallationProcess\MariaDbInstallationCompletion($db,$p))->facts($caseId);}
    private static function domainFailure(mysqli$db,DomainException$error):never{$map=['ACTOR_NOT_AUTHORIZED'=>[403,'Действие недоступно для вашей роли.'],'CASE_NOT_FOUND'=>[404,'Объект не найден.'],'CASE_NOT_WORKING'=>[409,'Работы по объекту не открыты.'],'CHECKLIST_INCOMPLETE'=>[409,'Сначала завершите монтажные работы до 85%.'],'PTO_REQUIRED'=>[409,'Сначала зафиксируйте дату акта ПТО.'],'FACT_ALREADY_RECORDED'=>[409,'Документ уже зафиксирован.'],'FACT_NOT_FOUND'=>[409,'Исправляемая запись не найдена.'],'REASON_REQUIRED'=>[422,'Укажите причину исправления.'],'INVALID_FACT'=>[422,'Проверьте дату и реквизиты документа.']];[$status,$message]=$map[$error->getMessage()]??[409,'Действие не выполнено.'];self::fail($db,$status,$message);}
    private static function assertHttpSchemaReady(mysqli$db,string$p):void{try{self::assertSchemaReady($db,$p);}catch(Throwable){self::unavailable();}}
    private static function case(mysqli$db,string$p,int$id,bool$lock):?array{$s=$db->prepare("SELECT id,process_state FROM `{$p}fm2_installation_cases` WHERE legacy_installation_object_id=? LIMIT 2".($lock?' FOR UPDATE':''));$s->bind_param('i',$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);return count($rows)===1?$rows[0]:null;}
    private static function actorId(mysqli$db,string$p):int{$userId=filter_var($_SERVER['FMONITOR_AUTH_USER_ID']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($userId===false)self::plain(403,'Пользователь не найден.');$s=$db->prepare("SELECT user_id FROM `{$p}fm2_pilot_users` WHERE user_id=? AND status=1 LIMIT 1");$s->bind_param('i',$userId);$s->execute();$row=$s->get_result()->fetch_assoc();if(!is_array($row))self::plain(403,'Пользователь не найден.');return(int)$row['user_id'];}
    private static function db():mysqli{mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli(getenv('FMONITOR_DB_HOST')?:getenv('FMONITOR_DEMO_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:getenv('FMONITOR_DEMO_DB_USER')?:'',getenv('FMONITOR_DB_PASSWORD')?:getenv('FMONITOR_DEMO_DB_PASSWORD')?:'',getenv('FMONITOR_DB_NAME')?:getenv('FMONITOR_DEMO_DB_NAME')?:'',(int)(getenv('FMONITOR_DB_PORT')?:getenv('FMONITOR_DEMO_DB_PORT')?:3306));$db->set_charset('utf8mb4');return$db;}
    private static function prefix():string{$p=getenv('FMONITOR_PROCESS_TABLE_PREFIX');if(!is_string($p)||preg_match('/^[A-Za-z0-9_]{0,25}$/D',$p)!==1)throw new RuntimeException();return$p;}
    private static function date(string$v):?string{if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$v,$m)!==1||!checkdate((int)$m[2],(int)$m[3],(int)$m[1]))return null;return$v;}
    private static function now():string{return(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format(DATE_ATOM);}
    private static function shortDate(string$v):string{return(DateTimeImmutable::createFromFormat('!Y-m-d',$v)?->format('d.m.Y'))??$v;}
    private static function fail(mysqli$db,int$status,string$message):never{$db->rollback();self::plain($status,$message);}
    private static function unavailable():never{$body="Service unavailable.\n";http_response_code(503);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');header('Retry-After: 60');header('Content-Length: '.strlen($body));if(($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD')echo$body;exit;}
    private static function plain(int$status,string$message):never{http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');echo$message."\n";exit;}
}
