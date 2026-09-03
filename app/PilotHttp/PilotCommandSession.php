<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

use FMonitor\IdentityAccess\PilotSessionOperationStatus;
use FMonitor\IdentityAccess\PilotSessionStorage;

final class PilotCommandSession
{
    private ?string $id = null;
    private ?array $state = null;
    private array $headers = [];

    public function __construct(private readonly PilotSessionStorage $storage) {}

    public function open(?string $incoming,string $cookieName,bool $secure,int $actorId,bool $create):bool
    {
        if($this->state!==null)return true;
        if($incoming===null&&!$create)return false;
        $started=$this->storage->start($incoming);
        if($started->status()===PilotSessionOperationStatus::UNAVAILABLE)throw new PilotHttpInfrastructureUnavailable();
        if($started->status()!==PilotSessionOperationStatus::OK){if(!$create)return false;$started=$this->storage->start(null);}
        $id=$started->currentSessionId();if($id===null)throw new PilotHttpInfrastructureUnavailable();
        $decoded=(new PilotSessionPayloadCodec())->decode((string)$started->sessionPayload());if($decoded===null)throw new PilotHttpInfrastructureUnavailable();
        $this->id=$id;$this->state=$decoded;
        if(isset($this->state['actor'])&&$this->state['actor']!==$actorId){$this->state=[];$result=$this->storage->regenerate($id,\serialize($this->state));if($result->status()!==PilotSessionOperationStatus::OK||$result->currentSessionId()===null)throw new PilotHttpInfrastructureUnavailable();$this->id=$result->currentSessionId();$this->headers=['Set-Cookie'=>$this->cookie($cookieName,$this->id,$secure)];}
        if(!isset($this->state['actor'])){if(!$create){$this->state=null;$this->id=null;return false;}$this->state=['actor'=>$actorId,'secret'=>\random_bytes(32),'tokens'=>[],'flash'=>[]];$this->commit();}
        if($incoming===null)$this->headers=['Set-Cookie'=>$this->cookie($cookieName,(string)$this->id,$secure)];
        return true;
    }

    public function state():array { if($this->state===null)throw new PilotHttpInfrastructureUnavailable();return$this->state; }
    public function replace(array $state,bool $commit):void { $this->state=$state;if($commit)$this->commit(); }
    public function headers():array { return$this->headers; }

    private function commit():void
    {
        if($this->id===null||$this->state===null)throw new PilotHttpInfrastructureUnavailable();$result=$this->storage->writeCommit($this->id,\serialize($this->state));if($result->status()!==PilotSessionOperationStatus::OK)throw new PilotHttpInfrastructureUnavailable();$this->id=$result->currentSessionId();
    }
    private function cookie(string$name,string$id,bool$secure):string{return$name.'='.$id.($secure?'; Secure':'').'; HttpOnly; SameSite=Strict; Path=/pilot';}
}
