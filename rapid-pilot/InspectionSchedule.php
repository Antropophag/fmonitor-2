<?php

declare(strict_types=1);

final class RapidPilotInspectionSchedule
{
    private const ZONE = 'Europe/Moscow';

    public static function matches(string $path): bool
    {
        return preg_match('#^/pilot/objects/([1-9][0-9]*)/inspection-schedule$#D', $path) === 1;
    }

    public static function handle(string $path): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') self::fail(405, 'Метод не поддерживается.');
        if (preg_match('#^/pilot/objects/([1-9][0-9]*)/inspection-schedule$#D', $path, $match) !== 1) self::fail(404, 'Объект не найден.');
        $prefix = self::prefix();
        $db = self::db();
        try {
            self::ensureSchema($db, $prefix);
            $csrf = (string) ($_POST['csrfToken'] ?? '');
            $expected = (string) ($_SERVER['FMONITOR_AUTH_CSRF'] ?? '');
            if ($csrf === '' || $expected === '' || !hash_equals($expected, $csrf)) self::fail(403, 'Недопустимый запрос.');
            $userId = filter_var($_SERVER['FMONITOR_AUTH_USER_ID'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($userId === false || !self::canSchedule($db, $prefix, (int) $userId)) self::fail(403, 'Недостаточно прав для планирования инспекции.');
            $date = (string) ($_POST['inspectionDate'] ?? '');
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone(self::ZONE));
            if (!$parsed || $parsed->format('Y-m-d') !== $date) self::fail(422, 'Укажите корректную дату инспекции.');
            if ($date < self::now()->format('Y-m-d')) self::fail(422, 'Инспекцию нельзя запланировать на прошедшую дату.');
            $objectId = (int) $match[1];
            $query = $db->prepare("SELECT c.id,o.control_engineer_user_id FROM `{$prefix}fm2_installation_cases` c JOIN `{$prefix}fm2_assignment_orders` o ON o.installation_case_id=c.id AND o.version_no=(SELECT MAX(x.version_no) FROM `{$prefix}fm2_assignment_orders` x WHERE x.installation_case_id=c.id) WHERE c.legacy_installation_object_id=? AND c.process_state IN('working','needs_assignment_change') AND o.status='registered' LIMIT 1");
            $query->bind_param('i', $objectId); $query->execute(); $case = $query->get_result()->fetch_assoc();
            if (!is_array($case) || (int) $case['control_engineer_user_id'] < 1) self::fail(409, 'Сначала откройте работы и назначьте инженера стройконтроля.');
            $caseId = (int) $case['id']; $engineerId = (int) $case['control_engineer_user_id']; $now = self::now()->format(DATE_ATOM);
            $db->begin_transaction();
            $insert = $db->prepare("INSERT IGNORE INTO `{$prefix}fm2_pilot_inspection_schedules`(installation_case_id,legacy_object_id,control_engineer_user_id,inspection_date,scheduled_by_user_id,scheduled_at) VALUES(?,?,?,?,?,?)");
            $insert->bind_param('iiisis', $caseId, $objectId, $engineerId, $date, $userId, $now); $insert->execute();
            if ($insert->affected_rows === 1) {
                $scheduleId = $insert->insert_id;
                $payload = json_encode(['scheduleId'=>$scheduleId,'inspectionDate'=>$date,'controlEngineerUserId'=>$engineerId], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $event = $db->prepare("INSERT INTO `{$prefix}fm2_pilot_inspection_schedule_events`(schedule_id,installation_case_id,event_type,payload_json,actor_user_id,occurred_at) VALUES(?,?,'inspection_scheduled',?,?,?)");
                $event->bind_param('iisis', $scheduleId, $caseId, $payload, $userId, $now); $event->execute();
            }
            $db->commit();
            header('Location: /pilot/objects?inspectionScheduled=' . rawurlencode($date), true, 303);
            header('Cache-Control: no-store'); exit;
        } catch (Throwable $error) {
            try { $db->rollback(); } catch (Throwable) {}
            error_log('inspection_schedule_failed ' . get_class($error) . ' ' . $error->getMessage());
            self::fail(503, 'Не удалось запланировать инспекцию. Повторите попытку.');
        } finally { $db->close(); }
    }

    public static function ensureSchema(mysqli $db, string $prefix): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_inspection_schedules`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,installation_case_id BIGINT UNSIGNED NOT NULL,legacy_object_id BIGINT UNSIGNED NOT NULL,control_engineer_user_id BIGINT UNSIGNED NOT NULL,inspection_date DATE NOT NULL,scheduled_by_user_id BIGINT UNSIGNED NOT NULL,scheduled_at VARCHAR(40) NOT NULL,UNIQUE KEY unique_planned_inspection(installation_case_id,control_engineer_user_id,inspection_date),KEY calendar_date(inspection_date,id),KEY engineer_day(control_engineer_user_id,inspection_date,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_inspection_schedule_events`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,schedule_id BIGINT UNSIGNED NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,event_type VARCHAR(80) NOT NULL,payload_json JSON NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,KEY(schedule_id,id),KEY(installation_case_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_inspection_schedulers`(user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,origin VARCHAR(80) NOT NULL,granted_at VARCHAR(40) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $grantedAt=self::now()->format(DATE_ATOM);
        $grant=$db->prepare("INSERT INTO `{$prefix}fm2_pilot_inspection_schedulers`(user_id,origin,granted_at) SELECT user_id,'corporate_pilot_owner',? FROM `{$prefix}fm2_pilot_users` WHERE status=1 AND LOWER(TRIM(email))='ts.grishin@shlz.ru' ON DUPLICATE KEY UPDATE origin=VALUES(origin)");
        $grant->bind_param('s',$grantedAt);$grant->execute();
    }

    public static function canSchedule(mysqli $db, string $prefix, int $userId): bool
    {
        $query=$db->prepare("SELECT 1 FROM `{$prefix}fm2_process_user_capabilities` WHERE user_id=? AND capability='assignment_order.prepare' UNION SELECT 1 FROM `{$prefix}fm2_pilot_inspection_schedulers` WHERE user_id=? LIMIT 1");
        $query->bind_param('ii',$userId,$userId);$query->execute();return $query->get_result()->fetch_row()!==null;
    }

    public static function queueButton(int $objectId, string $label, string $csrf): string
    {
        $e=static fn(string $v):string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');
        return '<button class="shlz-button shlz-button--sm fm2-schedule-button" type="button" data-inspection-schedule data-object-id="'.$objectId.'" data-object-label="'.$e($label).'" aria-label="Запланировать инспекцию" title="Запланировать инспекцию"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="6" width="16" height="14" rx="2"/><path d="M8 3v6M16 3v6M4 10h16"/></svg></button>';
    }

    public static function dialog(string $csrf): string
    {
        $today=self::now()->format('Y-m-d');$token=htmlspecialchars($csrf,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        return '<dialog class="fm2-inspection-dialog" data-inspection-dialog><form method="post" class="fm2-inspection-dialog__surface" data-inspection-form><header><div><h2>Запланировать инспекцию</h2><p data-inspection-object></p></div><button type="button" aria-label="Закрыть" data-inspection-close>×</button></header><div class="fm2-inspection-dialog__body"><input type="hidden" name="csrfToken" value="'.$token.'"><label class="shlz-field"><span class="shlz-field__label">Дата инспекции</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="inspectionDate" min="'.$today.'" value="'.$today.'" required></span><span class="shlz-field__secondary">Инспекция появится в календаре и в очереди назначенного инженера.</span></label></div><footer><button class="shlz-button" type="button" data-inspection-close>Отмена</button><button class="shlz-button shlz-button--primary" type="submit">Запланировать</button></footer></form></dialog>';
    }

    public static function enhanceControl(string $html): string
    {
        $prefix=self::prefix();$db=self::db();try{self::ensureSchema($db,$prefix);$today=self::now()->format('Y-m-d');$s=$db->prepare("SELECT legacy_object_id FROM `{$prefix}fm2_pilot_inspection_schedules` WHERE inspection_date=?");$s->bind_param('s',$today);$s->execute();$planned=array_fill_keys(array_map('intval',array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'legacy_object_id')),true);}finally{$db->close();}
        if($planned===[])return$html;
        if(preg_match('#<tbody>(.*?)</tbody>#s',$html,$body)!==1)return$html;
        preg_match_all('#<tr class="fm2-control-row"[^>]*data-object-id="([1-9][0-9]*)".*?</tr>#s',$body[1],$matches,PREG_SET_ORDER);$first='';$rest=$body[1];
        foreach($matches as$m){$id=(int)$m[1];if(!isset($planned[$id]))continue;$row=str_replace('class="fm2-control-row"','class="fm2-control-row fm2-control-row--planned"',$m[0]);$badge='<span class="fm2-planned-inspection"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="6" width="16" height="14" rx="2"/><path d="M8 3v6M16 3v6M4 10h16"/></svg>Запланировано на сегодня</span>';$row=str_replace('<span class="fm2-activity-line">',$badge.'<span class="fm2-activity-line">',$row);$first.=$row;$rest=str_replace($m[0],'',$rest);}
        return str_replace($body[0],'<tbody>'.$first.$rest.'</tbody>',$html);
    }

    private static function prefix(): string {$p=(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');if(preg_match('/^[A-Za-z0-9_]+$/D',$p)!==1)throw new RuntimeException('Invalid prefix');return$p;}
    private static function db(): mysqli {$db=new mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_DB_PASSWORD'),(string)getenv('FMONITOR_DB_NAME'),(int)(getenv('FMONITOR_DB_PORT')?:3306));$db->set_charset('utf8mb4');return$db;}
    private static function now(): DateTimeImmutable {$fixed=getenv('FMONITOR_NOW');try{return(new DateTimeImmutable(is_string($fixed)&&$fixed!==''?$fixed:'now'))->setTimezone(new DateTimeZone(self::ZONE));}catch(Throwable){return new DateTimeImmutable('now',new DateTimeZone(self::ZONE));}}
    private static function fail(int $status,string $message):never{http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');echo$message."\n";exit;}
}
