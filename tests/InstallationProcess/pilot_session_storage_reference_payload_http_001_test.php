<?php
declare(strict_types=1);

// PILOT-SESSION-STORAGE-001 v10 §§3,6,8. Reuse the exact raw-HTTP harness with
// the independently fixed self-reference case; the production server remains
// a separate process and receives no test selector.
putenv('FMONITOR_TEST_SESSION_PAYLOAD_CASE=reference');
require __DIR__ . '/pilot_session_storage_malformed_payload_http_001_test.php';
