<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

/** Read-only presentation economics. Preserves the existing row/global distinction. */
final class ObjectEconomy
{
    /** The global fold needs no SQL norm join: parse each compact input once. */
    public static function norms(array $row,NativePremiumNorms $norms): array
    {
        $floors=filter_var(json_decode((string)$row['floor_json'],true),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $capacity=filter_var(json_decode((string)$row['capacity_json'],true),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $text=mb_strtolower((string)$row['type_text'],'UTF-8');
        $type=str_contains($text,'груз')?'cargo':(str_contains($text,'пассаж')?'passenger':null);
        $row['premium_cents']=$floors===false||$capacity===false?null:$norms->premiumCents($type,(int)$floors,(int)$capacity);
        $row['shaft_bp']=$norms->shaftBasisPoints((string)$row['material_text']);
        return $row;
    }

    public static function decorate(array $row): array
    {
        $row['premium_cents']=$row['premium_cents']===null?null:(int)$row['premium_cents'];
        $row['shaft_bp']=$row['shaft_bp']===null?null:(int)$row['shaft_bp'];
        $row['fund_cents']=$row['premium_cents']!==null&&$row['shaft_bp']!==null
            ?intdiv($row['premium_cents']*$row['shaft_bp'],10000):null;
        $row['deadline_penalty_cents']=(int)$row['has_calculation']===1
            ?max(0,(int)$row['trace_progress_cents']-(int)$row['accrued_cents']):0;
        $row['state']=$row['fund_cents']===null?'missing_norm':($row['snapshot_id']===null?'planned':(string)$row['calculation_state']);
        if($row['state']==='ready'&&(int)$row['pool_cents']>0&&(int)$row['snapshot_closed_cents']>=(int)$row['pool_cents'])$row['state']='completed';
        return $row;
    }

    public static function emptySummary(): array
    { return ['total'=>0,'fund'=>0,'earned'=>0,'paid'=>0,'penalties'=>0,'balance'=>0,'blocked'=>0,'calculated'=>0]; }

    public static function accumulate(array &$summary,array $row): void
    {
        $summary['total']++;
        if($row['fund_cents']!==null){$summary['fund']+=$row['fund_cents'];$summary['calculated']++;}
        $summary['earned']+=(int)$row['accrued_cents'];
        $summary['paid']+=(int)$row['paid_cents'];
        $summary['penalties']+=(int)$row['discipline_cents']+$row['deadline_penalty_cents'];
        $summary['balance']+=max(0,(int)$row['fund_cents']-(int)$row['paid_cents']-(int)$row['discipline_cents']-(int)$row['deadline_cents']);
        if($row['calculation_state']==='blocked')$summary['blocked']++;
    }
}
