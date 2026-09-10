<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class YiiObjectCardApplicationIntegrity
{
    public static function validateComposition(array $row, array $selected): void
    {
        $caseId = YiiObjectCardValues::positiveId($row['installation_case_id']);
        $orderId = YiiObjectCardValues::positiveId($row['assignment_order_id']);
        $version = YiiObjectCardValues::positiveId($row['order_version']);
        $engineer = $selected['selectedEngineer'];
        $installers = $selected['selectedInstallers'];
        if ($caseId === null || YiiObjectCardValues::positiveId($row['object_id']) === null || $orderId === null || $version === null
            || !is_array($engineer) || !is_array($installers) || $installers === []
            || YiiObjectCardValues::positiveId($engineer['userId'] ?? null) !== (int) $row['control_engineer_user_id']
            || trim((string) ($engineer['fullName'] ?? '')) === '' || trim((string) ($engineer['position'] ?? '')) === '') {
            throw new \RuntimeException('Malformed applied composition.');
        }
        $installerIds = [];
        foreach ($installers as $installer) {
            if (!is_array($installer) || YiiObjectCardValues::positiveId($installer['tabId'] ?? null) === null
                || trim((string) ($installer['fullName'] ?? '')) === '' || trim((string) ($installer['position'] ?? '')) === '') {
                throw new \RuntimeException('Malformed applied composition.');
            }
            $installerIds[] = (int) $installer['tabId'];
        }
        $sorted = array_values(array_unique($installerIds));
        sort($sorted, SORT_NUMERIC);
        $identity = 'composition-' . $orderId . '-v' . $version;
        $composition = ['caseId' => $caseId, 'compositionIdentity' => $identity, 'engineerUserId' => (int) $engineer['userId'], 'installers' => $installerIds, 'orderId' => $orderId];
        $encoded = json_encode($composition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if ($installerIds !== $sorted || (string) $row['composition_identity'] !== $identity
            || !hash_equals((string) $row['composition_sha256'], hash('sha256', $encoded))) {
            throw new \RuntimeException('Malformed applied composition identity.');
        }
    }
}
