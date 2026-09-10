<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;

final class MariaDbYiiChecklist implements YiiChecklist
{
    use MariaDbYiiChecklistRead;
    use MariaDbYiiChecklistAdmission;
    use MariaDbYiiChecklistMutation;
    use MariaDbYiiChecklistPhoto;
    use MariaDbYiiChecklistPersistence;

    private const ITEMS = [
        1 => [28,29,30,31,32,33,34,35,36], 2 => [37,38,39,40,41],
        3 => [1,2,3,4,5,6], 4 => [7,8,9,10], 5 => [11,12,13,14,15],
        6 => [16,17,18,19,20,21], 7 => [22,23,24,25,26,27],
    ];
    private ?\mysqli $db = null;
    private ?\yii\db\Transaction $transaction = null;

    public function __construct(
        private object $yii, private string $prefix, private string $legacyPrefix,
        private string $storageRoot, private string $now,
        private ?InspectionRecording $nativeRecording = null,
    ) {
        foreach ([$prefix, $legacyPrefix] as $candidate) {
            if (strlen($candidate) > 25 || preg_match('/^[A-Za-z0-9_]*$/D', $candidate) !== 1) {
                throw new \InvalidArgumentException('Invalid checklist database prefix.');
            }
        }
        if ($yii instanceof \mysqli) { $this->db = $yii; return; }
        if (!$yii instanceof \yii\db\Connection) {
            throw new \InvalidArgumentException('Checklist persistence connection is unsupported.');
        }
    }
}
