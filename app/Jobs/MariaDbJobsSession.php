<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** @internal Owns short queue transactions; never spans handler or transport calls. */
final class MariaDbJobsSession
{
    private ?\Closure $clock;
    public function __construct(public readonly \mysqli $db,public readonly string $prefix,?callable $clock=null)
    {
        JobValues::text($prefix,'/^[A-Za-z0-9_]{0,25}$/D');
        $this->clock=$clock===null?null:\Closure::fromCallable($clock);
    }
    public function now(): string
    {
        $value=$this->clock!==null?($this->clock)():$this->db->query("SELECT DATE_FORMAT(UTC_TIMESTAMP(6),'%Y-%m-%dT%H:%i:%s.%fZ')")->fetch_column();
        return JobValues::date($value);
    }
    public function transaction(callable $operation): mixed
    {
        if((int)$this->db->query('SELECT @@in_transaction')->fetch_column()!==0)throw new \LogicException('Jobs requires an idle connection.');
        $wait=(int)$this->db->query('SELECT @@SESSION.innodb_lock_wait_timeout')->fetch_column();
        $owned=false;
        try{
            $this->db->query('SET SESSION innodb_lock_wait_timeout=2');
            $this->db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->db->begin_transaction();$owned=true;
            $result=$operation();$this->db->commit();$owned=false;
            return $result;
        }catch(\Throwable $error){
            if($owned)try{$this->db->rollback();}catch(\Throwable){}
            throw new \RuntimeException('JOBS_UNAVAILABLE',0,$error);
        }finally{$this->db->query('SET SESSION innodb_lock_wait_timeout='.$wait);}
    }
    public function readOnly(callable $operation): mixed
    {
        if((int)$this->db->query('SELECT @@in_transaction')->fetch_column()!==0)throw new \LogicException('Jobs requires an idle connection.');
        $owned=false;
        try{
            $this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY|MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);$owned=true;
            $result=$operation();$this->db->rollback();$owned=false;return $result;
        }catch(\Throwable $error){
            if($owned)try{$this->db->rollback();}catch(\Throwable){}
            throw new \RuntimeException('JOBS_UNAVAILABLE',0,$error);
        }
    }
    public function execute(string $sql,array $parameters=[]): \mysqli_stmt
    {
        $statement=$this->db->prepare($sql);
        if($parameters!==[]){$types='';foreach($parameters as $value)$types.=is_int($value)?'i':'s';$statement->bind_param($types,...$parameters);}
        $statement->execute();return $statement;
    }
    public function rows(string $sql,array $parameters=[]): array
    {
        return $this->execute($sql,$parameters)->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function table(string $name): string {return '`'.$this->prefix.$name.'`';}
    public function event(array $job,string $type,string $now,array $details=[]): void
    {
        $this->execute('INSERT INTO '.$this->table('fm2_job_events').'(job_id,event_type,attempt,occurred_at_utc,worker_id,lease_token,details_json) VALUES(?,?,?,?,?,?,?)',[
            (int)$job['job_id'],$type,(int)$job['attempt'],$now,$job['worker_id']??null,$job['lease_token']??null,JobValues::json($details),
        ]);
    }
}
