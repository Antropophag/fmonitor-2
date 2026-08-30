<?php

declare(strict_types=1);

final class RapidPilotCompletionFlow
{
    private const WEIGHTS = [28=>2,29=>2,30=>2,31=>2,32=>1,33=>1,34=>2,35=>1,36=>2,37=>3,38=>3,39=>3,40=>3,41=>2,1=>2,2=>2,3=>1,4=>2,5=>1,6=>1,7=>2,8=>5,9=>1,10=>1,11=>3,12=>2,13=>2,14=>1,15=>2,16=>4,17=>4,18=>3,19=>3,20=>3,21=>2,22=>3,23=>2,24=>1,25=>1,26=>1,27=>1];

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
        $db=self::db();$prefix=self::prefix();self::ensureSchema($db,$prefix);
        $actor=self::actorId($db,$prefix);$now=self::now();
        try{$db->begin_transaction();$case=self::case($db,$prefix,$objectId,true);if($case===null)self::fail($db,404,'Объект не найден.');
            $progress=self::installationProgress($db,$prefix,(int)$case['id']);if($progress<85)self::fail($db,409,'Сначала завершите монтажные работы до 85%.');
            $facts=self::facts($db,$prefix,(int)$case['id']);$action=(string)($_POST['action']??'');
            if($action==='record_pto'){
                if(isset($facts['pto_act']))self::fail($db,409,'Дата акта ПТО уже зафиксирована.');
                $date=self::date((string)($_POST['ptoActDate']??''));if($date===null||$date>substr($now,0,10))self::fail($db,422,'Укажите дату акта ПТО не позже сегодняшней.');
                self::insert($db,$prefix,(int)$case['id'],'pto_act',$date,'',$actor,$now);
            }elseif($action==='record_declaration'){
                if(!isset($facts['pto_act']))self::fail($db,409,'Сначала зафиксируйте дату акта ПТО.');
                if(isset($facts['declaration']))self::fail($db,409,'Декларация уже зафиксирована.');
                $details=trim((string)($_POST['declarationDetails']??''));$date=self::date((string)($_POST['declarationDate']??''));
                if($details===''||mb_strlen($details)>500||$date===null||$date>substr($now,0,10))self::fail($db,422,'Укажите дату и реквизиты декларации.');
                self::insert($db,$prefix,(int)$case['id'],'declaration',$date,$details,$actor,$now);
            }else self::fail($db,422,'Неизвестное действие.');
            $db->commit();
        }catch(Throwable $error){if($db->errno===0)$db->rollback();throw$error;}
        header('Location: /pilot/objects/'.$objectId.'#completion',true,303);header('Cache-Control: no-store');exit;
    }

    public static function blocksLegacyCompletion(string $path): bool
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||preg_match('#^/pilot/objects/[1-9][0-9]*/checklist/operations$#D',$path)!==1)return false;
        $body=file_get_contents('php://input');if(!is_string($body))return false;$payload=json_decode($body,true);
        if(!is_array($payload)||($payload['itemId']??null)!==42)return false;
        http_response_code(409);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
        echo json_encode(['status'=>'rejected','message'=>'Последние 15% закрываются актом ПТО и декларацией в карточке объекта.'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);return true;
    }

    public static function enhanceCard(string $html,int $objectId):string
    {
        try{$db=self::db();$prefix=self::prefix();self::ensureSchema($db,$prefix);$case=self::case($db,$prefix,$objectId,false);if($case===null||($case['process_state']??null)!=='working')return$html;$progress=self::installationProgress($db,$prefix,(int)$case['id']);$facts=self::facts($db,$prefix,(int)$case['id']);}
        catch(Throwable){return$html;}
        $complete=isset($facts['pto_act'],$facts['declaration']);$status=self::status($progress,$complete);
        $paint=$complete?'shlz-status--bright-green':($progress>=85?'shlz-status--orange':'shlz-status--blue');
        $html=preg_replace('#<span class="shlz-status [^"]*">(?:В работе|Изменяющее распоряжение подготовлено)</span>#','<span class="shlz-status '.$paint.'">'.$status.'</span>',$html,1)??$html;
        $html=preg_replace_callback('#<section class="fm2-next-action"[^>]*>.*?</section>#s',static function(array$match)use($objectId,$progress,$facts):string{
            preg_match('#<div class="fm2-action-stack">.*?</div>#s',$match[0],$actions);
            $current=$progress<85?$match[0]:self::currentAction($objectId,$progress,$facts,(string)($_SERVER['FMONITOR_AUTH_CSRF']??''),$actions[0]??'');
            return$current.self::panel($progress,$facts);
        },$html,1)??$html;
        return$html;
    }

    public static function decorateQueue(array $objects,mysqli$db,string$prefix):array
    {
        if($objects===[])return[];self::ensureSchema($db,$prefix);$objectIds=array_map('intval',array_column($objects,'id'));$idList=implode(',',$objectIds);$cases=$db->query("SELECT id,legacy_installation_object_id FROM `{$prefix}fm2_installation_cases` WHERE legacy_installation_object_id IN({$idList})")->fetch_all(MYSQLI_ASSOC);$objectByCase=[];$caseIds=[];foreach($cases as$row){$case=(int)$row['id'];$caseIds[]=$case;$objectByCase[$case]=(int)$row['legacy_installation_object_id'];}if($caseIds===[])return$objects;$caseList=implode(',',$caseIds);
        $progress=[];$items=$db->query("SELECT DISTINCT installation_case_id,item_id FROM `{$prefix}fm2_checklist_operations` WHERE installation_case_id IN({$caseList}) AND operation_type='item_completed'")->fetch_all(MYSQLI_ASSOC);foreach($items as$row){$case=(int)$row['installation_case_id'];$progress[$case]=min(85,($progress[$case]??0)+(self::WEIGHTS[(int)$row['item_id']]??0));}
        $factTypes=[];$factRows=$db->query("SELECT installation_case_id,fact_type FROM `{$prefix}fm2_pilot_completion_facts` WHERE installation_case_id IN({$caseList})")->fetch_all(MYSQLI_ASSOC);foreach($factRows as$row)$factTypes[(int)$row['installation_case_id']][(string)$row['fact_type']]=true;
        $state=[];foreach($objectByCase as$case=>$objectId){$value=$progress[$case]??0;$pto=isset($factTypes[$case]['pto_act']);$complete=$pto&&isset($factTypes[$case]['declaration']);$state[$objectId]=['status'=>self::status($value,$complete),'nextStep'=>$complete?'Монтаж закрыт актом ПТО и декларацией':($value<85?'Продолжить монтажные работы':($pto?'Добавить декларацию':'Зафиксировать дату акта ПТО'))];}
        foreach($objects as&$object)if(($object['status']??null)==='В работе'&&isset($state[(int)$object['id']]))$object=array_replace($object,$state[(int)$object['id']]);unset($object);return$objects;
    }

    public static function ensureQueueSchema(mysqli$db,string$prefix):void{self::ensureSchema($db,$prefix);}

    public static function paintStatuses(string$html):string
    {
        $map=['Требуется распоряжение'=>'shlz-status--orange','Готов к открытию'=>'shlz-status--source-blue','Монтажные работы'=>'shlz-status--cyan','Документарное закрытие'=>'shlz-status--purple','Работы завершены'=>'shlz-status--bright-green','Требуется изменение'=>'shlz-status--pink'];
        return preg_replace_callback('#<span class="([^"]*\bshlz-status\b[^"]*)">([^<]+)</span>#u',static function(array$match)use($map):string{$label=trim(html_entity_decode($match[2],ENT_QUOTES|ENT_HTML5,'UTF-8'));$label=preg_replace('/\s+·\s+\d+%$/u','',$label)??$label;if(!isset($map[$label]))return$match[0];$classes=preg_replace('/\s*shlz-status--(?:green|bright-green|source-blue|blue|orange|purple|cyan|pink|neutral)\b/','',$match[1])??$match[1];return'<span class="'.trim($classes).' '.$map[$label].'">'.$match[2].'</span>';},$html)??$html;
    }

    public static function enhanceChecklist(string $html,int $objectId):string
    {
        try{$db=self::db();$prefix=self::prefix();self::ensureSchema($db,$prefix);$case=self::case($db,$prefix,$objectId,false);if($case===null)return$html;$facts=self::facts($db,$prefix,(int)$case['id']);}
        catch(Throwable){return$html;}
        $pto=isset($facts['pto_act']);$complete=$pto&&isset($facts['declaration']);$final=$complete?100:85;
        $status=$complete?'Работы завершены':($pto?'Ожидается декларация':'Ожидается акт ПТО');$paint=$complete?'shlz-status--bright-green':'shlz-status--orange';$action=$complete?'Посмотреть документы':($pto?'Добавить декларацию':'Зафиксировать акт ПТО');
        $ptoMark=$pto?'is-done':'';$declarationMark=$complete?'is-done':'';
        $closeout='<section class="fm2-check-section fm2-check-section--completion" data-check-section="8"><div class="fm2-check-closeout"><div class="fm2-check-closeout__copy"><div><strong>Документарное закрытие</strong><span class="shlz-status '.$paint.'">'.$status.'</span></div><p>Последние 15% закрываются после монтажа двумя обязательными основаниями.</p><ul><li class="'.$ptoMark.'"><span></span>Дата акта ПТО</li><li class="'.$declarationMark.'"><span></span>Декларация</li></ul></div><a class="shlz-button '.($complete?'':'shlz-button--primary').'" href="/pilot/objects/'.$objectId.'#completion">'.$action.'</a></div></section>';
        $html=preg_replace('#<section class="fm2-check-section" data-check-section="8".*?</section>#s',$closeout,$html,1)??$html;
        $html=str_replace('<span data-total-progress>0</span>%','<span data-total-progress data-progress-cap="'.$final.'">0</span>%',$html);
        $html=str_replace('<span data-total-items>0</span> из 42 работ','<span data-total-items>0</span> из 41 монтажной работы',$html);
        return str_replace('</body>','<script>document.addEventListener("DOMContentLoaded",()=>{const n=document.querySelector("[data-progress-cap]");if(!n)return;const cap=Number(n.dataset.progressCap);const paint=()=>{const v=Number(n.textContent);if((cap===100&&v!==100)||(cap===85&&v>85))n.textContent=String(cap)};new MutationObserver(paint).observe(n,{childList:true,characterData:true,subtree:true});paint()})</script></body>',$html);
    }

    private static function currentAction(int$id,int$progress,array$facts,string$csrf,string$actions):string
    {
        $pto=$facts['pto_act']??null;$declaration=$facts['declaration']??null;$complete=$pto!==null&&$declaration!==null;$e=static fn(string$v):string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');
        $form=$complete?'':($pto===null?'<form method="post" action="/pilot/objects/'.$id.'/completion" class="fm2-completion-form"><input type="hidden" name="csrfToken" value="'.$e($csrf).'"><input type="hidden" name="action" value="record_pto"><label class="shlz-field"><span class="shlz-field__label">Дата акта ПТО</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="ptoActDate" max="'.substr(self::now(),0,10).'" required></span></label><button class="shlz-button shlz-button--primary">Зафиксировать акт ПТО</button></form>':'<form method="post" action="/pilot/objects/'.$id.'/completion" class="fm2-completion-form"><input type="hidden" name="csrfToken" value="'.$e($csrf).'"><input type="hidden" name="action" value="record_declaration"><label class="shlz-field"><span class="shlz-field__label">Дата декларации</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="declarationDate" max="'.substr(self::now(),0,10).'" required></span></label><label class="shlz-field fm2-completion-details"><span class="shlz-field__label">Реквизиты декларации</span><span class="shlz-field__control"><input class="shlz-input" name="declarationDetails" maxlength="500" placeholder="Номер, кем и когда выдана" required></span></label><button class="shlz-button shlz-button--primary">Завершить работы</button></form>');
        $heading=$complete?'Документы приняты':($pto===null?'Зафиксируйте акт ПТО':'Добавьте декларацию');
        $copy=$complete?'Акт ПТО и декларация подтверждены. Работы по объекту завершены.':($pto===null?'Монтажная часть выполнена. Укажите дату акта ПТО.':'Акт ПТО зафиксирован. Добавьте реквизиты декларации, чтобы завершить работы.');
        return'<section class="fm2-next-action fm2-completion-action" id="completion" aria-labelledby="completion-title"><div><h2 id="completion-title">'.$heading.'</h2><p>'.$copy.'</p></div>'.$form.$actions.'</section>';
    }

    private static function panel(int$progress,array$facts):string
    {
        $pto=$facts['pto_act']??null;$declaration=$facts['declaration']??null;$complete=$pto!==null&&$declaration!==null;$shown=$complete?100:min(85,$progress);$e=static fn(string$v):string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');
        $ptoState=$pto?'<strong>Акт ПТО от '.self::shortDate($pto['fact_date']).'</strong><span>Контрольная точка пройдена</span>':($progress>=85?'<strong>Зафиксируйте дату акта ПТО</strong><span>Монтажная часть выполнена на 85%</span>':'<strong>Завершите монтаж до 85%</strong><span>До акта ПТО осталось '.(85-$shown).'%</span>');
        $declarationState=$declaration?'<strong>'.$e($declaration['details']).'</strong><span>Декларация от '.self::shortDate($declaration['fact_date']).'</span>':($pto?'<strong>Добавьте декларацию</strong><span>Она закроет последние 15% работ</span>':'<strong>Ожидает акта ПТО</strong><span>Декларация фиксируется после контрольной точки</span>');
        $status=self::status($progress,$complete);
        return'<section class="fm2-completion" aria-labelledby="work-progress-title"><header><div><h2 id="work-progress-title">Ход работ</h2><p>Монтаж даёт 85%. Последние 15% закрываются актом ПТО и декларацией.</p></div><span class="shlz-status '.($complete?'shlz-status--bright-green':($progress>=85?'shlz-status--orange':'shlz-status--blue')).'">'.$status.' · '.$shown.'%</span></header><div class="fm2-completion-track" role="progressbar" aria-label="Готовность работ" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'.$shown.'"><progress class="fm2-completion-track__segment fm2-completion-track__segment--installation" max="85" value="'.min(85,$progress).'" aria-hidden="true"></progress><progress class="fm2-completion-track__segment fm2-completion-track__segment--documents" max="15" value="'.($complete?'15':'0').'" aria-hidden="true"></progress></div><ol class="fm2-completion-steps"><li class="'.($progress>=85?'is-done':'is-current').'"><span>1</span><div><strong>Чеклист монтажных работ</strong><small>'.min(85,$progress).' из 85%</small></div></li><li class="'.($pto?'is-done':($progress>=85?'is-current':'')).'"><span>2</span><div>'.$ptoState.'</div></li><li class="'.($declaration?'is-done':($pto?'is-current':'')).'"><span>3</span><div>'.$declarationState.'</div></li></ol></section>';
    }

    private static function installationProgress(mysqli$db,string$p,int$caseId):int{$ids=implode(',',array_keys(self::WEIGHTS));$s=$db->prepare("SELECT DISTINCT item_id FROM `{$p}fm2_checklist_operations` WHERE installation_case_id=? AND operation_type='item_completed' AND item_id IN({$ids})");$s->bind_param('i',$caseId);$s->execute();$sum=0;foreach($s->get_result()->fetch_all(MYSQLI_ASSOC)as$r)$sum+=self::WEIGHTS[(int)$r['item_id']]??0;return min(85,$sum);}
    private static function status(int$progress,bool$complete):string{return$complete?'Работы завершены':($progress>=85?'Документарное закрытие':'Монтажные работы');}
    private static function facts(mysqli$db,string$p,int$caseId):array{$s=$db->prepare("SELECT fact_type,fact_date,details,recorded_at,recorded_by_user_id FROM `{$p}fm2_pilot_completion_facts` WHERE installation_case_id=? ORDER BY id");$s->bind_param('i',$caseId);$s->execute();$facts=[];foreach($s->get_result()->fetch_all(MYSQLI_ASSOC)as$r)$facts[$r['fact_type']]=$r;return$facts;}
    private static function insert(mysqli$db,string$p,int$caseId,string$type,string$date,string$details,int$actor,string$now):void{$s=$db->prepare("INSERT INTO `{$p}fm2_pilot_completion_facts`(installation_case_id,fact_type,fact_date,details,recorded_at,recorded_by_user_id)VALUES(?,?,?,?,?,?)");$s->bind_param('issssi',$caseId,$type,$date,$details,$now,$actor);$s->execute();}
    private static function ensureSchema(mysqli$db,string$p):void{$db->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_pilot_completion_facts`(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,installation_case_id BIGINT UNSIGNED NOT NULL,fact_type ENUM('pto_act','declaration') NOT NULL,fact_date DATE NOT NULL,details VARCHAR(500) NOT NULL DEFAULT '',recorded_at VARCHAR(40) NOT NULL,recorded_by_user_id BIGINT UNSIGNED NOT NULL,UNIQUE KEY uq_case_fact(installation_case_id,fact_type),KEY(installation_case_id,id))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
    private static function case(mysqli$db,string$p,int$id,bool$lock):?array{$s=$db->prepare("SELECT id,process_state FROM `{$p}fm2_installation_cases` WHERE legacy_installation_object_id=? LIMIT 2".($lock?' FOR UPDATE':''));$s->bind_param('i',$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);return count($rows)===1?$rows[0]:null;}
    private static function actorId(mysqli$db,string$p):int{$email=(string)($_SERVER['REMOTE_USER']??'');$s=$db->prepare("SELECT user_id FROM `{$p}fm2_pilot_users` WHERE email=? AND status=1 LIMIT 1");$s->bind_param('s',$email);$s->execute();$row=$s->get_result()->fetch_assoc();if(!is_array($row))self::plain(403,'Пользователь не найден.');return(int)$row['user_id'];}
    private static function db():mysqli{mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli(getenv('FMONITOR_DB_HOST')?:getenv('FMONITOR_DEMO_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:getenv('FMONITOR_DEMO_DB_USER')?:'',getenv('FMONITOR_DB_PASSWORD')?:getenv('FMONITOR_DEMO_DB_PASSWORD')?:'',getenv('FMONITOR_DB_NAME')?:getenv('FMONITOR_DEMO_DB_NAME')?:'',(int)(getenv('FMONITOR_DB_PORT')?:getenv('FMONITOR_DEMO_DB_PORT')?:3306));$db->set_charset('utf8mb4');return$db;}
    private static function prefix():string{$p=(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');if(preg_match('/^[A-Za-z0-9_]+$/D',$p)!==1)throw new RuntimeException();return$p;}
    private static function date(string$v):?string{if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$v,$m)!==1||!checkdate((int)$m[2],(int)$m[3],(int)$m[1]))return null;return$v;}
    private static function now():string{return(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format(DATE_ATOM);}
    private static function shortDate(string$v):string{return(DateTimeImmutable::createFromFormat('!Y-m-d',$v)?->format('d.m.Y'))??$v;}
    private static function fail(mysqli$db,int$status,string$message):never{$db->rollback();self::plain($status,$message);}
    private static function plain(int$status,string$message):never{http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');echo$message."\n";exit;}
}
