<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class MariaDbPilotUserDirectory
{
    public function __construct(private readonly \mysqli $db, private readonly string $prefix) {}

    public function read(): array
    {
        try {
            $users=$this->db->query("SELECT user_id,full_name,email,phone,status,source_updated_at FROM `{$this->prefix}fm2_pilot_users` ORDER BY status DESC,full_name,user_id")->fetch_all(MYSQLI_ASSOC);
            $roles=$this->db->query("SELECT r.role_id,r.name,r.status,r.source_updated_at,COUNT(ur.user_id) user_count FROM `{$this->prefix}fm2_pilot_roles` r LEFT JOIN `{$this->prefix}fm2_pilot_user_roles` ur ON ur.role_id=r.role_id GROUP BY r.role_id,r.name,r.status,r.source_updated_at ORDER BY r.status DESC,r.name,r.role_id")->fetch_all(MYSQLI_ASSOC);
            $assignments=$this->db->query("SELECT ur.user_id,ur.role_id,r.name,r.status FROM `{$this->prefix}fm2_pilot_user_roles` ur JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id ORDER BY r.name,r.role_id")->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $error) { throw new PilotHttpInfrastructureUnavailable('',0,$error); }
        $byUser=[];foreach($assignments as$row)$byUser[(int)$row['user_id']][]=['id'=>(int)$row['role_id'],'name'=>(string)$row['name'],'active'=>(int)$row['status']===1];
        $normalizedUsers=[];foreach($users as$row)$normalizedUsers[]=['id'=>(int)$row['user_id'],'name'=>(string)$row['full_name'],'email'=>(string)$row['email'],'phone'=>(string)$row['phone'],'active'=>(int)$row['status']===1,'updatedAt'=>(string)$row['source_updated_at'],'roles'=>$byUser[(int)$row['user_id']]??[]];
        $normalizedRoles=[];foreach($roles as$row)$normalizedRoles[]=['id'=>(int)$row['role_id'],'name'=>(string)$row['name'],'active'=>(int)$row['status']===1,'userCount'=>(int)$row['user_count'],'updatedAt'=>(string)$row['source_updated_at']];
        return ['users'=>$normalizedUsers,'roles'=>$normalizedRoles];
    }

    public function changeRole(int $userId,int $roleId,string $action,int $actorId,string $occurredAt):bool
    {
        if(!in_array($action,['attach','detach'],true))return false;
        $user=$this->one("SELECT user_id FROM `{$this->prefix}fm2_pilot_users` WHERE user_id=?",$userId);$role=$this->one("SELECT role_id,status FROM `{$this->prefix}fm2_pilot_roles` WHERE role_id=?",$roleId);
        if($user===null||$role===null||($action==='attach'&&(int)$role['status']!==1))return false;
        $this->db->begin_transaction();try{
            $existing=$this->db->prepare("SELECT user_id FROM `{$this->prefix}fm2_pilot_user_roles` WHERE user_id=? AND role_id=? FOR UPDATE");$existing->bind_param('ii',$userId,$roleId);$existing->execute();$present=$existing->get_result()->num_rows===1;
            if(($action==='attach'&&$present)||($action==='detach'&&!$present)){$this->db->commit();return true;}
            if($action==='attach'){$statement=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES(?,?,'pilot_admin',?,?)");$statement->bind_param('iisi',$userId,$roleId,$occurredAt,$actorId);}else{$statement=$this->db->prepare("DELETE FROM `{$this->prefix}fm2_pilot_user_roles` WHERE user_id=? AND role_id=?");$statement->bind_param('ii',$userId,$roleId);}$statement->execute();
            $event=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_user_role_events`(user_id,role_id,action,occurred_at,actor_user_id) VALUES(?,?,?,?,?)");$eventAction=$action==='attach'?'role_attached':'role_detached';$event->bind_param('iissi',$userId,$roleId,$eventAction,$occurredAt,$actorId);$event->execute();$this->db->commit();return true;
        }catch(\Throwable $error){$this->db->rollback();throw new PilotHttpInfrastructureUnavailable('',0,$error);}
    }
    private function one(string $sql,int $id):?array{$statement=$this->db->prepare($sql);$statement->bind_param('i',$id);$statement->execute();$rows=$statement->get_result()->fetch_all(MYSQLI_ASSOC);return count($rows)===1?$rows[0]:null;}
}

final class ProductionUserDirectoryRenderer
{
    public function render(HttpUser $actor,array $directory,array $tokens):string
    {
        $e=[PilotView::class,'e'];$users=$directory['users'];$roles=$directory['roles'];$active=count(array_filter($users,fn($x)=>$x['active']));$latest='';foreach([...$users,...$roles]as$item)$latest=max($latest,$item['updatedAt']);$latestLabel=$latest===''?'Нет данных':(new \DateTimeImmutable($latest))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('d.m.Y H:i');
        $body='<div class="fm2-page-header"><div><h1>Пользователи</h1><p>Учётные записи FMonitor и назначенные роли</p></div><span class="fm2-result-count">'.count($users).' пользователей</span></div><section class="fm2-directory-summary fm2-user-summary"><div><strong>'.count($users).'</strong><span>Всего</span></div><div><strong>'.$active.'</strong><span>Активные</span></div><div><strong>'.(count($users)-$active).'</strong><span>Заблокированы</span></div><p><b>Снимок production:</b> '.$e($latestLabel).'<br><span>Legacy FMonitor · только чтение</span></p></section>';
        $rows='';foreach($users as$user){$assigned=array_column($user['roles'],'id');$tags='';foreach($user['roles']as$role)$tags.='<form method="post" action="/pilot/users/'.$user['id'].'/roles/'.$role['id'].'"><input type="hidden" name="csrfToken" value="'.$e($tokens[$user['id']]).'"><input type="hidden" name="action" value="detach"><button class="fm2-role-tag'.($role['active']?'':' fm2-role-tag--archived').'" type="submit" title="Снять роль '.$e($role['name']).'"><span>'.$e($role['name']).'</span><svg viewBox="0 0 12 12" aria-hidden="true"><path d="m3 3 6 6m0-6L3 9"/></svg></button></form>';if($tags==='')$tags='<span class="fm2-no-role">Роли не назначены</span>';$options='';foreach($roles as$role)if($role['active']&&!in_array($role['id'],$assigned,true))$options.='<option value="'.$role['id'].'">'.$e($role['name']).'</option>';$add=$options===''?'':'<form class="fm2-role-add" method="post" data-user-id="'.$user['id'].'"><input type="hidden" name="csrfToken" value="'.$e($tokens[$user['id']]).'"><input type="hidden" name="action" value="attach"><select aria-label="Добавить роль пользователю '.$e($user['name']).'" onchange="if(this.value){this.form.action=\'/pilot/users/'.$user['id'].'/roles/\'+this.value;this.form.submit()}"><option value="">+ Добавить роль</option>'.$options.'</select></form>';
            $rows.='<tr class="fm2-user-row fm2-user-row--'.($user['active']?'active':'blocked').'" data-user-id="'.$user['id'].'"><td data-label="Пользователь"><span class="fm2-installer-person"><span class="shlz-avatar shlz-avatar--32" aria-hidden="true">'.PilotView::initials($user['name']).'</span><span><strong>'.$e($user['name']).'</strong><small>ID '.$user['id'].'</small></span></span></td><td data-label="Контакты"><a class="shlz-link" href="mailto:'.$e($user['email']).'">'.$e($user['email']).'</a><small>'.$e($user['phone']!==''?$user['phone']:'Телефон не указан').'</small></td><td data-label="Статус"><span class="fm2-employment fm2-employment--'.($user['active']?'active':'dismissed').'">'.($user['active']?'Активен':'Заблокирован').'</span></td><td data-label="Роли"><div class="fm2-role-stack">'.$tags.$add.'</div></td></tr>';}
        $body.='<section class="fm2-list-surface fm2-user-directory"><div class="fm2-user-toolbar"><label class="shlz-field shlz-field--medium"><span class="shlz-field__label">Поиск</span><span class="shlz-field__control"><input class="shlz-field__input" type="search" placeholder="ФИО, email или телефон" data-user-search></span></label><div class="fm2-installer-filters" aria-label="Фильтр пользователей"><input checked type="radio" name="user-filter" id="uf-all"><label for="uf-all">Все <b>'.count($users).'</b></label><input type="radio" name="user-filter" id="uf-active"><label for="uf-active">Активные <b>'.$active.'</b></label><input type="radio" name="user-filter" id="uf-blocked"><label for="uf-blocked">Заблокированные <b>'.(count($users)-$active).'</b></label></div></div><div class="fm2-installer-table-scroll"><table class="shlz-table fm2-installer-table fm2-user-table"><thead><tr><th>Пользователь</th><th>Контакты</th><th>Статус</th><th>Роли</th></tr></thead><tbody>'.$rows.'</tbody></table></div></section>';
        $roleRows='';foreach($roles as$role)$roleRows.='<tr><td><strong>'.$e($role['name']).'</strong><small>ID '.$role['id'].'</small></td><td><span class="fm2-employment fm2-employment--'.($role['active']?'active':'dismissed').'">'.($role['active']?'Активна':'Архивная').'</span></td><td>'.$role['userCount'].'</td></tr>';$body.='<section class="fm2-role-catalog"><div class="fm2-section-head"><h2>Справочник ролей</h2><p>Роли загружены из production FMonitor и не редактируются в пилоте.</p></div><div class="fm2-list-surface"><table class="shlz-table fm2-role-table"><thead><tr><th>Роль</th><th>Статус</th><th>Пользователей</th></tr></thead><tbody>'.$roleRows.'</tbody></table></div></section><script src="/pilot/assets/users.js" defer></script>';
        return PilotView::document($actor,'Пользователи','Пользователи','',$body);
    }
}
