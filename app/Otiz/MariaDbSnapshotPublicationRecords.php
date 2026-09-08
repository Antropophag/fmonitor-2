<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

/** Durable publication receipt and its verification against stored result rows. */
final class MariaDbSnapshotPublicationRecords
{
    private const VERSION='otiz-publication-v1';
    public function __construct(private \mysqli $db,private string $prefix) {}

    public function replay(int $actor,string $operation,string $fingerprint): ?int
    {
        $s=$this->db->prepare("SELECT snapshot_id,request_sha256 FROM `{$this->prefix}fm2_otiz_publications` WHERE actor_user_id=? AND operation_id=?");
        $s->bind_param('is',$actor,$operation);$s->execute();$r=$s->get_result()->fetch_assoc();
        if($r===null)return null;
        if(!hash_equals($r['request_sha256'],$fingerprint))throw new \DomainException('OPERATION_CONFLICT');
        return (int)$r['snapshot_id'];
    }

    private function contents(int $id): array
    {
        $p=$this->prefix;
        $snapshot=$this->db->query("SELECT * FROM `{$p}fm2_pilot_otiz_snapshots` WHERE id={$id}")->fetch_assoc();
        if($snapshot===null)throw new \DomainException('NOT_FOUND');
        return ['snapshot'=>$snapshot,
            'objects'=>$this->db->query("SELECT * FROM `{$p}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$id} ORDER BY object_id")->fetch_all(MYSQLI_ASSOC),
            'allocations'=>$this->db->query("SELECT * FROM `{$p}fm2_pilot_otiz_snapshot_allocations` WHERE snapshot_id={$id} ORDER BY object_id,tab_id,id")->fetch_all(MYSQLI_ASSOC),
            'issues'=>$this->db->query("SELECT * FROM `{$p}fm2_pilot_otiz_snapshot_issues` WHERE snapshot_id={$id} ORDER BY object_id,id")->fetch_all(MYSQLI_ASSOC)];
    }

    private function digest(array $contents): string
    {
        unset($contents['snapshot']['status'],$contents['snapshot']['accepted_at'],$contents['snapshot']['accepted_by_user_id']);
        $normalize=static function(array $row):array{return array_map(static fn($v)=>$v===null?null:(string)$v,$row);};
        $contents['snapshot']=$normalize($contents['snapshot']);
        foreach(['objects','allocations','issues']as$key)$contents[$key]=array_map($normalize,$contents[$key]);
        return hash('sha256',json_encode($contents,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    }

    public function publish(int $id,int $actor,string $operation,string $fingerprint,string $now): void
    {
        $contents=$this->contents($id);$hash=$this->digest($contents);$version=self::VERSION;
        $objects=count($contents['objects']);$allocations=count($contents['allocations']);$issues=count($contents['issues']);
        $s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_otiz_publications`(snapshot_id,actor_user_id,operation_id,request_sha256,manifest_version,manifest_sha256,object_count,allocation_count,issue_count,published_at) VALUES(?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param('iissssiiis',$id,$actor,$operation,$fingerprint,$version,$hash,$objects,$allocations,$issues,$now);$s->execute();
        $this->event($id,$actor,$now,'draft_calculated',['reportDate'=>$contents['snapshot']['report_date'],'hash'=>$contents['snapshot']['content_hash']]);
    }

    public function assertPublished(int $id): void
    {
        $receipt=$this->db->query("SELECT * FROM `{$this->prefix}fm2_otiz_publications` WHERE snapshot_id={$id}")->fetch_assoc();
        $contents=$this->contents($id);
        if($receipt===null || $receipt['manifest_version']!==self::VERSION
            || !preg_match('/^[a-f0-9]{64}$/D',$contents['snapshot']['content_hash'])
            || (int)$receipt['object_count']!==count($contents['objects'])
            || (int)$receipt['allocation_count']!==count($contents['allocations'])
            || (int)$receipt['issue_count']!==count($contents['issues'])
            || !hash_equals($receipt['manifest_sha256'],$this->digest($contents))) throw new \DomainException('SNAPSHOT_INCOMPLETE');
    }

    public function event(int $id,int $actor,string $now,string $type,array $payload): void
    {
        $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_events`(snapshot_id,object_id,event_type,payload_json,actor_user_id,occurred_at) VALUES(?,NULL,?,?,?,?)");
        $s->bind_param('issis',$id,$type,$json,$actor,$now);$s->execute();
    }

    public function read(int $id): array
    {
        $contents=$this->contents($id);
        $contents['events']=$this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_events` WHERE snapshot_id={$id} ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        $contents['publication']=$this->db->query("SELECT * FROM `{$this->prefix}fm2_otiz_publications` WHERE snapshot_id={$id}")->fetch_assoc();
        return $contents;
    }
}
