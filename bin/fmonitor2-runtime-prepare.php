<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

FMonitor2\Runtime\RuntimeCommand::run(FMonitor2\Runtime\RuntimeStorage::prepare(...));
