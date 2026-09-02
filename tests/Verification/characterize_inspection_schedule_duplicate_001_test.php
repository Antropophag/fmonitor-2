<?php
declare(strict_types=1);

final class InspectionScheduleTestFailure extends RuntimeException {}
const ISD_TIMEOUT = 12.0, ISD_SPEC_HASH = "55fb13233f3fcc6102512dc14157e1e29aa3b5bb4bb1db4474a9bdaeb143b566", ISD_TRANSCRIPT_HASH = "6a7d8676c3457eefcbcba772acc4dd853d0ccad557c479632454a8b06eb55da4";

function isdAssert(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) { throw new InspectionScheduleTestFailure($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)); }
}
function isdConfig(): array
{
    $c = ["HOST" => "127.0.0.1", "PORT" => "23306", "NAME" => "fmonitor2_test", "USER" => "fmonitor2_test", "PASSWORD" => "fmonitor2_test_local"];
    foreach ($c as $k => $v) {
        $a = getenv("FMONITOR_VERIFY_DB_$k");
        $b = getenv("FMONITOR_TEST_DB_$k");
        $c[$k] = is_string($a) && $a !== "" ? $a : (is_string($b) && $b !== "" ? $b : $v);
    }
    return $c;
}
function isdEnvironment(array $overrides = []): array
{
    $environment = [];
    foreach ([getenv(), $_ENV] as $source) {
        if (!is_array($source)) { continue; }
        foreach ($source as $name => $value) {
            if (is_string($name) && is_scalar($value)) { $environment[$name] = (string) $value; }
        }
    }
    foreach ($overrides as $name => $value) {
        if (is_string($name) && is_scalar($value)) { $environment[$name] = (string) $value; }
    }
    return $environment;
}
function isdDb(array $c): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $d = new mysqli($c["HOST"], $c["USER"], $c["PASSWORD"], $c["NAME"], (int) $c["PORT"]);
        $d->set_charset("utf8mb4");
        return $d;
    } catch (Throwable $e) {
        throw new InspectionScheduleTestFailure("SETUP_FAILURE: disposable MariaDB unavailable: " . $e->getMessage());
    }
}
function isdToken(string $t): void
{
    if (preg_match("/\A[a-f0-9]{12}\z/D", $t) !== 1) {
        throw new InspectionScheduleTestFailure("SETUP_FAILURE: invalid test run token");
    }
}
function isdPrefix(string $t): string
{
    isdToken($t);
    $p = "isd_{$t}_";
    isdAssert(true, strlen($p) <= 28 && preg_match("/\A[a-z0-9_]+\z/D", $p) === 1, "SETUP_FAILURE: SQL prefix must be safe and at most 28 bytes");
    return $p;
}
function isdTables(string $t): array
{
    $p = isdPrefix($t);
    return array_map(static fn($n) => $p . $n, [
        "fm2_pilot_users", "fm2_pilot_roles", "fm2_pilot_role_permissions", "fm2_pilot_user_roles", "fm2_installation_cases", "fm2_assignment_orders", "fm2_pilot_inspection_schedules", "fm2_pilot_inspection_schedule_events",
    ]);
}
function isdExists(mysqli $d, string $t): bool
{
    $q = $d->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $q->bind_param("s", $t);
    $q->execute();
    return $q->get_result()->fetch_row() !== null;
}
function isdNamespaceFree(mysqli $d, string $t, string $r): void
{
    foreach (isdTables($t) as $n) {
        if (isdExists($d, $n)) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: occupied exact SQL name $n"); }
    }
    if (file_exists($r . "/inspection-schedule-$t")) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: occupied exact artifact child"); }
}
function isdDrop(mysqli $d, string $t): void
{
    foreach (array_reverse(isdTables($t)) as $n) { $d->query("DROP TABLE IF EXISTS `$n`"); }
}
function isdCreate(mysqli $d, string $t): void
{
    $p = isdPrefix($t);
    $d->query("CREATE TABLE `{$p}fm2_pilot_users`(user_id BIGINT UNSIGNED PRIMARY KEY,full_name VARCHAR(120) NOT NULL,status TINYINT NOT NULL,activation_state VARCHAR(20) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $d->query("CREATE TABLE `{$p}fm2_pilot_roles`(role_id BIGINT UNSIGNED PRIMARY KEY,status TINYINT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $d->query("CREATE TABLE `{$p}fm2_pilot_role_permissions`(role_id BIGINT UNSIGNED NOT NULL,permission VARCHAR(100) NOT NULL,PRIMARY KEY(role_id,permission)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $d->query("CREATE TABLE `{$p}fm2_pilot_user_roles`(user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(user_id,role_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $d->query("CREATE TABLE `{$p}fm2_installation_cases`(id BIGINT UNSIGNED PRIMARY KEY,legacy_installation_object_id BIGINT UNSIGNED NOT NULL UNIQUE,process_state VARCHAR(40) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $d->query(
        "CREATE TABLE `{$p}fm2_assignment_orders`(id BIGINT UNSIGNED PRIMARY KEY,installation_case_id BIGINT UNSIGNED NOT NULL,version_no INT NOT NULL,status VARCHAR(40) NOT NULL,control_engineer_user_id BIGINT UNSIGNED NOT NULL,UNIQUE KEY(installation_case_id,version_no)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    require_once dirname(__DIR__, 2) . "/app/InstallationProcess/InspectionPlanningSchemaMigration.php";
    $migration = FMonitor2\InstallationProcess\InspectionPlanningSchemaMigration::apply($d, $p);
    isdAssert(9, $migration["schemaVersion"] ?? null, "SETUP_FAILURE: canonical planning migration must be terminal v9");
    isdAssert(true, $migration["applied"] ?? null, "SETUP_FAILURE: canonical planning family must start empty and be created once");
    $d->query("INSERT INTO `{$p}fm2_pilot_users` VALUES(8101,'Authorized Scheduler',1,'active'),(8301,'Denied Scheduler',1,'active')");
    $d->query("INSERT INTO `{$p}fm2_pilot_roles` VALUES(9101,1),(9102,1)");
    $d->query("INSERT INTO `{$p}fm2_pilot_role_permissions` VALUES(9101,'inspection.schedule'),(9102,'objects.read')");
    $d->query("INSERT INTO `{$p}fm2_pilot_user_roles` VALUES(8101,9101),(8301,9102)");
    $d->query("INSERT INTO `{$p}fm2_installation_cases` VALUES(6101,451201,'working'),(6201,451202,'working'),(6301,451203,'working'),(6401,451204,'working'),(6501,451205,'needs_assignment_order')");
    $d->query(
        "INSERT INTO `{$p}fm2_assignment_orders` VALUES(6111,6101,1,'registered',7299),(6112,6101,2,'registered',7301),(6211,6201,1,'registered',7301),(6311,6301,1,'registered',7301),(6411,6401,1,'registered',7301),(6511,6501,1,'registered',7301)",
    );
}
function isdSnapshot(mysqli $d, string $t): array
{
    $s = [];
    foreach (array_merge(isdTables($t), [isdPrefix($t) . "unrelated_decoy"]) as $n) {
        $def = (string) $d->query("SHOW CREATE TABLE `$n`")->fetch_row()[1];
        $rows = $d->query("SELECT * FROM `$n`")->fetch_all(MYSQLI_ASSOC);
        usort($rows, static fn(array $a, array $b): int => strcmp(json_encode($a, JSON_THROW_ON_ERROR), json_encode($b, JSON_THROW_ON_ERROR)));
        $s[$n] = ["definition" => preg_replace("/ AUTO_INCREMENT=\d+/", "", $def), "rows" => $rows];
    }
    return $s;
}
function isdRows(mysqli $d, string $t): array
{
    return $d->query("SELECT * FROM `$t` ORDER BY id")->fetch_all(MYSQLI_ASSOC);
}
function isdRouter(string $path): void
{
    $src = <<<'PHP'
    <?php
    declare(strict_types=1);
    function isdRouterHistory(mysqli $db,string $prefix):array{$out=[];foreach(['fm2_pilot_users','fm2_pilot_roles','fm2_pilot_role_permissions','fm2_pilot_user_roles','fm2_installation_cases','fm2_assignment_orders','fm2_pilot_inspection_schedules','fm2_pilot_inspection_schedule_events','unrelated_decoy']as$name){$table=$prefix.$name;$create=(string)$db->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];$rows=$db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);usort($rows,static fn(array $a,array $b):int=>strcmp(json_encode($a,JSON_THROW_ON_ERROR),json_encode($b,JSON_THROW_ON_ERROR)));$out[$name]=['definition'=>preg_replace('/ AUTO_INCREMENT=\d+/','',$create),'rows'=>$rows];}return $out;}
    $body=(string)file_get_contents('php://input');$uri=(string)($_SERVER['REQUEST_URI']??'');$path=(string)parse_url($uri,PHP_URL_PATH);$nonce=(string)getenv('ISD_REQUEST_NONCE');$log=(string)getenv('ISD_REQUEST_LOG');
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli((string)getenv('FMONITOR_DB_HOST'),(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_DB_PASSWORD'),(string)getenv('FMONITOR_DB_NAME'),(int)getenv('FMONITOR_DB_PORT'));$db->set_charset('utf8mb4');$prefix=(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');$pre=isdRouterHistory($db,$prefix);
    $_SERVER['FMONITOR_AUTH_USER_ID']=$path==='/pilot/objects/451203/inspection-schedule'?'8301':'8101';$_SERVER['FMONITOR_AUTH_CSRF']='schedule-characterization-csrf-001';ob_start();
    register_shutdown_function(static function()use($body,$uri,$nonce,$log,$db,$prefix,$pre):void{$headers=[];foreach(headers_list()as$h){[$n,$v]=array_pad(explode(':',$h,2),2,'');$n=strtolower(trim($n));if(in_array($n,['location','cache-control'],true))$headers[$n]=trim($v);}$r=['nonce'=>$nonce,'method'=>(string)($_SERVER['REQUEST_METHOD']??''),'route'=>$uri,'body'=>$body,'status'=>http_response_code(),'headers'=>$headers,'response_bytes'=>strlen((string)ob_get_contents()),'pre'=>$pre,'post'=>isdRouterHistory($db,$prefix)];file_put_contents($log,json_encode($r,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",FILE_APPEND|LOCK_EX);$db->close();});
    require getenv('ISD_REPOSITORY_ROOT').'/rapid-pilot/InspectionSchedule.php';RapidPilotInspectionSchedule::handle($path);
    PHP;
    if (file_put_contents($path, $src, LOCK_EX) === false) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: cannot write test-owned router"); }
}
function isdStop($process, array $pipes, int $pid): void
{
    $s = proc_get_status($process);
    if ($s["running"] ?? false) {
        if ($pid > 0 && function_exists("posix_kill")) { @posix_kill(-$pid, SIGTERM); } else {
            proc_terminate($process, 15);
        }
        $end = microtime(true) + 1;
        do {
            usleep(20000);
            $s = proc_get_status($process);
        } while (($s["running"] ?? false) && microtime(true) < $end);
        if ($s["running"] ?? false) {
            if ($pid > 0 && function_exists("posix_kill")) { @posix_kill(-$pid, SIGKILL); } else {
                proc_terminate($process, 9);
            }
        }
    }
    foreach ($pipes as $p) {
        if (is_resource($p)) { fclose($p); }
    }
    proc_close($process);
    $end = microtime(true) + 1;
    while ($pid > 0 && is_dir("/proc/$pid") && microtime(true) < $end) { usleep(20000); }
    isdAssert(false, $pid > 0 && is_dir("/proc/$pid"), "SETUP_FAILURE: owned process was not reaped");
}
function isdServer(string $root, string $router, string $log, string $nonce, array $env): array
{
    $sock = stream_socket_server("tcp://127.0.0.1:0", $eno, $err);
    if (!is_resource($sock)) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: cannot reserve loopback port: $err"); }
    $addr = (string) stream_socket_get_name($sock, false);
    fclose($sock);
    $port = (int) substr(strrchr($addr, ":"), 1);
    $env += ["ISD_REPOSITORY_ROOT" => $root, "ISD_REQUEST_LOG" => $log, "ISD_REQUEST_NONCE" => $nonce];
    $proc = proc_open(["setsid", "--wait", PHP_BINARY, "-S", "127.0.0.1:$port", $router], [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes, $root, $env);
    if (!is_resource($proc)) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: loopback server did not start"); }
    fclose($pipes[0]);
    $pid = (int) (proc_get_status($proc)["pid"] ?? 0);
    $end = microtime(true) + 3;
    do {
        $probe = @fsockopen("127.0.0.1", $port, $e, $m, 0.1);
        if (is_resource($probe)) {
            fclose($probe);
            return ["process" => $proc, "pipes" => $pipes, "pid" => $pid, "url" => "http://127.0.0.1:$port"];
        }
        if (!(proc_get_status($proc)["running"] ?? false)) { break; }
        usleep(30000);
    } while (microtime(true) < $end);
    isdStop($proc, $pipes, $pid);
    throw new InspectionScheduleTestFailure("SETUP_FAILURE: loopback server readiness failed");
}
function isdRun(array $command, string $root, array $env, float $timeout = ISD_TIMEOUT): array
{
    $proc = proc_open(array_merge(["setsid", "--wait"], $command), [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes, $root, $env);
    if (!is_resource($proc)) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: verifier did not start"); }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $pid = (int) (proc_get_status($proc)["pid"] ?? 0);
    $end = microtime(true) + $timeout;
    $out = "";
    $err = "";
    $timedOut = false;
    $exit = null;
    while (true) {
        $out .= stream_get_contents($pipes[1]);
        $err .= stream_get_contents($pipes[2]);
        $s = proc_get_status($proc);
        if (!($s["running"] ?? false)) {
            $exit = (int) $s["exitcode"];
            break;
        }
        if (microtime(true) >= $end) {
            $timedOut = true;
            if ($pid > 0 && function_exists("posix_kill")) { @posix_kill(-$pid, SIGTERM); } else {
                proc_terminate($proc, 15);
            }
            usleep(100000);
            if (proc_get_status($proc)["running"] ?? false) {
                if ($pid > 0 && function_exists("posix_kill")) { @posix_kill(-$pid, SIGKILL); } else {
                    proc_terminate($proc, 9);
                }
            }
            break;
        }
        usleep(20000);
    }
    stream_set_blocking($pipes[1], true);
    stream_set_blocking($pipes[2], true);
    $out .= stream_get_contents($pipes[1]);
    $err .= stream_get_contents($pipes[2]);
    foreach ($pipes as $p) {
        if (is_resource($p)) { fclose($p); }
    }
    $closed = proc_close($proc);
    if ($exit === null || $exit < 0) { $exit = $closed; }
    $end = microtime(true) + 1;
    while ($pid > 0 && is_dir("/proc/$pid") && microtime(true) < $end) { usleep(20000); }
    isdAssert(false, $pid > 0 && is_dir("/proc/$pid"), "SETUP_FAILURE: verifier process was not reaped");
    return ["status" => $timedOut ? 124 : $exit, "stdout" => $out, "stderr" => $err, "timed_out" => $timedOut];
}
function isdInvalid(string $verifier, string $root, array $env, mysqli $db, string $token, string $ar, string $label): void
{
    $r = isdRun([PHP_BINARY, $verifier], $root, $env);
    isdAssert(false, $r["timed_out"], "$label probe bounded");
    isdAssert(2, $r["status"], "REGRESSION_FAILURE: $label must exit 2 before mutation");
    isdAssert("", $r["stdout"], "REGRESSION_FAILURE: $label stdout must be empty");
    isdNamespaceFree($db, $token, $ar);
    isdAssert(false, isdExists($db, isdPrefix($token) . "unrelated_decoy"), "$label leaves no decoy");
}
function isdLog(string $p): array
{
    if (!is_file($p)) { return []; }
    $a = [];
    foreach (file($p, FILE_IGNORE_NEW_LINES) ?: [] as $l) {
        $r = json_decode($l, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($r)) { throw new InspectionScheduleTestFailure("REGRESSION_FAILURE: malformed request log"); }
        $a[] = $r;
    }
    return $a;
}
function isdTranscript(): string
{
    return "INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact\nINSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0\nINSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0\nCHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001\n";
}
function isdAudit(mysqli $d, string $t, string $nonce, string $log, array $before, array $decoy, array $r): void
{
    isdAssert(false, $r["timed_out"], "SETUP_FAILURE: verifier exceeded bounded timeout");
    isdAssert(0, $r["status"], "RED_ASSERTION: missing verifier must become a successful HTTP characterization");
    isdAssert("", $r["stderr"], "REGRESSION_FAILURE: verifier stderr must be empty");
    isdAssert(isdTranscript(), $r["stdout"], "REGRESSION_FAILURE: normalized transcript must be exact");
    $requests = isdLog($log);
    isdAssert(6, count($requests), "REGRESSION_FAILURE: verifier must make exactly six observed HTTP requests");
    $csrf = "schedule-characterization-csrf-001";
    $forms = [
        [451201, $csrf, "2026-09-03", 303], [451201, $csrf, "2026-09-03", 303], [451202, "wrong-schedule-csrf", "2026-09-03", 403],
        [451203, $csrf, "2026-09-03", 403], [451204, $csrf, "2026-02-30", 422], [451205, $csrf, "2026-09-03", 409],
    ];
    foreach ($forms as $i => [$obj, $submitted, $date, $status]) {
        $x = $requests[$i];
        $route = "/pilot/objects/$obj/inspection-schedule";
        isdAssert($nonce, $x["nonce"] ?? null, "request $i nonce");
        isdAssert("POST", $x["method"] ?? null, "request $i method");
        isdAssert($route, $x["route"] ?? null, "request $i route");
        isdAssert(http_build_query(["csrfToken" => $submitted, "inspectionDate" => $date]), $x["body"] ?? null, "request $i form");
        isdAssert($status, $x["status"] ?? null, "request $i status");
        isdAssert("no-store", $x["headers"]["cache-control"] ?? null, "request $i cache");
        isdAssert($status === 303 ? "/pilot/objects?inspectionScheduled=$date" : null, $x["headers"]["location"] ?? null, "request $i redirect");
    }
    $p = isdPrefix($t);
    $names = ["fm2_pilot_inspection_schedules", "fm2_pilot_inspection_schedule_events"];
    $initial = [];
    foreach ($before as $table => $state) { $initial[substr($table, strlen($p))] = $state; }
    $initialBytes = json_encode($initial, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    isdAssert($initialBytes, json_encode($requests[0]["pre"] ?? null, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "request 1 immediately begins with exact complete fixture state");
    $firstPost = $requests[0]["post"] ?? null;
    isdAssert(true, is_array($firstPost), "request 1 shutdown history exists");
    $loggedSchedules = $firstPost[$names[0]]["rows"] ?? [];
    $loggedEvents = $firstPost[$names[1]]["rows"] ?? [];
    isdAssert(1, count($loggedSchedules), "request 1 immediately creates one schedule");
    isdAssert(1, count($loggedEvents), "request 1 immediately creates one event");
    $loggedId = (int) ($loggedSchedules[0]["id"] ?? 0);
    isdAssert(true, $loggedId > 0, "request 1 schedule identity positive");
    $exactState = $initial;
    $exactState[$names[0]]["rows"] = [["id" => (string) $loggedId, "installation_case_id" => "6101", "legacy_object_id" => "451201", "control_engineer_user_id" => "7301", "inspection_date" => "2026-09-03", "scheduled_by_user_id" => "8101", "scheduled_at" => "2026-09-01T09:30:00+03:00"]];
    $exactState[$names[1]]["rows"] = [["id" => (string) ((int) ($loggedEvents[0]["id"] ?? 0)), "schedule_id" => (string) $loggedId, "installation_case_id" => "6101", "event_type" => "inspection_scheduled", "payload_json" => '{"scheduleId":' . $loggedId . ',"inspectionDate":"2026-09-03","controlEngineerUserId":7301}', "actor_user_id" => "8101", "occurred_at" => "2026-09-01T09:30:00+03:00"]];
    $exactBytes = json_encode($exactState, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    isdAssert($exactBytes, json_encode($firstPost, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "request 1 changes only schedule and event in complete fixture state");
    for ($i = 1; $i < 6; $i++) {
        isdAssert($exactBytes, json_encode($requests[$i]["pre"] ?? null, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "request $i pre-history byte-identical");
        isdAssert($exactBytes, json_encode($requests[$i]["post"] ?? null, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "request $i post-history byte-identical");
    }
    $after = isdSnapshot($d, $t);
    foreach ($before as $table => $state) {
        if (!str_ends_with($table, "inspection_schedules") && !str_ends_with($table, "inspection_schedule_events")) { isdAssert($state, $after[$table] ?? null, "authorization/case/order facts must remain exact"); }
        isdAssert($state["definition"], $after[$table]["definition"] ?? null, "fixture structures must remain exact");
    }
    isdAssert($decoy, isdTableFingerprint($d, $p . "unrelated_decoy"), "unrelated prefixed table must remain exact");
    $s = isdRows($d, $p . "fm2_pilot_inspection_schedules");
    $e = isdRows($d, $p . "fm2_pilot_inspection_schedule_events");
    isdAssert(1, count($s), "creation/replay/rejections leave one schedule");
    isdAssert(1, count($e), "creation/replay/rejections leave one event");
    $id = (int) ($s[0]["id"] ?? 0);
    isdAssert(true, $id > 0, "schedule identity positive");
    $expectedSchedule = ["id" => (string) $id, "installation_case_id" => "6101", "legacy_object_id" => "451201", "control_engineer_user_id" => "7301", "inspection_date" => "2026-09-03", "scheduled_by_user_id" => "8101", "scheduled_at" => "2026-09-01T09:30:00+03:00"];
    isdAssert($expectedSchedule, $s[0], "schedule fact exact");
    $expectedEvent = ["id" => (string) ((int) ($e[0]["id"] ?? 0)), "schedule_id" => (string) $id, "installation_case_id" => "6101", "event_type" => "inspection_scheduled", "payload_json" => '{"scheduleId":' . $id . ',"inspectionDate":"2026-09-03","controlEngineerUserId":7301}', "actor_user_id" => "8101", "occurred_at" => "2026-09-01T09:30:00+03:00"];
    isdAssert($expectedEvent, $e[0], "event history exact");
}
function isdTree(string $p): void
{
    if (is_link($p) || is_file($p)) {
        unlink($p);
        return;
    }
    if (!is_dir($p)) { return; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $x) { $x->isDir() ? rmdir($x->getPathname()) : unlink($x->getPathname()); }
    rmdir($p);
}
function isdTableFingerprint(mysqli $d, string $n): array
{
    return ["definition" => preg_replace("/ AUTO_INCREMENT=\d+/", "", (string) $d->query("SHOW CREATE TABLE `$n`")->fetch_row()[1]), "rows" => $d->query("SELECT * FROM `$n` ORDER BY id")->fetch_all(MYSQLI_ASSOC)];
}
function isdExecute(string $root, mysqli $d, array $c, string $t, string $ar, array $cmd, bool $failSetup = false, bool $failCleanup = false): array
{
    isdNamespaceFree($d, $t, $ar);
    $p = isdPrefix($t);
    $decoy = $p . "unrelated_decoy";
    if (isdExists($d, $decoy)) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: occupied exact SQL name $decoy"); }
    $control = $ar . "/control-" . $t;
    $server = null;
    $result = null;
    $failure = null;
    try {
        $d->query("CREATE TABLE `$decoy`(id INT PRIMARY KEY,payload VARBINARY(64) NOT NULL) ENGINE=InnoDB");
        $bytes = bin2hex(random_bytes(31));
        $q = $d->prepare("INSERT INTO `$decoy` VALUES(1,?)");
        $q->bind_param("s", $bytes);
        $q->execute();
        isdCreate($d, $t);
        $before = isdSnapshot($d, $t);
        $decoyFingerprint = isdTableFingerprint($d, $decoy);
        if (!mkdir($control, 0700)) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: cannot create control directory"); }
        if ($failSetup) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: controlled post-decoy setup failure"); }
        $router = $control . "/router.php";
        $log = $control . "/requests.jsonl";
        $nonce = bin2hex(random_bytes(24));
        isdRouter($router);
        $env = isdEnvironment([
            "FMONITOR_DB_HOST" => $c["HOST"], "FMONITOR_DB_PORT" => $c["PORT"], "FMONITOR_DB_NAME" => $c["NAME"], "FMONITOR_DB_USER" => $c["USER"], "FMONITOR_DB_PASSWORD" => $c["PASSWORD"],
            "FMONITOR_PROCESS_TABLE_PREFIX" => $p, "FMONITOR_NOW" => "2026-09-01T09:30:00+03:00", "FMONITOR_INSPECTION_SCHEDULE_VERIFY_RUN_TOKEN" => $t, "FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT" => $ar,
        ]);
        $server = isdServer($root, $router, $log, $nonce, $env);
        $env["FMONITOR_INSPECTION_SCHEDULE_VERIFY_BASE_URL"] = $server["url"];
        $result = isdRun($cmd, $root, $env);
        isdAudit($d, $t, $nonce, $log, $before, $decoyFingerprint, $result);
    } catch (Throwable $e) {
        $failure = $e;
    } finally {
        $cleanup = [];
        $steps = [
            "server" => static function () use (&$server, $failCleanup): void { if (is_array($server)) { isdStop($server["process"], $server["pipes"], $server["pid"]); } if ($failCleanup) { throw new InspectionScheduleTestFailure("injected first cleanup error"); } },
            "fixtures" => static fn() => isdDrop($d, $t), "control" => static fn() => isdTree($control), "decoy" => static fn() => $d->query("DROP TABLE IF EXISTS `$decoy`"),
        ];
        foreach ($steps as $label => $step) { try { $step(); } catch (Throwable $e) { $cleanup[] = "$label=" . $e->getMessage(); } }
        if ($cleanup !== []) {
            $details = "CLEANUP_FAILURE: " . implode("; ", $cleanup);
            $failure = new InspectionScheduleTestFailure(($failure?->getMessage() ?? $details) . ($failure ? "\n$details" : ""), 0, $failure);
        }
    }
    isdNamespaceFree($d, $t, $ar);
    isdAssert(false, isdExists($d, $decoy), "SETUP_FAILURE: exact decoy cleanup leaked");
    if ($failure instanceof Throwable) { throw $failure; }
    if (!is_array($result)) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: verifier produced no result"); }
    return $result;
}

$root = dirname(__DIR__, 2);
$db = null;
$ar = $root . "/.local/test-artifacts/inspection-schedule-duplicate-" . bin2hex(random_bytes(6));
$tokens = [bin2hex(random_bytes(6)), bin2hex(random_bytes(6))];
$externalProbePaths = [];
try {
    isdAssert(ISD_SPEC_HASH, hash_file("sha256", $root . "/specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md"), "Gate 2 pins approved v0.2 spec");
    isdAssert(ISD_TRANSCRIPT_HASH, hash("sha256", isdTranscript()), "Gate 2 pins transcript");
    if (!mkdir($ar, 0700, true)) { throw new InspectionScheduleTestFailure("SETUP_FAILURE: cannot create repository-owned artifact root"); }
    file_put_contents($ar . "/ambient.bin", random_bytes(37), LOCK_EX);
    $ambient = hash_file("sha256", $ar . "/ambient.bin");
    $c = isdConfig();
    $db = isdDb($c);
    $db->query("SELECT 1");
    foreach ($tokens as $t) { isdNamespaceFree($db, $t, $ar); }
    $t = $tokens[0];
    $collision = isdTables($t)[0];
    $db->query("CREATE TABLE `$collision`(id INT PRIMARY KEY) ENGINE=InnoDB");
    try {
        isdNamespaceFree($db, $t, $ar);
        throw new InspectionScheduleTestFailure("REGRESSION_FAILURE: occupied SQL accepted");
    } catch (InspectionScheduleTestFailure $e) {
        isdAssert(true, str_starts_with($e->getMessage(), "SETUP_FAILURE: occupied exact SQL name"), "occupied SQL classified");
    } finally {
        $db->query("DROP TABLE `$collision`");
    }
    $child = $ar . "/inspection-schedule-$t";
    mkdir($child, 0700);
    file_put_contents($child . "/occupied.bin", random_bytes(19), LOCK_EX);
    $hash = hash_file("sha256", $child . "/occupied.bin");
    try {
        isdNamespaceFree($db, $t, $ar);
        throw new InspectionScheduleTestFailure("REGRESSION_FAILURE: occupied storage accepted");
    } catch (InspectionScheduleTestFailure $e) {
        isdAssert("SETUP_FAILURE: occupied exact artifact child", $e->getMessage(), "occupied storage classified");
        isdAssert($hash, hash_file("sha256", $child . "/occupied.bin"), "occupied storage preserved");
    } finally {
        isdTree($child);
    }
    try {
        isdExecute($root, $db, $c, $tokens[0], $ar, [PHP_BINARY, "-r", ""], true);
        throw new InspectionScheduleTestFailure("REGRESSION_FAILURE: controlled setup failure accepted");
    } catch (InspectionScheduleTestFailure $e) {
        isdAssert("SETUP_FAILURE: controlled post-decoy setup failure", $e->getMessage(), "controlled setup failure classified");
        isdNamespaceFree($db, $tokens[0], $ar);
        isdAssert(false, isdExists($db, isdPrefix($tokens[0]) . "unrelated_decoy"), "controlled setup failure removes decoy");
        isdAssert($ambient, hash_file("sha256", $ar . "/ambient.bin"), "controlled setup failure preserves ambient storage");
    }
    try {
        isdExecute($root, $db, $c, $tokens[0], $ar, [PHP_BINARY, "-r", ""], false, true);
        throw new InspectionScheduleTestFailure("REGRESSION_FAILURE: controlled cleanup failure accepted");
    } catch (InspectionScheduleTestFailure $e) {
        isdAssert(true, str_starts_with($e->getMessage(), "REGRESSION_FAILURE: normalized transcript must be exact") && str_contains($e->getMessage(), "CLEANUP_FAILURE: server=injected first cleanup error"), "primary failure preserved after process cleanup with cleanup diagnostics");
        isdNamespaceFree($db, $tokens[0], $ar);
        isdAssert(false, isdExists($db, isdPrefix($tokens[0]) . "unrelated_decoy"), "later SQL cleanup runs after first cleanup error");
        isdAssert(false, file_exists($ar . "/control-" . $tokens[0]), "later storage cleanup runs after first cleanup error");
        isdAssert($ambient, hash_file("sha256", $ar . "/ambient.bin"), "cleanup fault preserves ambient storage");
    }
    $fake = $ar . "/echo.php";
    file_put_contents($fake, "<?php echo " . var_export(isdTranscript(), true) . ";", LOCK_EX);
    try {
        isdExecute($root, $db, $c, $tokens[0], $ar, [PHP_BINARY, $fake]);
        throw new InspectionScheduleTestFailure("REGRESSION_FAILURE: echo-only verifier passed");
    } catch (InspectionScheduleTestFailure $e) {
        isdAssert(true, str_contains($e->getMessage(), "exactly six observed HTTP requests"), "echo-only verifier fails on independent HTTP evidence");
    }
    unlink($fake);
    $fake = $ar . "/final-state.php";
    $fakeSource = <<<'PHP'
    <?php
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$d=new mysqli(getenv('FMONITOR_DB_HOST'),getenv('FMONITOR_DB_USER'),getenv('FMONITOR_DB_PASSWORD'),getenv('FMONITOR_DB_NAME'),(int)getenv('FMONITOR_DB_PORT'));$p=getenv('FMONITOR_PROCESS_TABLE_PREFIX');$d->query("INSERT INTO `{$p}fm2_pilot_inspection_schedules` VALUES(77,6101,451201,7301,'2026-09-03',8101,'2026-09-01T09:30:00+03:00')");$d->query("INSERT INTO `{$p}fm2_pilot_inspection_schedule_events` VALUES(88,77,6101,'inspection_scheduled','{\"scheduleId\":77,\"inspectionDate\":\"2026-09-03\",\"controlEngineerUserId\":7301}',8101,'2026-09-01T09:30:00+03:00')");
    $csrf='schedule-characterization-csrf-001';$forms=[[451201,$csrf,'2026-09-03'],[451201,$csrf,'2026-09-03'],[451202,'wrong-schedule-csrf','2026-09-03'],[451203,$csrf,'2026-09-03'],[451204,$csrf,'2026-02-30'],[451205,$csrf,'2026-09-03']];foreach($forms as[$o,$c,$date]){$body=http_build_query(['csrfToken'=>$c,'inspectionDate'=>$date]);$ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>$body,'ignore_errors'=>true,'follow_location'=>0]]);file_get_contents(getenv('FMONITOR_INSPECTION_SCHEDULE_VERIFY_BASE_URL')."/pilot/objects/$o/inspection-schedule",false,$ctx);}echo "INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact\nINSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0\nINSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0\nCHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001\n";
    PHP;
    file_put_contents($fake, $fakeSource, LOCK_EX);
    try {
        isdExecute($root, $db, $c, $tokens[0], $ar, [PHP_BINARY, $fake]);
        throw new InspectionScheduleTestFailure("REGRESSION_FAILURE: final-state fabricator passed");
    } catch (InspectionScheduleTestFailure $e) {
        isdAssert(true, str_contains($e->getMessage(), "request 1 immediately begins with exact complete fixture state"), "final-state fabricator fails on pre-request fixture state");
        isdNamespaceFree($db, $tokens[0], $ar);
    }
    unlink($fake);
    $slow = $ar . "/slow.php";
    file_put_contents($slow, "<?php usleep(5000000);", LOCK_EX);
    $timed = isdRun([PHP_BINARY, $slow], $root, isdEnvironment(), 0.15);
    isdAssert(true, $timed["timed_out"], "SETUP_FAILURE: timeout control did not fire");
    isdAssert(124, $timed["status"], "SETUP_FAILURE: timeout status must be 124");
    unlink($slow);
    $verifier = $root . "/rapid-pilot/verify-inspection-schedule-duplicate.php";
    if (!is_file($verifier)) {
        $missing = isdRun([PHP_BINARY, "rapid-pilot/verify-inspection-schedule-duplicate.php"], $root, isdEnvironment());
        throw new InspectionScheduleTestFailure(
            "RED_ASSERTION: missing public inspection-schedule verifier must become a successful six-POST characterization; evidence=" . json_encode($missing, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }
    $base = isdEnvironment([
        "FMONITOR_DB_HOST" => $c["HOST"], "FMONITOR_DB_PORT" => $c["PORT"], "FMONITOR_DB_NAME" => $c["NAME"], "FMONITOR_DB_USER" => $c["USER"], "FMONITOR_DB_PASSWORD" => $c["PASSWORD"],
        "FMONITOR_INSPECTION_SCHEDULE_VERIFY_RUN_TOKEN" => $tokens[0], "FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT" => $ar, "FMONITOR_INSPECTION_SCHEDULE_VERIFY_BASE_URL" => "http://127.0.0.1:1",
    ]);
    $missingToken = $base; unset($missingToken["FMONITOR_INSPECTION_SCHEDULE_VERIFY_RUN_TOKEN"]);
    $missingRoot = $base; $missingRoot["FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT"] = "";
    $fallbackRoot = $base; unset($fallbackRoot["FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT"]); $fallbackRoot["TMPDIR"] = $ar;
    $linkRoot = $ar . "-link"; $fileRoot = $ar . "-file"; $relativeRoot = "isd-relative-" . $tokens[0];
    symlink($ar, $linkRoot); file_put_contents($fileRoot, random_bytes(23), LOCK_EX); $fileHash = hash_file("sha256", $fileRoot);
    $home = (string) getenv("HOME"); isdAssert(true, $home !== "" && is_dir($home), "SETUP_FAILURE: HOME needed for exact-root probe");
    $homeChild = $home . "/inspection-schedule-" . $tokens[0]; $tmpChild = "/tmp/inspection-schedule-" . $tokens[0];
    $externalProbePaths = [$linkRoot, $fileRoot, $homeChild, $tmpChild, $root . "/" . $relativeRoot];
    isdAssert(false, file_exists($homeChild) || file_exists($tmpChild) || file_exists($root . "/" . $relativeRoot), "SETUP_FAILURE: invalid-root probe child occupied");
    $probes = [
        [array_merge($base, ["FMONITOR_INSPECTION_SCHEDULE_VERIFY_RUN_TOKEN" => "not-a-token"]), "malformed token"], [$missingToken, "missing token"], [$missingRoot, "missing root"],
        [array_merge($base, ["FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT" => $linkRoot]), "symlink root"], [array_merge($base, ["FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT" => $fileRoot]), "non-directory root"],
        [array_merge($base, ["FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT" => $relativeRoot]), "relative root"], [array_merge($base, ["FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT" => $home]), "home root"],
        [array_merge($base, ["FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT" => "/tmp"]), "temporary root"], [$fallbackRoot, "unset fallback root"],
    ];
    foreach ($probes as [$env, $label]) { isdInvalid($verifier, $root, $env, $db, $tokens[0], $ar, $label); }
    isdAssert($ar, readlink($linkRoot), "symlink root preserved"); isdAssert($fileHash, hash_file("sha256", $fileRoot), "non-directory root preserved");
    isdAssert(false, file_exists($homeChild) || file_exists($tmpChild) || file_exists($root . "/" . $relativeRoot), "invalid roots leave no child");
    unlink($linkRoot); unlink($fileRoot);
    $runs = [];
    foreach ($tokens as $t) { $runs[] = isdExecute($root, $db, $c, $t, $ar, [PHP_BINARY, $verifier]); }
    isdAssert($runs[0]["stdout"], $runs[1]["stdout"], "distinct tokens exact transcript");
    isdAssert($ambient, hash_file("sha256", $ar . "/ambient.bin"), "ambient storage preserved");
    foreach ($tokens as $t) { isdNamespaceFree($db, $t, $ar); }
    echo isdTranscript();
} catch (InspectionScheduleTestFailure $e) {
    $m = $e->getMessage();
    fwrite(STDERR, $m . "\n"); if (file_exists($ar)) { isdTree($ar); }
    exit(str_starts_with($m, "SETUP_FAILURE:") ? 2 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, "SETUP_FAILURE: " . $e->getMessage() . "\n"); if (file_exists($ar)) { isdTree($ar); }
    exit(2);
} finally {
    if ($db instanceof mysqli) {
        foreach ($tokens as $t) {
            try {
                isdDrop($db, $t);
            } catch (Throwable) {
            }
        }
        $db->close();
    }
    if (file_exists($ar)) { isdTree($ar); }
    foreach ($externalProbePaths as $path) { if (file_exists($path) || is_link($path)) { isdTree($path); } }
}
