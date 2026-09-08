<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum SelectionLookupStatus:string
{ case FOUND='found'; case NOT_FOUND='not_found'; case UNAVAILABLE='unavailable'; }
