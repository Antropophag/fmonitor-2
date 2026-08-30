<?php

declare(strict_types=1);

/** Derived contract of Appendix 4 to Order No. 178, validated by the
 * fmonitor-premium-export-report implementation and its golden tests. */
final class NativePremiumNorms
{
    private const PASSENGER = [
        2=>[455,520,585,650],3=>[455,520,585,650],4=>[455,520,585,650],
        5=>[520,585,650,715],6=>[520,585,650,715],7=>[520,585,650,715],
        8=>[585,650,715,780],9=>[585,650,715,780],10=>[585,650,715,780],
        11=>[650,750,780,845],12=>[650,750,780,845],13=>[650,780,845,910],14=>[650,780,845,910],
        15=>[780,845,910,975],16=>[780,845,910,975],17=>[845,910,975,1040],18=>[910,975,1040,1105],
        19=>[975,1040,1105,1170],20=>[1040,1105,1170,1235],21=>[1105,1170,1235,1300],
        22=>[1170,1235,1300,1365],23=>[1235,1300,1365,1430],24=>[1300,1365,1430,1495],
        25=>[1365,1430,1495,1560],26=>[1430,1495,1560,1625],27=>[1495,1560,1625,1690],
        28=>[1560,1625,1690,1755],29=>[1625,1690,1755,1820],30=>[1690,1755,1820,1855],
    ];

    private const CARGO = [
        2=>[520,585,715,975,1105,1170],3=>[572,637,780,1066,1196,1261],
        4=>[624,689,845,1157,1287,1352],5=>[676,741,910,1248,1378,1443],
        6=>[728,793,975,1339,1469,1534],7=>[780,845,1040,1430,1560,1625],
        8=>[832,897,1105,1521,1651,1716],9=>[884,949,1170,1612,1742,1807],
        10=>[936,1001,1235,1703,1833,1898],
    ];

    public function premiumCents(?string $type, int $floors, int $capacity): ?int
    {
        if ($type === null && $capacity >= 240 && $capacity <= 1600) $type = 'passenger';
        if ($type === 'passenger') {
            if (!isset(self::PASSENGER[$floors])) return null;
            foreach ([[240,500],[525,800],[900,1200],[1275,1600]] as $index => [$from,$to]) {
                if ($capacity >= $from && $capacity <= $to) return self::PASSENGER[$floors][$index] * 100000;
            }
            return null;
        }
        if ($type === 'cargo') {
            if (!isset(self::CARGO[$floors])) return null;
            $index = array_search($capacity, [500,1000,1500,2500,3500,5000], true);
            return $index === false ? null : self::CARGO[$floors][$index] * 100000;
        }
        return null;
    }

    public function shaftBasisPoints(string $material): ?int
    {
        $value = mb_strtolower(trim($material), 'UTF-8');
        $value = preg_replace('/\s*\+\s*/u', ' + ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return [
            'железобетон'=>10000,'железобетон и металл'=>10000,
            'кирпич'=>11500,'кирпич и металл'=>11500,
            'металлокаркас и стекло'=>12500,'металлокаркас + стекло'=>12500,
        ][$value] ?? null;
    }
}
