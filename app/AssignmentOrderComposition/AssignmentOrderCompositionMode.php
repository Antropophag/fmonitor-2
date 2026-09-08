<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum AssignmentOrderCompositionMode: string
{ case NEW_ORDER = 'new_order'; case REPLACE_PENDING = 'replace_pending'; }
