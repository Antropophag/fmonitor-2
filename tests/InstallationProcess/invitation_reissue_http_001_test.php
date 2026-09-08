<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/autoload.php';
require_once dirname(__DIR__, 2) . '/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__) . '/Support/SelectionHttpFixture.php';

use FMonitor2\Tests\Support\SelectionHttpFixture;
use FMonitor2\IdentityAccess\MariaDbReissueUserInvitation;

// Owner feedback #46: an administrator can recover an invitation after leaving its one-time URL screen.
$fixture = null;
try {
    $fixture = new SelectionHttpFixture(true, static fn (): array => [
        'FMONITOR_NOW' => '2026-09-08T12:00:00+03:00',
    ]);
    $db = $fixture->original->selection->db;
    $db->query("INSERT INTO fm2_pilot_role_permissions(role_id,permission) VALUES(4,'access.administer')");
    if ((int) $db->query("SELECT COUNT(*) n FROM fm2_pilot_roles WHERE code='user'")->fetch_assoc()['n'] === 0) {
        $db->query("INSERT INTO fm2_pilot_roles(code,name,description,status,source_updated_at) VALUES('user','Пользователь','Default invitation fixture role',1,'2026-09-08T12:00:00+03:00')");
    }
    $sameOrigin = ['Origin' => 'http://127.0.0.1:' . $fixture->port, 'Sec-Fetch-Site' => 'same-origin'];

    $users = $fixture->request('GET', '/pilot/admin/users', '', 99);
    assertSameValue(200, $users['status'], 'authorized administrator opens user directory');
    if (preg_match('#<form method="post" action="/pilot/admin/users/invite".*?name="csrfToken" value="([0-9a-f]{32})"#s', $users['body'], $inviteForm) !== 1) {
        throw new TestFailure('SETUP_FAILURE: create invitation form and token');
    }

    $email = 'reissue.fixture@shlz.ru';
    $create = $fixture->request('POST', '/pilot/admin/users/invite', http_build_query([
        'csrfToken' => $inviteForm[1],
        'email' => $email,
        'fullName' => 'Получатель Перевыпуска',
    ]), 99, $sameOrigin);
    assertSameValue([303, '/pilot/admin/users'], [$create['status'], $create['headers']['location'] ?? null], 'create invitation redirects to directory; server log: ' . (string) @file_get_contents($fixture->original->control . '/http.log'));
    $created = $fixture->request('GET', '/pilot/admin/users', '', 99);
    if (preg_match('#/pilot/activate\?token=([A-Za-z0-9_-]{43})#', $created['body'], $firstLink) !== 1) {
        throw new TestFailure('SETUP_FAILURE: first invitation URL rendered once');
    }
    $firstToken = $firstLink[1];
    $user = $db->query("SELECT user_id,full_name,email,status,activation_state FROM fm2_pilot_users WHERE email='reissue.fixture@shlz.ru'")->fetch_assoc();
    if (!is_array($user)) throw new TestFailure('SETUP_FAILURE: invited user persisted');
    $userId = (int) $user['user_id'];
    $rolesBefore = $db->query("SELECT user_id,role_id,origin,assigned_at,assigned_by_user_id FROM fm2_pilot_user_roles WHERE user_id={$userId} ORDER BY role_id")->fetch_all(MYSQLI_ASSOC);
    assertSameValue(1, count($rolesBefore), 'new invited user has the default role');

    assertSameValue(200, $fixture->request('GET', '/pilot/admin/roles', '', 99)['status'], 'administrator leaves the one-time invitation result');
    $returned = $fixture->request('GET', '/pilot/admin/users', '', 99);
    assertSameValue(200, $returned['status'], 'administrator returns to user directory');
    assertSameValue(false, str_contains($returned['body'], $firstToken), 'consumed invitation flash does not disclose old token after return');
    $quotedUserId = preg_quote((string) $userId, '#');
    if (preg_match('#<form[^>]+action="/pilot/admin/users/' . $quotedUserId . '/invitation"[^>]*>.*?name="csrfToken" value="([0-9a-f]{32})".*?<button[^>]*>\s*Перевыпустить приглашение\s*</button>#s', $returned['body'], $reissueForm) !== 1) {
        throw new TestFailure('INTENTIONAL_RED: pending user exposes a CSRF-protected reissue action after return');
    }

    $beforeRejectedHttp = $fixture->original->selection->rows();
    $missingCsrf = $fixture->request('POST', "/pilot/admin/users/{$userId}/invitation", '', 99, $sameOrigin);
    assertSameValue(403, $missingCsrf['status'], 'reissue rejects missing CSRF');
    $invalidCsrf = $fixture->request('POST', "/pilot/admin/users/{$userId}/invitation", http_build_query(['csrfToken' => str_repeat('f', 32)]), 99, $sameOrigin);
    assertSameValue(403, $invalidCsrf['status'], 'reissue rejects invalid CSRF');
    $getCommand = $fixture->request('GET', "/pilot/admin/users/{$userId}/invitation", '', 99);
    assertSameValue(405, $getCommand['status'], 'reissue command refuses GET');
    assertSameValue($beforeRejectedHttp, $fixture->original->selection->rows(), 'missing/invalid CSRF and GET create no user, role or invitation facts');

    $beforeForbidden = $fixture->original->selection->rows();
    $forbidden = $fixture->request('POST', "/pilot/admin/users/{$userId}/invitation", http_build_query(['csrfToken' => $reissueForm[1]]), 18, $sameOrigin);
    assertSameValue(403, $forbidden['status'], 'user without access.administer cannot reissue invitation');
    assertSameValue($beforeForbidden, $fixture->original->selection->rows(), 'rejected reissue preserves users, roles and invitation history');

    $reissue = $fixture->request('POST', "/pilot/admin/users/{$userId}/invitation", http_build_query(['csrfToken' => $reissueForm[1]]), 99, $sameOrigin);
    assertSameValue([303, '/pilot/admin/users'], [$reissue['status'], $reissue['headers']['location'] ?? null], 'authorized reissue redirects to directory');
    $reissued = $fixture->request('GET', '/pilot/admin/users', '', 99);
    if (preg_match('#/pilot/activate\?token=([A-Za-z0-9_-]{43})#', $reissued['body'], $secondLink) !== 1) {
        throw new TestFailure('new invitation URL rendered after reissue');
    }
    $secondToken = $secondLink[1];
    assertSameValue(false, hash_equals($firstToken, $secondToken), 'reissue creates a distinct token');

    $sameUser = $db->query("SELECT user_id,full_name,email,status,activation_state FROM fm2_pilot_users WHERE email='reissue.fixture@shlz.ru'")->fetch_all(MYSQLI_ASSOC);
    assertSameValue([$user], $sameUser, 'reissue preserves the same pending user identity and status');
    assertSameValue($rolesBefore, $db->query("SELECT user_id,role_id,origin,assigned_at,assigned_by_user_id FROM fm2_pilot_user_roles WHERE user_id={$userId} ORDER BY role_id")->fetch_all(MYSQLI_ASSOC), 'reissue preserves the same role grants');
    $history = $db->query("SELECT user_id,used_at,revoked_at,created_by_user_id FROM fm2_pilot_invitations WHERE user_id={$userId} ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    assertSameValue(2, count($history), 'reissue appends a second invitation history row');
    assertSameValue(true, $history[0]['revoked_at'] !== null && $history[0]['used_at'] === null, 'old unused invitation is revoked, not overwritten or deleted');
    assertSameValue([$userId, null, null, 99], [(int) $history[1]['user_id'], $history[1]['used_at'], $history[1]['revoked_at'], (int) $history[1]['created_by_user_id']], 'new invitation is live and attributed to administrator');

    $application = new MariaDbReissueUserInvitation($db, '');
    $historyBeforeDenied = $db->query('SELECT id,user_id,used_at,revoked_at FROM fm2_pilot_invitations ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    assertSameValue(['status' => 'access_denied'], $application->reissue(18, $userId), 'application seam denies active actor without access.administer');
    assertSameValue(['status' => 'not_invited'], $application->reissue(99, 18), 'application seam rejects an already active target');
    assertSameValue(['status' => 'not_invited'], $application->reissue(99, 999999999), 'application seam rejects a missing target');
    $db->query("INSERT INTO fm2_pilot_users(full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES('Blocked invitation target','blocked.reissue@shlz.ru','',0,'blocked',1,'2026-09-08T12:00:00+03:00')");
    $blockedId = (int) $db->insert_id;
    assertSameValue(['status' => 'not_invited'], $application->reissue(99, $blockedId), 'application seam rejects a blocked target');
    assertSameValue($historyBeforeDenied, $db->query('SELECT id,user_id,used_at,revoked_at FROM fm2_pilot_invitations ORDER BY id')->fetch_all(MYSQLI_ASSOC), 'authorization and state refusals preserve invitation history');

    $defaultRole = (int) $db->query("SELECT role_id FROM fm2_pilot_roles WHERE code='user'")->fetch_assoc()['role_id'];
    $db->query("INSERT INTO fm2_pilot_users(full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES('Expired invitation target','expired.reissue@shlz.ru','',1,'invited',1,'2026-09-08T12:00:00+03:00')");
    $expiredId = (int) $db->insert_id;
    $db->query("INSERT INTO fm2_pilot_auth_credentials(user_id,email_normalized,password_hash,password_set_at,updated_at) VALUES({$expiredId},'expired.reissue@shlz.ru',NULL,NULL,'2026-09-08T12:00:00+03:00')");
    $db->query("INSERT INTO fm2_pilot_user_roles(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES({$expiredId},{$defaultRole},'administrator','2026-09-08T12:00:00+03:00',99)");
    $expiredHash = hash('sha256', str_repeat('E', 43), true);
    $expiredInsert = $db->prepare('INSERT INTO fm2_pilot_invitations(user_id,token_hash,expires_at,created_by_user_id,created_at) VALUES(?,?,DATE_SUB(NOW(6),INTERVAL 1 HOUR),99,DATE_SUB(NOW(6),INTERVAL 25 HOUR))');
    $expiredInsert->bind_param('is', $expiredId, $expiredHash);
    $expiredInsert->execute();
    $expiredRoles = $db->query("SELECT user_id,role_id,origin,assigned_at,assigned_by_user_id FROM fm2_pilot_user_roles WHERE user_id={$expiredId}")->fetch_all(MYSQLI_ASSOC);
    $expiredResult = $application->reissue(99, $expiredId);
    assertSameValue(['issued', 43], [$expiredResult['status'], strlen((string) ($expiredResult['token'] ?? ''))], 'application seam reissues an expired invitation');
    $repeatResult = $application->reissue(99, $expiredId);
    assertSameValue(['issued', 43], [$repeatResult['status'], strlen((string) ($repeatResult['token'] ?? ''))], 'sequential application command issues another distinct invitation');
    assertSameValue(false, hash_equals((string) $expiredResult['token'], (string) $repeatResult['token']), 'sequential reissue rotates token again');
    $expiredHistory = $db->query("SELECT used_at,revoked_at,created_by_user_id FROM fm2_pilot_invitations WHERE user_id={$expiredId} ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    assertSameValue([3, 2, 1], [count($expiredHistory), count(array_filter($expiredHistory, static fn (array $row): bool => $row['revoked_at'] !== null)), count(array_filter($expiredHistory, static fn (array $row): bool => $row['used_at'] === null && $row['revoked_at'] === null))], 'expired and repeated reissue append history with exactly one live invitation');
    assertSameValue($expiredRoles, $db->query("SELECT user_id,role_id,origin,assigned_at,assigned_by_user_id FROM fm2_pilot_user_roles WHERE user_id={$expiredId}")->fetch_all(MYSQLI_ASSOC), 'expired and repeated reissue preserve target roles');

    $db->query("INSERT INTO fm2_pilot_users(full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES('Concurrent invitation target','concurrent.reissue@shlz.ru','',1,'invited',1,'2026-09-08T12:00:00+03:00')");
    $concurrentId = (int) $db->insert_id;
    $db->query("INSERT INTO fm2_pilot_auth_credentials(user_id,email_normalized,password_hash,password_set_at,updated_at) VALUES({$concurrentId},'concurrent.reissue@shlz.ru',NULL,NULL,'2026-09-08T12:00:00+03:00')");
    $db->query("INSERT INTO fm2_pilot_user_roles(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES({$concurrentId},{$defaultRole},'administrator','2026-09-08T12:00:00+03:00',99)");
    $concurrentHash = hash('sha256', str_repeat('C', 43), true);
    $concurrentInsert = $db->prepare('INSERT INTO fm2_pilot_invitations(user_id,token_hash,expires_at,created_by_user_id,created_at) VALUES(?,?,DATE_ADD(NOW(6),INTERVAL 1 HOUR),99,NOW(6))');
    $concurrentInsert->bind_param('is', $concurrentId, $concurrentHash);
    $concurrentInsert->execute();
    $worker = dirname(__DIR__) . '/Support/invitation_reissue_worker.php';
    $workerEnvironment = array_replace(getenv(), [
        'FMONITOR_TEST_DB_PORT' => (string) (getenv('FMONITOR_TEST_DB_PORT') ?: '23306'),
        'FMONITOR_TEST_DB_ADMIN_PASSWORD' => (string) (getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local'),
    ]);
    $processes = [];
    foreach ([1, 2] as $ordinal) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, $worker, $fixture->original->selection->schema->source->name, '', '99', (string) $concurrentId], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2), $workerEnvironment);
        if (!is_resource($process)) throw new TestFailure("SETUP_FAILURE: concurrent reissue worker {$ordinal}");
        $processes[] = [$process, $pipes];
    }
    $workerResults = [];
    foreach ($processes as [$process, $pipes]) {
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        assertSameValue([0, ''], [$exit, $err], 'concurrent reissue worker exits without diagnostics');
        $workerResults[] = json_decode(trim((string) $out), true, 8, JSON_THROW_ON_ERROR);
    }
    assertSameValue(['issued', 'issued'], array_map(static fn (array $result): string => (string) $result['status'], $workerResults), 'both serialized concurrent reissues complete');
    $concurrentHistory = $db->query("SELECT used_at,revoked_at FROM fm2_pilot_invitations WHERE user_id={$concurrentId} ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    assertSameValue([3, 2, 1], [count($concurrentHistory), count(array_filter($concurrentHistory, static fn (array $row): bool => $row['revoked_at'] !== null)), count(array_filter($concurrentHistory, static fn (array $row): bool => $row['used_at'] === null && $row['revoked_at'] === null))], 'concurrent reissues serialize with append-only history and one live invitation');

    $db->query("INSERT INTO fm2_pilot_users(full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES('Rollback invitation target','rollback.reissue@shlz.ru','',1,'invited',1,'2026-09-08T12:00:00+03:00')");
    $rollbackId = (int) $db->insert_id;
    $rollbackHash = hash('sha256', str_repeat('R', 43), true);
    $rollbackInsert = $db->prepare('INSERT INTO fm2_pilot_invitations(user_id,token_hash,expires_at,created_by_user_id,created_at) VALUES(?,?,DATE_ADD(NOW(6),INTERVAL 1 HOUR),99,NOW(6))');
    $rollbackInsert->bind_param('is', $rollbackId, $rollbackHash);
    $rollbackInsert->execute();
    $trigger = 'fixture_reissue_insert_failure_' . bin2hex(random_bytes(4));
    $db->query("CREATE TRIGGER `{$trigger}` BEFORE INSERT ON fm2_pilot_invitations FOR EACH ROW BEGIN IF NEW.user_id={$rollbackId} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture reissue insert failure'; END IF; END");
    try {
        try {
            $application->reissue(99, $rollbackId);
            throw new TestFailure('rollback fixture must inject invitation insert failure');
        } catch (mysqli_sql_exception $error) {
            assertSameValue(true, str_contains($error->getMessage(), 'fixture reissue insert failure'), 'test trigger reaches insertion after attempted revocation');
        }
    } finally {
        $db->query("DROP TRIGGER `{$trigger}`");
    }
    $rollbackHistory = $db->query("SELECT used_at,revoked_at FROM fm2_pilot_invitations WHERE user_id={$rollbackId}")->fetch_all(MYSQLI_ASSOC);
    assertSameValue([["used_at" => null, "revoked_at" => null]], $rollbackHistory, 'failed insertion rolls back revocation and preserves the prior live invitation');

    $oldActivation = $fixture->request('GET', '/pilot/activate?token=' . $firstToken, '', null);
    assertSameValue(true, str_contains($oldActivation['body'], 'Ссылка активации недействительна или истекла.'), 'revoked old token is rejected by activation endpoint');
    $newActivation = $fixture->request('GET', '/pilot/activate?token=' . $secondToken, '', null);
    assertSameValue(true, str_contains($newActivation['body'], $email), 'new token opens activation for the same user');
    $password = 'Safe invitation passphrase 2026!';
    $activated = $fixture->request('POST', '/pilot/activate', http_build_query([
        'token' => $secondToken,
        'password' => $password,
        'passwordConfirmation' => $password,
    ]), null);
    assertSameValue(true, str_contains($activated['body'], 'Учётная запись активирована'), 'new invitation activates successfully');
    $credential = $db->query("SELECT u.user_id,u.activation_state,c.password_hash FROM fm2_pilot_users u JOIN fm2_pilot_auth_credentials c ON c.user_id=u.user_id WHERE u.user_id={$userId}")->fetch_assoc();
    assertSameValue([$userId, 'active', true], [(int) $credential['user_id'], $credential['activation_state'], password_verify($password, (string) $credential['password_hash'])], 'activation updates the same user credential');
    assertSameValue($rolesBefore, $db->query("SELECT user_id,role_id,origin,assigned_at,assigned_by_user_id FROM fm2_pilot_user_roles WHERE user_id={$userId} ORDER BY role_id")->fetch_all(MYSQLI_ASSOC), 'activation preserves the same role grants');

    echo "PASS invitation reissue HTTP lifecycle\n";
} finally {
    if ($fixture !== null) $fixture->close();
}
