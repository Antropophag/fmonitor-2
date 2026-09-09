<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Closed CLI grammar, validated before configuration or database access. */
final readonly class JobsRuntimeRequest
{
    private function __construct(public string $mode,public array $options) {}
    public static function parse(array $arguments): self
    {
        $mode=array_shift($arguments);
        $flags=match($mode){
            'schedule-once'=>['now-utc'], 'worker','scheduler','health'=>[],
            'list-failed'=>['page','limit'], 'retry'=>['job-id','operation-id','now-utc'],
            default=>throw new \InvalidArgumentException(),
        };
        if(count($arguments)!==count($flags)*2)throw new \InvalidArgumentException();
        $options=[];
        for($i=0;$i<count($arguments);$i+=2){
            $key=$arguments[$i];$value=$arguments[$i+1];
            if(!is_string($key)||!str_starts_with($key,'--')||!is_string($value))throw new \InvalidArgumentException();
            $key=substr($key,2);if(!in_array($key,$flags,true)||array_key_exists($key,$options))throw new \InvalidArgumentException();
            $options[$key]=$value;
        }
        foreach(['page','limit','job-id'] as $key)if(isset($options[$key])){
            if(preg_match('/^[1-9][0-9]*$/D',$options[$key])!==1)throw new \InvalidArgumentException();
            $number=filter_var($options[$key],FILTER_VALIDATE_INT);
            if($number===false)throw new \InvalidArgumentException();
            $options[$key]=JobValues::number($number,1,$key==='limit'?100:PHP_INT_MAX);
        }
        if(isset($options['now-utc']))JobValues::date($options['now-utc']);
        if(isset($options['operation-id']))JobValues::text($options['operation-id'],'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D');
        return new self($mode,$options);
    }
}
