<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
use FMonitor2\InstallationProcess\ErpEquipmentFactsDeliveryConfig;

/** Explicit Jobs configuration; no filesystem, manifest, endpoint or prefix inference. */
final readonly class JobsRuntimeConfiguration
{
    private function __construct(private array $environment) {}
    public static function fromEnvironment(): self
    {
        $environment=getenv();if(!is_array($environment))throw new \InvalidArgumentException();
        $config=new self($environment);
        foreach(['HOST','NAME','USER','PASSWORD'] as $suffix){
            $value=$config->value('FMONITOR_DB_'.$suffix);
            if(str_contains($value,"\0"))throw new \InvalidArgumentException();
        }
        $config->port();$config->prefix();return $config;
    }
    public function value(string $name): string
    {
        $value=$this->environment[$name]??null;
        if(!is_string($value)||$value==='')throw new \InvalidArgumentException('Invalid Jobs configuration.');
        return $value;
    }
    public function optionalValue(string $name): ?string
    {
        $value=$this->environment[$name]??null;
        if($value===null||$value==='')return null;
        if(!is_string($value)||str_contains($value,"\0")||preg_match('/[\x00-\x1f\x7f]/',$value)===1)throw new \InvalidArgumentException('Invalid Jobs configuration.');
        return $value;
    }
    public function secretFileValue(string $name): string
    {
        $path=$this->value($name);$before=@lstat($path);
        if($path[0]!=='/'||$before===false||(($before['mode']&0170000)!==0100000)||is_link($path)||$before['size']<1||$before['size']>4096)throw new \InvalidArgumentException('Invalid Jobs configuration.');
        $handle=@fopen($path,'rb');if($handle===false)throw new \InvalidArgumentException('Invalid Jobs configuration.');
        try{$opened=fstat($handle);$bytes=stream_get_contents($handle,4097);$after=@lstat($path);}finally{fclose($handle);}
        if($opened===false||$after===false||!is_string($bytes)||strlen($bytes)>4096||$before['dev']!==$opened['dev']||$before['ino']!==$opened['ino']||$after['dev']!==$opened['dev']||$after['ino']!==$opened['ino'])throw new \InvalidArgumentException('Invalid Jobs configuration.');
        $value=trim($bytes);if($value===''||str_contains($value,"\0")||preg_match('/[\x00-\x1f\x7f]/',$value)===1)throw new \InvalidArgumentException('Invalid Jobs configuration.');return$value;
    }
    public function port(): int
    {
        $value=$this->value('FMONITOR_DB_PORT');
        if(preg_match('/^[1-9][0-9]{0,4}$/D',$value)!==1||(int)$value>65535)throw new \InvalidArgumentException();
        return (int)$value;
    }
    public function prefix(): string
    {
        return JobValues::text($this->environment['FMONITOR_PROCESS_TABLE_PREFIX']??null,'/^[A-Za-z0-9_]{0,25}$/D');
    }
    public function instance(): string
    {
        return JobValues::text($this->value('FMONITOR_SESSION_INSTANCE'),'/^[A-Za-z0-9][A-Za-z0-9_.-]{0,63}$/D');
    }
    public function erpEquipmentFacts(): ErpEquipmentFactsDeliveryConfig
    {
        return new ErpEquipmentFactsDeliveryConfig(
            $this->value('FMONITOR_ERP_HOST'),$this->value('FMONITOR_ERP_DATABASE'),
            $this->value('FMONITOR_ERP_USER'),$this->value('FMONITOR_ERP_PASSWORD'),
            $this->bounded('FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS',1,10000),
            $this->bounded('FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS',1,60),
            $this->bounded('FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE',1,500),
        );
    }
    public function erpEquipmentFactsHmacKey(): string
    {
        $value=$this->value('FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY');
        if(strlen($value)<32||strlen($value)>4096||preg_match('/[\x00-\x1f\x7f]/',$value)===1)throw new \InvalidArgumentException('CONFIGURATION_INVALID');
        return$value;
    }
    private function bounded(string$name,int$minimum,int$maximum):int
    {
        $value=$this->value($name);if(preg_match('/^[1-9][0-9]{0,4}$/D',$value)!==1||(int)$value<$minimum||(int)$value>$maximum)throw new \InvalidArgumentException('CONFIGURATION_INVALID');return(int)$value;
    }
}
