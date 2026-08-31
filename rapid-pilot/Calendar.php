<?php

declare(strict_types=1);

use FMonitor2\PilotHttp\HttpUser;
use FMonitor2\PilotHttp\PilotView;
use FMonitor2\PilotHttp\AccessPolicy;

final class RapidPilotCalendar
{
    private const ZONE = 'Europe/Moscow';
    private const SOURCE_ROW_LIMIT = 5000;
    private const TOTAL_EVENT_LIMIT = 12000;
    private mysqli $db;
    private string $processPrefix;
    private string $legacyPrefix;

    public function __construct()
    {
        $this->processPrefix = (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX');
        $this->legacyPrefix = (string) getenv('FMONITOR_LEGACY_TABLE_PREFIX');
        if (preg_match('/^[A-Za-z0-9_]+$/D', $this->processPrefix) !== 1 || preg_match('/^[A-Za-z0-9_]*$/D', $this->legacyPrefix) !== 1) {
            throw new RuntimeException('Invalid calendar table prefix');
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->db = new mysqli(getenv('FMONITOR_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_DB_USER') ?: 'fmonitor2_demo', getenv('FMONITOR_DB_PASSWORD') ?: 'fmonitor2_demo_local', getenv('FMONITOR_DB_NAME') ?: 'fmonitor2_demo', (int) (getenv('FMONITOR_DB_PORT') ?: 23306));
        $this->db->set_charset('utf8mb4');
    }

    public static function matches(string $path): bool
    {
        return $path === '/pilot/calendar' || $path === '/pilot/calendar/';
    }

    public static function decorateNavigation(string $html, bool $active): string
    {
        $current = $active ? ' aria-current="page"' : '';
        $link = '<a class="fm2-nav-item" href="/pilot/calendar"' . $current . '><svg class="fm2-nav-icon fm2-nav-icon--shlz" viewBox="0 0 24 24" aria-hidden="true"><use href="/pilot/assets/shlz-icons.svg#shlz-icon-circle-grid-interface-sidebar"/></svg><span class="fm2-nav-text">Календарь</span></a>';
        return preg_replace('#(<a class="fm2-nav-item" href="/pilot/objects"[^>]*>.*?</a>)#s', '$1' . $link, $html, 1) ?? $html;
    }

    public function handle(): never
    {
        header('Cache-Control: no-store');
        try {
            $this->assertCalendarGridExport();
            [$first, $last] = $this->period();
            $selected = $this->selectedDate((string) ($_GET['date'] ?? ''), $first, $last);
            $user = $this->user();
            $events = $this->read($first, $last);
            $html = $this->render($user, $first, $last, $selected, $events);
            $html = RapidPilotShell::decorate($html, (string) ($_SERVER['FMONITOR_AUTH_CSRF'] ?? ''), true, RapidPilotOtiz::currentUserCanAccess(), false);
            $html = str_replace('</body>', '<script type="module" src="/pilot/assets/calendar.js"></script></body>', $html);
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Security-Policy: default-src \'self\'; style-src \'self\'; script-src \'self\'; img-src \'self\'; font-src \'self\'; base-uri \'none\'; frame-ancestors \'none\'');
            echo $html;
        } catch (InvalidArgumentException $error) {
            $this->fail(400, $error->getMessage());
        } catch (Throwable $error) {
            error_log('calendar_read_failed ' . $error->getMessage());
            $this->fail(503, 'Календарь временно недоступен. Обновите страницу или вернитесь к объектам монтажа.');
        }
        exit;
    }

    private function assertCalendarGridExport(): void
    {
        $shlzRoot = getenv('FMONITOR_SHLZ_UI_ROOT') ?: dirname(__DIR__, 2) . '/shlz-ui';
        $css = @file_get_contents($shlzRoot . '/packages/styles/dist/shlz.css');
        if (!is_string($css) || !str_contains($css, '.shlz-calendar-grid') || !is_file($shlzRoot . '/packages/behaviors/dist/calendar-grid.js')) {
            $this->fail(503, 'Настроенный shlz-ui не экспортирует Calendar Grid. Укажите совместимый публичный checkout в FMONITOR_SHLZ_UI_ROOT.');
        }
    }

    /** @return array{DateTimeImmutable,DateTimeImmutable} */
    private function period(): array
    {
        $today = $this->now()->setTime(0, 0);
        return [$today->modify('-30 days'), $today->modify('+6 months')];
    }

    private function selectedDate(string $requested, DateTimeImmutable $first, DateTimeImmutable $last): DateTimeImmutable
    {
        $today = $this->now()->setTime(0, 0);
        if ($requested === '') return $today >= $first && $today <= $last ? $today : $first;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $requested, new DateTimeZone(self::ZONE));
        if (!$date || $date->format('Y-m-d') !== $requested || $date < $first || $date > $last) throw new InvalidArgumentException('Выбранный день не входит в доступный период календаря.');
        return $date;
    }

    private function now(): DateTimeImmutable
    {
        $fixed = getenv('FMONITOR_NOW');
        try { return (new DateTimeImmutable(is_string($fixed) && $fixed !== '' ? $fixed : 'now'))->setTimezone(new DateTimeZone(self::ZONE)); }
        catch (Throwable) { return new DateTimeImmutable('now', new DateTimeZone(self::ZONE)); }
    }

    private function user(): HttpUser
    {
        $userId=filter_var($_SERVER['FMONITOR_AUTH_USER_ID']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($userId===false)throw new RuntimeException('Authenticated user id unavailable');
        $statement = $this->db->prepare("SELECT user_id,full_name,email FROM `{$this->processPrefix}fm2_pilot_users` WHERE user_id=? AND status=1 LIMIT 1");
        $statement->bind_param('i', $userId); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
        if (!is_array($row)) throw new RuntimeException('Pilot user unavailable');
        $permissions=AccessPolicy::forUser($this->db,$this->processPrefix,(int)$row['user_id']);
        if(!AccessPolicy::grants($permissions,AccessPolicy::OBJECTS_READ))$this->fail(403,'Календарь недоступен для вашей роли.');
        return new HttpUser((int) $row['user_id'], (string) $row['full_name'], (string) $row['email'],$permissions);
    }

    /** @return list<array<string,mixed>> */
    private function read(DateTimeImmutable $first, DateTimeImmutable $last): array
    {
        $p = $this->processPrefix; $l = $this->legacyPrefix;
        RapidPilotInspectionSchedule::ensureSchema($this->db,$p);
        $start = $first->format('Y-m-d'); $end = $last->format('Y-m-d');
        $legacyBase="SELECT m.id object_id,m.ordadr_address address,m.entrance,m.regnumber,COALESCE(c.process_state,'') event_status,%s event_date,'' identity,'' task_type FROM `{$l}fm_maintable` m LEFT JOIN `{$p}fm2_installation_cases` c ON c.legacy_installation_object_id=m.id WHERE %s BETWEEN ? AND ? ORDER BY event_date,m.id LIMIT ".(self::SOURCE_ROW_LIMIT+1);
        $definitions=[
            ['planned_start','Плановое начало',sprintf($legacyBase,"NULLIF(LEFT(m.workdatestart,10),'0000-00-00')","NULLIF(LEFT(m.workdatestart,10),'0000-00-00')")],
            ['planned_end','Плановое окончание',sprintf($legacyBase,"COALESCE(NULLIF(LEFT(m.workdatefinish,10),'0000-00-00'),NULLIF(LEFT(m.plan_finish_date,10),'0000-00-00'))","COALESCE(NULLIF(LEFT(m.workdatefinish,10),'0000-00-00'),NULLIF(LEFT(m.plan_finish_date,10),'0000-00-00'))")],
        ];
        $events = [];
        foreach($definitions as[$type,$label,$sql]){
            $rows=$this->boundedProjection($sql,$start,$end);
            foreach($rows as$row){$base=['objectId'=>(int)$row['object_id'],'address'=>(string)$row['address'],'entrance'=>(string)$row['entrance'],'registration'=>(string)$row['regnumber']];$this->add($events,$base,$row['event_date'],$type,$label,(string)$row['event_status'],(string)$row['identity']);}
            if(count($events)>self::TOTAL_EVENT_LIMIT)throw new RuntimeException('Calendar event projection overflow.');
        }
        $inspectionSql="SELECT s.legacy_object_id object_id,m.ordadr_address address,m.entrance,m.regnumber,'scheduled' event_status,s.inspection_date event_date,CAST(s.id AS CHAR) identity,'' task_type FROM `{$p}fm2_pilot_inspection_schedules` s JOIN `{$l}fm_maintable` m ON m.id=s.legacy_object_id WHERE s.inspection_date BETWEEN ? AND ? ORDER BY s.inspection_date,s.id LIMIT ".(self::SOURCE_ROW_LIMIT+1);
        foreach($this->boundedProjection($inspectionSql,$start,$end) as $row){$base=['objectId'=>(int)$row['object_id'],'address'=>(string)$row['address'],'entrance'=>(string)$row['entrance'],'registration'=>(string)$row['regnumber']];$this->add($events,$base,$row['event_date'],'inspection','Инспекция','scheduled',(string)$row['identity']);}
        ksort($events); return array_values($events);
    }

    /** @return list<array<string,mixed>> */
    private function boundedProjection(string $sql,string $start,string $end):array
    {
        $statement=$this->db->prepare($sql);$statement->bind_param('ss',$start,$end);$statement->execute();$rows=$statement->get_result()->fetch_all(MYSQLI_ASSOC);
        if(count($rows)>self::SOURCE_ROW_LIMIT)throw new RuntimeException('Calendar source projection overflow.');
        return$rows;
    }

    private function add(array &$events, array $base, mixed $date, string $type, string $label, string $status, string $identity=''): void
    {
        $date = is_string($date) ? substr($date, 0, 10) : '';
        if (preg_match('/^20\d{2}-\d{2}-\d{2}$/D', $date) !== 1) return;
        $key = $type.'|'.$date.'|'.$base['objectId'].'|'.$identity;
        $events[$key] = $base + ['date'=>$date,'type'=>$type,'label'=>$label,'status'=>$status];
    }

    private function render(HttpUser $user, DateTimeImmutable $first, DateTimeImmutable $last, DateTimeImmutable $selected, array $events): string
    {
        $types=['planned_start'=>['Плановое начало','accent'],'planned_end'=>['Плановое окончание','warning'],'inspection'=>['Инспекции','success']];
        $by=[]; foreach($events as $event)$by[$event['type']][$event['date']][]=$event;
        $days=[]; for($day=$first;$day<=$last;$day=$day->modify('+1 day'))$days[]=$day;
        $today=$this->now()->format('Y-m-d');$head='';$groups=[];foreach($days as$day){$iso=$day->format('Y-m-d');$state=$iso===$today?'today':($iso<$today?'past':'future');$groups[$state]=($groups[$state]??0)+1;$week=['1'=>'Пн','2'=>'Вт','3'=>'Ср','4'=>'Чт','5'=>'Пт','6'=>'Сб','7'=>'Вс'][$day->format('N')];$head.='<th id="calendar-day-'.$iso.'" scope="col" data-shlz-calendar-grid-state="'.$state.'"><button class="fm2-calendar-day'.($iso===$selected->format('Y-m-d')?' is-selected':'').'" type="button" data-calendar-date="'.$iso.'" aria-pressed="'.($iso===$selected->format('Y-m-d')?'true':'false').'"><span class="shlz-calendar-grid__date-primary">'.$day->format('j').'</span><span class="shlz-calendar-grid__date-secondary">'.$week.'</span></button></th>';}$groupHead='';foreach(['past'=>'Прошлое','today'=>'Сегодня','future'=>'Будущее']as$state=>$label){if(isset($groups[$state]))$groupHead.='<th scope="colgroup" colspan="'.$groups[$state].'" data-shlz-calendar-grid-state="'.$state.'"><span class="shlz-calendar-grid__group-label">'.$label.'</span></th>';}
        $body=''; foreach($types as$type=>[$label,$tone]){$cells='';foreach($days as$day){$iso=$day->format('Y-m-d');$state=$iso===$today?'today':($iso<$today?'past':'future');$items=$by[$type][$iso]??[];$list='';foreach(array_slice($items,0,3)as$event)$list.=$this->eventMarkup($event,$tone);if(count($items)>3){$moreId='calendar-more-'.$type.'-'.$iso;$hidden='';foreach(array_slice($items,3)as$event)$hidden.=$this->eventMarkup($event,$tone);$list.='<li><button class="shlz-button shlz-button--sm shlz-calendar-grid__disclosure" type="button" data-shlz-calendar-grid-disclosure="cell" aria-controls="'.$moreId.'" aria-expanded="false">Ещё '.(count($items)-3).'</button><ul class="shlz-calendar-grid__items" id="'.$moreId.'" hidden>'.$hidden.'</ul></li>';}$cells.='<td headers="calendar-row-'.$type.' calendar-day-'.$iso.'" data-shlz-calendar-grid-state="'.$state.'">'.($list===''?'<span class="fm2-calendar-cell-empty" aria-label="Нет событий">—</span>':'<ul class="shlz-calendar-grid__items">'.$list.'</ul>').'</td>';}$body.='<tr><th id="calendar-row-'.$type.'" scope="row"><span>'.$this->e($label).'</span><span class="shlz-calendar-grid__row-description">'.count($by[$type]??[]).' дн.</span></th>'.$cells.'</tr>';}
        $agendaEvents=array_values(array_filter($events,fn(array$event):bool=>$event['date']===$selected->format('Y-m-d')));$agenda='';foreach($agendaEvents as$event){$tone=$types[$event['type']][1];$agenda.='<li class="fm2-agenda-item"><span class="fm2-agenda-tone" data-tone="'.$tone.'" aria-hidden="true"></span><div><strong>'.$this->e($event['label']).'</strong><a class="shlz-link" href="/pilot/objects/'.$event['objectId'].'">'.$this->e($event['address']).', подъезд '.$this->e($event['entrance']).'</a><span>Рег. № '.$this->e($event['registration']).' · '.$this->e($this->status($event['status'])).'</span></div></li>';}$agenda=$agenda===''?'<p class="fm2-calendar-empty">На этот день событий нет.</p>':'<ul class="fm2-agenda-list">'.$agenda.'</ul>';
        $selectedLabel=$selected->format('d.m.Y');$rangeLabel=$first->format('d.m.Y').' — '.$last->format('d.m.Y');
        $content='<section class="fm2-calendar-page" data-calendar-page><header class="fm2-calendar-header"><div><h1>Календарь работ</h1><p>Плановые даты начала и окончания работ по объектам монтажа</p></div><span class="fm2-result-count">'.count($events).' событий</span></header><div class="fm2-calendar-toolbar" aria-label="Навигация по календарю"><div class="fm2-calendar-period"><h2>Непрерывная шкала дат</h2><span>'.$this->e($rangeLabel).'</span></div><a class="shlz-button" href="/pilot/calendar">Сегодня</a></div><div class="fm2-calendar-layout"><div class="shlz-calendar-grid fm2-calendar-grid" data-shlz-calendar-grid><table><caption class="fm2-visually-hidden">Рабочие события с '.$this->e($first->format('d.m.Y')).' по '.$this->e($last->format('d.m.Y')).'</caption><thead><tr><th scope="col" rowspan="2">Тип события</th>'.$groupHead.'</tr><tr data-shlz-calendar-grid-header-row="dates">'.$head.'</tr></thead><tbody>'.$body.'</tbody></table></div><aside class="fm2-calendar-agenda" aria-labelledby="calendar-agenda-title" aria-live="polite"><header><div><h2 id="calendar-agenda-title">'.$selectedLabel.'</h2><p>'.count($agendaEvents).' событий</p></div></header><div data-calendar-agenda>'.$agenda.'</div></aside></div><noscript><p class="fm2-calendar-note">Выберите день в адресной строке параметром <code>date=ГГГГ-ММ-ДД</code>.</p></noscript></section>';
        return PilotView::document($user,'Календарь работ','Календарь',PilotView::breadcrumb([['Моя работа','/pilot/']], 'Календарь'),$content);
    }

    private function eventMarkup(array $event,string $tone):string{return '<li class="shlz-calendar-grid__item" data-tone="'.$tone.'"><a class="fm2-calendar-event-link" href="/pilot/objects/'.$event['objectId'].'"><strong>'.$this->e($event['registration']).'</strong><span>'.$this->e($event['status']===''?$event['label']:$this->status($event['status'])).'</span></a></li>';}
    private function status(string $status):string{return ['scheduled'=>'Запланировано','needs_assignment_order'=>'Требуется распоряжение','order_prepared'=>'Распоряжение подготовлено','registered'=>'Зарегистрировано','working'=>'Работы открыты','open'=>'Открыта','completed'=>'Выполнена','cancelled'=>'Отменена','prepared'=>'Подготовлено','зафиксирован'=>'Зафиксирован'][$status]??($status!==''?str_replace('_',' ',$status):'Статус не указан');}
    private function e(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');}
    private function fail(int $status,string $message):never{http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');echo $message."\n";exit;}
}
