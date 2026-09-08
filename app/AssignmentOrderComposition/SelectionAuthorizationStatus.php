<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

enum SelectionAuthorizationStatus:string
{ case ALLOWED='allowed'; case DENIED='denied'; case UNAVAILABLE='unavailable'; }
