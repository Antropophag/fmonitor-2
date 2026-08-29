<?php

declare(strict_types=1);

use FMonitor2\PilotHttp\HttpUser;
use FMonitor2\PilotHttp\PilotView;

final class RapidPilotCalendar
{
    private const ZONE = 'Europe/Moscow';
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
        $link = '<a class="fm2-nav-item" href="/pilot/calendar"' . $current . '><svg class="fm2-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3v3m14-3v3M3.5 9h17M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm2 7h3v3H7zm5 0h3v3h-3z"/></svg><span class="fm2-nav-text">Календарь</span></a>';
        return str_replace('<a class="fm2-nav-item" href="/pilot/objects"', $link . '<a class="fm2-nav-item" href="/pilot/objects"', $html);
    }

    public function handle(): never
    {
        header('Cache-Control: no-store');
        try {
            $this->assertCalendarGridExport();
            [$month, $first, $last] = $this->period((string) ($_GET['month'] ?? ''));
            $selected = $this->selectedDate((string) ($_GET['date'] ?? ''), $first, $last);
            $user = $this->user();
            $events = $this->read($first, $last);
            $html = $this->render($user, $month, $first, $last, $selected, $events);
            $html = self::decorateNavigation($html, true);
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

    /** @return array{DateTimeImmutable,DateTimeImmutable,DateTimeImmutable} */
    private function period(string $requested): array
    {
        $zone = new DateTimeZone(self::ZONE);
        $now = $this->now();
        $value = $requested === '' ? $now->format('Y-m') : $requested;
        if (preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/D', $value) !== 1) throw new InvalidArgumentException('Укажите месяц в формате ГГГГ-ММ.');
        $month = DateTimeImmutable::createFromFormat('!Y-m', $value, $zone);
        if (!$month) throw new InvalidArgumentException('Не удалось определить месяц.');
        return [$month, $month, $month->modify('last day of this month')];
    }

    private function selectedDate(string $requested, DateTimeImmutable $first, DateTimeImmutable $last): DateTimeImmutable
    {
        $today = $this->now()->setTime(0, 0);
        if ($requested === '') return $today >= $first && $today <= $last ? $today : $first;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $requested, new DateTimeZone(self::ZONE));
        if (!$date || $date->format('Y-m-d') !== $requested || $date < $first || $date > $last) throw new InvalidArgumentException('Выбранный день не входит в открытый месяц.');
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
        $email = (string) ($_SERVER['REMOTE_USER'] ?? '');
        $statement = $this->db->prepare("SELECT user_id,full_name,email FROM `{$this->processPrefix}fm2_pilot_users` WHERE BINARY email=BINARY ? AND status=1 LIMIT 2");
        $statement->bind_param('s', $email); $statement->execute(); $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($rows) !== 1) throw new RuntimeException('Pilot user unavailable');
        return new HttpUser((int) $rows[0]['user_id'], (string) $rows[0]['full_name'], (string) $rows[0]['email']);
    }

    /** @return list<array<string,mixed>> */
    private function read(DateTimeImmutable $first, DateTimeImmutable $last): array
    {
        $p = $this->processPrefix; $l = $this->legacyPrefix;
        $sql = "SELECT m.id object_id,m.ordadr_address address,m.entrance,m.regnumber,
                NULLIF(LEFT(m.workdatestart,10),'0000-00-00') planned_start,
                COALESCE(NULLIF(LEFT(m.workdatefinish,10),'0000-00-00'),NULLIF(LEFT(m.plan_finish_date,10),'0000-00-00')) planned_end,
                NULLIF(LEFT(m.workdateendadjusted,10),'0000-00-00') adjusted_end,
                NULLIF(LEFT(m.ptoactdate,10),'0000-00-00') pto_date,
                c.actual_start_date,c.process_state,
                o.id order_id,o.version_no,o.status order_status,o.order_date,o.registered_at,o.registration_number,
                t.id task_id,t.task_type,t.due_date task_due,t.status task_status
            FROM `{$l}fm_maintable` m
            LEFT JOIN `{$p}fm2_installation_cases` c ON c.legacy_installation_object_id=m.id
            LEFT JOIN `{$p}fm2_assignment_orders` o ON o.installation_case_id=c.id
            LEFT JOIN `{$p}fm2_process_tasks` t ON t.installation_case_id=c.id
            WHERE (NULLIF(LEFT(m.workdatestart,10),'0000-00-00') BETWEEN ? AND ?)
               OR (COALESCE(NULLIF(LEFT(m.workdatefinish,10),'0000-00-00'),NULLIF(LEFT(m.plan_finish_date,10),'0000-00-00')) BETWEEN ? AND ?)
               OR (NULLIF(LEFT(m.workdateendadjusted,10),'0000-00-00') BETWEEN ? AND ?)
               OR (NULLIF(LEFT(m.ptoactdate,10),'0000-00-00') BETWEEN ? AND ?)
               OR (c.actual_start_date BETWEEN ? AND ?)
               OR (o.order_date BETWEEN ? AND ?)
               OR (LEFT(o.registered_at,10) BETWEEN ? AND ?)
               OR (t.due_date BETWEEN ? AND ?)
            ORDER BY m.id,o.version_no,t.id LIMIT 5000";
        $start = $first->format('Y-m-d'); $end = $last->format('Y-m-d');
        $statement = $this->db->prepare($sql);
        $statement->bind_param(str_repeat('s', 16), $start,$end,$start,$end,$start,$end,$start,$end,$start,$end,$start,$end,$start,$end,$start,$end);
        $statement->execute(); $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $events = [];
        foreach ($rows as $row) {
            $base = ['objectId'=>(int)$row['object_id'],'address'=>(string)$row['address'],'entrance'=>(string)$row['entrance'],'registration'=>(string)$row['regnumber']];
            $this->add($events, $base, $row['planned_start'], 'planned_start', 'Плановое начало', (string)$row['process_state']);
            $this->add($events, $base, $row['planned_end'], 'planned_end', 'Плановое окончание', (string)$row['process_state']);
            $this->add($events, $base, $row['adjusted_end'], 'adjusted_end', 'Скорректированное окончание', (string)$row['process_state']);
            $this->add($events, $base, $row['actual_start_date'], 'actual_start', 'Фактическое начало', (string)$row['process_state']);
            $this->add($events, $base, $row['pto_date'], 'pto_act', 'Акт ПТО', 'зафиксирован');
            if ($row['order_id'] !== null) {
                $orderKey = 'order-' . $row['order_id'];
                $this->add($events, $base, $row['order_date'], 'order_issued', 'Распоряжение выпущено', (string)$row['order_status'], $orderKey);
                $this->add($events, $base, $row['registered_at'] === null ? null : substr((string)$row['registered_at'],0,10), 'order_registered', 'Распоряжение зарегистрировано', (string)($row['registration_number'] ?: $row['order_status']), $orderKey . '-registered');
            }
            if ($row['task_id'] !== null) $this->add($events, $base, $row['task_due'], 'process_task', $this->taskLabel((string)$row['task_type']), (string)$row['task_status'], 'task-'.$row['task_id']);
        }
        ksort($events); return array_values($events);
    }

    private function add(array &$events, array $base, mixed $date, string $type, string $label, string $status, string $identity=''): void
    {
        $date = is_string($date) ? substr($date, 0, 10) : '';
        if (preg_match('/^20\d{2}-\d{2}-\d{2}$/D', $date) !== 1) return;
        $key = $type.'|'.$date.'|'.$base['objectId'].'|'.$identity;
        $events[$key] = $base + ['date'=>$date,'type'=>$type,'label'=>$label,'status'=>$status];
    }

    private function taskLabel(string $type): string
    {
        return ['prepare_assignment_order'=>'Подготовить распоряжение','confirm_assignment_order_registration'=>'Зарегистрировать распоряжение','perform_inspection'=>'Провести инспекцию'][$type] ?? 'Процессная задача';
    }

    private function render(HttpUser $user, DateTimeImmutable $month, DateTimeImmutable $first, DateTimeImmutable $last, DateTimeImmutable $selected, array $events): string
    {
        $months=['01'=>'январь','02'=>'февраль','03'=>'март','04'=>'апрель','05'=>'май','06'=>'июнь','07'=>'июль','08'=>'август','09'=>'сентябрь','10'=>'октябрь','11'=>'ноябрь','12'=>'декабрь'];
        $monthTitle=mb_strtoupper(mb_substr($months[$month->format('m')],0,1)).mb_substr($months[$month->format('m')],1).' '.$month->format('Y');
        $types=[
            'planned_start'=>['Плановое начало','accent'], 'planned_end'=>['Плановое окончание','accent'],
            'adjusted_end'=>['Скорректированное окончание','warning'], 'actual_start'=>['Фактическое начало','success'],
            'pto_act'=>['Акт ПТО','danger'], 'order_issued'=>['Распоряжение выпущено','accent'],
            'order_registered'=>['Регистрация в 1С ДО','success'], 'process_task'=>['Процессные задачи','warning'],
        ];
        $by=[]; foreach($events as $event)$by[$event['type']][$event['date']][]=$event;
        $days=[]; for($day=$first;$day<=$last;$day=$day->modify('+1 day'))$days[]=$day;
        $today=$this->now()->format('Y-m-d'); $head=''; foreach($days as $day){$iso=$day->format('Y-m-d');$state=$iso===$today?'today':($iso<$today?'past':'future');$week=['1'=>'Пн','2'=>'Вт','3'=>'Ср','4'=>'Чт','5'=>'Пт','6'=>'Сб','7'=>'Вс'][$day->format('N')];$head.='<th id="calendar-day-'.$iso.'" scope="col" data-shlz-calendar-grid-state="'.$state.'"><button class="fm2-calendar-day'.($iso===$selected->format('Y-m-d')?' is-selected':'').'" type="button" data-calendar-date="'.$iso.'" aria-pressed="'.($iso===$selected->format('Y-m-d')?'true':'false').'"><span class="shlz-calendar-grid__date-primary">'.$day->format('j').'</span><span class="shlz-calendar-grid__date-secondary">'.$week.'</span></button></th>';}
        $body=''; foreach($types as$type=>[$label,$tone]){$cells='';foreach($days as$day){$iso=$day->format('Y-m-d');$items=$by[$type][$iso]??[];$list='';foreach(array_slice($items,0,3)as$event)$list.=$this->eventMarkup($event,$tone);if(count($items)>3){$moreId='calendar-more-'.$type.'-'.$iso;$hidden='';foreach(array_slice($items,3)as$event)$hidden.=$this->eventMarkup($event,$tone);$list.='<li><button class="shlz-link shlz-calendar-grid__disclosure" type="button" data-shlz-calendar-grid-disclosure="cell" aria-controls="'.$moreId.'" aria-expanded="false">Ещё <span class="shlz-calendar-grid__count">'.(count($items)-3).'</span></button><ul class="shlz-calendar-grid__items" id="'.$moreId.'" hidden>'.$hidden.'</ul></li>';}$cells.='<td headers="calendar-row-'.$type.' calendar-day-'.$iso.'">'.($list===''?'<span class="fm2-calendar-cell-empty" aria-label="Нет событий">—</span>':'<ul class="shlz-calendar-grid__items">'.$list.'</ul>').'</td>';}$body.='<tr><th id="calendar-row-'.$type.'" scope="row"><span>'.$this->e($label).'</span><span class="shlz-calendar-grid__row-description">'.count($by[$type]??[]).' дн.</span></th>'.$cells.'</tr>';}
        $agendaEvents=array_values(array_filter($events,fn(array$event):bool=>$event['date']===$selected->format('Y-m-d')));$agenda='';foreach($agendaEvents as$event){$tone=$types[$event['type']][1];$agenda.='<li class="fm2-agenda-item"><span class="fm2-agenda-tone" data-tone="'.$tone.'" aria-hidden="true"></span><div><strong>'.$this->e($event['label']).'</strong><a class="shlz-link" href="/pilot/objects/'.$event['objectId'].'">'.$this->e($event['address']).', подъезд '.$this->e($event['entrance']).'</a><span>Рег. № '.$this->e($event['registration']).' · '.$this->e($this->status($event['status'])).'</span></div></li>';}$agenda=$agenda===''?'<p class="fm2-calendar-empty">На этот день событий нет.</p>':'<ul class="fm2-agenda-list">'.$agenda.'</ul>';
        $previous=$month->modify('-1 month')->format('Y-m');$next=$month->modify('+1 month')->format('Y-m');$todayMonth=$this->now()->format('Y-m');$selectedLabel=$selected->format('d.m.Y');
        $content='<section class="fm2-calendar-page" data-calendar-page><header class="fm2-calendar-header"><div><h1>Календарь работ</h1><p>Сроки, документы и задачи по объектам монтажа</p></div><span class="fm2-result-count">'.count($events).' событий</span></header><div class="fm2-calendar-toolbar" aria-label="Навигация по календарю"><div class="fm2-calendar-period"><a class="shlz-button" href="/pilot/calendar?month='.$previous.'" aria-label="Предыдущий месяц">←</a><h2>'.$this->e($monthTitle).'</h2><a class="shlz-button" href="/pilot/calendar?month='.$next.'" aria-label="Следующий месяц">→</a></div><a class="shlz-button" href="/pilot/calendar?month='.$todayMonth.'">Сегодня</a></div><div class="fm2-calendar-layout"><div class="shlz-calendar-grid fm2-calendar-grid" data-shlz-calendar-grid><table><caption class="fm2-visually-hidden">Рабочие события за '.$this->e($monthTitle).'</caption><thead><tr><th scope="col">Тип события</th>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table></div><aside class="fm2-calendar-agenda" aria-labelledby="calendar-agenda-title" aria-live="polite"><header><div><h2 id="calendar-agenda-title">'.$selectedLabel.'</h2><p>'.count($agendaEvents).' событий</p></div></header><div data-calendar-agenda>'.$agenda.'</div></aside></div><noscript><p class="fm2-calendar-note">Выберите день в адресной строке параметром <code>date=ГГГГ-ММ-ДД</code>. Навигация по месяцам работает без JavaScript.</p></noscript></section>';
        return PilotView::document($user,'Календарь работ','Календарь',PilotView::breadcrumb([['Моя работа','/pilot/']], 'Календарь'),$content);
    }

    private function eventMarkup(array $event,string $tone):string{return '<li class="shlz-calendar-grid__item" data-tone="'.$tone.'"><a class="fm2-calendar-event-link" href="/pilot/objects/'.$event['objectId'].'"><strong>'.$this->e($event['registration']).'</strong><span>'.$this->e($event['status']===''?$event['label']:$this->status($event['status'])).'</span></a></li>';}
    private function status(string $status):string{return ['needs_assignment_order'=>'Требуется распоряжение','order_prepared'=>'Распоряжение подготовлено','registered'=>'Зарегистрировано','working'=>'Работы открыты','open'=>'Открыта','completed'=>'Выполнена','cancelled'=>'Отменена','prepared'=>'Подготовлено','зафиксирован'=>'Зафиксирован'][$status]??($status!==''?str_replace('_',' ',$status):'Статус не указан');}
    private function e(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');}
    private function fail(int $status,string $message):never{http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');echo $message."\n";exit;}
}
