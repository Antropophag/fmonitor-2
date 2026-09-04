<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationDatabaseFixture;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mode = (string) getenv('AOOU_FIXTURE_WORKER_MODE');
    if (!in_array($mode, ['seed', 'cleanup'], true)) {
        throw new RuntimeException('mode');
    }
    $database = new mysqli(
        (string) getenv('AOOU_FIXTURE_WORKER_HOST'),
        (string) getenv('AOOU_FIXTURE_WORKER_USER'),
        (string) getenv('AOOU_FIXTURE_WORKER_PASSWORD'),
        (string) getenv('AOOU_FIXTURE_WORKER_DATABASE'),
        (int) getenv('AOOU_FIXTURE_WORKER_PORT'),
    );
    $database->set_charset('utf8mb4');
    stream_set_timeout(STDIN, 5);
    fwrite(STDOUT, "READY {$mode} " . (int) $database->thread_id . "\n");
    fflush(STDOUT);
    if (fgets(STDIN) !== "ENTER {$mode}\n") {
        throw new RuntimeException('barrier');
    }
    $control = (string) getenv('AOOU_FIXTURE_WORKER_CONTROL');
    if ($control === 'hang_before_seam') {
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, SIG_IGN);
    }
    fwrite(STDOUT, "ENTERED {$mode}\n");
    fflush(STDOUT);
    if ($control === 'fail_before_seam') {
        throw new RuntimeException('controlled');
    }
    if ($control === 'hang_before_seam') {
        while (true) {
            usleep(100_000);
        }
    }
    if ($control !== '') {
        throw new RuntimeException('control');
    }
    if ($mode === 'seed') {
        AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(
            $database,
            (string) getenv('AOOU_FIXTURE_WORKER_PREFIX'),
        );
    } else {
        AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA(
            $database,
            (string) getenv('AOOU_FIXTURE_WORKER_PREFIX'),
        );
    }
    $database->close();
    fwrite(STDOUT, "OK {$mode}\n");
    exit(0);
} catch (Throwable) {
    fwrite(STDERR, "FIXTURE_WORKER_FAILED\n");
    exit(70);
}
