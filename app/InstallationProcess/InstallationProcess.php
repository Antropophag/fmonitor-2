<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class InstallationProcess
{
    public function __construct(private readonly object $environment)
    {
    }

    /**
     * @param list<int|string> $installerTabIds
     * @return array{accepted: bool, violations: list<array{code: string, message: string, field: ?string}>}
     */
    public function prepareAssignmentOrder(
        int $installationObjectId,
        array $installerTabIds,
        ?int $controlEngineerUserId,
        int $actorId,
    ): array {
        if (!$this->environment->actorCanPrepareAssignmentOrder($actorId)) {
            $this->environment->appendSecurityEvent($installationObjectId, [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $this->environment->now(),
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => ['FORBIDDEN'],
                    'installerCount' => count(array_filter(
                        $installerTabIds,
                        static fn (int|string $installerTabId): bool => !is_string($installerTabId)
                            || trim($installerTabId) !== '',
                    )),
                    'controlEngineerProvided' => $controlEngineerUserId !== null,
                ],
            ]);

            return [
                'accepted' => false,
                'violations' => [
                    [
                        'code' => 'FORBIDDEN',
                        'message' => 'У вас нет права формировать распоряжение.',
                        'field' => null,
                    ],
                ],
            ];
        }

        $normalizedInstallerTabIds = array_values(array_unique(array_filter(
            $installerTabIds,
            static fn (int|string $installerTabId): bool => !is_string($installerTabId)
                || trim($installerTabId) !== '',
        )));

        $violations = [];
        if ($normalizedInstallerTabIds === []) {
            $violations[] = [
                'code' => 'INSTALLER_REQUIRED',
                'message' => 'Выберите хотя бы одного монтажника.',
                'field' => 'installerTabIds',
            ];
        }

        if ($controlEngineerUserId === null) {
            $violations[] = [
                'code' => 'CONTROL_ENGINEER_REQUIRED',
                'message' => 'Выберите инженера строительного контроля.',
                'field' => 'controlEngineerUserId',
            ];
        }

        if ($violations !== []) {
            $this->environment->appendEvent($installationObjectId, [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $this->environment->now(),
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => array_column($violations, 'code'),
                    'installerCount' => count($normalizedInstallerTabIds),
                    'controlEngineerProvided' => $controlEngineerUserId !== null,
                ],
            ]);

            return ['accepted' => false, 'violations' => $violations];
        }

        $observedProcess = $this->environment->loadInstallationObjectProcessAtRevision($installationObjectId);
        $currentProcess = $observedProcess['process'];
        $observedProcessRevision = $observedProcess['revision'];
        $currentAssignmentOrders = $currentProcess['assignmentOrders'] ?? [];
        $currentAssignmentOrder = $currentAssignmentOrders === []
            ? null
            : $currentAssignmentOrders[array_key_last($currentAssignmentOrders)];
        if ($currentAssignmentOrder !== null
            && (($currentAssignmentOrder['status'] ?? null) === 'prepared'
                || ($currentAssignmentOrder['status'] ?? null) !== 'registered'
                || !in_array($currentProcess['processState'] ?? null, ['working', 'needs_assignment_change'], true))
        ) {
            $this->environment->appendEvent($installationObjectId, [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $this->environment->now(),
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => ['ASSIGNMENT_ORDER_ALREADY_PREPARED'],
                    'installerCount' => count($normalizedInstallerTabIds),
                    'controlEngineerProvided' => true,
                    'currentOrderVersion' => $currentAssignmentOrder['version'],
                ],
            ]);

            return [
                'accepted' => false,
                'violations' => [
                    [
                        'code' => 'ASSIGNMENT_ORDER_ALREADY_PREPARED',
                        'message' => 'По объекту монтажа уже существует актуальное распоряжение. Для изменения состава подготовьте изменяющее распоряжение.',
                        'field' => null,
                    ],
                ],
            ];
        }

        $installationObjectSnapshot = $this->environment->getInstallationObjectSnapshot($installationObjectId);
        $requiredInstallationObjectFields = [
            'address' => 'В объекте монтажа не заполнен адрес объекта.',
            'entrance' => 'В объекте монтажа не заполнен подъезд или секция объекта.',
            'objectRegistrationNumber' => 'В объекте монтажа не заполнен регистрационный номер объекта.',
            'plannedStartDate' => 'В объекте монтажа не заполнена плановая дата начала работ.',
            'plannedFinishDate' => 'В объекте монтажа не заполнена плановая дата завершения работ.',
        ];
        $missingFields = [];
        $installationObjectDataViolations = [];
        foreach ($requiredInstallationObjectFields as $field => $message) {
            $value = $installationObjectSnapshot[$field] ?? null;
            if ($value !== null && (!is_string($value) || trim($value) !== '')) {
                continue;
            }

            $missingFields[] = $field;
            $installationObjectDataViolations[] = [
                'code' => 'INSTALLATION_OBJECT_REQUIRED_DATA_MISSING',
                'message' => $message,
                'field' => $field,
            ];
        }

        if ($installationObjectDataViolations !== []) {
            $occurredAt = $this->environment->now();
            $this->environment->appendEvent($installationObjectId, [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $occurredAt,
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => ['INSTALLATION_OBJECT_REQUIRED_DATA_MISSING'],
                    'missingFields' => $missingFields,
                    'installerCount' => count($normalizedInstallerTabIds),
                    'controlEngineerProvided' => true,
                ],
            ]);

            return ['accepted' => false, 'violations' => $installationObjectDataViolations];
        }

        $assignmentOrderVersion = $currentAssignmentOrder === null
            ? 1
            : (int) $currentAssignmentOrder['version'] + 1;
        $occurredAt = $this->environment->now();
        $assignmentOrderDate = (new \DateTimeImmutable($occurredAt))
            ->setTimezone(new \DateTimeZone('Europe/Moscow'))
            ->format('Y-m-d');
        $installerSnapshots = [];
        $installerViolations = [];
        foreach ($normalizedInstallerTabIds as $index => $installerTabId) {
            $installerSnapshot = $this->environment->findInstallerSnapshot($installerTabId);
            if ($installerSnapshot === null) {
                $installerViolations[] = [
                    'code' => 'INSTALLER_NOT_IN_CATALOG',
                    'message' => "Монтажник с табельным номером {$installerTabId} отсутствует в актуальном кадровом каталоге.",
                    'field' => "installerTabIds[{$index}]",
                ];
                continue;
            }

            $employedFrom = $installerSnapshot['employedFrom'] ?? null;
            $employedTo = $installerSnapshot['employedTo'] ?? null;
            $isEmployedForRequiredPeriod = ($installerSnapshot['status'] ?? null) === 'employed'
                && is_string($employedFrom)
                && $employedFrom <= $assignmentOrderDate
                && ($employedTo === null || (is_string($employedTo) && $employedTo >= $assignmentOrderDate))
                && ($employedTo === null
                    || (is_string($employedTo) && $employedTo >= $installationObjectSnapshot['plannedFinishDate']));

            if (!$isEmployedForRequiredPeriod) {
                $installerViolations[] = [
                    'code' => 'INSTALLER_NOT_EMPLOYED',
                    'message' => "Монтажник с табельным номером {$installerTabId} не имеет подтверждённого периода трудоустройства на требуемый срок работ.",
                    'field' => "installerTabIds[{$index}]",
                ];
                continue;
            }

            $installerSnapshots[] = $installerSnapshot;
        }

        if ($installerViolations !== []) {
            $this->environment->appendEvent($installationObjectId, [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $occurredAt,
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => array_values(array_unique(array_column($installerViolations, 'code'))),
                    'installerCount' => count($normalizedInstallerTabIds),
                    'invalidInstallerCount' => count($installerViolations),
                    'controlEngineerProvided' => true,
                ],
            ]);

            return ['accepted' => false, 'violations' => $installerViolations];
        }

        $controlEngineer = $this->environment->findEngineerSnapshot($controlEngineerUserId);
        if ($controlEngineer === null
            || ($controlEngineer['active'] ?? null) !== true
            || ($controlEngineer['role'] ?? null) !== 'construction_control_engineer'
        ) {
            $this->environment->appendEvent($installationObjectId, [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $occurredAt,
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => ['CONTROL_ENGINEER_NOT_ELIGIBLE'],
                    'installerCount' => count($normalizedInstallerTabIds),
                    'controlEngineerProvided' => true,
                    'controlEngineerEligible' => false,
                ],
            ]);

            return [
                'accepted' => false,
                'violations' => [
                    [
                        'code' => 'CONTROL_ENGINEER_NOT_ELIGIBLE',
                        'message' => 'Выбранный пользователь не является активным инженером строительного контроля.',
                        'field' => 'controlEngineerUserId',
                    ],
                ],
            ];
        }

        $organizationType = count($installerSnapshots) === 1 ? 'individual' : 'brigade';
        $documentInstallers = array_map(
            static fn (array $installer): array => $installer + ['workStatus' => 'Работа'],
            $installerSnapshots,
        );
        if ($currentAssignmentOrder !== null) {
            $selectedIds = array_fill_keys(array_map('strval', $normalizedInstallerTabIds), true);
            foreach ($currentAssignmentOrder['installers'] ?? [] as $previousInstaller) {
                if (!isset($selectedIds[(string) ($previousInstaller['tabId'] ?? '')])) {
                    $documentInstallers[] = $previousInstaller + ['workStatus' => 'Перемещён'];
                }
            }
        }
        try {
            $renderedArtifacts = $this->environment->renderAssignmentOrder([
                'assignmentOrderVersion' => $assignmentOrderVersion,
                'assignmentOrderDate' => $assignmentOrderDate,
                'organizationType' => $organizationType,
                'installationObjectSnapshot' => $installationObjectSnapshot,
                'installers' => $installerSnapshots,
                'documentInstallers' => $documentInstallers,
                'controlEngineer' => $controlEngineer,
            ]);
        } catch (\Throwable) {
            $this->environment->appendEvent($installationObjectId, [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $occurredAt,
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => ['ASSIGNMENT_ORDER_RENDER_FAILED'],
                    'installerCount' => count($normalizedInstallerTabIds),
                    'controlEngineerProvided' => true,
                ],
            ]);

            return [
                'accepted' => false,
                'violations' => [
                    [
                        'code' => 'ASSIGNMENT_ORDER_RENDER_FAILED',
                        'message' => 'Не удалось сформировать документы распоряжения. Повторите действие позже.',
                        'field' => null,
                    ],
                ],
            ];
        }

        $artifacts = [];
        $artifactSha256 = [];
        foreach ($renderedArtifacts as $renderedArtifact) {
            $sha256 = hash('sha256', $renderedArtifact['bytes']);
            $artifacts[] = [
                'type' => $renderedArtifact['type'],
                'filename' => $renderedArtifact['filename'],
                'mediaType' => $renderedArtifact['mediaType'],
                'size' => strlen($renderedArtifact['bytes']),
                'sha256' => $sha256,
            ];
            $artifactSha256[$renderedArtifact['type']] = $sha256;
        }

        $isChangingOrder = $currentAssignmentOrder !== null;
        $currentProcess['processState'] = $isChangingOrder ? 'working' : 'assignment_order_prepared';
        $currentProcess['assignmentOrders'][] = [
                'version' => $assignmentOrderVersion,
                'status' => 'prepared',
                'registrationNumber' => null,
                'assignmentOrderDate' => $assignmentOrderDate,
                'organizationType' => $organizationType,
                'installationObjectSnapshot' => $installationObjectSnapshot,
                'installers' => $installerSnapshots,
                'controlEngineer' => $controlEngineer,
                'artifacts' => $artifacts,
            ];
        $newAssignments = array_merge(
            array_map(
                static fn (int|string $installerTabId): array => [
                    'role' => 'installer',
                    'tabId' => $installerTabId,
                    'assignmentOrderVersion' => $assignmentOrderVersion,
                    'status' => 'preliminary',
                ],
                $normalizedInstallerTabIds,
            ),
            [[
                'role' => 'control_engineer',
                'userId' => $controlEngineerUserId,
                'assignmentOrderVersion' => $assignmentOrderVersion,
                'status' => 'preliminary',
            ]],
        );
        $currentProcess['assignments'] = array_merge($currentProcess['assignments'] ?? [], $newAssignments);
        if (!$isChangingOrder) $currentProcess['openTasks'] = [];
        $currentProcess['events'][] = [
            'type' => 'assignment_order_prepared',
            'occurredAt' => $occurredAt,
            'actorId' => $actorId,
            'payload' => [
                'assignmentOrderVersion' => $assignmentOrderVersion,
                'assignmentOrderDate' => $assignmentOrderDate,
                'installerTabIds' => $normalizedInstallerTabIds,
                'controlEngineerUserId' => $controlEngineerUserId,
                'organizationType' => $organizationType,
                ...($isChangingOrder ? ['changesPreviousVersion' => $currentAssignmentOrder['version']] : []),
                'artifactSha256' => $artifactSha256,
            ],
        ];
        $preparationOperationId = $this->environment->newPreparationOperationId();
        try {
            $replacement = $this->environment->replaceInstallationObjectProcessAtRevision(
                $installationObjectId,
                $observedProcessRevision,
                $currentProcess,
                $preparationOperationId,
            );
        } catch (PersistenceCommitOutcomeUnknown) {
            $storedResult = $this->environment->findPreparationResult($preparationOperationId);
            if ($storedResult !== null) {
                return $storedResult;
            }

            return [
                'accepted' => false,
                'violations' => [[
                    'code' => 'ASSIGNMENT_ORDER_RESULT_UNKNOWN',
                    'message' => 'Не удалось подтвердить результат формирования распоряжения. Обновите данные объекта монтажа перед дальнейшими действиями.',
                    'field' => null,
                ]],
            ];
        } catch (PersistenceFailureWithConfirmedRollback) {
            $this->environment->appendEvent($installationObjectId, [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $occurredAt,
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => ['ASSIGNMENT_ORDER_PERSISTENCE_FAILED'],
                    'installerCount' => count($normalizedInstallerTabIds),
                    'controlEngineerProvided' => true,
                ],
            ]);

            return [
                'accepted' => false,
                'violations' => [
                    [
                        'code' => 'ASSIGNMENT_ORDER_PERSISTENCE_FAILED',
                        'message' => 'Не удалось сохранить распоряжение. Повторите действие позже.',
                        'field' => null,
                    ],
                ],
            ];
        }
        if (!$replacement['replaced']) {
            $rejectionEvent = [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => $occurredAt,
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => ['CONCURRENT_MODIFICATION'],
                    'installerCount' => count($normalizedInstallerTabIds),
                    'controlEngineerProvided' => true,
                    'observedProcessRevision' => $observedProcessRevision,
                    'currentProcessRevision' => $replacement['currentRevision'],
                ],
            ];
            $auditRevision = $replacement['currentRevision'];
            do {
                $auditAppend = $this->environment->appendEventAtRevision(
                    $installationObjectId,
                    $auditRevision,
                    $rejectionEvent,
                );
                $auditRevision = $auditAppend['currentRevision'];
            } while (!$auditAppend['appended']);

            return [
                'accepted' => false,
                'violations' => [
                    [
                        'code' => 'CONCURRENT_MODIFICATION',
                        'message' => 'Объект монтажа изменился во время подготовки распоряжения. Обновите данные и повторите действие при необходимости.',
                        'field' => null,
                    ],
                ],
            ];
        }

        return [
            'accepted' => true,
            'assignmentOrderVersion' => $assignmentOrderVersion,
            'status' => 'prepared',
            'assignmentOrderDate' => $assignmentOrderDate,
            'organizationType' => $organizationType,
        ];
    }

    /** @return array<string, mixed> */
    public function confirmOrderRegistration(
        int $installationObjectId,
        int $assignmentOrderVersion,
        string $registrationNumber,
        string $source,
        int $actorId,
    ): array {
        if (!$this->environment->actorCanConfirmOrderRegistration($actorId)) {
            throw new \LogicException('Registration confirmation is not authorized.');
        }

        $normalizedRegistrationNumber = trim($registrationNumber);
        if ($normalizedRegistrationNumber === '' || $source !== 'manual') {
            throw new \InvalidArgumentException('Manual registration confirmation requires a registration number.');
        }

        $observedProcess = $this->environment->loadInstallationObjectProcessAtRevision($installationObjectId);
        $currentProcess = $observedProcess['process'];
        $assignmentOrders = $currentProcess['assignmentOrders'] ?? [];
        if ($assignmentOrders === []) {
            throw new \LogicException('The current prepared assignment order was not found.');
        }
        $currentOrderIndex = array_key_last($assignmentOrders);
        $currentOrder = $assignmentOrders[$currentOrderIndex];
        if (($currentOrder['version'] ?? null) !== $assignmentOrderVersion
            || ($currentOrder['status'] ?? null) !== 'prepared') {
            throw new \LogicException('The requested assignment order is not the current prepared version.');
        }

        $registeredAt = $this->environment->now();
        $currentOrder['status'] = 'registered';
        $currentOrder['registrationNumber'] = $normalizedRegistrationNumber;
        $registeredOrder = [];
        foreach ($currentOrder as $field => $value) {
            $registeredOrder[$field] = $value;
            if ($field !== 'registrationNumber') {
                continue;
            }
            $registeredOrder['registeredAt'] = $registeredAt;
            $registeredOrder['registrationActorType'] = 'user';
            $registeredOrder['registrationActorId'] = $actorId;
            $registeredOrder['registrationSource'] = 'manual';
            $registeredOrder['externalRegistrationId'] = null;
        }
        $currentProcess['assignmentOrders'][$currentOrderIndex] = $registeredOrder;
        $currentProcess['events'][] = [
            'type' => 'assignment_order_registered',
            'occurredAt' => $registeredAt,
            'actorId' => $actorId,
            'payload' => [
                'assignmentOrderVersion' => $assignmentOrderVersion,
                'registrationNumber' => $normalizedRegistrationNumber,
                'registrationSource' => 'manual',
                'registrationActorType' => 'user',
            ],
        ];

        $replacement = $this->environment->replaceInstallationObjectProcessAtRevision(
            $installationObjectId,
            $observedProcess['revision'],
            $currentProcess,
        );
        if (!$replacement['replaced']) {
            throw new \RuntimeException('Registration confirmation was not persisted atomically.');
        }

        return [
            'accepted' => true,
            'assignmentOrderVersion' => $assignmentOrderVersion,
            'status' => 'registered',
            'registrationNumber' => $normalizedRegistrationNumber,
            'registeredAt' => $registeredAt,
            'registrationActorType' => 'user',
            'registrationActorId' => $actorId,
            'registrationSource' => 'manual',
            'externalRegistrationId' => null,
            'processState' => $currentProcess['processState'],
        ];
    }

    /** @return array<string, mixed> */
    public function openInstallation(int $installationObjectId, string $actualStartDate, int $actorId): array
    {
        if (!$this->environment->actorCanOpenInstallation($actorId)) {
            throw new \LogicException('Installation opening is not authorized.');
        }

        $observedProcess = $this->environment->loadInstallationObjectProcessAtRevision($installationObjectId);
        $currentProcess = $observedProcess['process'];
        if (($currentProcess['installationOpened'] ?? false) === true) {
            throw new \LogicException('Installation is already open.');
        }
        $orders = $currentProcess['assignmentOrders'] ?? [];
        if ($orders === []) {
            throw new \LogicException('A current registered assignment order is required.');
        }
        $currentOrder = $orders[array_key_last($orders)];
        if (($currentOrder['status'] ?? null) !== 'registered') {
            throw new \LogicException('The current assignment order is not registered.');
        }
        $installers = $currentOrder['installers'] ?? null;
        if (!is_array($installers) || $installers === []) {
            $this->environment->appendEvent($installationObjectId, [
                'type' => 'installation_open_rejected',
                'occurredAt' => $this->environment->now(),
                'actorId' => $actorId,
                'payload' => [
                    'reasonCodes' => ['REGISTERED_ORDER_COMPOSITION_INVALID'],
                    'assignmentOrderVersion' => $currentOrder['version'],
                    'installerCount' => 0,
                ],
            ]);

            return [
                'accepted' => false,
                'violations' => [[
                    'code' => 'REGISTERED_ORDER_COMPOSITION_INVALID',
                    'message' => 'Зарегистрированное распоряжение не содержит ни одного монтажника. Открытие работ невозможно.',
                    'field' => null,
                ]],
            ];
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $actualStartDate, $dateParts) !== 1
            || !checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])) {
            throw new \InvalidArgumentException('Actual start date must be a calendar date in YYYY-MM-DD format.');
        }
        $openedAt = $this->environment->now();
        $moscowToday = (new \DateTimeImmutable($openedAt))
            ->setTimezone(new \DateTimeZone('Europe/Moscow'))
            ->format('Y-m-d');
        if ($actualStartDate < (string) $currentOrder['assignmentOrderDate'] || $actualStartDate > $moscowToday) {
            throw new \InvalidArgumentException('Actual start date is outside the permitted interval.');
        }

        foreach ($installers as $installer) {
            $currentInstaller = $this->environment->findCurrentInstallerSnapshot($installer['tabId']);
            $employedFrom = $currentInstaller['employedFrom'] ?? null;
            $employedTo = $currentInstaller['employedTo'] ?? null;
            if ($currentInstaller === null
                || ($currentInstaller['status'] ?? null) !== 'employed'
                || !is_string($employedFrom)
                || $employedFrom > $actualStartDate
                || ($employedTo !== null && (!is_string($employedTo) || $employedTo < $actualStartDate))) {
                throw new \LogicException('A current installer is not employed on the actual start date.');
            }
        }

        $openedProcess = [];
        foreach ($currentProcess as $field => $value) {
            $openedProcess[$field] = $field === 'processState' ? 'working' : $value;
            if ($field !== 'processState') {
                continue;
            }
            $openedProcess['actualStartDate'] = $actualStartDate;
            $openedProcess['openedAt'] = $openedAt;
            $openedProcess['openedByUserId'] = $actorId;
        }
        $openedProcess['installationOpened'] = true;
        $openedProcess['checklistAvailable'] = true;
        $openedProcess['events'][] = [
            'type' => 'installation_opened',
            'occurredAt' => $openedAt,
            'actorId' => $actorId,
            'payload' => [
                'actualStartDate' => $actualStartDate,
                'assignmentOrderVersion' => $currentOrder['version'],
                'installerCount' => count($installers),
            ],
        ];

        $replacement = $this->environment->replaceInstallationObjectProcessAtRevision(
            $installationObjectId,
            $observedProcess['revision'],
            $openedProcess,
        );
        if (!$replacement['replaced']) {
            throw new \RuntimeException('Installation opening was not persisted atomically.');
        }

        return [
            'accepted' => true,
            'processState' => 'working',
            'actualStartDate' => $actualStartDate,
            'openedAt' => $openedAt,
            'openedByUserId' => $actorId,
            'installationOpened' => true,
            'checklistAvailable' => true,
            'assignmentOrderVersion' => $currentOrder['version'],
        ];
    }

    /** @return array<string, mixed> */
    public function getInstallationObjectProcess(int $installationObjectId): array
    {
        return $this->environment->getInstallationObjectProcess($installationObjectId);
    }

    /**
     * @return list<array<string, mixed>>|array{accepted: false, violations: list<array{code: string, message: string, field: null}>}
     */
    public function getSecurityAudit(int $installationObjectId, int $actorId): array
    {
        if (!$this->environment->actorCanReadSecurityAudit($actorId)) {
            return [
                'accepted' => false,
                'violations' => [
                    [
                        'code' => 'FORBIDDEN',
                        'message' => 'У вас нет права просматривать security-аудит.',
                        'field' => null,
                    ],
                ],
            ];
        }

        return $this->environment->getSecurityEvents($installationObjectId);
    }
}
