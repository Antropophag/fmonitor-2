<?php
declare(strict_types=1);

$queue = (string) file_get_contents(dirname(__DIR__).'/app/PilotHttp/control-queue.js');
$worker = (string) file_get_contents(dirname(__DIR__).'/app/PilotHttp/checklist-sw.js');
$checklist = (string) file_get_contents(dirname(__DIR__).'/app/PilotHttp/checklist.js');
$view = (string) file_get_contents(dirname(__DIR__).'/app/PilotHttp/ConstructionControlView.php');

$requirements = [
    [str_contains($queue, "register('/pilot/assets/checklist-sw.js'"), 'Construction-control queue must register the checklist service worker.'],
    [str_contains($queue, "type:'CACHE_CHECKLIST'"), 'Construction-control queue must request prefetch for listed checklist URLs.'],
    [str_contains($queue, 'prefetchChecklists()'), 'Checklist prefetch must run while the queue is viewed.'],
    [str_contains($queue, "item_installers_changed:1"), 'Installer corrections must sync before section completion.'],
    [str_contains($queue, "['queued','sending','retryable_error'].includes(operation.status)"), 'Only retryable local statuses may be automatically resent.'],
    [str_contains($worker, "const CACHE='fmonitor2-checklist-shell-v6'"), 'Service worker and page cache generation must be current.'],
    [str_contains($checklist, "caches.open('fmonitor2-checklist-shell-v6')"), 'Checklist page must write into the same cache generation as the worker.'],
    [str_contains($worker, "request.mode==='navigate'"), 'Offline navigation must be intercepted.'],
    [str_contains($worker, 'const cached=await cache.match(request.url)'), 'Offline navigation must fall back to the exact prefetched checklist URL.'],
    [str_contains($view, '/checklist?offline-user='), 'Prefetched checklist cache keys must be isolated by the authenticated user.'],
];

foreach ($requirements as [$passed, $message]) {
    if (!$passed) {
        throw new RuntimeException($message);
    }
}

echo "Checklist offline prefetch contract OK.\n";
