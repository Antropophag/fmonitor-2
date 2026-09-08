<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
/** Exact terminal-request primary-key collision; the UoW must roll back. */
final class SelectionNativeRequestRace extends \RuntimeException {}
