<?php

declare(strict_types=1);

require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';

use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

// DATA-INTEGRITY-001v0.6 section4: negative metadata cannot hide contradictory membership.
foreach (['root', 'revision-owner'] as $position) {
    foreach ([O\AssignmentOrderOriginalLookupStatus::NOT_FOUND, O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE] as $status) {
        foreach (['false-control', 'true', 'throw'] as $membership) {
            integrityCase($position.'-'.$status->value.'-'.$membership, function () use ($position, $status, $membership) {
                $absent = S\OriginalIntegrityLineage::absent($status);
                $values = $absent->values;
                $values['contains'] = $membership === 'true';
                $line = new S\OriginalIntegrityLineage($values, $membership === 'throw' ? 'contains' : null);
                $target = $position === 'root' ? 'revision-0001' : 'revision-0099';
                $options = ['correction' => true, 'target' => $target];
                $options[$position === 'root' ? 'lineage' : 'revisionLineage'] = $line;
                $fixture = new S\OriginalIntegrityFixture($options);
                $result = $fixture->run();
                if ($membership !== 'false-control' || $status === O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE) {
                    integrityFailure($fixture, $result, streamed: $position === 'revision-owner');
                    if ($membership !== 'false-control')
                        assertSameValue([$target], $line->membershipCalls, 'known queried member read once, no invented probe');
                } else {
                    assertSameValue(O\AssignmentOrderOriginalStatus::CONFLICT, $result->status(), 'genuine negative control is business conflict');
                    assertSameValue($position === 'root' ? O\AssignmentOrderOriginalReason::SEMANTIC_COLLISION : O\AssignmentOrderOriginalReason::TARGET_NOT_FOUND,
                        $result->reasonCode(), 'negative control retains exact existing reason');
                    assertSameValue(1, count($fixture->repository->attempts), 'valid conflict records one terminal attempt');
                }
                assertSameValue($position === 'root' ? [] : [$target], $fixture->repository->revisionQueries, 'exact owner query path');
                assertSameValue([], $fixture->repository->acceptedCalls, 'negative membership never accepts');
                assertSameValue(0, $fixture->ids->rootCalls + $fixture->ids->revisionCalls, 'resolution precedes allocation');
            });
        }
    }
}

integrityDone('ASSIGNMENT_ORDER_ORIGINAL_DATA_NEGATIVE_MEMBERSHIP_OK');
