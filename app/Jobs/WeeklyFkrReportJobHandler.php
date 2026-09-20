<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final class WeeklyFkrReportJobHandler
{
    private \Closure $generate;
    private \Closure $append;
    public function __construct(callable $generate,callable $append){$this->generate=\Closure::fromCallable($generate);$this->append=\Closure::fromCallable($append);}
    public function handle(array $payload): array
    {
        $week=$payload['reportWeek']??null;if(!is_string($week)||preg_match('/^\d{4}-\d{2}-\d{2}$/D',$week)!==1)throw new \InvalidArgumentException('INVALID_REPORT_WEEK');
        $intents=[];foreach(($this->generate)($week)as$item){$identity=(string)$item['identity'];$report=$item['report'];$intent=['idempotencyKey'=>'weekly-fkr-report/v1/'.$week.'/'.$identity,'recipientIdentity'=>$identity,'subject'=>$report['subject'],'html'=>$report['html'],'text'=>$report['text']];$intents[]=($this->append)($intent);}
        return ['reportWeek'=>$week,'intents'=>$intents];
    }
}
