<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class MariaDbLocalAuthRepository
{
    public function __construct(private readonly \mysqli$db,private readonly string$prefix){}
    public function activeUser(int$id,string$email):?array{$s=$this->db->prepare("SELECT u.user_id,u.email FROM `{$this->prefix}fm2_pilot_users` u JOIN `{$this->prefix}fm2_pilot_auth_credentials` c ON c.user_id=u.user_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND c.email_normalized=? AND c.password_hash IS NOT NULL");$s->bind_param('is',$id,$email);$s->execute();$row=$s->get_result()->fetch_assoc();return \is_array($row)?$row:null;}
    public function findUser(string$email):?array{$s=$this->db->prepare("SELECT u.user_id,u.full_name,u.status,c.password_hash FROM `{$this->prefix}fm2_pilot_users` u JOIN `{$this->prefix}fm2_pilot_auth_credentials` c ON c.user_id=u.user_id WHERE c.email_normalized=? AND u.activation_state='active' LIMIT 1");$s->bind_param('s',$email);$s->execute();$row=$s->get_result()->fetch_assoc();return \is_array($row)?$row:null;}
    public function rateLimited(string$email):bool{$s=$this->db->prepare("SELECT COUNT(*) failures FROM `{$this->prefix}fm2_pilot_auth_attempts` WHERE email_normalized=? AND succeeded=0 AND attempted_at>=DATE_SUB(NOW(6),INTERVAL 15 MINUTE)");$s->bind_param('s',$email);$s->execute();return(int)$s->get_result()->fetch_assoc()['failures']>=10;}
    public function recordAttempt(string$email,bool$success):void{$value=$success?1:0;$s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_auth_attempts`(email_normalized,succeeded,attempted_at) VALUES(?,?,NOW(6))");$s->bind_param('si',$email,$value);$s->execute();if($success){$s=$this->db->prepare("DELETE FROM `{$this->prefix}fm2_pilot_auth_attempts` WHERE email_normalized=?");$s->bind_param('s',$email);$s->execute();}}
}
