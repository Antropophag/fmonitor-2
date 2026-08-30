<?php

declare(strict_types=1);

final class RapidPilotObjectDetails
{
    public static function enhance(string $html, string $path): string
    {
        if (preg_match('#^/pilot/objects/([1-9][0-9]*)/assignment-order/prepare$#D', $path, $prepareMatch) === 1) {
            return self::enhanceAssignmentForm($html, (int) $prepareMatch[1]);
        }
        if (preg_match('#^/pilot/objects/([1-9][0-9]*)$#D', $path, $match) !== 1) return $html;
        $html=str_replace('>Изменяющее распоряжение подготовлено</span>','>В работе</span>',$html);
        $html=str_replace('>Распоряжение подготовлено</span>','>Требуется распоряжение</span>',$html);
        $html = self::correctUnavailableOpenCommand($html);
        $details = self::read((int) $match[1]);
        $identity=self::extractElement($html,'<header class="fm2-object-identity"');$action=self::extractElement($html,'<section class="fm2-next-action"');$dashboard=self::extractElement($html,'<div class="fm2-object-dashboard"');
        if ($identity===null||$action===null||$dashboard===null) return $html;
        $originNotice=self::extractElement($html,'<div class="fm2-alert fm2-origin-notice"');
        $identityFacts=self::identityFacts($identity['html']);$section = ($originNotice['html']??'').self::render($details,$action['html'],$dashboard['html'],$identityFacts);
        $start=min($identity['start'],$action['start'],$dashboard['start']);$end=max($identity['end'],$action['end'],$dashboard['end']);
        $html=substr_replace($html,$section,$start,$end-$start);
        $id=(string)$match[1];$registration=self::e($identityFacts['registration']);$html=str_replace('<strong>Объект монтажа № '.$id.'</strong>','<strong>Карточка объекта</strong>',$html);$html=str_replace('<span aria-current="page">Объект монтажа № '.$id.'</span>','<span aria-current="page">Рег. № '.$registration.'</span>',$html);
        return str_replace('</body>', '<script type="module" src="/pilot/assets/object-details.js"></script></body>', $html);
    }

    private static function enhanceAssignmentForm(string $html,int $objectId):string
    {
        if(!str_contains($html,'Изменить состав монтажников'))return$html;
        foreach(self::currentInstallerIds($objectId)as$id)$html=preg_replace('/(<span data-id="'.preg_quote((string)$id,'/').'"[^>]*data-selected=")0("[^>]*>)/','${1}1$2',$html,1)??$html;
        $html=str_replace('>Выбрать монтажников</button>','>Изменить состав</button>',$html);
        $html=str_replace('<strong>Выбрано</strong>','<strong>Состав после изменения</strong>',$html);
        return str_replace('</body>','<script type="module" src="/pilot/assets/object-details.js"></script></body>',$html);
    }

    private static function currentInstallerIds(int$objectId):array
    {
        $prefix=getenv('FMONITOR_PROCESS_TABLE_PREFIX');if(!is_string($prefix)||preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)return[];
        try{$db=new mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_DB_PASSWORD'),(string)getenv('FMONITOR_DB_NAME'),(int)(getenv('FMONITOR_DB_PORT')?:3306));$db->set_charset('utf8mb4');$sql="SELECT oi.installer_tab_id FROM `{$prefix}fm2_order_installers` oi JOIN `{$prefix}fm2_assignment_orders` o ON o.id=oi.assignment_order_id JOIN `{$prefix}fm2_installation_cases` c ON c.id=o.installation_case_id WHERE c.legacy_installation_object_id=? AND o.status='registered' AND o.version_no=(SELECT MAX(latest.version_no) FROM `{$prefix}fm2_assignment_orders` latest WHERE latest.installation_case_id=c.id AND latest.status='registered') AND oi.change_action<>'release' ORDER BY oi.installer_tab_id";$s=$db->prepare($sql);$s->bind_param('i',$objectId);$s->execute();$ids=array_map('intval',array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'installer_tab_id'));$db->close();return$ids;}catch(Throwable){return[];}
    }

    private static function extractElement(string $html,string $needle):?array
    {
        $start=strpos($html,$needle);if($start===false)return null;$openEnd=strpos($html,'>',$start);if($openEnd===false)return null;
        preg_match('/^<([a-z0-9]+)/i',substr($html,$start),$match);$tag=$match[1]??'';if($tag==='')return null;
        $offset=$openEnd+1;$depth=1;while($depth>0){$nextOpen=strpos($html,'<'.$tag,$offset);$nextClose=strpos($html,'</'.$tag.'>',$offset);if($nextClose===false)return null;if($nextOpen!==false&&$nextOpen<$nextClose){$depth++;$offset=strpos($html,'>',$nextOpen)+1;}else{$depth--;$offset=$nextClose+strlen('</'.$tag.'>');}}
        return ['start'=>$start,'end'=>$offset,'html'=>substr($html,$start,$offset-$start)];
    }

    private static function correctUnavailableOpenCommand(string $html): string
    {
        if (!str_contains($html, '>Готов к открытию</span>')) return $html;
        $incorrect = '<div><h2>Работы открыты</h2><p>Стройконтроль может фиксировать ход монтажа и фотоотчёт.</p></div><a class="fm2-primary-link" href="';
        $start = strpos($html, $incorrect);
        if ($start === false) return $html;
        $end = strpos($html, '</a>', $start);
        if ($end === false) return $html;
        $replacement = '<div><h2>Работы готовы к открытию</h2><p>Распоряжение зарегистрировано. Открыть работы может сотрудник с соответствующим полномочием.</p></div>';
        return substr_replace($html, $replacement, $start, $end + 4 - $start);
    }

    private static function identityFacts(string $identity):array
    {
        $document=new DOMDocument();$previous=libxml_use_internal_errors(true);$document->loadHTML('<?xml encoding="utf-8"?>'.$identity,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();libxml_use_internal_errors($previous);$xpath=new DOMXPath($document);$address=trim($xpath->query('//header/div/p')?->item(0)?->textContent??'');$registrationText=trim($xpath->query('//header/div/span')?->item(0)?->textContent??'');preg_match('/([0-9]+)$/u',$registrationText,$match);$status=$xpath->query('//header/span[contains(@class,"shlz-status")]')?->item(0);$statusHtml=$status?$document->saveHTML($status):'';return['address'=>$address,'registration'=>$match[1]??$registrationText,'status'=>is_string($statusHtml)?$statusHtml:''];
    }

    private static function read(int $objectId): ?array
    {
        $prefix = getenv('FMONITOR_PROCESS_TABLE_PREFIX');
        if (!is_string($prefix) || preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) return self::unavailableDetails('Проекция технических данных поколения недоступна.');
        try {
            $db = new mysqli(
                getenv('FMONITOR_DB_HOST') ?: '127.0.0.1',
                (string) getenv('FMONITOR_DB_USER'),
                (string) getenv('FMONITOR_DB_PASSWORD'),
                (string) getenv('FMONITOR_DB_NAME'),
                (int) (getenv('FMONITOR_DB_PORT') ?: 3306),
            );
            $statement = $db->prepare("SELECT payload_json,captured_at FROM `{$prefix}fm2_pilot_object_details` WHERE object_id=? LIMIT 1");
            $statement->bind_param('i', $objectId);
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            if (!is_array($row)) return self::unavailableDetails('Проекция технических данных для объекта недоступна.');
            $payload = json_decode((string) $row['payload_json'], true, flags: JSON_THROW_ON_ERROR);
            if(!is_array($payload))return self::unavailableDetails('Проекция технических данных повреждена.');
            $team=$db->prepare("SELECT oi.installer_tab_id,oi.fio_snapshot,oi.employment_status_snapshot,w.employment_status FROM `{$prefix}fm2_order_installers` oi JOIN `{$prefix}fm2_assignment_orders` o ON o.id=oi.assignment_order_id JOIN `{$prefix}fm2_installation_cases` c ON c.id=o.installation_case_id LEFT JOIN `{$prefix}fm2_workforce_catalog` w ON w.installer_tab_id=oi.installer_tab_id WHERE c.legacy_installation_object_id=? AND o.version_no=(SELECT MAX(latest.version_no) FROM `{$prefix}fm2_assignment_orders` latest WHERE latest.installation_case_id=c.id) AND oi.change_action<>'release' ORDER BY oi.installer_tab_id");$team->bind_param('i',$objectId);$team->execute();$installers=$team->get_result()->fetch_all(MYSQLI_ASSOC);
            $history=$db->prepare("SELECT o.version_no,o.kind,o.status,o.order_date,o.registration_number,o.prepared_at,a.artifact_type,a.filename,a.byte_size FROM `{$prefix}fm2_assignment_orders` o JOIN `{$prefix}fm2_installation_cases` c ON c.id=o.installation_case_id LEFT JOIN `{$prefix}fm2_order_artifacts` a ON a.assignment_order_id=o.id WHERE c.legacy_installation_object_id=? ORDER BY o.version_no DESC,FIELD(a.artifact_type,'signed_original','order','appendix'),a.artifact_type");$history->bind_param('i',$objectId);$history->execute();$documents=$history->get_result()->fetch_all(MYSQLI_ASSOC);$db->close();
            return $payload + ['objectId'=>$objectId,'capturedAt' => (string) $row['captured_at'],'installers'=>$installers,'assignmentDocuments'=>$documents];
        } catch (Throwable) {
            return self::unavailableDetails('Проекция технических данных поколения недоступна.');
        }
    }

    private static function unavailableDetails(string$message):array{return['fields'=>[],'capturedAt'=>'','installers'=>[],'assignmentDocuments'=>[],'projectionUnavailable'=>$message];}

    private static function render(array $data,string $action,string $dashboard,array $identity): string
    {
        $fields = $data['fields'] ?? [];
        $teamCommand='';
        $action=preg_replace_callback(
            '#<a class="shlz-link" href="([^"]+)">Изменить состав новым распоряжением</a>#u',
            static function(array $match)use(&$teamCommand):string{
                $teamCommand='<section class="fm2-team-command"><div><h3>Изменение состава</h3><p>Новая версия распоряжения сохранит прежний состав и историю закреплений.</p></div><form method="get" action="'.$match[1].'"><button class="shlz-button shlz-button--primary shlz-button--sm" type="submit">Изменить состав</button></form></section>';
                return '';
            },
            $action,
        )??$action;
        $existing=self::existingPanels($dashboard);$processDates=self::panelPairs($existing['Сроки работ']??'');$teamFacts=self::panelPairs($existing['Команда объекта']??'');
        foreach(['Плановое начало'=>'workdatestart','Плановое окончание'=>'workdatefinish','Фактическое начало'=>'factworkstartdate']as$label=>$key)if(isset($processDates[$label]))$fields[$key]['display']=$processDates[$label]==='Не зафиксировано'?'':$processDates[$label];
        $updated = self::dateTime((string) ($data['capturedAt'] ?? ''));
        $passport=self::compactRow('Расположение',[['Округ','area'],['Район','district']],$fields)
            .self::compactRow('Лифт',[['Этажность','floors'],['Грузоподъёмность','weight',' кг'],['Скорость','speed',' м/с']],$fields)
            .self::compactRow('Шахта',[['Тип','pittype'],['Материал','pitmaterial'],['Очередность','paired']],$fields)
            .self::compactRow('Двери',[['Кабина','doorcabin_type'],['Шахта','typepitdoor'],['Заводской №','zavnumber']],$fields);
        $schedule=self::compactRow('Начало работ',[['План','workdatestart'],['С учётом очередности','workdatestartadjusted'],['Факт','factworkstartdate']],$fields)
            .self::compactRow('Окончание работ',[['План','workdatefinish'],['С учётом очередности','workdateendadjusted'],['Прогноз','plan_finish_date']],$fields)
            .self::compactRow('Оборудование',[['Изготовлено','equipmentproduced'],['Поставлено','equiponobject'],['Дата поставки','equipmentdeliverydate']],$fields)
            .self::compactRow('Подготовка',[['Замеры','measurements']],$fields);
        $engineer=$teamFacts['Инженер стройконтроля']??trim((string)($fields['responsstroicontrol']['display']??''));$engineer=preg_replace('/\s+·\s+.*$/u','',$engineer)??$engineer;$roster='';foreach($data['installers']??[]as$installer){$employed=($installer['employment_status']??$installer['employment_status_snapshot']??'')==='employed';$tab=str_pad((string)$installer['installer_tab_id'],6,'0',STR_PAD_LEFT);$roster.='<div class="fm2-object-installer"><strong>'.self::e(trim((string)$installer['fio_snapshot'])).'</strong><span>Табельный № '.self::e($tab).'</span><span class="shlz-status '.($employed?'shlz-status--bright-green':'shlz-status--neutral').'">'.($employed?'Работает':'Уволен').'</span></div>';}$participants=self::teamRow($engineer,trim((string)($fields['contact_phone_itn']['display']??'')),$roster)
            .self::compactRow('Подрядчики',[['Генподрядчик','generalcontractor'],['Субподрядчик','subsuplier']],$fields)
            .self::compactRow('Ответственный на объекте',[['ФИО','respperson'],['Телефон','contact_phone']],$fields)
            .self::compactRow('Начальник участка / бригадир',[['ФИО','headofconstructarea'],['Телефон','contact_phone_headofconstruct']],$fields);
        $documents=self::compactRow('Акт открытия',[['Загружен','openingactuploaded'],['Проверен','openingactverified']],$fields)
            .self::compactRow('Ситуационный план',[['Загружен','siteplanuploaded'],['Проверен','siteplanverified']],$fields)
            .self::compactRow('Акт передачи',[['Подписан','transferactsign'],['Дата','transferactdate'],['Статус','transferactstatus']],$fields)
            .self::compactRow('Движение акта передачи',[['Привезён','transferactdeliverdate'],['Направлен в УЛХ','acttransfertoulhdate']],$fields)
            .self::compactRow('Файл акта передачи',[['Загружен','transfer_act_uploaded'],['Проверен','transferactverified']],$fields);
        $control=self::compactRow('ПТО и несоответствия',[['Дата акта ПТО','ptoactdate'],['Акт несоответствия','non_conformance_act_date']],$fields)
            .self::compactRow('Декларации',[['Реквизиты','declarations'],['Переданы подрядчику','contractor_docs_transfer_date']],$fields)
            .self::compactRow('Состояние',[['Legacy-статус','object_status'],['Контроль','control_flag']],$fields)
            .self::compactRow('Комментарии',[['Причина задержки','comments'],['Комментарий СМ','sm_comment']],$fields);
        $orderHistory=self::assignmentDocumentHistory($data['assignmentDocuments']??[],(int)($data['objectId']??0));$documents.=$orderHistory!==''?$orderHistory:($existing['Распоряжение']??$existing['Распоряжение и 1С ДО']??'');$control.=($existing['Проблемы']??'').($existing['Последние события']??'');
        $tabs=['schedule'=>['Сроки и готовность',$schedule],'participants'=>['Команда',$teamCommand.$participants],'documents'=>['Документы',$documents],'control'=>['Контроль и история',$control]];$buttons='';$panels='';
        foreach($tabs as$key=>[$label,$content]){$selected=$key==='schedule';$buttons.='<button class="shlz-tabs__tab" id="object-'.$key.'-tab" type="button" role="tab" aria-selected="'.($selected?'true':'false').'" aria-controls="object-'.$key.'-panel"'.($selected?'':' tabindex="-1"').'>'.$label.'</button>';$panels.='<div class="shlz-tabs__panel fm2-object-tab-panel" id="object-'.$key.'-panel" role="tabpanel" aria-labelledby="object-'.$key.'-tab"'.($selected?'':' hidden').'><div class="fm2-compact-list">'.$content.'</div></div>';}
        $unavailable=isset($data['projectionUnavailable'])?'<p class="fm2-data-unavailable" role="alert">'.self::e((string)$data['projectionUnavailable']).'</p>':'';return '<section class="fm2-object-data"><header class="fm2-card-header"><div class="fm2-object-title"><div class="fm2-object-title__line"><h1>'.self::e($identity['address']).'</h1>'.$identity['status'].'</div><p>Данные объекта актуальны на '.$updated.'</p></div><div class="fm2-registration"><strong>'.self::e($identity['registration']).'</strong><span>Регистрационный номер</span></div></header>'.$unavailable.'<div class="fm2-object-layout"><aside class="fm2-static-passport" aria-labelledby="passport-title"><div class="fm2-passport-heading"><h2 id="passport-title">Паспорт объекта</h2><span>Постоянные реквизиты</span></div><div class="fm2-compact-list">'.$passport.'</div></aside><div class="fm2-object-workspace">'.$action.'<div class="shlz-tabs shlz-tabs--boxed fm2-object-tabs" data-shlz-tabs><div class="shlz-tabs__list" role="tablist" aria-label="Рабочие данные объекта">'.$buttons.'</div>'.$panels.'</div></div></div></section>';
    }

    private static function existingPanels(string $dashboard):array
    {
        $document=new DOMDocument();$previous=libxml_use_internal_errors(true);$document->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$dashboard.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();libxml_use_internal_errors($previous);$xpath=new DOMXPath($document);$result=[];
        foreach($xpath->query('//section[contains(concat(" ",normalize-space(@class)," ")," fm2-panel ")]')?:[]as$section){$heading=$xpath->query('./h2',$section)?->item(0);if(!$heading)continue;$title=trim($heading->textContent);$markup=$document->saveHTML($section);if(is_string($markup))$result[$title]=str_replace('class="fm2-panel"','class="fm2-panel fm2-integrated-panel"',$markup);}
        return $result;
    }

    private static function panelPairs(string $panel):array
    {
        if($panel==='')return[];$document=new DOMDocument();$previous=libxml_use_internal_errors(true);$document->loadHTML('<?xml encoding="utf-8"?>'.$panel,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();libxml_use_internal_errors($previous);$xpath=new DOMXPath($document);$terms=$xpath->query('//dt');$result=[];if(!$terms)return[];foreach($terms as$term){$value=$term->nextSibling;while($value&&$value->nodeType!==XML_ELEMENT_NODE)$value=$value->nextSibling;if($value)$result[trim($term->textContent)]=trim($value->textContent);}return$result;
    }

    private static function panelValues(string $panel,string $wanted):array
    {
        if($panel==='')return[];$document=new DOMDocument();$previous=libxml_use_internal_errors(true);$document->loadHTML('<?xml encoding="utf-8"?>'.$panel,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();libxml_use_internal_errors($previous);$xpath=new DOMXPath($document);$result=[];foreach($xpath->query('//dt')?:[]as$term){if(trim($term->textContent)!==$wanted)continue;$value=$term->nextSibling;while($value&&$value->nodeType!==XML_ELEMENT_NODE)$value=$value->nextSibling;if($value){$text=trim($value->textContent);if($text!==''&&$text!=='Ещё не назначены')$result[]=$text;}}return$result;
    }

    private static function compactRow(string $title,array $values,array $fields):string
    {
        $items='';foreach($values as$definition){[$label,$key]=$definition;$suffix=$definition[2]??'';$value=trim((string)($fields[$key]['display']??''));$empty=$value===''||($key==='zavnumber'&&$value==='0');$shown=$empty?'Не указано':self::e($value.$suffix);if(!$empty&&in_array($value,['Да','Нет'],true))$shown='<span class="shlz-status '.($value==='Да'?'shlz-status--bright-green':'shlz-status--neutral').'">'.$shown.'</span>';$items.='<div class="fm2-compact-value'.($empty?' fm2-compact-value--empty':'').'"><span>'.self::e($label).'</span><strong>'.$shown.'</strong></div>';}
        return '<div class="fm2-compact-row"><h4>'.self::e($title).'</h4><div class="fm2-compact-values">'.$items.'</div></div>';
    }

    private static function compactDirectRow(string $title,array $values):string
    {
        $items='';foreach($values as[$label,$value]){$value=trim($value);$empty=$value===''||$value==='Ещё не назначены';$items.='<div class="fm2-compact-value'.($empty?' fm2-compact-value--empty':'').'"><span>'.self::e($label).'</span><strong>'.self::e($empty?'Не указано':$value).'</strong></div>';}
        return '<div class="fm2-compact-row"><h4>'.self::e($title).'</h4><div class="fm2-compact-values">'.$items.'</div></div>';
    }

    private static function teamRow(string $engineer,string $phone,string $roster):string
    {
        $engineer=trim($engineer);$phone=trim($phone);$people=$roster===''?'<span class="fm2-roster-empty">Не назначены</span>':$roster;
        return '<div class="fm2-compact-row fm2-team-row"><h4>Команда объекта</h4><div class="fm2-team-content"><div class="fm2-team-engineer"><div><span>Инженер стройконтроля</span><strong>'.self::e($engineer===''?'Не назначен':$engineer).'</strong></div><div><span>Телефон</span><strong>'.self::e($phone===''?'Не указано':$phone).'</strong></div></div><div><span class="fm2-team-label">Монтажники</span><div class="fm2-installer-roster">'.$people.'</div></div></div></div>';
    }

    private static function assignmentDocumentHistory(array $rows,int $objectId):string
    {
        if($rows===[])return'';$versions=[];foreach($rows as$row){$version=(int)($row['version_no']??0);if($version<1)continue;$versions[$version]??=['kind'=>(string)($row['kind']??''),'status'=>(string)($row['status']??''),'date'=>(string)($row['order_date']??''),'number'=>trim((string)($row['registration_number']??'')),'artifacts'=>[]];if($row['artifact_type']!==null)$versions[$version]['artifacts'][]=$row;}if($versions===[])return'';
        $content='';foreach($versions as$version=>$order){$kind=$order['kind']==='change'?'Изменение состава':'Первичное закрепление';$number=$order['number']!==''?'№ '.self::e($order['number']):'без номера';$date=self::shortDate($order['date']);$files='';foreach($order['artifacts']as$artifact){$type=(string)$artifact['artifact_type'];$label=match($type){'signed_original'=>'Подписанный оригинал','order'=>'Распоряжение','appendix'=>'Приложение',default=>(string)$artifact['filename']};$href='/pilot/objects/'.$objectId.'/assignment-orders/'.$version.'/artifacts/'.rawurlencode($type);$files.='<a class="shlz-link" href="'.$href.'">'.self::e($label).'</a>';}$files=$files===''?'<span class="fm2-document-empty">Файлы не сохранены</span>':$files;$content.='<article class="fm2-assignment-version"><div><strong>Версия '.$version.' · '.$number.'</strong><span>'.$kind.' · '.$date.'</span></div><div class="fm2-assignment-files">'.$files.'</div></article>';}
        return'<section class="fm2-compact-row fm2-assignment-history"><h4>Распоряжения</h4><div class="fm2-assignment-list">'.$content.'</div></section>';
    }

    private static function shortDate(string$value):string{try{return(new DateTimeImmutable($value))->format('d.m.Y');}catch(Throwable){return'дата не указана';}}

    private static function dateTime(string $value): string
    {
        try { return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Moscow'))->format('d.m.Y, H:i'); }
        catch (Throwable) { return 'дата снимка не указана'; }
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
