<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

final class MariaDbSnapshotStore
{
    private MariaDbSnapshotPublicationRecords $records;
    public function __construct(private \mysqli $db,private string $prefix)
    {
        if(!preg_match('/^[A-Za-z0-9_]*$/D',$prefix)) throw new \InvalidArgumentException('Invalid table prefix');
        \FMonitor2\InstallationProcess\OtizPublicationSchemaMigration::assertReady($db,$prefix);
        $this->records=new MariaDbSnapshotPublicationRecords($db,$prefix);
    }

    public function authorize(int $actor): void
    {
        $policy=\FMonitor2\IdentityAccess\MariaDbPilotAccessPolicy::class;
        if(!$policy::grants($policy::forUser($this->db,$this->prefix,$actor),$policy::OTIZ_MANAGE)) {
            throw new \DomainException('FORBIDDEN');
        }
    }

    public function transaction(\Closure $action): mixed
    {
        if((int)$this->db->query('SELECT @@in_transaction AS active')->fetch_assoc()['active'] !== 0) throw new \LogicException('Publication owns its transaction');
        $this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->db->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
        try{$result=$action();$this->db->commit();return $result;}
        catch(\Throwable $error){$this->db->rollback();throw $error;}
    }

    public function replay(int $actor,string $operation,string $fingerprint): ?int
    {
        return $this->records->replay($actor,$operation,$fingerprint);
    }

    public function createDraft(int $actor,string $date,string $now): int
    {
        $p=$this->prefix;
        $s=$this->db->prepare("SELECT id FROM `{$p}fm2_pilot_otiz_snapshots` WHERE status='accepted' AND report_date<? ORDER BY report_date DESC,id DESC LIMIT 1");
        $s->bind_param('s',$date);$s->execute();$row=$s->get_result()->fetch_assoc();$previous=$row===null?null:(int)$row['id'];
        $rules=PremiumCalculation::VERSION;
        $s=$this->db->prepare("INSERT INTO `{$p}fm2_pilot_otiz_snapshots`(report_date,status,previous_snapshot_id,rules_version,calculated_at,calculated_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash) VALUES(?,'draft',?,?,?,?,0,0,0,'pending')");
        $s->bind_param('sissi',$date,$previous,$rules,$now,$actor);$s->execute();return (int)$s->insert_id;
    }

    public function populate(int $id,string $date,\Closure $inputs): void
    {
        (new MariaDbSnapshotBuilder($this->db,$this->prefix,$inputs))->populate($id,$date);
    }

    public function publish(int $id,int $actor,string $operation,string $fingerprint,string $now): void
    {
        $this->records->publish($id,$actor,$operation,$fingerprint,$now);
    }

    public function lockedSnapshot(int $id): array
    {
        $r=$this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshots` WHERE id={$id} FOR UPDATE")->fetch_assoc();
        if($r===null) throw new \DomainException('NOT_FOUND');return $r;
    }

    public function assertPublished(int $id): void { $this->records->assertPublished($id); }

    public function acceptPublished(int $id,int $actor,string $now): void
    {
        $p=$this->prefix;
        $count=(int)$this->db->query("SELECT COUNT(*) n FROM `{$p}fm2_pilot_otiz_snapshot_issues` WHERE snapshot_id={$id} AND severity='blocker' AND state='open'")->fetch_assoc()['n'];
        if($count>0) throw new \DomainException('BLOCKERS');
        $s=$this->db->prepare("UPDATE `{$p}fm2_pilot_otiz_snapshots` SET status='accepted',accepted_at=?,accepted_by_user_id=? WHERE id=?");
        $s->bind_param('sii',$now,$actor,$id);$s->execute();
        $hash=$this->db->query("SELECT content_hash FROM `{$p}fm2_pilot_otiz_snapshots` WHERE id={$id}")->fetch_assoc()['content_hash'];
        $this->records->event($id,$actor,$now,'snapshot_accepted',['hash'=>$hash]);
    }

    public function read(int $id): array { return $this->records->read($id); }
    public function history(): array
    {
        return $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshots` ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    }
}
