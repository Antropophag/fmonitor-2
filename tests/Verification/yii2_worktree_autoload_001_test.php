<?php

declare(strict_types=1);

// YII2-SHLZ-VISUAL-CONTRACT-001 baseline: Composer and application classes must resolve from one exact worktree.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__, 2) . '/app/autoload.php';
require dirname(__DIR__) . '/bootstrap.php';

$root = realpath(dirname(__DIR__, 2));
assertSameValue(true, is_string($root), 'repository root resolves');

foreach ([
    FMonitor2\YiiRuntime\MainNavigation::class,
    FMonitor2\YiiRuntime\ViewSupport::class,
] as $class) {
    $source = (new ReflectionClass($class))->getFileName();
    assertSameValue(true, is_string($source) && str_starts_with(realpath($source) ?: '', $root . DIRECTORY_SEPARATOR), 'exact worktree owns Composer class ' . $class);
}

assertSameValue(true, method_exists(FMonitor2\YiiRuntime\MainNavigation::class, 'icon'), 'current ViewSupport dependency exists in the same source tree');
echo "PASS: YII2-SHLZ-VISUAL-CONTRACT-001 exact worktree autoload\n";
