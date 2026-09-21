<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Bounded read-only SQL Server transport for the two owned ERP queries. */
final class MariaDbSqlServerEquipmentFactsTransport
{
    private \PDO $connection;
    public function __construct(ErpEquipmentFactsDeliveryConfig $config)
    {
        $this->connection=new \PDO('sqlsrv:Server='.$config->host.';Database='.$config->database.';ApplicationIntent=ReadOnly',$config->user,$config->password,[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
    }
    public function __invoke(string $sql,array $options):array
    {
        if(($options['readOnly']??null)!==true||!preg_match('/\bSELECT\b/iu',$sql))throw new \RuntimeException();
        $bounded=preg_replace('/\bSELECT\b/iu','SELECT TOP ('.((int)$options['maxRows']+1).')',$sql,1);if(!is_string($bounded))throw new \RuntimeException();
        $statement=$this->connection->prepare($bounded,[\PDO::SQLSRV_ATTR_QUERY_TIMEOUT=>(int)$options['timeoutSeconds']]);$statement->execute();$rows=[];
        while(($row=$statement->fetch(\PDO::FETCH_ASSOC))!==false){$rows[]=$row;if(count($rows)>$options['maxRows'])throw new \RuntimeException();}
        return$rows;
    }
}
