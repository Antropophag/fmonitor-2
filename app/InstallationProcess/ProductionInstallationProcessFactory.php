<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProductionInstallationProcessFactory
{
    public static function create(
        \mysqli $connection,
        ProductionInstallationProcessConfig $config,
        ?Clock $clock = null,
    ): InstallationProcess {
        [$processPrefix,$legacyPrefix,$artifactRoot]=self::configuration($config);
        $store=new ContentAddressedArtifactStore($artifactRoot);
        self::initializeConnection($connection);

        $workforce = new MariaDbWorkforceCatalog($connection, $processPrefix);
        $directory = new MariaDbProcessUserDirectory($connection, $processPrefix, $legacyPrefix);
        $facts = new ProductionInstallationProcessFacts(
            new MariaDbLegacyInstallationObject($connection, $legacyPrefix),
            $workforce,
            $directory,
            new StoringAssignmentOrderRenderer(new ProductionHtmlAssignmentOrderRenderer(),$store),
            $clock ?? new SystemClock(),
        );

        return new InstallationProcess(
            new MariaDbInstallationProcessEnvironment($connection, $facts, $processPrefix),
        );
    }

    public static function createArtifactService(\mysqli $connection,ProductionInstallationProcessConfig $config): AssignmentOrderArtifactService
    {
        [$processPrefix,$legacyPrefix,$artifactRoot]=self::configuration($config);
        $store=new ContentAddressedArtifactStore($artifactRoot);self::initializeConnection($connection);
        $directory=new MariaDbProcessUserDirectory($connection,$processPrefix,$legacyPrefix);
        $forbidden=new class{public function __call(string $method,array $arguments):never{throw new \LogicException('External facts are unavailable during artifact lookup.');}};
        $process=new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection,$forbidden,$processPrefix));
        return new AssignmentOrderArtifactService($process,$directory,$store);
    }

    private static function configuration(ProductionInstallationProcessConfig $config): array
    {
        try{$artifactRoot=$config->artifactStorageRoot;}catch(\Throwable){throw new \InvalidArgumentException('Invalid artifact storage root.');}
        if($artifactRoot==='')throw new \InvalidArgumentException('Invalid artifact storage root.');
        try{$processPrefix=$config->processTablePrefix;$legacyPrefix=$config->legacyTablePrefix;}catch(\Throwable){throw new \InvalidArgumentException('Invalid production installation process configuration.');}
        self::validatePrefix($processPrefix);self::validatePrefix($legacyPrefix);
        return [$processPrefix,$legacyPrefix,$artifactRoot];
    }

    private static function initializeConnection(\mysqli $connection): void
    {
        try{if(!$connection->set_charset('utf8mb4'))throw new \RuntimeException();}catch(\Throwable){throw new \RuntimeException('Production installation process initialization failed.');}
    }

    private static function validatePrefix(string $prefix): void
    {
        if (strlen($prefix) > 32 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid production installation process configuration.');
        }
    }
}
