<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionAuthorization
{ public function __construct(public SelectionAuthorizationStatus $status) {} }
