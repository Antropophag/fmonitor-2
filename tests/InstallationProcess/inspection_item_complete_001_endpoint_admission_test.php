<?php
declare(strict_types=1);
require dirname(__DIR__) . "/bootstrap.php";
function ieaQ(string $s): string
{
    return "`" . str_replace("`", "``", $s) . "`";
}
function ieaDb(?string $d = null): mysqli
{
    $c = new mysqli(
        getenv("FMONITOR_TEST_DB_HOST") ?: "127.0.0.1",
        getenv("FMONITOR_TEST_DB_ADMIN_USER") ?: "root",
        getenv("FMONITOR_TEST_DB_ADMIN_PASSWORD") ?:
        "fmonitor2_test_root_local",
        $d,
        (int) (getenv("FMONITOR_TEST_DB_PORT") ?: 23306),
    );
    $c->set_charset("utf8mb4");
    return $c;
}
function ieaMigrate(string $d, string $p): void
{
    $e = [
        "FMONITOR_DB_HOST" => getenv("FMONITOR_TEST_DB_HOST") ?: "127.0.0.1",
        "FMONITOR_DB_PORT" => getenv("FMONITOR_TEST_DB_PORT") ?: "23306",
        "FMONITOR_DB_NAME" => $d,
        "FMONITOR_DB_USER" => getenv("FMONITOR_TEST_DB_ADMIN_USER") ?: "root",
        "FMONITOR_DB_PASSWORD" =>
            getenv("FMONITOR_TEST_DB_ADMIN_PASSWORD") ?:
            "fmonitor2_test_root_local",
        "FMONITOR_PROCESS_TABLE_PREFIX" => $p,
    ];
    $c = ["env"];
    foreach ($e as $k => $v) {
        $c[] = "$k=$v";
    }
    $c[] = "php";
    $c[] = dirname(__DIR__, 2) . "/bin/fmonitor2-migrate.php";
    $pipes = [];
    $r = proc_open(
        $c,
        [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]],
        $pipes,
        dirname(__DIR__, 2),
    );
    if (!is_resource($r)) {
        throw new TestFailure("SETUP_FAILURE: migrate start");
    }
    fclose($pipes[0]);
    $o = stream_get_contents($pipes[1]);
    $x = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    assertSameValue(
        0,
        proc_close($r),
        "SETUP_FAILURE: canonical v1-v8 " . $o . $x,
    );
}
function ieaPort(): int
{
    $s = stream_socket_server("tcp://127.0.0.1:0", $e, $m);
    if ($s === false) {
        throw new TestFailure("SETUP_FAILURE: port");
    }
    $n = (string) stream_socket_get_name($s, false);
    fclose($s);
    return (int) substr($n, strrpos($n, ":") + 1);
}
function ieaStart(array $e): array
{
    global $ieaRouterReaped;
    $ieaRouterReaped = false;
    $p = ieaPort();
    $c = ["/usr/bin/env", "-i"];
    foreach ($e as $k => $v) {
        $c[] = "$k=$v";
    }
    $c = [
        ...$c,
        PHP_BINARY,
        "-d",
        "expose_php=0",
        "-S",
        "127.0.0.1:$p",
        dirname(__DIR__, 2) . "/public/router.php",
    ];
    $pipes = [];
    $r = proc_open(
        $c,
        [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]],
        $pipes,
        dirname(__DIR__, 2),
    );
    if (!is_resource($r)) {
        throw new TestFailure("SETUP_FAILURE: HTTP start");
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $end = microtime(true) + 5;
    do {
        if (!proc_get_status($r)["running"]) {
            ieaStop(["process" => $r, "pipes" => $pipes, "port" => $p]);
            throw new TestFailure("SETUP_FAILURE: HTTP exited");
        }
        $s = @stream_socket_client("tcp://127.0.0.1:$p", $x, $m, 0.1);
        if ($s !== false) {
            fclose($s);
            return ["process" => $r, "pipes" => $pipes, "port" => $p];
        }
        usleep(50000);
    } while (microtime(true) < $end);
    ieaStop(["process" => $r, "pipes" => $pipes, "port" => $p]);
    throw new TestFailure("SETUP_FAILURE: HTTP listen");
}
function ieaStop(?array $s): void
{
    global $ieaCleanupFailures, $ieaLastRouterPid;
    global $ieaRouterReaped;
    if ($s === null || !is_resource($s["process"])) {
        return;
    }
    $status = proc_get_status($s["process"]);
    $pid = (int) ($status["pid"] ?? 0);
    $ieaLastRouterPid = $pid;
    $deadline = microtime(true) + 2;
    if ($status["running"]) {
        proc_terminate($s["process"]);
    }
    do {
        foreach ([1, 2] as $i) {
            while (
                is_resource($s["pipes"][$i]) &&
                ($chunk = fread($s["pipes"][$i], 8192)) !== false &&
                $chunk !== ""
            ) {
            };
        }
        $status = proc_get_status($s["process"]);
        if (!$status["running"]) {
            break;
        }
        usleep(10000);
    } while (microtime(true) < $deadline);
    if ($status["running"]) {
        proc_terminate($s["process"], 9);
        $deadline = microtime(true) + 1;
        do {
            $status = proc_get_status($s["process"]);
            if (!$status["running"]) {
                break;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
    }
    foreach ([1, 2] as $i) {
        if (is_resource($s["pipes"][$i])) {
            fclose($s["pipes"][$i]);
        }
    }
    if ($status["running"]) {
        $ieaCleanupFailures[] = "owned process $pid remains running after TERM/KILL deadline";
        return;
    }
    proc_close($s["process"]);
    $ieaRouterReaped = true;
    if ($pid > 0 && function_exists("posix_kill") && @posix_kill($pid, 0)) {
        $ieaCleanupFailures[] = "owned process $pid remains alive after reap";
    }
}
function ieaRequest(
    int $p,
    string $m,
    string $path,
    array $headers = [],
    string $body = "",
): array {
    $s = stream_socket_client("tcp://127.0.0.1:$p", $e, $x, 2);
    if ($s === false) {
        throw new TestFailure("HTTP connect");
    }
    stream_set_timeout($s, 2);
    $raw = "$m $path HTTP/1.1\r\nHost: pilot.example\r\nConnection: close\r\n";
    foreach ($headers as $k => $v) {
        $raw .= "$k: $v\r\n";
    }
    $raw .= "\r\n$body";
    for ($o = 0, $n = strlen($raw); $o < $n; ) {
        $w = fwrite($s, substr($raw, $o));
        if ($w === false || $w === 0) {
            throw new TestFailure("HTTP write");
        }
        $o += $w;
    }
    stream_socket_shutdown($s, STREAM_SHUT_WR);
    $all = "";
    $end = microtime(true) + 3;
    while (!feof($s) && microtime(true) < $end) {
        $chunk = fread($s, 65536);
        if ($chunk === false) {
            break;
        }
        $all .= $chunk;
        if (strlen($all) > 1048576) {
            throw new TestFailure("HTTP response cap");
        }
    }
    $meta = stream_get_meta_data($s);
    fclose($s);
    if (($meta["timed_out"] ?? false) || microtime(true) >= $end) {
        throw new TestFailure("HTTP deadline");
    }
    [$h, $b] = array_pad(explode("\r\n\r\n", $all, 2), 2, "");
    preg_match("/^HTTP\/\d\.\d (\d{3})/", $h, $mm);
    $hs = [];
    foreach (array_slice(explode("\r\n", $h), 1) as $l) {
        if (str_contains($l, ":")) {
            [$k, $v] = explode(":", $l, 2);
            $hs[strtolower(trim($k))][] = trim($v);
        }
    }
    return ["status" => (int) ($mm[1] ?? 0), "headers" => $hs, "body" => $b];
}
function ieaSnapshot(mysqli $db, string $p, string $root): array
{
    $out = [];
    foreach (
        [
            "fm2_checklist_revisions",
            "fm2_checklist_operations",
            "fm2_checklist_operation_installers",
            "fm2_checklist_photos",
        ]
        as $b
    ) {
        $n = $p . $b;
        $out[$b] = [
            "create" => $db
                ->query("SHOW CREATE TABLE " . ieaQ($n))
                ->fetch_assoc()["Create Table"],
            "rows" => $db
                ->query("SELECT * FROM " . ieaQ($n) . " ORDER BY 1,2")
                ->fetch_all(MYSQLI_ASSOC),
        ];
    }
    $files = [];
    $sessions = ieaSessionEntries($root, "iea-" . substr(basename($root), 4));
    if (is_dir($root)) {
        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    FilesystemIterator::SKIP_DOTS,
                ),
            )
            as $f
        ) {
            if ($f->isFile()) {
                $entry =
                    substr($f->getPathname(), strlen($root) + 1) .
                    "|" .
                    hash_file("sha256", $f->getPathname());
                if (!str_starts_with($f->getPathname(), $root . "/sessions/")) {
                    $files[] = $entry;
                }
            }
        }
        sort($files);
        sort($sessions);
    }
    return ["tables" => $out, "artifacts" => $files, "sessions" => $sessions];
}
function ieaSessionEntries(string $root,string $instance):array
{
    if(preg_match('/^[a-z0-9][a-z0-9_-]{0,31}$/D',$instance)!==1)throw new TestFailure('Invalid owned session instance.');
    $sessions=$root.'/sessions';$managed=$sessions.'/'.$instance;$uid=posix_geteuid();
    foreach([$sessions,$managed]as$directory){$stat=@lstat($directory);if($stat===false||is_link($directory)||($stat['mode']&0170000)!==0040000||($stat['mode']&0777)!==0700||$stat['uid']!==$uid)throw new TestFailure('Session directory identity/mode unavailable.');}
    $children=array_values(array_diff(scandir($sessions)?:[],['.','..']));if($children!==[$instance])throw new TestFailure('Unexpected session directory entry.');
    $entries=[];foreach(array_values(array_diff(scandir($managed)?:[],['.','..']))as$name){$path=$managed.'/'.$name;$stat=@lstat($path);if($stat===false||is_link($path)||($stat['mode']&0170000)!==0100000||($stat['mode']&0777)!==0600||$stat['uid']!==$uid)throw new TestFailure('Session entry identity/type/mode unavailable.');$id='[A-Za-z0-9,-]{16,128}';$hash='[0-9a-f]{64}';$token='[0-9a-f]{32}';$valid=preg_match('/^(?:s-'.$id.'\.session|l-'.$hash.'\.lock|\.stage-'.$hash.'-'.$token.'\.session|\.revoked-'.$hash.'-'.$token.'\.session)$/D',$name)===1;if(!$valid)throw new TestFailure('Unexpected session filename.');if(dirname($path)!==$managed)throw new TestFailure('Session path escaped managed instance.');$entries[]='sessions/'.$instance.'/'.$name.'|'.hash_file('sha256',$path);}sort($entries,SORT_STRING);return$entries;
}
function ieaSessionOwnershipSensitivity(string$root,string$instance):void
{
    $cases=['unexpected-file','dangling-symlink','file-symlink','unexpected-dir','dir-symlink','fifo','socket','path-escape'];
    foreach($cases as$case){$probe=$root.'/sensitivity-'.$case;$sessions=$probe.'/sessions';$managed=$sessions.'/'.$instance;mkdir($managed,0700,true);$outside=$probe.'/outside';file_put_contents($outside,'x');chmod($outside,0600);$socket=null;
        if($case==='unexpected-file'){file_put_contents($managed.'/unexpected', 'x');chmod($managed.'/unexpected',0600);}
        elseif($case==='dangling-symlink')symlink($probe.'/missing',$managed.'/s-aaaaaaaaaaaaaaaa.session');
        elseif($case==='file-symlink')symlink($outside,$managed.'/s-aaaaaaaaaaaaaaaa.session');
        elseif($case==='unexpected-dir')mkdir($managed.'/extra',0700);
        elseif($case==='dir-symlink'){mkdir($probe.'/other',0700);symlink($probe.'/other',$managed.'/extra');}
        elseif($case==='fifo')posix_mkfifo($managed.'/s-aaaaaaaaaaaaaaaa.session',0600);
        elseif($case==='socket'){$short='/home/antropophag/code/iea-socket-'.substr(hash('sha256',$probe),0,12);$socket=stream_socket_server('unix://'.$short);if(!is_resource($socket)||!rename($short,$managed.'/s-aaaaaaaaaaaaaaaa.session'))throw new TestFailure('SETUP_FAILURE: socket sensitivity fixture');}
        else{ieaRemoveOwned($probe);mkdir($probe,0700,true);mkdir($probe.'/real-sessions',0700);symlink($probe.'/real-sessions',$sessions);}
        $rejected=false;try{ieaSessionEntries($probe,$instance);}catch(TestFailure){$rejected=true;}assertSameValue(true,$rejected,'Session ownership sensitivity rejects '.$case);if(is_resource($socket))fclose($socket);ieaRemoveOwned($probe);assertSameValue(false,file_exists($probe)||is_link($probe),'Sensitivity cleanup removes '.$case);
    }
}
function ieaGet(int $p, string $path): array
{
    global $db, $q, $root;
    $hash = str_repeat("a", 64);
    $payload = $db->real_escape_string(
        '{"sections":[{"id":1,"items":[{"id":28}]}]}',
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_checklist_template_snapshots",
        )} VALUES(9101,'fixture-v1','2026-08-01','2026-08-01','installation','fixture','$hash','$payload','2026-08-01')",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_checklist_template_associations",
        )} VALUES(9001,'fixture-association','operational_case','9512','2026-08-01',9101,'fixture-v1','$hash','2026-08-01')",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_checklist_revisions",
        )} VALUES(9512,0,'2026-09-01T09:00:00+03:00')",
    );
    $prefix = substr($q("x"), 1, -2);
    $before = ieaSnapshot($db, $prefix, $root);
    $page = ieaRequest($p, "GET", "/pilot/objects/4512/checklist");
    if (getenv("FMONITOR_TEST_ITEM_UI_CONTRACT") === "1") {
        ieaAssertItemOnlyUiContract($page);
    }
    preg_match('/data-csrf="([^"]+)"/', $page["body"], $token);
    $cookie = explode(";", $page["headers"]["set-cookie"][0] ?? "", 2)[0];
    $operation = [
        "clientOperationId" => "11111111-1111-4111-8111-111111111111",
        "deviceInstallationId" => "not-a-uuid",
        "type" => "item_completed",
        "deviceTime" => "2026-09-01T08:55:00+03:00",
        "baseRevision" => 0,
        "sectionId" => 1,
        "itemId" => 28,
        "installerTabIds" => [1042],
    ];
    $body = json_encode($operation, JSON_THROW_ON_ERROR);
    $headers = [
        "Cookie" => $cookie,
        "Origin" => "https://pilot.example",
        "Sec-Fetch-Site" => "same-origin",
        "Content-Type" => "application/json",
        "Content-Length" => (string) strlen($body),
        "X-FM2-CSRF" => $token[1] ?? "",
    ];
    $post = ieaRequest(
        $p,
        "POST",
        "/pilot/objects/4512/checklist/operations",
        $headers,
        $body,
    );
    $other = $operation;
    $other["clientOperationId"] = "33333333-3333-4333-8333-333333333333";
    $other["deviceInstallationId"] = "22222222-2222-4222-8222-222222222222";
    $other["type"] = "section_completed";
    $otherBody = json_encode($other, JSON_THROW_ON_ERROR);
    $headers["Content-Length"] = (string) strlen($otherBody);
    $nonItem = ieaRequest(
        $p,
        "POST",
        "/pilot/objects/4512/checklist/operations",
        $headers,
        $otherBody,
    );
    $context = ieaRequest($p, "GET", $path);
    $after = ieaSnapshot($db, $prefix, $root);
    $postJson = json_decode($post["body"], true, 512, JSON_THROW_ON_ERROR);
    $contextJson = json_decode(
        $context["body"],
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    assertSameValue($before["tables"], $after["tables"], "Admission probes preserve exact v8 schema and rows.");
    assertSameValue($before["artifacts"], $after["artifacts"], "Admission probes preserve every non-session artifact.");
    assertSameValue([], $before["sessions"], "Owned session tree starts empty.");
    assertSameValue(true, $after["sessions"] !== [], "CSRF/session flow creates durable state only in the owned session subtree.");
    assertSameValue(
        200,
        $page["status"],
        "Unassigned engineer obtains checklist CSRF page.",
    );
    assertSameValue(
        true,
        ($token[1] ?? "") !== "" && $cookie !== "",
        "Public HTML independently supplies CSRF/cookie.",
    );
    assertSameValue(
        422,
        $post["status"],
        "Admitted malformed item maps to HTTP 422.",
    );
    assertSameValue(
        ["status" => "rejected", "revision" => 0, "projectionRevision" => 0],
        [
            "status" => $postJson["status"] ?? null,
            "revision" => $postJson["revision"] ?? null,
            "projectionRevision" => $postJson["projection"]["revision"] ?? null,
        ],
        "Malformed item exact public rejection and unchanged projection.",
    );
    assertSameValue(
        403,
        $nonItem["status"],
        "Same exact-capability user remains denied for non-item operation.",
    );
    assertSameValue(
        200,
        $context["status"],
        "Exact-capability user obtains sync context.",
    );
    assertSameValue(
        0,
        $contextJson["revision"] ?? null,
        "Sync context remains revision zero.",
    );
    return $context;
}
function ieaAssertItemOnlyUiContract(array $page): void
{
    $failures = [];
    $expect = static function (bool $condition, string $message) use (&$failures): void {
        if (!$condition) {
            $failures[] = $message;
        }
    };
    $document = new DOMDocument();
    $loaded = @$document->loadHTML((string) $page["body"]);
    $xpath = new DOMXPath($document);
    $expect($page["status"] === 200, "public checklist page is HTTP 200");
    $expect($loaded, "public checklist page is parseable HTML");
    $expect(
        (int) $xpath->evaluate(
            "count(//*[@data-checklist and @data-item-completion-enabled='true' and @data-legacy-operations-enabled='false'])",
        ) === 1,
        "root exposes item-only completion capability",
    );
    $expect(
        (int) $xpath->evaluate(
            "count(//*[@data-check-item]//button[contains(concat(' ',normalize-space(@class),' '),' fm2-check-toggle ') and not(@disabled)])",
        ) === 42,
        "all 42 item completion controls are usable",
    );
    $expect(
        (int) $xpath->evaluate("count(//main[contains(concat(' ',normalize-space(@class),' '),' fm2-check-layout ') and not(@inert)])") === 1,
        "item completion controls are not disabled by an inert ancestor",
    );
    $expect(
        (int) $xpath->evaluate("count(//*[@data-photo-input and @disabled])") === 16,
        "photo upload controls stay disabled",
    );
    $expect(
        (int) $xpath->evaluate("count(//*[@data-installer-edit and @disabled])") === 42,
        "installer correction controls stay disabled",
    );
    $expect(
        (int) $xpath->evaluate("count(//*[@data-check-all and @disabled])") === 8,
        "bulk/section completion controls stay disabled",
    );
    $source = file_get_contents(dirname(__DIR__, 2) . "/app/PilotHttp/checklist.js");
    $rootNode = $xpath->query("//*[@data-checklist]")->item(0);
    $fixture = [
        "source" => $source,
        "rootDataset" => [
            "enabled" => $rootNode?->getAttribute("data-enabled"),
            "itemCompletionEnabled" => $rootNode?->getAttribute("data-item-completion-enabled"),
            "legacyOperationsEnabled" => $rootNode?->getAttribute("data-legacy-operations-enabled"),
            "objectId" => $rootNode?->getAttribute("data-object-id"),
            "userId" => $rootNode?->getAttribute("data-user-id"),
            "csrf" => $rootNode?->getAttribute("data-csrf"),
            "projection" => base64_encode(json_encode([
                "revision" => 0,
                "crew" => [["tabId" => 1042, "fio" => "Монтажник", "employmentStatus" => "employed"]],
                "items" => new stdClass(),
                "photos" => [],
                "completedSections" => new stdClass(),
            ], JSON_THROW_ON_ERROR)),
        ],
        "controls" => ["item" => true, "photo" => false, "installer" => false, "bulk" => false],
    ];
    $command = ["node", __DIR__ . "/support/inspection_item_complete_ui_browser.js"];
    $pipes = [];
    $process = proc_open($command, [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]], $pipes, dirname(__DIR__, 2));
    $browser = null;
    if (is_resource($process) && is_string($source)) {
        fwrite($pipes[0], json_encode($fixture, JSON_THROW_ON_ERROR));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit === 0) {
            $browser = json_decode($stdout, true);
        } else {
            $failures[] = "executable client harness failed: " . trim($stderr);
        }
    } else {
        $failures[] = "executable client harness could not start";
    }
    $persistedTypes = $browser["persistedTypes"] ?? [];
    $expect(
        $persistedTypes !== [] && array_values(array_unique($persistedTypes)) === ["item_completed"],
        "item click observably enqueues only item_completed",
    );
    $expect(
        ($browser["sentTypes"] ?? []) === ["item_completed"],
        "item click observably sends item_completed",
    );
    require_once dirname(__DIR__, 2) . "/app/PilotHttp/PilotHttp.php";
    require_once dirname(__DIR__, 2) . "/app/PilotHttp/ChecklistView.php";
    $renderer = new \FMonitor2\PilotHttp\ProductionChecklistRenderer();
    $case = [
        "id" => 4512,
        "address" => "Москва",
        "entrance" => "1",
        "registrationNumber" => "77-4512",
        "opened" => true,
        "controlEngineer" => ["userId" => 7302],
    ];
    $assigned = $renderer->render(
        new \FMonitor2\PilotHttp\HttpUser(7302, "Assigned", "assigned@example.test"),
        $case,
    );
    $legacyRole = $renderer->render(
        new \FMonitor2\PilotHttp\HttpUser(7399, "Legacy", "legacy@example.test"),
        $case,
        true,
    );
    $expect(
        str_contains($assigned, 'data-enabled="true"') &&
            str_contains($legacyRole, 'data-enabled="true"'),
        "existing assigned-engineer and legacy-role rendering remains enabled",
    );
    if ($failures !== []) {
        throw new TestFailure("Item-only UI/client contract:\n- " . implode("\n- ", $failures));
    }
}
function ieaRemoveOwned(string $root): void
{
    if (!is_dir($root)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($it as $f) {
        if ($f->isLink()) {
            if (!unlink($f->getPathname())) throw new RuntimeException("cleanup symlink " . $f->getPathname());
        } elseif ($f->isDir()) {
            if (!rmdir($f->getPathname())) {
                throw new RuntimeException(
                    "cleanup directory " . $f->getPathname(),
                );
            }
        } elseif (!unlink($f->getPathname())) {
            throw new RuntimeException("cleanup file " . $f->getPathname());
        }
    }
    if (!rmdir($root)) {
        throw new RuntimeException("cleanup root " . $root);
    }
}
function ieaCleanupSelfCheck(string $base): void
{
    global $ieaCleanupFailures;
    $probe = $base . "-cleanup-probe";
    mkdir($probe . "/partial/deep", 0700, true);
    file_put_contents($probe . "/partial/deep/member", "owned");
    ieaRemoveOwned($probe);
    assertSameValue(
        false,
        file_exists($probe),
        "Forced partial artifact tree is removed.",
    );
    $pipes = [];
    $proc = proc_open(
        [PHP_BINARY, "-r", "while(true){usleep(100000);}"],
        [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]],
        $pipes,
    );
    if (!is_resource($proc)) {
        throw new TestFailure("SETUP_FAILURE: cleanup child");
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $started = microtime(true);
    ieaStop(["process" => $proc, "pipes" => $pipes, "port" => 0]);
    assertSameValue(
        true,
        microtime(true) - $started < 3.5,
        "Forced hung child cleanup is deadline bounded.",
    );
    assertSameValue(
        [],
        $ieaCleanupFailures,
        "Forced hung child is killed and reaped without cleanup errors.",
    );
}
function ieaVerdict(Throwable $primary, array $cleanup): string
{
    return "PRIMARY: " .
        get_class($primary) .
        ": " .
        $primary->getMessage() .
        ($cleanup === [] ? "" : "\nCLEANUP:\n- " . implode("\n- ", $cleanup));
}
function ieaGuard(string $label, callable $cleanup, array &$errors): void
{
    try {
        $cleanup();
    } catch (Throwable $failure) {
        $errors[] = $label . ": " . $failure->getMessage();
    }
}
function ieaFinalFailure(?Throwable $primary, array $cleanup): ?TestFailure
{
    if ($primary === null && $cleanup === []) {
        return null;
    }
    return new TestFailure(
        $primary === null
            ? "PRIMARY: body passed\nCLEANUP:\n- " . implode("\n- ", $cleanup)
            : ieaVerdict($primary, $cleanup),
    );
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$t = substr(hash("sha256", __FILE__ . hrtime(true)), 0, 10);
$d = "t_iea_" . $t;
$p = "ea" . substr($t, 0, 6) . "_";
$a = ieaDb();
$db = null;
$server = null;
$root = dirname(__DIR__, 2) . "/.test-artifacts/iea-" . $t;
$ieaCleanupFailures = [];
$ieaLastRouterPid = 0;
$ieaRouterReaped = false;
ieaCleanupSelfCheck($root);
$injectedCleanupFailure = ieaFinalFailure(null, ["injected cleanup failure"]);
assertSameValue(
    true,
    $injectedCleanupFailure instanceof TestFailure &&
        str_contains(
            $injectedCleanupFailure->getMessage(),
            "PRIMARY: body passed\nCLEANUP:\n- injected cleanup failure",
        ),
    "Injected cleanup failure makes an otherwise-green body fail the final aggregate.",
);
$primary = null;
try {
    $a->query(
        "CREATE DATABASE " .
            ieaQ($d) .
            " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    );
    ieaMigrate($d, $p);
    $db = ieaDb($d);
    $q = fn(string $n): string => ieaQ($p . $n);
    $db->query(
        "CREATE TABLE legacy_fm_maintable(id BIGINT PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),ptoactdate VARCHAR(40),responsstroicontrol VARCHAR(80))ENGINE=InnoDB",
    );
    $db->query(
        "INSERT INTO legacy_fm_maintable VALUES(4512,'Москва','1','77-4512','2026-08-28','2026-12-20',NULL,NULL,NULL,'7302')",
    );
    $db->query(
        "CREATE TABLE legacy_users(id BIGINT PRIMARY KEY,name VARCHAR(300),email VARCHAR(254),role_id BIGINT,status INT)ENGINE=InnoDB",
    );
    $db->query(
        "CREATE TABLE legacy_users_roles(id BIGINT PRIMARY KEY,name VARCHAR(120),status INT)ENGINE=InnoDB",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_pilot_users",
        )} VALUES(7301,'Фактический инженер','engineer@example.test','',1,'active',1,'2026-09-01T08:00:00+03:00'),(7302,'Назначенный инженер','assigned@example.test','',1,'active',1,'2026-09-01T08:00:00+03:00')",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_pilot_roles",
        )} VALUES(7001,'inspection_engineer','Инженер','Fixture',1,'2026-09-01T08:00:00+03:00')",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_pilot_role_permissions",
        )} VALUES(7001,'inspection.item.complete')",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_pilot_user_roles",
        )} VALUES(7301,7001,'fixture','2026-09-01T08:00:00+03:00',NULL)",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_installation_cases",
        )}(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version)VALUES(9512,4512,'working','2026-08-28','2026-08-28T09:00:00+03:00',18,'2026-08-28T09:00:00+03:00','2026-08-28T09:00:00+03:00',1)",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_assignment_orders",
        )}(id,installation_case_id,version_no,kind,status,order_date,registration_number,registered_at,registration_actor_type,registration_actor_id,registration_source,external_registration_id,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,previous_assignment_order_id,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,pto_act_date_snapshot,prepared_at,prepared_by_user_id)VALUES(8101,9512,1,'initial','registered','2026-08-27','12-Р','2026-08-27T12:00:00+03:00','user','18','manual',NULL,7302,'Назначенный инженер','Инженер строительного контроля','brigade',NULL,'Москва','1','77-4512','2026-08-28','2026-12-20',NULL,'2026-08-27T10:00:00+03:00',18)",
    );
    $db->query(
        "INSERT INTO {$q(
            "fm2_order_installers",
        )} VALUES(8101,1042,'Иванов','Электромеханик','employed','2024-01-01',NULL,'fixture','2026-09-01T08:00:00+03:00','2026-08-27',NULL,'assign')",
    );
    mkdir($root, 0700, true);
    $sessionRoot = $root . "/sessions";
    if (!mkdir($sessionRoot, 0700) || realpath($sessionRoot) !== $sessionRoot || !str_starts_with($sessionRoot, $root . "/")) throw new TestFailure("SETUP_FAILURE: invalid owned session root");
    $sessionInstance = "iea-" . $t;
    if (!mkdir($sessionRoot . "/" . $sessionInstance, 0700)) throw new TestFailure("SETUP_FAILURE: owned session instance");
    ieaSessionOwnershipSensitivity($root,$sessionInstance);
    $env = [
        "FMONITOR_DB_HOST" => getenv("FMONITOR_TEST_DB_HOST") ?: "127.0.0.1",
        "FMONITOR_DB_PORT" => getenv("FMONITOR_TEST_DB_PORT") ?: "23306",
        "FMONITOR_DB_NAME" => $d,
        "FMONITOR_DB_USER" => getenv("FMONITOR_TEST_DB_ADMIN_USER") ?: "root",
        "FMONITOR_DB_PASSWORD" =>
            getenv("FMONITOR_TEST_DB_ADMIN_PASSWORD") ?:
            "fmonitor2_test_root_local",
        "FMONITOR_LEGACY_TABLE_PREFIX" => "legacy_",
        "FMONITOR_PROCESS_TABLE_PREFIX" => $p,
        "FMONITOR_ARTIFACT_STORAGE_ROOT" => $root,
        "FMONITOR_SESSION_STATE_ROOT" => $root,
        "FMONITOR_SESSION_INSTANCE" => $sessionInstance,
        "FMONITOR_SHLZ_CSS_PATH" =>
            dirname(__DIR__, 3) . "/shlz-ui/packages/styles/dist/shlz.css",
        "FMONITOR_PILOT_CSS_PATH" =>
            dirname(__DIR__, 2) . "/app/PilotHttp/pilot.css",
        "FMONITOR_NOW" => "2026-09-01T09:05:00+03:00",
        "REMOTE_USER" => "engineer@example.test",
    ];
    $server = ieaStart($env);
    $response = ieaGet(
        $server["port"],
        "/pilot/construction-control/objects/4512/sync-context",
    );
    assertSameValue(
        200,
        $response["status"],
        "Unassigned active exact-capability engineer obtains offline sync context.",
    );
    $body = json_decode($response["body"], true, 512, JSON_THROW_ON_ERROR);
    assertSameValue(
        true,
        is_string($body["csrf"] ?? null) && ($body["revision"] ?? null) === 0,
        "Sync context exposes CSRF and revision zero.",
    );
    echo "PASS: INSPECTION-ITEM-COMPLETE-001 raw HTTP endpoint admission\n";
} catch (Throwable $failure) {
    $primary = $failure;
} finally {
    ieaGuard("server stop", static fn() => ieaStop($server), $ieaCleanupFailures);
    ieaGuard("runtime DB close", static function () use (&$db): void {
        if ($db instanceof mysqli) $db->close();
        $db = null;
    }, $ieaCleanupFailures);
    ieaGuard("database drop", static fn() => $a->query("DROP DATABASE IF EXISTS " . ieaQ($d)), $ieaCleanupFailures);
    ieaGuard("admin close", static fn() => $a->close(), $ieaCleanupFailures);
    ieaGuard("artifact root delete", static fn() => ieaRemoveOwned($root), $ieaCleanupFailures);
    ieaGuard("database absence", static function () use ($d, &$ieaCleanupFailures): void {
        $probe = ieaDb();
        try {
            $escaped = $probe->real_escape_string($d);
            if ((int)$probe->query("SELECT COUNT(*) n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$escaped'")->fetch_assoc()["n"] !== 0) throw new RuntimeException("test database remains");
        } finally {
            $probe->close();
        }
    }, $ieaCleanupFailures);
    ieaGuard("router absence", static function () use (&$ieaRouterReaped, &$ieaLastRouterPid): void {
        if (!$ieaRouterReaped) throw new RuntimeException("router was not confirmed exited and reaped");
        if ($ieaLastRouterPid > 0 && function_exists("posix_kill") && @posix_kill($ieaLastRouterPid, 0)) throw new RuntimeException("router PID remains alive");
    }, $ieaCleanupFailures);
    ieaGuard("artifact absence", static function () use ($root): void {
        if (file_exists($root)) throw new RuntimeException("owned artifact root remains");
    }, $ieaCleanupFailures);
}
if (($finalFailure = ieaFinalFailure($primary, $ieaCleanupFailures)) !== null) {
    throw $finalFailure;
}
