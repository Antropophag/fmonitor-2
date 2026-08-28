<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\InstallationProcess;

// Specification: TERMINOLOGY-OBJECT-001, public interface replacement.
$module = new InstallationProcess(new stdClass());

assertSameValue(
    false,
    method_exists($module, 'prepareOrder'),
    'TERMINOLOGY-OBJECT-001 must remove the ambiguous prepareOrder interface.',
);
assertSameValue(
    false,
    method_exists($module, 'getOrderProcess'),
    'TERMINOLOGY-OBJECT-001 must remove the ambiguous getOrderProcess interface.',
);
assertSameValue(
    true,
    method_exists($module, 'prepareAssignmentOrder'),
    'TERMINOLOGY-OBJECT-001 must expose prepareAssignmentOrder.',
);
assertSameValue(
    true,
    method_exists($module, 'getInstallationObjectProcess'),
    'TERMINOLOGY-OBJECT-001 must expose getInstallationObjectProcess.',
);

fwrite(STDOUT, "PASS TERMINOLOGY-OBJECT-001 public interface\n");
