<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Database metadata is unsuitable for applying canonical migrations safely. */
final class DatabaseUnavailable extends \RuntimeException
{
}
