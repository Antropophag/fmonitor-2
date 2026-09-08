<?php
declare(strict_types=1);
$email=mb_strtolower(trim((string)($argv[1]??'')));if(filter_var($email,FILTER_VALIDATE_EMAIL)===false||preg_match('/^[^@]+@shlz\.ru$/D',$email)!==1)throw new InvalidArgumentException('Usage: php rapid-pilot/issue-invitation.php name@shlz.ru');
$home=getenv('HOME');if(!is_string($home)||$home==='')throw new RuntimeException('Home unavailable');$root=dirname(__DIR__);$fingerprint=substr(hash('sha256',(string)realpath($root)),0,8);$manifest=json_decode((string)file_get_contents($home.'/.local/state/fmonitor2/pilot-demo/'.$fingerprint.'/active.json'),true,flags:JSON_THROW_ON_ERROR);$p=(string)($manifest['processPrefix']??'');if(preg_match('/^[A-Za-z0-9_]+$/D',$p)!==1)throw new RuntimeException('Pilot generation unavailable');
$actorId=filter_var($argv[2]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if($actorId===false||$actorId===null)throw new InvalidArgumentException('Usage: php rapid-pilot/issue-invitation.php name@shlz.ru administrator-user-id');
require_once $root.'/app/autoload.php';
$db=new mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:'fmonitor2_demo',getenv('FMONITOR_DB_PASSWORD')?:'fmonitor2_demo_local',getenv('FMONITOR_DB_NAME')?:'fmonitor2_demo',(int)(getenv('FMONITOR_DB_PORT')?:'23306'));
try {
    $db->set_charset('utf8mb4');
    $userId=(new \FMonitor2\IdentityAccess\MariaDbInvitationRecipientLookup($db,$p))->userIdForEmail($email);
    $result=(new \FMonitor2\IdentityAccess\MariaDbReissueUserInvitation($db,$p))->reissue($actorId,$userId??0);
    if($result['status']!=='issued')throw new RuntimeException('Invitation not issued: '.$result['status']);
    echo "/pilot/activate?token={$result['token']}\n";
} finally {
    $db->close();
}
