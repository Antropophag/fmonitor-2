<?php

declare(strict_types=1);

require_once __DIR__.'/PremiumCalculation.php';

final class HistoricalPremiumReplayAdapter
{
    public const VERSION='historical-premium-replay-adapter-v1';

    public static function replay(array $operands,array $closures,array $actualPayouts,array $exclusions=[],array $balanceAssertions=[]):array
    {
        $calculation=PremiumCalculation::calculate($operands,['closures'=>$closures,'actualPayouts'=>$actualPayouts],$exclusions);
        return ['adapterVersion'=>self::VERSION,'calculation'=>$calculation,
            'payoutComparison'=>$actualPayouts===[]?['state'=>'unavailable','discrepancyCents'=>null,'reason'=>'ACTUAL_PAYOUT_EVIDENCE_ABSENT']:
                ['state'=>$calculation['amounts']['payoutDiscrepancyCents']===0?'matched':'discrepant','discrepancyCents'=>$calculation['amounts']['payoutDiscrepancyCents'],'reason'=>$calculation['amounts']['payoutDiscrepancyCents']===0?'ACTUAL_PAYOUT_MATCH':'ACTUAL_PAYOUT_DIFFERS'],
            'balanceAssertions'=>['count'=>count($balanceAssertions),'usedAsActualPayout'=>false]];
    }
}
