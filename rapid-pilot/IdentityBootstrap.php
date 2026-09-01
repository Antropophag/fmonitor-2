<?php
declare(strict_types=1);

require_once dirname(__DIR__).'/app/RapidPilot/LocalRoleCatalog.php';
require_once dirname(__DIR__).'/app/InstallationProcess/MariaDbSchemaInspector.php';
require_once dirname(__DIR__).'/app/InstallationProcess/IdentityAccessDefinitionSchemaMigration.php';
require_once dirname(__DIR__).'/app/InstallationProcess/IdentityAccessSchemaMigration.php';
require_once dirname(__DIR__).'/app/PilotHttp/MariaDbIdentityBootstrapApplication.php';

/** Temporary pilot adapter for the public identity bootstrap application seam. */
final class RapidPilotIdentityBootstrap
{
    public static function apply(mysqli $db,string $p,string $configured,string $bootstrapPassword):void
    {
        \FMonitor2\PilotHttp\MariaDbIdentityBootstrapApplication::apply($db,$p,$configured,$bootstrapPassword);
    }

    public static function rebuild(mysqli $db,string $p,string $configured,string $bootstrapPassword):void
    {
        \FMonitor2\PilotHttp\MariaDbIdentityBootstrapApplication::rebuild($db,$p,$configured,$bootstrapPassword);
    }
}
