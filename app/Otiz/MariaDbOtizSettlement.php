<?php

declare(strict_types=1);

namespace FMonitor2\Otiz;

use DomainException;
use yii\db\Connection;
use yii\db\IntegrityException;

final class MariaDbOtizSettlement
{
    private const FIXED_BASIS = 'Выплаты выполнены по подтверждению ОТиЗ';

    public function __construct(private Connection $db, private string $prefix, private \Closure $clock)
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) throw new \InvalidArgumentException('INVALID_TABLE_PREFIX');
    }

    public function recordDiscipline(int $actor, int $snapshot, int $object, int $positiveCents, string $basis, string $artifact, string $operationId): array
    {
        $basis=trim($basis);$artifact=trim($artifact);
        if($snapshot<1||$object<1||$positiveCents<1||$positiveCents>1000000000000||$basis===''||mb_strlen($basis)>500||mb_strlen($artifact)>300)$this->fail('INVALID_COMMAND');
        return $this->execute($actor,$operationId,['discipline',$snapshot,$object,$positiveCents,$basis,$artifact],function()use($actor,$snapshot,$object,$positiveCents,$basis,$artifact):array{
            $row=$this->object($snapshot,$object);$this->lockObjects([$object]);$row=$this->object($snapshot,$object,true);
            if($row['calculation_state']==='blocked')$this->fail('OBJECT_BLOCKED');
            if($positiveCents>$this->budget($object,(int)$row['accrued_cents']))$this->fail('AMOUNT_UNAVAILABLE');
            $id=$this->closure($snapshot,$object,0,$positiveCents,0,$basis,$artifact,$actor,null);
            $this->event($snapshot,$object,'payment_closure_recorded',['closureId'=>$id,'closedCents'=>$positiveCents],$actor);
            return ['status'=>'recorded','closureId'=>$id,'disciplineCents'=>$positiveCents];
        });
    }

    public function completeSnapshotPayments(int $actor, int $snapshot, string $operationId): array
    {
        if($snapshot<1)$this->fail('INVALID_COMMAND');
        return $this->execute($actor,$operationId,['complete',$snapshot],function()use($actor,$snapshot):array{
            $this->snapshot($snapshot);$rows=$this->db->createCommand("SELECT object_id FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id=:s AND calculation_state<>'blocked' ORDER BY object_id",[':s'=>$snapshot])->queryAll();
            $ids=array_map(static fn(array$r):int=>(int)$r['object_id'],$rows);$this->lockObjects($ids);$closures=[];$total=0;
            foreach($ids as$object){$row=$this->object($snapshot,$object,true);$paid=$this->budget($object,(int)$row['accrued_cents']);if($paid===0)continue;$id=$this->closure($snapshot,$object,$paid,0,0,self::FIXED_BASIS,'',$actor,null);$closures[]=$id;$total+=$paid;$this->event($snapshot,$object,'payment_completed',['closureId'=>$id,'paidCents'=>$paid,'scope'=>'snapshot'],$actor);}
            if($closures===[])return['status'=>'no_change','closureIds'=>[],'objectCount'=>0,'paidCents'=>0];
            $this->event($snapshot,null,'snapshot_payments_completed',['objectCount'=>count($closures),'paidCents'=>$total],$actor);
            return['status'=>'completed','closureIds'=>$closures,'objectCount'=>count($closures),'paidCents'=>$total];
        });
    }

    public function reverse(int $actor, int $closureId, string $basis, string $operationId): array
    {
        $basis=trim($basis);if($closureId<1||$basis===''||mb_strlen($basis)>500)$this->fail('INVALID_COMMAND');
        return $this->execute($actor,$operationId,['reverse',$closureId,$basis],function()use($actor,$closureId,$basis):array{
            $original=$this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE id=:id",[':id'=>$closureId])->queryOne();if($original===false)$this->fail('NOT_FOUND');
            $object=(int)$original['object_id'];$this->lockObjects([$object]);$original=$this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE id=:id FOR UPDATE",[':id'=>$closureId])->queryOne();
            if($original===false||(int)($original['reverses_payment_closure_id']??0)>0)$this->fail('NOT_FOUND');
            if($this->db->createCommand("SELECT id FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE reverses_payment_closure_id=:id",[':id'=>$closureId])->queryScalar()!==false)$this->fail('ALREADY_REVERSED');
            $id=$this->closure((int)$original['snapshot_id'],$object,-(int)$original['paid_cents'],-(int)$original['discipline_cents'],-(int)$original['deadline_cents'],$basis,'',$actor,$closureId);
            $this->event((int)$original['snapshot_id'],$object,'payment_closure_reversed',['closureId'=>$closureId,'reversalId'=>$id],$actor);
            return['status'=>'reversed','closureId'=>$id,'reversesClosureId'=>$closureId];
        });
    }

    private function execute(int$actor,string$operationId,array$fingerprint,\Closure$work):array
    {
        if($actor<1||preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$operationId)!==1)$this->fail('INVALID_COMMAND');
        if($this->db->getTransaction()!==null)throw new \LogicException('Settlement owns the outer transaction');
        $hash=hash('sha256',json_encode($fingerprint,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $this->db->createCommand('SET TRANSACTION ISOLATION LEVEL READ COMMITTED')->execute();$transaction=$this->db->beginTransaction();
        try{
            $this->authorize($actor);$saved=$this->receipt($actor,$operationId);if($saved!==false){if(!hash_equals((string)$saved['request_sha256'],$hash))$this->fail('OPERATION_CONFLICT');$transaction->commit();return json_decode((string)$saved['result_json'],true,flags:JSON_THROW_ON_ERROR);}
            $result=$work();$json=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$status=(string)$result['status'];$now=($this->clock)();
            $this->db->createCommand("INSERT INTO `{$this->prefix}fm2_otiz_settlement_operations`(actor_user_id,operation_id,request_sha256,status,result_json,created_at)VALUES(:a,:o,:h,:s,:r,:t)",[':a'=>$actor,':o'=>$operationId,':h'=>$hash,':s'=>$status,':r'=>$json,':t'=>$now])->execute();$transaction->commit();return$result;
        }catch(IntegrityException$e){$transaction->rollBack();if((int)($e->errorInfo[1]??0)!==1062||!str_contains($e->getMessage(),'fm2_otiz_settlement_operations'))throw$e;$saved=$this->receipt($actor,$operationId);if($saved!==false&&hash_equals((string)$saved['request_sha256'],$hash))return json_decode((string)$saved['result_json'],true,flags:JSON_THROW_ON_ERROR);$this->fail('OPERATION_CONFLICT');}
        catch(DomainException$e){if($transaction->isActive)$transaction->rollBack();$saved=$this->receipt($actor,$operationId);if($saved!==false){if(!hash_equals((string)$saved['request_sha256'],$hash))$this->fail('OPERATION_CONFLICT');return json_decode((string)$saved['result_json'],true,flags:JSON_THROW_ON_ERROR);}throw$e;}
        catch(\Throwable$e){if($transaction->isActive)$transaction->rollBack();throw$e;}
    }

    private function authorize(int$actor):void{$ok=$this->db->createCommand("SELECT 1 FROM `{$this->prefix}fm2_pilot_users` u JOIN `{$this->prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$this->prefix}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=:a AND u.status=1 AND BINARY u.activation_state='active' AND r.status=1 AND BINARY rp.permission='otiz.manage' LIMIT 1",[':a'=>$actor])->queryScalar();if($ok===false)$this->fail('FORBIDDEN');}
    private function receipt(int$a,string$o):array|false{return$this->db->createCommand("SELECT request_sha256,result_json FROM `{$this->prefix}fm2_otiz_settlement_operations` WHERE actor_user_id=:a AND operation_id=:o",[':a'=>$a,':o'=>$o])->queryOne();}
    private function snapshot(int$id):array{$r=$this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshots` WHERE id=:id FOR UPDATE",[':id'=>$id])->queryOne();if($r===false)$this->fail('NOT_FOUND');if($r['status']!=='accepted')$this->fail('SNAPSHOT_NOT_ACCEPTED');return$r;}
    private function object(int$s,int$o,bool$locked=false):array{$this->snapshot($s);$r=$this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id=:s AND object_id=:o".($locked?' FOR UPDATE':''),[':s'=>$s,':o'=>$o])->queryOne();if($r===false)$this->fail('NOT_FOUND');return$r;}
    private function lockObjects(array$ids):void{sort($ids,SORT_NUMERIC);foreach(array_unique($ids)as$id){$this->db->createCommand("INSERT IGNORE INTO `{$this->prefix}fm2_otiz_settlement_locks`(object_id)VALUES(:id)",[':id'=>$id])->execute();$this->db->createCommand("SELECT object_id FROM `{$this->prefix}fm2_otiz_settlement_locks` WHERE object_id=:id FOR UPDATE",[':id'=>$id])->queryScalar();}}
    private function budget(int$o,int$accrued):int{$closed=(int)$this->db->createCommand("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id=:o",[':o'=>$o])->queryScalar();return max(0,$accrued-$closed);}
    private function closure(int$s,int$o,int$p,int$d,int$l,string$b,string$a,int$actor,?int$reverse):int{$now=($this->clock)();$date=substr($now,0,10);$this->db->createCommand("INSERT INTO `{$this->prefix}fm2_pilot_otiz_payment_closures`(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at,reverses_payment_closure_id)VALUES(:s,:o,:date,:p,:d,:l,:b,:a,:actor,:now,:r)",compact('s','o','date','p','d','l','b','a','actor','now')+['r'=>$reverse])->execute();return(int)$this->db->getLastInsertID();}
    private function event(int$s,?int$o,string$type,array$payload,int$actor):void{$this->db->createCommand("INSERT INTO `{$this->prefix}fm2_pilot_otiz_events`(snapshot_id,object_id,event_type,payload_json,actor_user_id,occurred_at)VALUES(:s,:o,:e,:p,:a,:t)",[':s'=>$s,':o'=>$o,':e'=>$type,':p'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),':a'=>$actor,':t'=>($this->clock)()])->execute();}
    private function fail(string$reason):never{throw new DomainException($reason);}
}
