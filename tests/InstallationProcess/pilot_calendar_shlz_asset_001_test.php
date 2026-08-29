<?php

declare(strict_types=1);

// Specification: PILOT-CALENDAR-SHLZ-ASSET-001.

$root = dirname(__DIR__, 2);
$dockerfile = (string) file_get_contents($root . '/Dockerfile');
$revision = 'a0a8ca6df60b84aa1fe10a1cb500de32dacd4516';
if (!str_contains($dockerfile, "ARG SHLZ_UI_REVISION={$revision}")) {
    throw new RuntimeException('Pilot image is not pinned to the approved Calendar Grid export revision.');
}
if (!str_contains($dockerfile, 'git -C /shlz-ui checkout --detach "${SHLZ_UI_REVISION}"')
    || !str_contains($dockerfile, 'test "$(git -C /shlz-ui rev-parse HEAD)" = "${SHLZ_UI_REVISION}"')) {
    throw new RuntimeException('Pilot dependency build does not verify that the copied checkout matches its revision pin.');
}
if (!str_contains($dockerfile, 'COPY --from=shlz-ui-build /shlz-ui /workspace/shlz-ui')) {
    throw new RuntimeException('Pilot runtime does not copy the probed public artifact set.');
}
$iid = tempnam(sys_get_temp_dir(), 'fm2-calendar-image-');
if ($iid === false) throw new RuntimeException('Unable to allocate image receipt.');
unlink($iid);

$build = ['docker', 'build', '--target', 'shlz-ui-build', '--iidfile', $iid, $root];
$process = proc_open($build, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if (!is_resource($process)) throw new RuntimeException('Unable to start the pilot dependency build.');
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
$status = proc_close($process);
if ($status !== 0 || !is_file($iid)) {
    @unlink($iid);
    throw new RuntimeException("Pilot dependency build failed:\n{$stdout}\n{$stderr}");
}

$image = trim((string) file_get_contents($iid));
unlink($iid);
$probe = proc_open([
    'docker', 'run', '--rm', $image, 'sh', '-lc',
    "test -s /shlz-ui/packages/behaviors/dist/calendar-grid.js"
    . " && grep -q 'shlz-calendar-grid' /shlz-ui/packages/styles/dist/shlz.css"
    . " && grep -q '\"./calendar-grid\"' /shlz-ui/packages/behaviors/package.json",
], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if (!is_resource($probe)) throw new RuntimeException('Unable to inspect the built public artifact set.');
$probeOutput = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
$probeStatus = proc_close($probe);
if ($probeStatus !== 0) {
    throw new RuntimeException("Built pilot dependency lacks the public Calendar Grid artifacts. {$probeOutput}");
}

echo "PILOT-CALENDAR-SHLZ-ASSET-001 passed\n";
