<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
interface BusinessDateClock extends Clock { public function businessDate():string; }
