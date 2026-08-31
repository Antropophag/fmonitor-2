<?php
declare(strict_types=1);

final class RapidPilotLocalAuth
{
    private mysqli $db;
    private string $prefix;

    public function __construct()
    {
        $this->prefix=(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');
        if(preg_match('/^[A-Za-z0-9_]+$/D',$this->prefix)!==1)throw new RuntimeException('Invalid pilot table prefix');
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        $this->db=new mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:'fmonitor2_demo',getenv('FMONITOR_DB_PASSWORD')?:'fmonitor2_demo_local',getenv('FMONITOR_DB_NAME')?:'fmonitor2_demo',(int)(getenv('FMONITOR_DB_PORT')?:'23306'));
        $this->db->set_charset('utf8mb4');$this->startSession();
    }

    public function handle(string $path):void
    {
        if($path==='/pilot/logout'){
            if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!$this->validCsrf((string)($_POST['csrfToken']??'')))$this->plain(403,'Недопустимый запрос.');
            $this->destroySession();$this->redirect('/pilot/login');
        }
        if($path==='/pilot/login'){
            if($this->user()!==null)$this->redirect('/pilot/objects');
            $this->loginPage();
        }
        $user=$this->user();
        if($user===null){if(($_SERVER['REQUEST_METHOD']??'GET')==='GET')$_SESSION['auth_return_to']=$this->safeReturnTo((string)($_SERVER['REQUEST_URI']??'/pilot/objects'));$this->redirect('/pilot/login');}
        $_SERVER['REMOTE_USER']=$user['email'];
        $_SERVER['FMONITOR_AUTH_USER_ID']=(string)$user['user_id'];
        $_SERVER['FMONITOR_AUTH_CSRF']=$this->csrf();
        session_write_close();
    }

    private function loginPage():never
    {
        $email=$this->normalizeEmail((string)($_POST['email']??''));$stage='email';$error='';$user=null;
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            if(!$this->validCsrf((string)($_POST['csrfToken']??'')))$this->plain(403,'Недопустимый запрос.');
            if(!$this->allowedEmail($email))$error='Введите рабочий email в домене @shlz.ru.';
            else{
                $user=$this->findUser($email);
                if($this->isRateLimited($email)){$this->recordAttempt($email,false);$error='Не удалось войти. Проверьте email и данные доступа.';}
                elseif($user!==null&&(int)$user['status']!==1){$this->recordAttempt($email,false);$error='Учётная запись заблокирована. Обратитесь к администратору FMonitor.';}
                else{
                    if($user===null)$user=['user_id'=>0,'full_name'=>$email,'password_hash'=>null,'status'=>1];
                    if($user['password_hash']===null){
                    $stage='setup';
                    if(isset($_POST['password'])){
                        $password=(string)$_POST['password'];$passwordError=$this->passwordError($password,(string)($_POST['passwordConfirmation']??''),$email);
                        if($passwordError!==null)$error=$passwordError;
                        else{
                            $hash=password_hash($password,defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_BCRYPT);
                            if(!is_string($hash))throw new RuntimeException('Password hashing failed');
                            $userId=(int)$user['user_id'];if($userId===0){$user=$this->provisionUser($email);$userId=(int)$user['user_id'];}
                            $statement=$this->db->prepare("UPDATE `{$this->prefix}fm2_pilot_auth_credentials` SET password_hash=?,password_set_at=?,updated_at=? WHERE user_id=? AND password_hash IS NULL");$now=$this->now();$statement->bind_param('sssi',$hash,$now,$now,$userId);$statement->execute();
                            if($statement->affected_rows!==1){$error='Пароль уже был задан. Войдите с установленным паролем.';$stage='password';}
                            else{$this->recordAttempt($email,true);$this->signIn($userId,$email);}
                        }
                    }
                    }else{
                        $stage='password';
                        if(isset($_POST['password'])){
                            $password=(string)$_POST['password'];
                            if(!password_verify($password,(string)$user['password_hash'])){$this->recordAttempt($email,false);$error='Не удалось войти. Проверьте email и данные доступа.';}
                            else{$this->recordAttempt($email,true);$this->signIn((int)$user['user_id'],$email);}
                        }
                    }
                }
            }
        }
        $this->renderLogin($stage,$email,$error,is_array($user)?(string)$user['full_name']:'');
    }

    private function renderLogin(string $stage,string $email,string $error,string $fullName):never
    {
        $csrf=$this->csrf();$safe=static fn(string $v):string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $title=$stage==='setup'?'Создайте пароль':($stage==='password'?'Введите пароль':'Вход в FMonitor');
        $intro=$stage==='setup'?'Это ваш первый вход. Придумайте пароль для локального пилота.':($stage==='password'?'Используйте пароль, который вы задали при первом входе.':'Введите корпоративный email. Доступ открыт пользователям, перенесённым из FMonitor.');
        $emailField=$stage==='email'?'<label class="shlz-field"><span class="shlz-field__label">Корпоративный email</span><span class="shlz-field__control"><input class="shlz-input" type="email" name="email" value="'.$safe($email).'" placeholder="name@shlz.ru" autocomplete="username" inputmode="email" required autofocus></span></label>':'<input type="hidden" name="email" value="'.$safe($email).'"><div class="fm2-auth-identity"><span class="fm2-auth-avatar" aria-hidden="true">'.$safe($this->initials($fullName)).'</span><span><strong>'.$safe($fullName).'</strong><small>'.$safe($email).'</small></span></div>';
        $passwordFields=$stage==='setup'?'<label class="shlz-field"><span class="shlz-field__label">Новый пароль</span><span class="shlz-field__control"><input class="shlz-input" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="200" required autofocus></span><span class="shlz-field__secondary">Не менее 12 символов</span></label><label class="shlz-field"><span class="shlz-field__label">Повторите пароль</span><span class="shlz-field__control"><input class="shlz-input" type="password" name="passwordConfirmation" autocomplete="new-password" minlength="12" maxlength="200" required></span></label>':($stage==='password'?'<label class="shlz-field"><span class="shlz-field__label">Пароль</span><span class="shlz-field__control"><input class="shlz-input" type="password" name="password" autocomplete="current-password" maxlength="200" required autofocus></span></label>':'');
        $button=$stage==='setup'?'Создать пароль и войти':($stage==='password'?'Войти':'Продолжить');$back=$stage==='email'?'':'<a class="fm2-auth-back" href="/pilot/login">Войти с другим email</a>';$errorHtml=$error===''?'':'<p class="fm2-auth-error" role="alert">'.$safe($error).'</p>';
        header('Content-Type: text/html; charset=UTF-8');header('Cache-Control: no-store');header('X-Frame-Options: DENY');header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
        echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$safe($title).' — FMonitor</title><script src="/pilot/assets/preloader.js"></script><link rel="stylesheet" href="/pilot/assets/shlz.css"><link rel="stylesheet" href="/pilot/assets/pilot.css"><link rel="icon" type="image/svg+xml" href="/pilot/assets/favicon.svg"></head><body class="shlz-scope fm2-auth-page"><main class="fm2-auth-layout"><section class="fm2-auth-brand" aria-label="FMonitor"><img src="/pilot/assets/favicon.svg" alt="" width="56" height="56"><div><strong>FMonitor</strong><span>Управление монтажными работами</span></div></section><section class="fm2-auth-card"><div class="fm2-auth-heading"><span class="fm2-auth-kicker">ЩЛЗ · защищённый контур</span><h1>'.$safe($title).'</h1><p>'.$safe($intro).'</p></div><form method="post" action="/pilot/login" class="fm2-auth-form"><input type="hidden" name="csrfToken" value="'.$safe($csrf).'">'.$emailField.$passwordFields.$errorHtml.'<button class="shlz-button shlz-button--primary fm2-auth-submit" type="submit">'.$safe($button).'</button>'.$back.'</form></section><p class="fm2-auth-note">Пароль хранится только в виде необратимого криптографического хэша.</p></main></body></html>';exit;
    }

    private function startSession():void
    {
        $sessionPath='/home/fmonitor/.local/state/fmonitor2/sessions';
        if(!is_dir($sessionPath)&&!mkdir($sessionPath,0700,true)&&!is_dir($sessionPath))throw new RuntimeException('Session storage unavailable');
        if(!is_writable($sessionPath))throw new RuntimeException('Session storage unavailable');
        session_save_path($sessionPath);
        $cookieName=$this->sessionCookieName();
        session_name($cookieName);
        session_set_cookie_params(['lifetime'=>604800,'path'=>'/pilot','secure'=>$this->isHttps(),'httponly'=>true,'samesite'=>'Strict']);
        $incoming=null;
        if(preg_match('/(?:^|;\s*)'.preg_quote($cookieName,'/').'=([A-Za-z0-9,-]{16,128})(?:;|$)/',(string)($_SERVER['HTTP_COOKIE']??''),$match)===1)$incoming=$match[1];
        $path=(string)(parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH)??'');
        if($path!=='/pilot/login'){
            if($incoming===null){$_SESSION=[];return;}
            ini_set('session.use_cookies','0');
            session_id($incoming);
        }
        session_start(['use_strict_mode'=>1,'use_only_cookies'=>1,'cookie_httponly'=>1,'cookie_samesite'=>'Strict','gc_maxlifetime'=>604800]);
    }
    private function user():?array{$userId=(int)($_SESSION['auth_user_id']??0);$email=$this->normalizeEmail((string)($_SESSION['auth_email']??''));if($userId<1||!$this->allowedEmail($email))return null;$s=$this->db->prepare("SELECT u.user_id,u.email FROM `{$this->prefix}fm2_pilot_users` u JOIN `{$this->prefix}fm2_pilot_auth_credentials` c ON c.user_id=u.user_id WHERE u.user_id=? AND u.status=1 AND c.email_normalized=? AND c.password_hash IS NOT NULL");$s->bind_param('is',$userId,$email);$s->execute();$row=$s->get_result()->fetch_assoc();return is_array($row)?['user_id'=>(int)$row['user_id'],'email'=>$this->normalizeEmail((string)$row['email'])]:null;}
    private function findUser(string $email):?array{$s=$this->db->prepare("SELECT u.user_id,u.full_name,u.status,c.password_hash FROM `{$this->prefix}fm2_pilot_users` u JOIN `{$this->prefix}fm2_pilot_auth_credentials` c ON c.user_id=u.user_id WHERE c.email_normalized=? LIMIT 1");$s->bind_param('s',$email);$s->execute();$row=$s->get_result()->fetch_assoc();return is_array($row)?$row:null;}
    private function provisionUser(string $email):array
    {
        $lockName=$this->prefix.'pilot-auth-provision';$lock=$this->db->prepare('SELECT GET_LOCK(?,5) acquired');$lock->bind_param('s',$lockName);$lock->execute();if((int)$lock->get_result()->fetch_assoc()['acquired']!==1)throw new RuntimeException('Account provisioning lock unavailable');
        try{
            $existing=$this->findUser($email);if($existing!==null)return$existing;
            $row=$this->db->query("SELECT GREATEST(COALESCE(MAX(user_id),0)+1,9000000000) next_id FROM `{$this->prefix}fm2_pilot_users`")->fetch_assoc();$userId=(int)$row['next_id'];$now=$this->now();$fullName=$email;
            $s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_users`(user_id,full_name,email,phone,status,source_updated_at) VALUES(?,?,?,'',1,?)");$s->bind_param('isss',$userId,$fullName,$email,$now);$s->execute();
            $s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_auth_credentials`(user_id,email_normalized,password_hash,password_set_at,updated_at) VALUES(?,?,NULL,NULL,?)");$s->bind_param('iss',$userId,$email,$now);$s->execute();
            return['user_id'=>$userId,'full_name'=>$fullName,'password_hash'=>null,'status'=>1];
        }finally{$release=$this->db->prepare('SELECT RELEASE_LOCK(?)');$release->bind_param('s',$lockName);$release->execute();}
    }
    private function signIn(int $userId,string $email):never{session_regenerate_id(true);$_SESSION['auth_user_id']=$userId;$_SESSION['auth_email']=$email;$_SESSION['auth_signed_in_at']=time();$returnTo=$this->safeReturnTo((string)($_SESSION['auth_return_to']??'/pilot/objects'));unset($_SESSION['auth_return_to']);$this->redirect($returnTo);}
    private function passwordError(string $password,string $confirmation,string $email):?string{if($password!==$confirmation)return'Пароли не совпадают.';if(strlen($password)<12)return'Пароль должен содержать не менее 12 символов.';if(strlen($password)>200)return'Пароль слишком длинный.';$local=strstr($email,'@',true);if(is_string($local)&&strlen($local)>=4&&str_contains(mb_strtolower($password),mb_strtolower($local)))return'Пароль не должен содержать ваш email.';return null;}
    private function isRateLimited(string $email):bool{$s=$this->db->prepare("SELECT COUNT(*) failures FROM `{$this->prefix}fm2_pilot_auth_attempts` WHERE email_normalized=? AND succeeded=0 AND attempted_at>=DATE_SUB(NOW(6),INTERVAL 15 MINUTE)");$s->bind_param('s',$email);$s->execute();return(int)$s->get_result()->fetch_assoc()['failures']>=10;}
    private function recordAttempt(string $email,bool $success):void{$value=$success?1:0;$s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_auth_attempts`(email_normalized,succeeded,attempted_at) VALUES(?,?,NOW(6))");$s->bind_param('si',$email,$value);$s->execute();if($success){$s=$this->db->prepare("DELETE FROM `{$this->prefix}fm2_pilot_auth_attempts` WHERE email_normalized=?");$s->bind_param('s',$email);$s->execute();}}
    private function csrf():string{if(!isset($_SESSION['auth_csrf'])||!is_string($_SESSION['auth_csrf']))$_SESSION['auth_csrf']=bin2hex(random_bytes(32));return$_SESSION['auth_csrf'];}
    private function validCsrf(string $token):bool{return$token!==''&&hash_equals($this->csrf(),$token);}
    private function normalizeEmail(string $email):string{return mb_strtolower(trim($email));}
    private function allowedEmail(string $email):bool{return filter_var($email,FILTER_VALIDATE_EMAIL)!==false&&preg_match('/^[^@]+@shlz\.ru$/Di',$email)===1;}
    private function now():string{return(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format(DATE_ATOM);}
    private function isHttps():bool{return(string)($_SERVER['HTTPS']??'')==='on'||(string)($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https';}
    private function sessionCookieName():string
    {
        $host=(string)($_SERVER['HTTP_HOST']??'');
        return preg_match('/:(\d{1,5})$/D',$host,$match)===1?'fm2auth_'.$match[1]:'fm2auth';
    }
    private function safeReturnTo(string $path):string{return preg_match('#^/pilot/(?!assets(?:/|$)|login(?:/|$)|logout(?:/|$))[A-Za-z0-9/_?&=.%~-]*$#D',$path)===1?$path:'/pilot/objects';}
    private function initials(string $name):string{$parts=preg_split('/\s+/u',trim($name),3,PREG_SPLIT_NO_EMPTY)?:[];return mb_strtoupper(implode('',array_map(static fn(string $p):string=>mb_substr($p,0,1),array_slice($parts,0,2))));}
    private function redirect(string $path):never{header('Location: '.$path,true,303);header('Cache-Control: no-store');exit;}
    private function plain(int $status,string $message):never{http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');echo$message."\n";exit;}
    private function destroySession():void{$_SESSION=[];setcookie(session_name(),'', ['expires'=>time()-42000,'path'=>'/pilot','secure'=>$this->isHttps(),'httponly'=>true,'samesite'=>'Strict']);if(session_status()===PHP_SESSION_ACTIVE)session_destroy();}
}
