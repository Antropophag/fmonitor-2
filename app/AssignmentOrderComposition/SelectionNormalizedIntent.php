<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionNormalizedIntent
{ public function __construct(public int $actorUserId,public ?int $engineerUserId,
  public int $expectedRevision,public int $objectId,public InstallerTabIdSet $installers,
  public AssignmentOrderCompositionMode $mode,public string $canonicalJson,
  public string $fingerprint) {} }
