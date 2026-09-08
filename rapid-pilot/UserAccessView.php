<?php

declare(strict_types=1);

final class RapidPilotUserAccessView
{
    /** @var array<string,string> */
    private const LABELS = [
        'objects.read' => 'Просмотр объектов монтажа',
        'installers.read' => 'Просмотр справочника монтажников',
        'construction_control.read' => 'Просмотр строительного контроля',
        'checklist.read' => 'Просмотр чек-листов',
        'checklist.edit' => 'Ведение чек-листов',
        'inspection.schedule' => 'Планирование инспекций',
        'otiz.manage' => 'Управление расчётами ОТиЗ',
        'management.read' => 'Просмотр управленческого контроля',
        'assignment_order_artifact.read' => 'Просмотр документов распоряжения',
        'assignment_order.prepare' => 'Формирование распоряжения',
        'assignment_order.confirm_registration' => 'Регистрация распоряжения в 1С ДО',
        'installation.open' => 'Открытие работ',
        'construction_control_engineer' => 'Работа инженером строительного контроля',
        'access.administer' => 'Управление пользователями и ролями',
        'access.superadminister' => 'Управление привилегированными ролями',
        'access.audit.read' => 'Просмотр журнала доступа',
    ];

    public static function enhance(string $html, string $path): string
    {
        if ($path === '/pilot/admin/roles') return self::localizeRoles($html);
        if ($path !== '/pilot/admin/users') return $html;

        $access = self::readAccess();
        $states = self::readUserStates();
        $actorId = (int) ($_SERVER['FMONITOR_AUTH_USER_ID'] ?? 0);
        $csrf = (string) ($_SERVER['FMONITOR_AUTH_CSRF'] ?? '');
        $html = str_replace(
            '<th class="shlz-table__cell" scope="col">Роли</th>',
            '<th class="shlz-table__cell" scope="col">Роли</th><th class="shlz-table__cell" scope="col">Итоговый доступ</th><th class="shlz-table__cell" scope="col">Действия</th>',
            $html
        );
        $html = str_replace(
            'Локальные учётные записи FMonitor и назначенные роли',
            'Создавайте приглашения, назначайте роли и проверяйте итоговый доступ пользователя',
            $html
        );
        $html = preg_replace_callback(
            '#<section class="fm2-role-model-note"><strong>Преднастроить пользователя</strong><form method="post" action="/pilot/admin/users/invite" class="fm2-auth-form"><input type="hidden" name="csrfToken" value="([^"]*)"><input class="shlz-input" type="email" name="email" placeholder="name@shlz\.ru" required><input class="shlz-input" name="fullName" placeholder="ФИО" required><button class="shlz-button shlz-button--primary" type="submit">Создать и выдать приглашение</button></form></section>#D',
            static fn (array $match): string => '<section class="fm2-role-model-note fm2-invite-panel"><strong>Пригласить пользователя</strong><p>FMonitor создаст одноразовую ссылку. Скопируйте и передайте её пользователю самостоятельно — отправка писем в rapid pilot не подключена.</p><form method="post" action="/pilot/admin/users/invite" class="fm2-auth-form"><input type="hidden" name="csrfToken" value="' . self::e($match[1]) . '"><label class="shlz-field"><span class="shlz-field__label">Корпоративный email</span><span class="shlz-field__control"><input class="shlz-input" type="email" name="email" placeholder="name@shlz.ru" autocomplete="off" pattern="[^@]+@shlz\\.ru" required></span></label><label class="shlz-field"><span class="shlz-field__label">ФИО</span><span class="shlz-field__control"><input class="shlz-input" name="fullName" autocomplete="off" maxlength="300" required></span></label><button class="shlz-button shlz-button--primary" type="submit">Создать приглашение</button></form></section>',
            $html
        ) ?? $html;
        return preg_replace_callback(
            '#(<tr class="shlz-table__row fm2-user-row[^>]*data-user-id="([1-9][0-9]*)".*?)(</tr>)#s',
            static function (array $match) use ($access, $states, $actorId, $csrf): string {
                $userId = (int) $match[2];
                $state=$states[$userId]??'blocked';$row=preg_replace('/fm2-user-row--(?:active|blocked)/','fm2-user-row--'.$state,$match[1],1)??$match[1];$labels=['active'=>'Активен','invited'=>'Приглашён','blocked'=>'Заблокирован'];$control='<span class="shlz-table__empty">Нет действий</span>';if($state!=='invited'&&$userId!==$actorId){$action=$state==='active'?'block':'unblock';$button=$state==='active'?'Заблокировать':'Восстановить';$control='<form method="post" action="/pilot/admin/users/'.$userId.'/status" class="fm2-user-status-form"><input type="hidden" name="csrfToken" value="'.self::e($csrf).'"><input type="hidden" name="action" value="'.$action.'"><button class="shlz-button shlz-button--secondary" type="submit">'.$button.'</button></form>';}$status='<span class="fm2-employment fm2-employment--'.$state.'">'.$labels[$state].'</span>';$row=preg_replace('#<span class="fm2-employment fm2-employment--(?:active|dismissed)">(?:Активен|Заблокирован)</span>#D',$status,$row,1)??$row;return $row.self::accessCell($access[$userId]??[]).'<td class="shlz-table__cell" data-label="Действия">'.$control.'</td>'.$match[3];
            },
            $html
        ) ?? $html;
    }

    public static function handleStatus(int $userId): never
    {
        $actorId=(int)($_SERVER['FMONITOR_AUTH_USER_ID']??0);$csrf=(string)($_POST['csrfToken']??'');$expected=(string)($_SERVER['FMONITOR_AUTH_CSRF']??'');$action=(string)($_POST['action']??'');if($actorId<1||$userId<1||$userId===$actorId||$csrf===''||$expected===''||!hash_equals($expected,$csrf)||!in_array($action,['block','unblock'],true))self::statusError(403);require_once dirname(__DIR__).'/app/PilotHttp/AccessPolicy.php';require_once dirname(__DIR__).'/app/InstallationProcess/MariaDbSchemaInspector.php';require_once dirname(__DIR__).'/app/InstallationProcess/IdentityAccessDefinitionSchemaMigration.php';require_once dirname(__DIR__).'/app/InstallationProcess/IdentityAccessSchemaMigration.php';require_once dirname(__DIR__).'/app/PilotHttp/MariaDbUserStatusApplication.php';$db=self::db();$status=\FMonitor2\PilotHttp\MariaDbUserStatusApplication::change($db,self::prefix(),$actorId,$userId,$action);if($status!==303)self::statusError($status,$db);$db->close();header('Location: /pilot/admin/users',true,303);header('Cache-Control: no-store');exit;
    }
    private static function statusError(int $status,?mysqli$db=null):never{if($db)try{$db->rollback();}catch(Throwable){}http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');echo$status===403?"Недопустимый запрос.\n":"Не удалось изменить статус пользователя.\n";exit;}

    public static function invitationResponse(\FMonitor2\PilotHttp\PilotHttpResponse $response): \FMonitor2\PilotHttp\PilotHttpResponse
    {
        return $response;
    }

    /** @param array{kind:string,url?:string} $flash */
    private static function invitationFeedback(array $flash): string
    {
        if (($flash['kind'] ?? '') === 'error') return '<section class="fm2-invite-feedback fm2-invite-feedback--error" role="alert"><strong>Приглашение не создано</strong><p>Укажите новый корпоративный адрес в домене @shlz.ru и непустое ФИО. Если пользователь уже существует, управляйте его ролями в списке ниже.</p></section>';
        $url = (string) ($flash['url'] ?? '');
        return '<section class="fm2-invite-feedback" aria-labelledby="invite-created-title"><strong id="invite-created-title">Приглашение создано</strong><p>Скопируйте полную ссылку и передайте её пользователю согласованным способом. FMonitor ничего не отправляет автоматически. Ссылка одноразовая и действует 24 часа.</p><div class="fm2-invite-link"><label class="shlz-field"><span class="shlz-field__label">Ссылка активации</span><span class="shlz-field__control"><input class="shlz-input" type="text" readonly value="' . self::e($url) . '" data-invite-url></span></label><button class="shlz-button shlz-button--primary" type="button" data-copy-invite>Скопировать ссылку</button></div><p class="fm2-invite-copy-status" role="status" aria-live="polite" data-copy-status></p></section>';
    }

    private static function redirectResponse(\FMonitor2\PilotHttp\PilotHttpResponse $response): \FMonitor2\PilotHttp\PilotHttpResponse
    {
        $headers = $response->headers;
        $headers['Location'] = '/pilot/admin/users';
        $headers['Content-Length'] = '0';
        return new \FMonitor2\PilotHttp\PilotHttpResponse(303, $headers, '');
    }

    private static function absoluteUrl(string $path): string
    {
        $scheme = ((string) ($_SERVER['HTTPS'] ?? '')) === 'on' || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https' ? 'https' : 'http';
        return $scheme . '://' . (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8092') . $path;
    }

    private static function localizeRoles(string $html): string
    {
        $html = str_replace(
            [
                'Legacy-роли и унаследованные полномочия rapid pilot',
                'Legacy-роль',
                'Роль импортируется из legacy FMonitor. Rapid pilot переводит стабильный ID роли в прикладные полномочия; персональные capabilities добавляются как исключения. Названия ролей не участвуют в проверке доступа.',
            ],
            [
                'Системные роли и полномочия rapid pilot',
                'Роль',
                'Доступ пользователя складывается из полномочий всех назначенных активных ролей. Чтобы изменить доступ, назначьте или снимите роль на странице «Пользователи».',
            ],
            $html
        );
        foreach (self::LABELS as $code => $label) $html = str_replace('>' . $code . '<', '>' . $label . '<', $html);
        return preg_replace('#(<span class="shlz-tag fm2-permission-chip">)[a-z_]+(?:\.[a-z_]+)+(<)#D', '$1Неизвестное полномочие$2', $html) ?? $html;
    }

    /** @return array<int,array<string,list<string>>> */
    private static function readAccess(): array
    {
        $db=self::db();$prefix=self::prefix();
        try {
            $sql = "SELECT ur.user_id,r.name,rp.permission FROM `{$prefix}fm2_pilot_user_roles` ur JOIN `{$prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id AND r.status=1 JOIN `{$prefix}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id ORDER BY ur.user_id,rp.permission,r.name";
            $result = [];
            foreach ($db->query($sql)->fetch_all(MYSQLI_ASSOC) as $row) {
                $permission = (string) $row['permission'];
                $label = self::LABELS[$permission] ?? 'Неизвестное полномочие';
                $result[(int) $row['user_id']][$label][] = (string) $row['name'];
            }
            return $result;
        } finally {
            $db->close();
        }
    }
    private static function readUserStates():array{$db=self::db();$p=self::prefix();$out=[];try{foreach($db->query("SELECT user_id,status,activation_state FROM `{$p}fm2_pilot_users`")->fetch_all(MYSQLI_ASSOC)as$r)$out[(int)$r['user_id']]=(int)$r['status']===0||$r['activation_state']==='blocked'?'blocked':($r['activation_state']==='invited'?'invited':'active');return$out;}finally{$db->close();}}
    private static function prefix():string{$p=(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:'');if(preg_match('/^[A-Za-z0-9_]*$/D',$p)!==1)throw new RuntimeException();return$p;}
    private static function db():mysqli{$port=(int)(getenv('FMONITOR_DB_PORT')?:3306);$db=new mysqli((string)getenv('FMONITOR_DB_HOST'),(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_DB_PASSWORD'),(string)getenv('FMONITOR_DB_NAME'),$port);$db->set_charset('utf8mb4');return$db;}

    /** @param array<string,list<string>> $permissions */
    private static function accessCell(array $permissions): string
    {
        if ($permissions === []) return '<td class="shlz-table__cell" data-label="Итоговый доступ"><span class="shlz-table__empty">Нет полномочий от активных ролей</span></td>';
        $items = '';
        foreach ($permissions as $label => $roles) {
            $roles = array_values(array_unique($roles));
            $items .= '<li><strong>' . self::e($label) . '</strong><span>Через роль: ' . self::e(implode(', ', $roles)) . '</span></li>';
        }
        return '<td class="shlz-table__cell" data-label="Итоговый доступ"><details class="fm2-access-details"><summary>Посмотреть доступ <span>' . count($permissions) . '</span></summary><ul>' . $items . '</ul></details></td>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
