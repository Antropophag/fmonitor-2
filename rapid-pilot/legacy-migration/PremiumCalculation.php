<?php

declare(strict_types=1);

final class PremiumCalculation
{
    public const VERSION = 'premium-calculation-v1';

    /**
     * @param array<string,array<string,mixed>> $operands
     * @param array{closures:list<array<string,mixed>>,actualPayouts:list<array<string,mixed>>} $paymentEvidence
     * @param list<array<string,mixed>> $exclusions
     */
    public static function calculate(array $operands, array $paymentEvidence, array $exclusions = []): array
    {
        $reportDate = self::fact($operands, 'reportDate', 'date');
        $premium = self::fact($operands, 'premiumCents', 'money');
        $shaft = self::fact($operands, 'shaftBp', 'coefficientBp');
        $progress = self::fact($operands, 'progressBp', 'basisPoints');
        $deadline = self::fact($operands, 'deadlineDate', 'date');
        $completion = self::fact($operands, 'completionDate', 'nullableDate');

        $comparisonDate = $completion !== null && $completion <= $reportDate ? $completion : $reportDate;
        $daysLate = max(0, (int)((strtotime($comparisonDate . ' UTC') - strtotime($deadline . ' UTC')) / 86400));
        $kss = max(0, 10000 - 100 * $daysLate);
        $fund = intdiv($premium * $shaft, 10000);
        $progressAmount = intdiv($fund * $progress, 10000);
        $accrued = intdiv($progressAmount * $kss, 10000);

        $closed = self::payments($paymentEvidence['closures'] ?? null, 'closedOn');
        if ($closed < 0) throw new InvalidArgumentException('Net closed amount cannot be negative');
        $actualPayoutRows=$paymentEvidence['actualPayouts']??null;
        $actualPaid = self::payments($actualPayoutRows, 'paidOn');
        $hasActualPayoutEvidence=is_array($actualPayoutRows)&&$actualPayoutRows!==[];
        if ($actualPaid < 0) throw new InvalidArgumentException('Net actual payout cannot be negative');
        $pool = max(0, $accrued - $closed);
        $remaining = max(0, $fund - $closed);

        $normalizedExclusions = [];
        foreach ($exclusions as $index => $exclusion) {
            if (!is_array($exclusion) || preg_match('/^[A-Z][A-Z0-9_]{2,79}$/D', (string)($exclusion['code'] ?? '')) !== 1) throw new InvalidArgumentException("Invalid exclusion {$index}");
            self::date((string)($exclusion['effectiveDate'] ?? '')); self::source($exclusion['source'] ?? null);
            $normalizedExclusions[] = ['code'=>(string)$exclusion['code'],'effectiveDate'=>(string)$exclusion['effectiveDate'],'source'=>$exclusion['source']];
        }

        $reasons = [];
        if ($daysLate > 0) $reasons[] = 'DEADLINE_PENALTY';
        if ($pool === 0) $reasons[] = 'NO_NEW_AMOUNT';
        if ($hasActualPayoutEvidence&&$actualPaid !== $accrued) $reasons[] = 'PAYOUT_DISCREPANCY';
        if ($normalizedExclusions !== []) $reasons[] = 'CALCULATION_EXCLUDED';
        $distributable = $normalizedExclusions === [] ? $pool : 0;

        return [
            'calculationVersion'=>self::VERSION,
            'operandEvidence'=>$operands,
            'formulaTrace'=>[
                ['step'=>'fund','formula'=>'premiumCents * shaftBp / 10000','resultCents'=>$fund],
                ['step'=>'progress','formula'=>'fundCents * progressBp / 10000','resultCents'=>$progressAmount],
                ['step'=>'deadline','formula'=>'max(0, 10000 - 100 * daysLate)','daysLate'=>$daysLate,'resultBp'=>$kss],
                ['step'=>'accrued','formula'=>'progressAmountCents * kssBp / 10000','resultCents'=>$accrued],
                ['step'=>'pool','formula'=>'max(0, accruedCents - closedBeforeCents)','resultCents'=>$pool],
            ],
            'reasons'=>$reasons,'exclusions'=>$normalizedExclusions,
            'amounts'=>['fundCents'=>$fund,'accruedCents'=>$accrued,'closedBeforeCents'=>$closed,'poolCents'=>$pool,
                'remainingFundCents'=>$remaining,'distributableCents'=>$distributable,'actualPayoutCents'=>$actualPaid,
                'payoutDiscrepancyCents'=>$hasActualPayoutEvidence?$actualPaid-$accrued:null],
            'progressBp'=>$progress,'kssBp'=>$kss,
            'paymentEvidence'=>$paymentEvidence,
        ];
    }

    private static function fact(array $operands, string $name, string $type): mixed
    {
        $fact=$operands[$name]??null;if(!is_array($fact)||!array_key_exists('value',$fact))throw new InvalidArgumentException("Missing operand {$name}");
        self::date((string)($fact['effectiveDate']??''));self::source($fact['source']??null);$value=$fact['value'];
        if($type==='date'){self::date((string)$value);return(string)$value;}
        if($type==='nullableDate'){if($value===null)return null;self::date((string)$value);return(string)$value;}
        if(!is_int($value))throw new InvalidArgumentException("Operand {$name} must be integer");
        $max=$type==='money'?1000000000000:($type==='coefficientBp'?20000:10000);if($value<0||$value>$max)throw new InvalidArgumentException("Operand {$name} out of range");return$value;
    }
    private static function payments(mixed $rows,string $dateField):int
    {
        if(!is_array($rows)||!array_is_list($rows))throw new InvalidArgumentException('Payment evidence must be a list');$sum=0;
        foreach($rows as$i=>$row){if(!is_array($row)||!is_int($row['amountCents']??null)||abs($row['amountCents'])>1000000000000)throw new InvalidArgumentException("Invalid payment {$i}");self::date((string)($row[$dateField]??''));self::source($row['source']??null);$sum+=$row['amountCents'];}
        return$sum;
    }
    private static function source(mixed $source):void
    {
        if(!is_array($source)||trim((string)($source['label']??''))===''||trim((string)($source['locator']??''))===''||preg_match('/^[a-f0-9]{64}$/D',(string)($source['contentSha256']??''))!==1)throw new InvalidArgumentException('Invalid source provenance');
    }
    private static function date(string $value):void
    {
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value,new DateTimeZone('UTC'));$e=DateTimeImmutable::getLastErrors();if(!$d||($e!==false&&($e['warning_count']||$e['error_count']))||$d->format('Y-m-d')!==$value)throw new InvalidArgumentException('Invalid exact date');
    }
}
