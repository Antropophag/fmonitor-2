<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionClock { public function now(): SelectionInstantLookup; }
