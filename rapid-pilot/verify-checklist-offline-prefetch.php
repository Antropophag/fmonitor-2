<?php
declare(strict_types=1);

$queue = (string) file_get_contents(dirname(__DIR__).'/app/PilotHttp/control-queue.js');
$worker = (string) file_get_contents(dirname(__DIR__).'/app/PilotHttp/checklist-sw.js');
$checklist = (string) file_get_contents(dirname(__DIR__).'/app/PilotHttp/checklist.js');
$view = (string) file_get_contents(dirname(__DIR__).'/app/PilotHttp/ConstructionControlView.php');

$requirements = [
    [str_contains($queue, '/pilot/assets/checklist-sw.js'), 'Construction-control queue must register the checklist service worker.'],
    [str_contains($queue, 'CACHE_CHECKLIST'), 'Construction-control queue must request prefetch for listed checklist URLs.'],
    [str_contains($queue, 'prefetchChecklists()'), 'Checklist prefetch must run while the queue is viewed.'],
    [preg_match('/item_installers_changed:\s*1/', $queue) === 1, 'Installer corrections must sync before section completion.'],
    [str_contains($queue, 'retryable_error') && str_contains($queue, 'operation.status'), 'Only retryable local statuses may be automatically resent.'],
    [preg_match('/GENERATION\s*=\s*["\']v7["\']/', $worker) === 1, 'Service worker cache generation must be current.'],
    [!str_contains($checklist, 'caches.open('), 'Checklist page must delegate authenticated document caching to the service worker.'],
    [preg_match('/request\.mode\s*===\s*["\']navigate["\']/', $worker) === 1, 'Offline navigation must be intercepted.'],
    [preg_match('/DOCUMENT_PREFIX\s*\+\s*user/', $worker) === 1, 'Offline documents must be isolated by server-observed authenticated user.'],
    [!str_contains($view, 'offline-user='), 'Checklist identity must not be accepted from a client-controlled query parameter.'],
    [str_contains($queue, 'operation.status') && str_contains($queue, 'retryable_error') && str_contains($queue, 'blocked'), 'The list must aggregate distinct synchronization outcomes.'],
];

foreach ($requirements as [$passed, $message]) {
    if (!$passed) {
        throw new RuntimeException($message);
    }
}

echo "Checklist offline prefetch contract OK.\n";
