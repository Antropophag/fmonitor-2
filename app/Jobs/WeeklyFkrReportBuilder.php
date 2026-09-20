<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final readonly class WeeklyFkrReportBuilder
{
    public function __construct(
        private WeeklyFkrRecipientDirectory $directory,
        private WeeklyFkrReportSource $source,
        private WeeklyFkrOpeningEligibility $eligibility,
    ) {}

    public function build(string $recipientId, string $generatedAtUtc, string $baseUrl): array
    {
        $recipient=$this->directory->recipient($recipientId);
        if(!WeeklyFkrRecipientEligibility::eligible($recipient,true))throw new \RuntimeException('RECIPIENT_INELIGIBLE');
        if(filter_var($baseUrl,FILTER_VALIDATE_URL)===false||!str_starts_with($baseUrl,'https://'))throw new \InvalidArgumentException('PUBLIC_BASE_URL_INVALID');
        $local=(new \DateTimeImmutable($generatedAtUtc))->setTimezone(new \DateTimeZone('Europe/Moscow'));
        $periods=[
            'planStart'=>$local->modify('monday this week')->format('Y-m-d'),
            'planEnd'=>$local->modify('sunday this week')->format('Y-m-d'),
            'progressStart'=>$local->modify('monday last week')->format('Y-m-d'),
            'progressEnd'=>$local->modify('sunday last week')->format('Y-m-d'),
        ];
        $objects=$this->source->allObjectsAsOf($periods['progressStart'],$periods['progressEnd'],$generatedAtUtc);
        $sections=['plannedOpenings'=>[],'plannedClosings'=>[],'progress'=>[],'overdue'=>[],'attention'=>[]];
        foreach($objects as$row)$this->classify($sections,$row,$periods,$generatedAtUtc,$baseUrl);
        foreach(array_keys($sections)as$name)usort($sections[$name],self::byDate(...));
        return['recipientIdentity'=>$recipientId,'recipientEmail'=>$recipient['email'],'generatedAtUtc'=>$generatedAtUtc,'periods'=>$periods,'sections'=>$sections];
    }

    private function classify(array &$sections,array $row,array $periods,string $generatedAtUtc,string $baseUrl):void
    {
        $common=[
            'objectId'=>(string)$row['id'],
            'registrationNumber'=>(string)$row['registrationNumber'],
            'address'=>(string)$row['address'],
            'url'=>rtrim($baseUrl,'/').'/pilot/object/'.rawurlencode((string)$row['id']),
        ];
        $opening=$row['openingPlanDate']??null;$closing=$row['closingPlanDate']??null;
        if(!$row['openingCompleted']&&$this->within($opening,$periods['planStart'],$periods['planEnd'])){
            $sections['plannedOpenings'][]=$common+[
                'date'=>$opening,'label'=>(string)$row['openingStatus'],
            ];
        }
        if(!$row['closingCompleted']&&$this->within($closing,$periods['planStart'],$periods['planEnd']))$sections['plannedClosings'][]=$common+$this->closingRow($row,$closing);
        $this->appendProgress($sections,$common,$row,$periods['progressEnd']);
        $this->appendOverdue($sections,$common,$row,$opening,$closing,$periods['planStart']);
        $this->appendAttention($sections,$common,$row,$opening,$closing,$periods,$generatedAtUtc);
    }

    private function closingRow(array$row,string$date):array
    {
        $work=$row['work'];$documents=$row['documents'];
        return['date'=>$date,'label'=>'Плановое закрытие','work'=>$work,'documents'=>$documents,'total'=>$work===null||$documents===null?null:$work+$documents];
    }

    private function appendProgress(array&$sections,array$common,array$row,string$date):void
    {
        if(!$row['openingCompleted']||$row['closingCompleted'])return;
        [$workStart,$documentsStart]=$this->progressValues($row,'Start');[$workEnd,$documentsEnd]=$this->progressValues($row,'End');
        $totalStart=$workStart===null||$documentsStart===null?null:$workStart+$documentsStart;$totalEnd=$workEnd===null||$documentsEnd===null?null:$workEnd+$documentsEnd;
        $delta=$totalStart===null||$totalEnd===null?null:$totalEnd-$totalStart;
        if($delta===0||($workStart===null&&$documentsStart===null&&$workEnd===null&&$documentsEnd===null))return;
        $sections['progress'][]=$common+[
            'date'=>$date,
            'label'=>$delta===null?'Недостаточно данных для оценки':'Изменение: '.sprintf('%+d',$delta).' п.п.',
            'workStart'=>$workStart,'workEnd'=>$workEnd,
            'documentsStart'=>$documentsStart,'documentsEnd'=>$documentsEnd,
            'totalStart'=>$totalStart,'totalEnd'=>$totalEnd,'delta'=>$delta,
        ];
    }

    private function appendOverdue(array&$sections,array$common,array$row,mixed$opening,mixed$closing,string$today):void
    {
        if(!$row['openingCompleted']&&is_string($opening)&&$opening<$today){
            $sections['overdue'][]=$common+$this->overdueRow($opening,'Открытие просрочено',$today);
        }
        if(!$row['closingCompleted']&&is_string($closing)&&$closing<$today){
            $sections['overdue'][]=$common+$this->overdueRow($closing,'Закрытие просрочено',$today);
        }
    }

    private function overdueRow(string$date,string$label,string$today):array
    {
        return['date'=>$date,'label'=>$label,'daysLate'=>(new \DateTimeImmutable($date))->diff(new \DateTimeImmutable($today))->days];
    }

    private function appendAttention(array&$sections,array$common,array$row,mixed$opening,mixed$closing,array$periods,string$generatedAtUtc):void
    {
        if(!$row['openingCompleted']&&$this->within($opening,$periods['planStart'],$periods['planEnd'])){
            $reasons=$this->eligibility->reasons((string)$row['id'],$generatedAtUtc);
            if($reasons!==[])$sections['attention'][]=$common+[
                'date'=>$opening,'label'=>'Не готовы основания для открытия',
                'reasons'=>array_values($reasons),
            ];
        }
        if($row['closingCompleted']||!$this->within($closing,$periods['planStart'],$periods['planEnd']))return;
        $reasons=$this->closingReasons($row);
        if($reasons!==[])$sections['attention'][]=$common+['date'=>$closing,'label'=>'Недостаточная готовность к закрытию','reasons'=>$reasons];
    }

    private function closingReasons(array$row):array
    {
        $work=$row['work'];$documents=$row['documents'];
        if($work===null||$documents===null)return['Недостаточно данных для оценки'];
        $reasons=[];
        if($work<85)$reasons[]='Работы: '.$work.' из 85';
        if($documents<15)$reasons[]='Документы: '.$documents.' из 15';
        foreach($row['violations']as$violation)$reasons[]=(string)$violation;
        return$reasons;
    }

    private function progressValues(array$row,string$suffix):array
    {
        $value=$row['progress'.$suffix]??null;return is_array($value)?[$value['work']??null,$value['documents']??null]:[null,null];
    }
    private function within(mixed$date,string$start,string$end):bool{return is_string($date)&&$date>=$start&&$date<=$end;}
    private static function byDate(array$a,array$b):int
    {
        return strcmp($a['date'],$b['date'])
            ?:strcmp($a['registrationNumber'],$b['registrationNumber'])
            ?:strcmp($a['objectId'],$b['objectId']);
    }
}
