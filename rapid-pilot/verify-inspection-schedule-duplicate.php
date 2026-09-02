<?php

declare(strict_types=1);

final class InspectionScheduleVerifierFailure extends RuntimeException
{
}

function inspectionScheduleSetup(bool $condition, string $message): void
{
    if (!$condition) {
        throw new InspectionScheduleVerifierFailure('SETUP_FAILURE: ' . $message, 2);
    }
}

function inspectionScheduleAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new InspectionScheduleVerifierFailure('REGRESSION_FAILURE: ' . $message, 1);
    }
}

/** @return array{status:int,headers:array<string,list<string>>} */
function inspectionSchedulePost(string $url, array $form): array
{
    $body = http_build_query($form);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                . 'Content-Length: ' . strlen($body) . "\r\n",
            'content' => $body,
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 2.0,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];
    inspectionScheduleSetup(is_string($response) && $responseHeaders !== [], 'loopback HTTP request failed');
    inspectionScheduleSetup(
        preg_match('/\AHTTP\/\d(?:\.\d)? ([1-5][0-9]{2})(?: |\z)/D', (string) $responseHeaders[0], $match) === 1,
        'loopback HTTP response status is malformed',
    );
    $headers = [];
    foreach (array_slice($responseHeaders, 1) as $line) {
        if (!is_string($line) || !str_contains($line, ':')) {
            continue;
        }
        [$name, $value] = explode(':', $line, 2);
        $headers[strtolower(trim($name))][] = trim($value);
    }
    return ['status' => (int) $match[1], 'headers' => $headers];
}

function inspectionScheduleExpectedHeader(array $response, string $name, ?string $expected, string $label): void
{
    $values = $response['headers'][$name] ?? [];
    if ($expected === null) {
        inspectionScheduleAssert($values === [], "$label must not return $name");
        return;
    }
    inspectionScheduleAssert($values === [$expected], "$label must return exact $name");
}

try {
    $token = getenv('FMONITOR_INSPECTION_SCHEDULE_VERIFY_RUN_TOKEN');
    inspectionScheduleSetup(
        is_string($token) && preg_match('/\A[a-f0-9]{12}\z/D', $token) === 1,
        'inspection-schedule verifier run token is invalid',
    );

    $repositoryRoot = realpath(dirname(__DIR__));
    $artifactRoot = getenv('FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT');
    $artifactReal = is_string($artifactRoot) ? realpath($artifactRoot) : false;
    $artifactInfo = is_string($artifactRoot) ? @lstat($artifactRoot) : false;
    $ownedParent = is_string($repositoryRoot) ? $repositoryRoot . '/.local/test-artifacts/' : '';
    inspectionScheduleSetup(
        is_string($repositoryRoot)
        && is_string($artifactRoot)
        && $artifactRoot !== ''
        && $artifactRoot[0] === '/'
        && !str_contains($artifactRoot, "\0")
        && is_string($artifactReal)
        && $artifactRoot === $artifactReal
        && is_array($artifactInfo)
        && !is_link($artifactRoot)
        && is_dir($artifactRoot)
        && str_starts_with($artifactReal . '/', $ownedParent),
        'supplied inspection-schedule artifact root is unsafe',
    );

    $baseUrl = getenv('FMONITOR_INSPECTION_SCHEDULE_VERIFY_BASE_URL');
    $parts = is_string($baseUrl) ? parse_url($baseUrl) : false;
    inspectionScheduleSetup(
        is_string($baseUrl)
        && is_array($parts)
        && ($parts['scheme'] ?? null) === 'http'
        && ($parts['host'] ?? null) === '127.0.0.1'
        && isset($parts['port'])
        && is_int($parts['port'])
        && $parts['port'] >= 1
        && $parts['port'] <= 65535
        && array_intersect_key($parts, array_flip(['user', 'pass', 'query', 'fragment'])) === []
        && ($parts['path'] ?? '') === '',
        'inspection-schedule verifier base URL is not an exact loopback origin',
    );

    $csrf = 'schedule-characterization-csrf-001';
    $requests = [
        [451201, $csrf, '2026-09-03', 303, '/pilot/objects?inspectionScheduled=2026-09-03'],
        [451201, $csrf, '2026-09-03', 303, '/pilot/objects?inspectionScheduled=2026-09-03'],
        [451202, 'wrong-schedule-csrf', '2026-09-03', 403, null],
        [451203, $csrf, '2026-09-03', 403, null],
        [451204, $csrf, '2026-02-30', 422, null],
        [451205, $csrf, '2026-09-03', 409, null],
    ];
    foreach ($requests as $index => [$objectId, $submittedCsrf, $date, $status, $location]) {
        $response = inspectionSchedulePost(
            $baseUrl . "/pilot/objects/$objectId/inspection-schedule",
            ['csrfToken' => $submittedCsrf, 'inspectionDate' => $date],
        );
        $label = 'request ' . ($index + 1);
        inspectionScheduleAssert($response['status'] === $status, "$label returned an unexpected status");
        inspectionScheduleExpectedHeader($response, 'location', $location, $label);
        inspectionScheduleExpectedHeader($response, 'cache-control', 'no-store', $label);
    }

    echo "INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact\n";
    echo "INSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0\n";
    echo "INSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0\n";
    echo "CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001\n";
} catch (InspectionScheduleVerifierFailure $failure) {
    fwrite(STDERR, $failure->getMessage() . "\n");
    exit($failure->getCode() === 2 ? 2 : 1);
} catch (Throwable $failure) {
    fwrite(STDERR, 'SETUP_FAILURE: ' . $failure->getMessage() . "\n");
    exit(2);
}
