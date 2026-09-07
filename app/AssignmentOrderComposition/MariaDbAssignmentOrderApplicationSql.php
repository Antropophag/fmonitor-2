<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class MariaDbAssignmentOrderApplicationSql
{
    public function __construct(public \mysqli $db, public string $prefix)
    {
        if (PHP_INT_SIZE !== 8 || preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1)
            throw new AssignmentOrderApplicationConfigurationUnavailable();
    }
    public function table(string $suffix): string { return '`'.$this->prefix.$suffix.'`'; }
    public function rows(string $query, array $values = []): array
    {
        $statement=$this->db->prepare($query); if(!$statement) throw new \RuntimeException();
        try { if(!$statement->execute($values)) throw new \RuntimeException(); $result=$statement->get_result();
            if(!$result instanceof \mysqli_result) throw new \RuntimeException(); return $result->fetch_all(MYSQLI_ASSOC);
        } finally { $statement->close(); }
    }
    public function execute(string $query, array $values = []): int
    {
        $statement=$this->db->prepare($query); if(!$statement) throw new \RuntimeException();
        try { if(!$statement->execute($values)) throw new \RuntimeException(); return $statement->affected_rows; }
        finally { $statement->close(); }
    }
    public function idle(): bool { return (string)($this->rows('SELECT @@in_transaction n')[0]['n']??'1')==='0'; }
    public static function positive(mixed $value): int
    { $s=(string)$value;if(!preg_match('/^[1-9][0-9]*$/D',$s)||strlen($s)>19||(int)$s<1)throw new \RuntimeException();return(int)$s; }
    public static function instant(string $db): string
    { $value=preg_replace('/\.0{1,6}$/','',$db);return str_replace(' ','T',(string)$value).'Z'; }
}
