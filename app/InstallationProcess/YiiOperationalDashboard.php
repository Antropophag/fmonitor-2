<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final readonly class YiiOperationalDashboard
{
    private const STAGES = ['needs_assignment_order','ready_to_open','installation','document_closeout','completed','needs_assignment_change'];
    private const START_RISKS = ['overdue_start','order_0_7','order_8_14','ready_0_14','opened_0_14'];

    public function __construct(private YiiOperationalDashboardStore $store)
    {
    }

    public function read(int $actorId, string $cutoff): array
    {
        if (!$this->store->authorized($actorId)) {
            throw new \DomainException('ACCESS_DENIED');
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $cutoff, new \DateTimeZone('Europe/Moscow'));
        if ($date === false || $date->format('Y-m-d') !== $cutoff) {
            throw new \InvalidArgumentException('Invalid dashboard cutoff.');
        }
        $monday = $date->modify('monday this week');
        $weeks = [];
        for ($index = 0; $index < 6; $index++) {
            $start = $monday->modify('+' . $index . ' weeks');
            $weeks[] = [$start->format('Y-m-d'), $start->modify('+6 days')->format('Y-m-d')];
        }
        $result = $this->store->read($cutoff, $date->modify('+13 days')->format('Y-m-d'), $weeks);
        if (!$this->valid($result, $cutoff, $weeks)) {
            throw new \RuntimeException('Malformed dashboard chart aggregate.');
        }
        return $result;
    }

    private function valid(array $result, string $cutoff, array $weeks): bool
    {
        if (array_keys($result) !== ['cutoff','total','active','overdueCount','upcomingCount','upcoming','overdue','charts']
            || $result['cutoff'] !== $cutoff
            || !$this->nonnegative($result['total']) || !$this->nonnegative($result['active'])
            || !$this->nonnegative($result['overdueCount']) || !$this->nonnegative($result['upcomingCount'])
            || !is_array($result['upcoming']) || !is_array($result['overdue'])
            || !$this->rows($result['upcoming']) || !$this->rows($result['overdue'])
            || !is_array($result['charts'])
            || array_keys($result['charts']) !== ['stages','weeks','startRisk']) return false;

        $stages=$result['charts']['stages'];$weekRows=$result['charts']['weeks'];$risks=$result['charts']['startRisk'];
        if (!is_array($stages)||!is_array($weekRows)||!is_array($risks)
            || count($stages)!==6||count($weekRows)!==6||count($risks)!==5) return false;
        $stageValues=[];
        foreach($stages as$i=>$item){if(!is_array($item)||array_keys($item)!==['key','label','value']||$item['key']!==self::STAGES[$i]||!is_string($item['label'])||$item['label']===''||!$this->nonnegative($item['value']))return false;$stageValues[$item['key']]=$item['value'];}
        foreach($weekRows as$i=>$item){if(!is_array($item)||array_keys($item)!==['start','end','starts','finishes']||$item['start']!==$weeks[$i][0]||$item['end']!==$weeks[$i][1]||!$this->nonnegative($item['starts'])||!$this->nonnegative($item['finishes']))return false;$start=\DateTimeImmutable::createFromFormat('!Y-m-d',$item['start'],new \DateTimeZone('Europe/Moscow'));$end=\DateTimeImmutable::createFromFormat('!Y-m-d',$item['end'],new \DateTimeZone('Europe/Moscow'));if($start===false||$end===false||$start->format('N')!=='1'||$end->format('N')!=='7'||$start->modify('+6 days')->format('Y-m-d')!==$item['end']||($i>0&&$weekRows[$i-1]['end']!==$start->modify('-1 day')->format('Y-m-d')))return false;}
        foreach($risks as$i=>$item){if(!is_array($item)||array_keys($item)!==['key','label','value']||$item['key']!==self::START_RISKS[$i]||!is_string($item['label'])||$item['label']===''||!$this->nonnegative($item['value']))return false;}
        $activeStages=$stageValues['installation']+$stageValues['document_closeout']+$stageValues['needs_assignment_change'];
        return array_sum($stageValues)===$result['total'] && $activeStages===$result['active'];
    }

    private function rows(array $rows): bool
    {
        foreach($rows as$row)if(!is_array($row)||array_keys($row)!==['id','registrationNumber','date']||!is_int($row['id'])||$row['id']<1||!is_string($row['registrationNumber'])||!is_string($row['date'])||!self::date($row['date']))return false;
        return true;
    }

    private function nonnegative(mixed $value): bool { return is_int($value)&&$value>=0; }
    private static function date(string $value): bool { $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value,new \DateTimeZone('Europe/Moscow'));return $date!==false&&$date->format('Y-m-d')===$value; }
}
