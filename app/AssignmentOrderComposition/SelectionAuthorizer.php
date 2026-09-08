<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionAuthorizer
{ public function authorize(UserId $actor, SelectionCapability $capability): SelectionAuthorization; }
