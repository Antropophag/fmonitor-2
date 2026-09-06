<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

/** Native operations; transaction ownership stays with the calling adapter. */
final readonly class MariaDbSelectionSql
{
    public function __construct(public \mysqli $db,public string $prefix)
    { if(preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)!==1)throw new \InvalidArgumentException('Invalid selection table prefix.'); }
    public function table(string $name): string { return '`'.$this->prefix.$name.'`'; }
    public function rows(string $sql,array $values=[]): array
    {
        $s=$this->db->prepare($sql);if($s===false)throw new \RuntimeException('Selection persistence unavailable.');
        try { if(!$s->execute($values))throw new \mysqli_sql_exception('Selection persistence unavailable.',$s->errno);
            $r=$s->get_result();if(!$r instanceof \mysqli_result)throw new \RuntimeException('Selection read unavailable.');
            try{return $r->fetch_all(MYSQLI_ASSOC);}finally{$r->free();}
        }finally{$s->close();}
    }
    public function insert(string $table,array $row): void
    {
        $columns=implode(',',array_map(static fn($k)=>'`'.$k.'`',array_keys($row)));
        $s=$this->db->prepare('INSERT INTO '.$this->table($table).' ('.$columns.') VALUES ('.implode(',',array_fill(0,count($row),'?')).')');
        if($s===false)throw new \RuntimeException('Selection persistence unavailable.');
        try {if(!$s->execute(array_values($row)))throw new \mysqli_sql_exception('Selection persistence unavailable.',$s->errno);}finally{$s->close();}
    }
    public function idle(): bool { return (string)$this->rows('SELECT @@in_transaction active')[0]['active']==='0'; }
    public function ready(): bool
    {
        return $this->idle() && \FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigration::isReady($this->db,$this->prefix);
    }
    public function snapshot(callable $read): mixed
    {
        if(!$this->idle())throw new \RuntimeException('Ambient selection transaction.');
        if(!$this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ') || !$this->db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY|MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT))throw new \RuntimeException('Selection snapshot unavailable.');
        try{return $read();}finally{if(!$this->db->rollback())throw new \RuntimeException('Selection snapshot release unavailable.');}
    }
    public static function number(mixed $value,int $min=1,int $max=PHP_INT_MAX): int
    {
        $text=(string)$value;
        if(!preg_match('/^(0|[1-9][0-9]*)$/D',$text))throw new \RuntimeException('Invalid selection number.');
        if(strlen($text)>strlen((string)$max)||(strlen($text)===strlen((string)$max)&&strcmp($text,(string)$max)>0))throw new \OverflowException('Selection capacity exhausted.');
        $n=(int)$text;if($n<$min)throw new \RuntimeException('Invalid selection number.');return $n;
    }
    public function generatedId(): int { return self::number($this->rows('SELECT LAST_INSERT_ID() id')[0]['id']); }
    public static function time(SelectionInstant $at): string
    { if(!SelectionScalar::utc($at->utcRfc3339Seconds))throw new \RuntimeException('Invalid selection time.');return str_replace(['T','Z'],[' ','.000000'],$at->utcRfc3339Seconds); }
    public static function instant(string $time): string
    { $utc=str_replace(' ','T',$time);$utc=preg_replace('/\.000000$/','',$utc).'Z';if(!SelectionScalar::utc($utc))throw new \RuntimeException('Invalid stored selection time.');return $utc; }
}
