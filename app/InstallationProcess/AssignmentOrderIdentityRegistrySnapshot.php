<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final readonly class AssignmentOrderIdentityRegistrySnapshot
{
    /** @param list<AssignmentOrderIdentityRegistryRow> $identities */
    public function __construct(public array $identities, public ?AssignmentOrderIdentityRegistryReceipt $receipt, public string $nextId) {}
}
