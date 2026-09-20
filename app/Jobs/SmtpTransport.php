<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final class SmtpTransport
{
    private \Closure $recipient;
    private \Closure $sender;
    public function __construct(private SmtpConfiguration $config, callable $recipient, ?callable $sender=null)
    {
        $this->recipient=\Closure::fromCallable($recipient);
        $this->sender=$sender===null?\Closure::fromCallable($this->sendSmtp(...)):\Closure::fromCallable($sender);
    }

    public function send(array $message): array
    {
        $bytes=strlen((string)($message['subject']??'').(string)($message['html']??'').(string)($message['text']??''));
        if($bytes>SmtpConfiguration::MAX_MESSAGE_BYTES)return[
            'status'=>'permanent','failureCode'=>'REPORT_TOO_LARGE',
            'bytes'=>$bytes,'limit'=>SmtpConfiguration::MAX_MESSAGE_BYTES,
        ];
        $person=($this->recipient)((string)($message['recipientIdentity']??''));
        if(!WeeklyFkrRecipientEligibility::eligible($person,false))return['status'=>'permanent','failureCode'=>'RECIPIENT_INELIGIBLE'];
        $override=$this->config->testRecipient!==null;
        $wire=[
            'to'=>$override?$this->config->testRecipient:$person['email'],
            'from'=>$this->config->fromAddress,'fromName'=>$this->config->fromName,
            'subject'=>(string)$message['subject'],'html'=>(string)$message['html'],'text'=>(string)$message['text'],
        ];
        try{$result=($this->sender)($wire);}
        catch(\Throwable){return['status'=>'unknown','failureCode'=>'UNKNOWN_DELIVERY'];}
        if(!is_array($result)||!in_array($result['status']??null,['delivered','transient','permanent','unknown'],true)){
            return['status'=>'unknown','failureCode'=>'UNKNOWN_DELIVERY'];
        }
        $safe=['status'=>$result['status']];
        foreach(['failureCode','providerReference']as$key){
            if(isset($result[$key])&&is_string($result[$key]))$safe[$key]=$result[$key];
        }
        if($override)$safe['testRecipientOverride']=true;
        return$safe;
    }

    private function sendSmtp(array $wire): array
    {
        $context=stream_context_create(['ssl'=>[
            'verify_peer'=>true,'verify_peer_name'=>true,
            'peer_name'=>$this->config->host,'allow_self_signed'=>false,
        ]]);
        $socket=@stream_socket_client(
            $this->config->host.':'.$this->config->port,$errorNumber,$error,
            $this->config->timeoutSeconds,STREAM_CLIENT_CONNECT,$context,
        );
        if(!is_resource($socket))return['status'=>'transient','failureCode'=>'SMTP_CONNECT_FAILED'];
        stream_set_timeout($socket,$this->config->timeoutSeconds);$dataWritten=false;
        try{
            $this->expect($socket,[220]);
            $capabilities=$this->command($socket,'EHLO fmonitor',[250]);
            if(!preg_match('/^250[ -]STARTTLS\b/mi',$capabilities))return['status'=>'permanent','failureCode'=>'SMTP_STARTTLS_UNAVAILABLE'];
            $this->command($socket,'STARTTLS',[220]);
            if(!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)){
                return['status'=>'transient','failureCode'=>'SMTP_TLS_FAILED'];
            }
            $capabilities=$this->command($socket,'EHLO fmonitor',[250]);
            if(!preg_match('/^250[ -]AUTH[^\r\n]*\bLOGIN\b/mi',$capabilities))return['status'=>'permanent','failureCode'=>'SMTP_AUTH_UNAVAILABLE'];
            $this->authenticate($socket);
            $this->command($socket,'MAIL FROM:<'.$wire['from'].'>',[250]);
            $this->command($socket,'RCPT TO:<'.$wire['to'].'>',[250,251]);
            $this->command($socket,'DATA',[354]);
            $payload=$this->message($wire);$dataWritten=true;
            $this->write($socket,$this->dotStuff($payload)."\r\n.");
            $reply=$this->expect($socket,[250]);
            $this->command($socket,'QUIT',[221]);
            return['status'=>'delivered','providerReference'=>hash('sha256',$reply)];
        }catch(SmtpReplyException $failure){
            if($dataWritten)return['status'=>'unknown','failureCode'=>'UNKNOWN_DELIVERY'];
            return $failure->replyCode>=500
                ? ['status'=>'permanent','failureCode'=>'SMTP_REJECTED']
                : ['status'=>'transient','failureCode'=>'SMTP_TEMPORARY'];
        }catch(\Throwable){
            return$dataWritten
                ? ['status'=>'unknown','failureCode'=>'UNKNOWN_DELIVERY']
                : ['status'=>'transient','failureCode'=>'SMTP_TEMPORARY'];
        }
        finally{fclose($socket);}
    }

    private function message(array $wire): string
    {
        $boundary='fm2_'.bin2hex(random_bytes(12));
        $headers=[
            'From: '.$this->header($wire['fromName']).' <'.$wire['from'].'>',
            'To: <'.$wire['to'].'>','Subject: '.$this->header($wire['subject']),
            'MIME-Version: 1.0','Content-Type: multipart/alternative; boundary="'.$boundary.'"',
        ];
        return implode("\r\n",$headers)."\r\n\r\n"
            ."--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            .chunk_split(base64_encode($wire['text']))
            ."\r\n--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            .chunk_split(base64_encode($wire['html']))."\r\n--{$boundary}--";
    }
    private function dotStuff(string $payload):string{return preg_replace('/(?m)^\./','..',$payload)??$payload;}
    private function command($socket,string $command,array $codes):string{$this->write($socket,$command);return$this->expect($socket,$codes);}
    private function write($socket,string $value):void
    {
        $bytes=$value."\r\n";$offset=0;
        while($offset<strlen($bytes)){
            $written=fwrite($socket,substr($bytes,$offset));
            if($written===false||$written===0)throw new \RuntimeException('SMTP_WRITE_FAILED');
            $offset+=$written;
        }
    }
    private function authenticate($socket):void
    {
        $this->command($socket,'AUTH LOGIN',[334]);
        $this->command($socket,base64_encode($this->config->username),[334]);
        $this->command($socket,base64_encode($this->config->password()),[235]);
    }
    private function expect($socket,array $codes):string
    {
        $all='';
        do{$line=fgets($socket,4096);if($line===false)throw new \RuntimeException('SMTP_READ_FAILED');$all.=$line;}
        while(isset($line[3])&&$line[3]==='-');
        $code=(int)substr($line,0,3);
        if(!in_array($code,$codes,true))throw new SmtpReplyException($code);
        return$all;
    }
    private function header(string $value):string{return'=?UTF-8?B?'.base64_encode(str_replace(["\r","\n"],' ',$value)).'?=';}
}

final class SmtpReplyException extends \RuntimeException { public function __construct(public readonly int $replyCode){parent::__construct('SMTP_REPLY');} }
