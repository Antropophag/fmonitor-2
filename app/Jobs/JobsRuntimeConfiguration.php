<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

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
}
