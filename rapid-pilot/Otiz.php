<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/MigratedEvidenceReconciliation.php';
require_once __DIR__ . '/legacy-migration/MigratedEvidenceDecisionLedger.php';
require_once __DIR__ . '/legacy-migration/OtizMigratedEvidenceInputs.php';
require_once __DIR__ . '/legacy-migration/PremiumCalculation.php';
require_once __DIR__ . '/NativeOperationalPremiumInputs.php';
require_once dirname(__DIR__) . '/app/PilotHttp/AccessPolicy.php';
require_once __DIR__ . '/legacy-migration/HistoricalPremiumReplayReadModel.php';
require_once __DIR__ . '/legacy-migration/LegacyActiveBaselineReadModel.php';
require_once __DIR__ . '/legacy-migration/MigrationQuarantineReadModel.php';
require_once __DIR__ . '/legacy-migration/MigrationQuarantineDecisionLedger.php';

final class RapidPilotOtiz
{
    private mysqli $db;
    private string $prefix;
    private int $userId;
    private string $userName;
    private string $csrf;
    private MigratedEvidenceDecisionLedger $decisionLedger;
    private MigrationQuarantineDecisionLedger $quarantineLedger;

    public function __construct()
    {
        $this->prefix = (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX');
        if (preg_match('/^[A-Za-z0-9_]+$/D', $this->prefix) !== 1) throw new RuntimeException('Invalid pilot table prefix');
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->db = new mysqli(getenv('FMONITOR_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_DB_USER') ?: 'fmonitor2_demo', getenv('FMONITOR_DB_PASSWORD') ?: 'fmonitor2_demo_local', getenv('FMONITOR_DB_NAME') ?: 'fmonitor2_demo', (int) (getenv('FMONITOR_DB_PORT') ?: '23306'));
        $this->db->set_charset('utf8mb4');
        $userId = filter_var($_SERVER['FMONITOR_AUTH_USER_ID'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($userId === false) throw new RuntimeException('Authenticated pilot user unavailable');
        $statement = $this->db->prepare("SELECT user_id,full_name FROM `{$this->prefix}fm2_pilot_users` WHERE user_id=? AND status=1 LIMIT 1");
        $statement->bind_param('i', $userId); $statement->execute(); $user = $statement->get_result()->fetch_assoc();
        if (!is_array($user)) throw new RuntimeException('Pilot user unavailable');
        $this->userId = (int) $user['user_id']; $this->userName = (string) $user['full_name'];
        if (!\FMonitor2\PilotHttp\AccessPolicy::grants(\FMonitor2\PilotHttp\AccessPolicy::forUser($this->db,$this->prefix,$this->userId),\FMonitor2\PilotHttp\AccessPolicy::OTIZ_MANAGE)) $this->fail(403, 'Раздел доступен сотрудникам ОТиЗ и администраторам.');
        $this->csrf = (string) ($_SERVER['FMONITOR_AUTH_CSRF'] ?? '');
        $this->ensureSchema();
        $this->decisionLedger = new MigratedEvidenceDecisionLedger($this->db, $this->prefix);
        $this->decisionLedger->ensureSchema();
        $this->quarantineLedger=new MigrationQuarantineDecisionLedger($this->db,$this->prefix);$this->quarantineLedger->ensureSchema();
    }

    public static function matches(string $path): bool { return preg_match('#^/pilot/otiz(?:/|$)#D', $path) === 1; }

    public static function currentUserCanAccess(): bool
    {
        $prefix = (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX');
        $userId = filter_var($_SERVER['FMONITOR_AUTH_USER_ID'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1 || $userId === false) return false;
        try {
            $db = new mysqli(getenv('FMONITOR_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_DB_USER') ?: 'fmonitor2_demo', getenv('FMONITOR_DB_PASSWORD') ?: 'fmonitor2_demo_local', getenv('FMONITOR_DB_NAME') ?: 'fmonitor2_demo', (int) (getenv('FMONITOR_DB_PORT') ?: '23306'));
            $db->set_charset('utf8mb4'); $statement = $db->prepare("SELECT user_id FROM `{$prefix}fm2_pilot_users` WHERE user_id=? AND status=1 LIMIT 1");
            $statement->bind_param('i', $userId); $statement->execute();$row=$statement->get_result()->fetch_assoc();$allowed=is_array($row)&&\FMonitor2\PilotHttp\AccessPolicy::grants(\FMonitor2\PilotHttp\AccessPolicy::forUser($db,$prefix,(int)$row['user_id']),\FMonitor2\PilotHttp\AccessPolicy::OTIZ_MANAGE); $db->close(); return $allowed;
        } catch (Throwable) { return false; }
    }

    public static function decorateNavigation(string $html, bool $active): string
    {
        $current = $active ? ' aria-current="page"' : '';
        $icon = '<svg class="fm2-nav-icon fm2-nav-icon--shlz" viewBox="0 0 24 25" aria-hidden="true"><use href="/pilot/assets/shlz-icons.svg#shlz-icon-bar-chart-square-plus"/></svg>';
        return preg_replace('#<span class="fm2-nav-group">Управление</span><span class="fm2-nav-item fm2-nav-item--muted" aria-disabled="true"><svg[^>]*>.*?</svg><span class="fm2-nav-text">Расчёты ОТиЗ</span></span>#s', '<span class="fm2-nav-group">ОТиЗ</span><a class="fm2-nav-item" href="/pilot/otiz"' . $current . '>' . $icon . '<span class="fm2-nav-text">Расчёты ОТиЗ</span></a><span class="fm2-nav-group">Управление</span>', $html) ?? $html;
    }

    public function handle(string $path): never
    {
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') $this->command($path);
        if ($path === '/pilot/otiz' || $path === '/pilot/otiz/' || $path === '/pilot/otiz/objects') $this->objects();
        if ($path === '/pilot/otiz/payments') $this->queue();
        if ($path === '/pilot/otiz/history') $this->history();
        if ($path === '/pilot/otiz/reconciliation') $this->reconciliation();
        if ($path === '/pilot/otiz/reconciliation/quarantine') $this->migrationQuarantine();
        if ($path === '/pilot/otiz/active-baselines') $this->activeBaselines();
        if ($path === '/pilot/otiz/historical-replay') $this->historicalReplay();
        if (preg_match('#^/pilot/otiz/snapshots/(\d+)$#D', $path, $m) === 1) $this->snapshot((int) $m[1]);
        if (preg_match('#^/pilot/otiz/snapshots/(\d+)/export\.xlsx$#D', $path, $m) === 1) $this->export((int) $m[1]);
        http_response_code(404); echo "Not found.\n"; exit;
    }

    private function command(string $path): never
    {
        if ($this->csrf === '' || !hash_equals($this->csrf, (string) ($_POST['csrfToken'] ?? ''))) $this->fail(403, 'Недопустимый запрос.');
        if ($path === '/pilot/otiz/calculate') {
            $date = (string) ($_POST['reportDate'] ?? '');
            if (!$this->validDate($date)) $this->redirect('/pilot/otiz/payments?error=date');
            $id = $this->calculate($date); $this->redirect('/pilot/otiz/snapshots/' . $id . '?created=1');
        }
        if ($path === '/pilot/otiz/reconciliation/decisions') {
            $snapshotId = filter_var($_POST['snapshotId'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            $outcome = (string)($_POST['outcome'] ?? '');
            $target = trim((string)($_POST['targetLocator'] ?? ''));
            try {
                $result = $this->decisionLedger->decide([
                    'operationId'=>(string)($_POST['operationId'] ?? ''), 'snapshotId'=>$snapshotId,
                    'snapshotSha256'=>(string)($_POST['snapshotSha256'] ?? ''), 'projectionSha256'=>(string)($_POST['projectionSha256'] ?? ''),
                    'sourceLocator'=>(string)($_POST['sourceLocator'] ?? ''), 'issueCode'=>(string)($_POST['issueCode'] ?? ''),
                    'outcome'=>$outcome, 'targetLocator'=>$outcome === 'map_link' ? $target : null,
                    'reason'=>trim((string)($_POST['reason'] ?? '')), 'actorUserId'=>$this->userId, 'occurredAt'=>$this->now(),
                ]);
                $this->redirect('/pilot/otiz/reconciliation?decision='.$result['status'].'#snapshot-'.$snapshotId);
            } catch (InvalidArgumentException) {
                $this->redirect('/pilot/otiz/reconciliation?decisionError=invalid#snapshot-'.(int)$snapshotId);
            } catch (DomainException $error) {
                $message=$error->getMessage();$code=str_contains($message,'stale or unavailable')?'stale':(str_contains($message,'issue reference')?'conflict':(str_contains($message,'Operation id')?'operation':'forbidden'));
                $this->redirect('/pilot/otiz/reconciliation?decisionError='.$code.'#snapshot-'.(int)$snapshotId);
            }
        }
        if($path==='/pilot/otiz/reconciliation/quarantine/decisions'){
            try{$result=$this->quarantineLedger->decide(['operationId'=>(string)($_POST['operationId']??''),'sourceLocator'=>(string)($_POST['sourceLocator']??''),'sourceCutoffAt'=>(string)($_POST['sourceCutoffAt']??''),'sourceDigest'=>(string)($_POST['sourceDigest']??''),'classificationVersion'=>(string)($_POST['classificationVersion']??''),'quarantineCode'=>(string)($_POST['quarantineCode']??''),'outcome'=>(string)($_POST['outcome']??''),'reason'=>(string)($_POST['reason']??''),'actorUserId'=>$this->userId,'occurredAt'=>$this->now()]);$this->redirect('/pilot/otiz/reconciliation/quarantine?decision='.$result['status']);}catch(InvalidArgumentException){$this->redirect('/pilot/otiz/reconciliation/quarantine?decisionError=invalid');}catch(DomainException$e){$this->redirect('/pilot/otiz/reconciliation/quarantine?decisionError='.(str_contains($e->getMessage(),'STALE')?'stale':'forbidden'));}
        }
        if (preg_match('#^/pilot/otiz/snapshots/(\d+)/accept$#D', $path, $m) === 1) {
            $id = (int) $m[1]; $this->db->begin_transaction();
            $row = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshots` WHERE id={$id} LIMIT 1 FOR UPDATE")->fetch_assoc();
            if (!is_array($row)) { $this->db->rollback(); $this->fail(404, 'Срез не найден.'); }
            if ($row['status'] !== 'draft') { $this->db->rollback(); $this->redirect('/pilot/otiz/snapshots/' . $id . '?error=immutable'); }
            $count = (int) $this->db->query("SELECT COUNT(*) n FROM `{$this->prefix}fm2_pilot_otiz_snapshot_issues` WHERE snapshot_id={$id} AND severity='blocker' AND state='open'")->fetch_assoc()['n'];
            if ($count > 0) { $this->db->rollback(); $this->redirect('/pilot/otiz/snapshots/' . $id . '?error=blockers'); }
            $now = $this->now(); $s = $this->db->prepare("UPDATE `{$this->prefix}fm2_pilot_otiz_snapshots` SET status='accepted',accepted_at=?,accepted_by_user_id=? WHERE id=? AND status='draft'"); $s->bind_param('sii', $now, $this->userId, $id); $s->execute();
            $this->event($id, null, 'snapshot_accepted', ['hash' => $row['content_hash']]); $this->db->commit(); $this->redirect('/pilot/otiz/snapshots/' . $id . '?accepted=1');
        }
        if (preg_match('#^/pilot/otiz/snapshots/(\d+)/closures$#D', $path, $m) === 1) {
            $snapshotId = (int) $m[1];
            $objectId = (int) ($_POST['objectId'] ?? 0); $discipline = $this->money((string) ($_POST['discipline'] ?? '0')); $basis = trim((string) ($_POST['basis'] ?? ''));
            $paid = 0; $deadline = 0; $sum = $discipline;
            if ($discipline <= 0 || $basis === '' || mb_strlen($basis) > 500) $this->redirect('/pilot/otiz/snapshots/' . $snapshotId . '?error=closure');
            $artifact = trim((string) ($_POST['artifact'] ?? '')); if (mb_strlen($artifact) > 300) $this->redirect('/pilot/otiz/snapshots/' . $snapshotId . '?error=closure');
            $this->db->begin_transaction();
            $snapshot = $this->db->query("SELECT status FROM `{$this->prefix}fm2_pilot_otiz_snapshots` WHERE id={$snapshotId} LIMIT 1 FOR UPDATE")->fetch_assoc();
            if (!is_array($snapshot)) { $this->db->rollback(); $this->fail(404, 'Срез не найден.'); }
            if ($snapshot['status'] !== 'accepted') { $this->db->rollback(); $this->redirect('/pilot/otiz/snapshots/' . $snapshotId . '?error=accept-first'); }
            $object = $this->db->query("SELECT pool_cents,calculation_state FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$snapshotId} AND object_id={$objectId} LIMIT 1 FOR UPDATE")->fetch_assoc();
            $closed = (int) $this->db->query("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$snapshotId} AND object_id={$objectId}")->fetch_assoc()['n'];
            if (!is_array($object) || $object['calculation_state'] === 'blocked' || $sum > (int) $object['pool_cents'] - $closed) { $this->db->rollback(); $this->redirect('/pilot/otiz/snapshots/' . $snapshotId . '?error=closure'); }
            $now = $this->now(); $date = substr($now, 0, 10); $s = $this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_payment_closures`(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at,reverses_payment_closure_id) VALUES(?,?,?,?,?,?,?,?,?,?,NULL)"); $artifact = trim((string) ($_POST['artifact'] ?? '')); $s->bind_param('iisiiissis', $snapshotId, $objectId, $date, $paid, $discipline, $deadline, $basis, $artifact, $this->userId, $now); $s->execute();
            $this->event($snapshotId, $objectId, 'payment_closure_recorded', ['closureId' => $s->insert_id, 'closedCents' => $sum]); $this->db->commit(); $this->redirect('/pilot/otiz/snapshots/' . $snapshotId . '?closed=1');
        }
        if (preg_match('#^/pilot/otiz/snapshots/(\d+)/payments/complete$#D', $path, $m) === 1) {
            $snapshotId=(int)$m[1];$this->db->begin_transaction();
            $snapshot=$this->db->query("SELECT status FROM `{$this->prefix}fm2_pilot_otiz_snapshots` WHERE id={$snapshotId} LIMIT 1 FOR UPDATE")->fetch_assoc();
            if(!is_array($snapshot)){$this->db->rollback();$this->fail(404,'Срез не найден.');}
            if($snapshot['status']!=='accepted'){$this->db->rollback();$this->redirect('/pilot/otiz/snapshots/'.$snapshotId.'?error=payment');}
            $objects=$this->db->query("SELECT object_id,pool_cents,calculation_state FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$snapshotId} ORDER BY object_id FOR UPDATE")->fetch_all(MYSQLI_ASSOC);
            $date=substr($this->now(),0,10);$discipline=0;$deadline=0;$basis='Выплаты выполнены по подтверждению ОТиЗ';$artifact='';$now=$this->now();$count=0;$total=0;
            $insert=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_payment_closures`(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at,reverses_payment_closure_id) VALUES(?,?,?,?,?,?,?,?,?,?,NULL)");
            foreach($objects as$object){if($object['calculation_state']==='blocked')continue;$objectId=(int)$object['object_id'];$closed=(int)$this->db->query("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$snapshotId} AND object_id={$objectId}")->fetch_assoc()['n'];$paid=max(0,(int)$object['pool_cents']-$closed);if($paid===0)continue;$insert->bind_param('iisiiissis',$snapshotId,$objectId,$date,$paid,$discipline,$deadline,$basis,$artifact,$this->userId,$now);$insert->execute();$this->event($snapshotId,$objectId,'payment_completed',['closureId'=>$insert->insert_id,'paidCents'=>$paid,'scope'=>'snapshot']);$count++;$total+=$paid;}
            if($count===0){$this->db->rollback();$this->redirect('/pilot/otiz/snapshots/'.$snapshotId.'?paid=duplicate');}
            $this->event($snapshotId,null,'snapshot_payments_completed',['objectCount'=>$count,'paidCents'=>$total]);$this->db->commit();$this->redirect('/pilot/otiz/snapshots/'.$snapshotId.'?paid=1');
        }
        if (preg_match('#^/pilot/otiz/closures/(\d+)/reverse$#D', $path, $m) === 1) {
            $closureId = (int) $m[1]; $basis = trim((string) ($_POST['basis'] ?? ''));
            if ($basis === '' || mb_strlen($basis) > 500) $this->redirect('/pilot/otiz?error=reverse-basis');
            $this->db->begin_transaction();
            $closure = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE id={$closureId} LIMIT 1 FOR UPDATE")->fetch_assoc();
            if (!is_array($closure) || (int) $closure['reverses_payment_closure_id'] > 0) { $this->db->rollback(); $this->fail(404, 'Запись не найдена.'); }
            $exists = (int) $this->db->query("SELECT COUNT(*) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE reverses_payment_closure_id={$closureId}")->fetch_assoc()['n'];
            if ($exists > 0) { $this->db->rollback(); $this->redirect('/pilot/otiz/snapshots/' . $closure['snapshot_id'] . '?error=reversed'); }
            $snapshotId = (int) $closure['snapshot_id']; $objectId = (int) $closure['object_id']; $date = substr($this->now(), 0, 10); $paid = -(int) $closure['paid_cents']; $discipline = -(int) $closure['discipline_cents']; $deadline = -(int) $closure['deadline_cents']; $artifact = ''; $now = $this->now();
            $s = $this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_payment_closures`(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at,reverses_payment_closure_id) VALUES(?,?,?,?,?,?,?,?,?,?,?)"); $s->bind_param('iisiiissisi', $snapshotId, $objectId, $date, $paid, $discipline, $deadline, $basis, $artifact, $this->userId, $now, $closureId); $s->execute();
            $this->event($snapshotId, $objectId, 'payment_closure_reversed', ['closureId' => $closureId, 'reversalId' => $s->insert_id]); $this->db->commit(); $this->redirect('/pilot/otiz/snapshots/' . $snapshotId . '?reversed=1');
        }
        $this->fail(404, 'Команда не найдена.');
    }

    private function calculate(string $date): int
    {
        $previous = $this->db->query("SELECT id FROM `{$this->prefix}fm2_pilot_otiz_snapshots` WHERE status='accepted' AND report_date<'" . $this->db->real_escape_string($date) . "' ORDER BY report_date DESC,id DESC LIMIT 1")->fetch_assoc();
        $previousId = is_array($previous) ? (int) $previous['id'] : null; $now = $this->now();
        $rulesVersion=PremiumCalculation::VERSION;$s = $this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_snapshots`(report_date,status,previous_snapshot_id,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash) VALUES(?,'draft',?,?,?, ?,NULL,NULL,0,0,0,'pending')"); $s->bind_param('sissi', $date, $previousId,$rulesVersion, $now, $this->userId); $s->execute(); $snapshotId = (int) $s->insert_id;
        $legacyPrefix=(string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:$this->prefix);if(preg_match('/^[A-Za-z0-9_]+$/D',$legacyPrefix)!==1)throw new RuntimeException('Invalid legacy table prefix');
        $objects = (new NativeOperationalPremiumInputs($this->db,$this->prefix,$legacyPrefix))->forDate($date); $totalPool = 0; $totalClosed = 0; $payload = [];
        foreach ($objects as $o) {
            $progress=(int)$o['progress'];$previousProgress=0;$q=$this->db->query("SELECT so.current_progress_bp FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` so JOIN `{$this->prefix}fm2_pilot_otiz_snapshots` s ON s.id=so.snapshot_id AND s.status='accepted' WHERE so.object_id=".(int)$o['id']." AND s.report_date<'".$this->db->real_escape_string($date)."' ORDER BY s.report_date DESC,s.id DESC LIMIT 1");$r=$q->fetch_assoc();if(is_array($r))$previousProgress=(int)$r['current_progress_bp'];
            $blocked=$o['issues']!==[];$calculation=$blocked?null:PremiumCalculation::calculate($o['operands'],['closures'=>$this->closureEvidence((int)$o['id'],$date),'actualPayouts'=>[]],[]);
            $empty=['fundCents'=>0,'accruedCents'=>0,'closedBeforeCents'=>$this->closedBefore((int)$o['id'],$date),'poolCents'=>0,'remainingFundCents'=>0,'distributableCents'=>0];$amounts=$calculation['amounts']??$empty;
            $daysLate=(int)($calculation['formulaTrace'][2]['daysLate']??0);$kss=(int)($calculation['kssBp']??0);$fund=(int)$amounts['fundCents'];$accrued=(int)$amounts['accruedCents'];$closed=(int)$amounts['closedBeforeCents'];$pool=(int)$amounts['poolCents'];$remaining=(int)$amounts['remainingFundCents'];
            $state = $blocked ? 'blocked' : ($pool === 0 ? 'no_new_amount' : ($o['pto'] !== null && $progress === 10000 && $remaining === 0 ? 'completed' : 'ready'));
            $inputs = ['address' => $o['address'], 'deadline' => $o['deadline'], 'pto' => $o['pto'], 'daysLate' => $daysLate,
                'calculationOperandsSource' => 'native_operational_facts', 'calculationOperandsLabel' => 'Подтверждённые native facts FMonitor 2',
                'premiumCalculation'=>$calculation,'blockers'=>$o['issues']];
            $stmt = $this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_snapshot_objects`(snapshot_id,object_id,regnumber,address,previous_progress_bp,current_progress_bp,progress_fact_date,premium_cents,shaft_bp,kss_bp,accrued_cents,fund_cents,closed_before_cents,remaining_cents,pool_cents,distributed_cents,undistributed_cents,calculation_state,inputs_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $distributed = (int)($amounts['distributableCents']??0); $undistributed = $pool - $distributed; $progressDate = $date; $json = json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $stmt->bind_param('iissiisiiiiiiiiiiss', $snapshotId, $o['id'], $o['reg'], $o['address'], $previousProgress, $progress, $progressDate, $o['premium'], $o['shaft'], $kss, $accrued, $fund, $closed, $remaining, $pool, $distributed, $undistributed, $state, $json); $stmt->execute();
            foreach($o['issues'] as $issue)$this->issue($snapshotId,$o['id'],'blocker',$issue['code'],$issue['message'],$issue['owner']);
            if ($daysLate > 0) $this->issue($snapshotId, $o['id'], 'warning', 'DEADLINE_PENALTY', "Просрочка {$daysLate} календ. дн.; Ксс уменьшен до " . number_format($kss / 10000, 2, ',', ' '), 'ОТиЗ');
            $members = $o['team']; $weights = array_sum(array_column($members, 'weight'));
            foreach ($members as $member) {
                $amount = ($distributed > 0 && $weights > 0) ? intdiv($distributed * $member['weight'], $weights) : 0;
                $share = $weights > 0 ? intdiv(10000 * $member['weight'], $weights) : 0; $employment = $member['employment'] ?? 'employed'; $basis = $member['basis'] ?? 'Распоряжение № ' . $o['id'] . '-Р';
                $a = $this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_snapshot_allocations`(snapshot_id,object_id,tab_id,full_name,position_name,contribution_bp,base_ktu_bp,adjustment_ktu_bp,effective_ktu_bp,share_bp,amount_cents,employment_status,participation_basis) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $zero = 0;$baseKtu=10000;$effectiveKtu=10000;$contribution=(int)($member['contribution']??$member['weight']); $a->bind_param('iisssiiiiiiss', $snapshotId, $o['id'], $member['tab'], $member['name'], $member['position'], $contribution, $baseKtu, $zero, $effectiveKtu, $share, $amount, $employment, $basis); $a->execute();
            }
            $totalPool += $pool; $totalClosed += $closed; $payload[] = [$o['id'], $progress, $kss, $pool, $closed, $state];
        }
        $hash = hash('sha256', json_encode([$date, PremiumCalculation::VERSION, $payload], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $u = $this->db->prepare("UPDATE `{$this->prefix}fm2_pilot_otiz_snapshots` SET total_pool_cents=?,total_closed_cents=?,total_available_cents=?,content_hash=? WHERE id=?"); $u->bind_param('iiisi', $totalPool, $totalClosed, $totalPool, $hash, $snapshotId); $u->execute();
        $this->event($snapshotId, null, 'draft_calculated', ['reportDate' => $date, 'hash' => $hash]); return $snapshotId;
    }

    private function queue(): never
    {
        $latest = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshots` ORDER BY id DESC LIMIT 1")->fetch_assoc();
        $cards = '<section class="fm2-otiz-start"><div><h1>Подготовка выплат</h1><p>Выберите дату, проверьте начисления по объектам и подготовьте реестр к выплате.</p></div><form method="post" action="/pilot/otiz/calculate" class="fm2-otiz-date"><input type="hidden" name="csrfToken" value="' . $this->e($this->csrf) . '"><label class="shlz-field"><span class="shlz-field__label">Рассчитать по состоянию на</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="reportDate" value="2026-08-31" required></span></label><button class="shlz-button shlz-button--primary" type="submit">Подготовить расчёт</button></form></section>';
        $cards .= $this->primaryTabs('payments');
        if (is_array($latest)) {
            $closed = (int)$this->db->query("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id=".(int)$latest['id'])->fetch_assoc()['n'];
            $paymentDone = $latest['status'] === 'accepted' && (int)$latest['total_pool_cents'] > 0 && $closed >= (int)$latest['total_pool_cents'];
            $label = $paymentDone ? 'Выплаты выполнены' : ($latest['status'] === 'accepted' ? 'Готов к выплате' : 'Требует проверки');
            $description = $paymentDone ? 'Выплата зафиксирована и уже учтена в экономике объектов.' : ($latest['status'] === 'accepted' ? 'Начисления проверены. Можно выгрузить реестр и зафиксировать выплату.' : 'Проверьте объекты и устраните замечания перед оформлением реестра.');
            $cards .= '<section class="fm2-otiz-current"><div><span class="shlz-status ' . ($latest['status'] === 'accepted' ? 'shlz-status--green' : 'shlz-status--orange') . '">' . $label . '</span><h2>Расчёт на ' . $this->date($latest['report_date']) . '</h2><p>' . $description . '</p></div><div><strong>' . $this->rub(max(0, (int)$latest['total_pool_cents'] - $closed)) . '</strong><span>осталось выплатить</span><a class="shlz-button shlz-button--primary" href="/pilot/otiz/snapshots/' . (int) $latest['id'] . '">' . ($latest['status'] === 'accepted' ? 'Открыть реестр' : 'Продолжить проверку') . '</a></div></section>';
        }
        $cards .= $this->scenarioOverview();
        $this->page('Расчёты ОТиЗ', $cards);
    }

    private function snapshot(int $id): never
    {
        $s = $this->snapshotRow($id); $objects = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$id} ORDER BY FIELD(calculation_state,'blocked','ready','no_new_amount','completed'),regnumber")->fetch_all(MYSQLI_ASSOC);
        $blockers = (int) $this->db->query("SELECT COUNT(*) n FROM `{$this->prefix}fm2_pilot_otiz_snapshot_issues` WHERE snapshot_id={$id} AND severity='blocker' AND state='open'")->fetch_assoc()['n'];
        $payable=0;$deadlinePenalty=0;$closedByObject=[];foreach($objects as$object){$closed=(int)$this->db->query("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$id} AND object_id=".(int)$object['object_id'])->fetch_assoc()['n'];$closedByObject[(int)$object['object_id']]=$closed;if($object['calculation_state']!=='blocked')$payable+=max(0,(int)$object['pool_cents']-$closed);$deadlinePenalty+=$this->deadlinePenalty($object);}
        $flash = isset($_GET['created']) ? 'Расчёт подготовлен. Проверьте суммы и замечания по объектам.' : (isset($_GET['accepted']) ? 'Расчёт подтверждён и готов к оформлению выплаты.' : (($_GET['paid']??'')==='1' ? 'Выплаты отмечены выполненными и учтены по объектам.' : (($_GET['paid']??'')==='duplicate' ? 'Эти выплаты уже были отмечены выполненными.' : (isset($_GET['closed']) ? 'Удержание добавлено к выплате по объекту.' : (isset($_GET['reversed']) ? 'Предыдущая отметка о выплате отменена.' : '')))));
        $body = '<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><ol><li><a class="fm2-breadcrumb-link" href="/pilot/otiz/payments">Подготовка выплат</a></li><li><span aria-current="page">Расчёт на ' . $this->date($s['report_date']) . '</span></li></ol></nav>'.$this->primaryTabs('payments');
        if ($flash !== '') $body .= '<p class="fm2-alert" role="status">' . $this->e($flash) . '</p>';
        if (isset($_GET['error'])) $body .= '<p class="fm2-otiz-error" role="alert">Действие не выполнено. Устраните замечания по объектам и проверьте доступную сумму.</p>';
        $acceptedActions='<a class="shlz-link" href="/pilot/otiz/snapshots/'.$id.'/export.xlsx">Скачать реестр XLSX</a>'.($payable>0?'<form method="post" action="/pilot/otiz/snapshots/'.$id.'/payments/complete"><input type="hidden" name="csrfToken" value="'.$this->e($this->csrf).'"><button class="shlz-button shlz-button--primary" type="submit">Отметить выплаты выполненными</button><small>Сумма выплаты — '.$this->rub($payable).'</small></form>':'<span class="shlz-status shlz-status--green">Выплаты выполнены</span>');
        $snapshotStatus = $s['status'] === 'draft' ? 'На проверке' : ($payable === 0 ? 'Выплаты выполнены' : 'Готовы к выплате');
        $body .= '<header class="fm2-otiz-snapshot-head"><div><div class="fm2-otiz-title-line"><h1>Выплаты на ' . $this->date($s['report_date']) . '</h1><span class="shlz-status ' . ($s['status'] === 'accepted' ? 'shlz-status--green' : 'shlz-status--orange') . '">' . $snapshotStatus . '</span></div><p>Расчёт подготовлен ' . $this->dateTime($s['calculated_at']) . '. Проверьте начисления и замечания по каждому объекту.</p></div><div class="fm2-otiz-actions">' . ($s['status'] === 'draft' ? '<form method="post" action="/pilot/otiz/snapshots/' . $id . '/accept"><input type="hidden" name="csrfToken" value="' . $this->e($this->csrf) . '"><button class="shlz-button shlz-button--primary" type="submit"' . ($blockers > 0 ? ' disabled' : '') . '>Подтвердить расчёт</button></form>' : $acceptedActions) . '</div></header>';
        $body .= '<section class="fm2-otiz-summary" aria-label="Итоги расчёта"><div><span>К выплате</span><strong>' . $this->rub($payable) . '</strong></div><div><span>Удержано за сроки</span><strong>' . $this->rub($deadlinePenalty) . '</strong></div><div><span>Требуют внимания</span><strong>' . $blockers . '</strong></div><div><span>Объекты</span><strong>' . count($objects) . '</strong></div></section>';
        $body .= '<section class="fm2-list-surface"><div class="fm2-list-toolbar"><h2>Проверка начислений</h2><span class="fm2-result-count">' . count($objects) . ' объектов</span></div><div class="fm2-table-wrap"><table class="shlz-table fm2-queue-table fm2-otiz-table"><thead><tr><th>Объект</th><th>Выполнено работ</th><th>Коэффициент сроков</th><th>Удержано за сроки</th><th>Выплачено ранее</th><th>К выплате</th><th>Состояние</th></tr></thead><tbody>';
        foreach ($objects as $o) {
            $issues = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_issues` WHERE snapshot_id={$id} AND object_id=" . (int) $o['object_id'] . " ORDER BY FIELD(severity,'blocker','warning')")->fetch_all(MYSQLI_ASSOC);
            $issueHtml = ''; foreach ($issues as $issue) $issueHtml .= '<li><b>' . ($issue['severity'] === 'blocker' ? 'Нужно исправить' : 'Обратите внимание') . ':</b> ' . $this->e($issue['message']) . ' <span>Ответственный: ' . $this->e($issue['owner_role']) . '</span></li>';
            $objectClosed=$closedByObject[(int)$o['object_id']]??0;$objectPayable=max(0,(int)$o['pool_cents']-$objectClosed);$paymentDone=(int)$o['pool_cents']>0&&$objectPayable===0;
            $status = $o['calculation_state'] === 'blocked' ? ['shlz-status--orange','Требует данных'] : ($o['calculation_state'] === 'no_new_amount' ? ['','Нет новой суммы'] : ($o['calculation_state'] === 'completed' || $paymentDone ? ['shlz-status--green','Выплата выполнена'] : ['shlz-status--blue','Готов к выплате']));
            $body .= '<tr class="fm2-otiz-object-row"><td><details><summary><strong>' . $this->e($o['regnumber']) . '</strong><small>' . $this->e($o['address']) . '</small></summary><div class="fm2-otiz-detail">' . $this->calculationTrace($o) . $this->allocation($id, (int) $o['object_id']) . ($issueHtml !== '' ? '<ul class="fm2-otiz-issues">' . $issueHtml . '</ul>' : '') . $this->closureForm($s, $o) . '</div></details></td><td><strong>' . $this->percent((int) $o['previous_progress_bp']) . ' → ' . $this->percent((int) $o['current_progress_bp']) . '</strong><small>+' . $this->percent(max(0, (int) $o['current_progress_bp'] - (int) $o['previous_progress_bp'])) . '</small></td><td>' . number_format((int) $o['kss_bp'] / 10000, 2, ',', ' ') . '</td><td><strong>'.$this->rub($this->deadlinePenalty($o)).'</strong></td><td>' . $this->rub((int) $o['closed_before_cents']) . '</td><td><strong>' . $this->rub($objectPayable) . '</strong></td><td><span class="shlz-status ' . $status[0] . '">' . $status[1] . '</span></td></tr>';
        }
        $body .= '</tbody></table></div></section>' . $this->closures($id);
        $this->page('Выплаты на ' . $this->date($s['report_date']), $body);
    }

    private function history(): never
    {
        $rows = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshots` ORDER BY report_date DESC,id DESC")->fetch_all(MYSQLI_ASSOC);
        $body = '<header class="fm2-page-header"><div><h1>Архив расчётов</h1><p>Здесь хранятся подготовленные и подтверждённые расчёты за выбранные даты.</p></div></header>'.$this->primaryTabs('history').'<section class="fm2-list-surface"><div class="fm2-table-wrap"><table class="shlz-table fm2-queue-table fm2-otiz-history"><thead><tr><th>Дата расчёта</th><th>Состояние</th><th>Подтверждён</th><th>К выплате</th><th>Выплачено ранее</th></tr></thead><tbody>';
        foreach ($rows as $r) $body .= '<tr><td data-label="Дата расчёта"><a class="shlz-link" href="/pilot/otiz/snapshots/' . (int) $r['id'] . '">' . $this->date($r['report_date']) . '</a></td><td data-label="Состояние"><span class="shlz-status ' . ($r['status'] === 'accepted' ? 'shlz-status--green' : 'shlz-status--orange') . '">' . ($r['status'] === 'accepted' ? 'Подтверждён' : 'Требует проверки') . '</span></td><td data-label="Подтверждён">' . ($r['accepted_at'] ? $this->dateTime($r['accepted_at']) : '—') . '</td><td data-label="К выплате">' . $this->rub((int) $r['total_pool_cents']) . '</td><td data-label="Выплачено ранее">' . $this->rub((int) $r['total_closed_cents']) . '</td></tr>';
        $body .= '</tbody></table></div></section>'; $this->page('Архив расчётов', $body);
    }

    private function objects(): never
    {
        $legacyPrefix=(string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:$this->prefix);
        if(preg_match('/^[A-Za-z0-9_]+$/D',$legacyPrefix)!==1)throw new RuntimeException('Invalid legacy table prefix');
        $rows = $this->db->query("SELECT l.id object_id,l.regnumber,l.ordadr_address address,d.payload_json,d.captured_at,
            so.snapshot_id,so.report_date,so.current_progress_bp,so.progress_fact_date,so.accrued_cents,so.pool_cents,so.kss_bp,so.calculation_state,so.inputs_json,
            COALESCE(c.paid_cents,0) paid_cents,
            COALESCE(c.discipline_cents,0) discipline_cents,
            COALESCE(c.deadline_cents,0) deadline_cents,
            COALESCE(sc.closed_cents,0) snapshot_closed_cents
            FROM `{$legacyPrefix}fm_maintable` l
            LEFT JOIN `{$this->prefix}fm2_pilot_object_details` d ON d.object_id=l.id
            LEFT JOIN (
                SELECT candidate.*,snapshot.report_date
                FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` candidate
                JOIN `{$this->prefix}fm2_pilot_otiz_snapshots` snapshot ON snapshot.id=candidate.snapshot_id
                WHERE NOT EXISTS (
                    SELECT 1 FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` newer
                    JOIN `{$this->prefix}fm2_pilot_otiz_snapshots` ns ON ns.id=newer.snapshot_id
                    WHERE newer.object_id=candidate.object_id AND (ns.report_date>snapshot.report_date OR (ns.report_date=snapshot.report_date AND ns.id>snapshot.id))
                )
            ) so ON so.object_id=l.id
            LEFT JOIN (
                SELECT object_id,SUM(paid_cents) paid_cents,SUM(discipline_cents) discipline_cents,SUM(deadline_cents) deadline_cents
                FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` GROUP BY object_id
            ) c ON c.object_id=l.id
            LEFT JOIN (
                SELECT snapshot_id,object_id,SUM(paid_cents+discipline_cents+deadline_cents) closed_cents
                FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` GROUP BY snapshot_id,object_id
            ) sc ON sc.object_id=l.id AND sc.snapshot_id=so.snapshot_id
            ORDER BY so.snapshot_id IS NULL,FIELD(so.calculation_state,'blocked','ready','no_new_amount','completed'),l.regnumber,l.id")->fetch_all(MYSQLI_ASSOC);

        $norms=new NativePremiumNorms();$fund = $earned = $paid = $penalties = $balance = $blocked = $calculated = 0;
        foreach ($rows as &$row) {
            $payload=is_string($row['payload_json'])?json_decode($row['payload_json'],true):null;$fields=is_array($payload)?($payload['fields']??[]):[];
            $floors=filter_var($fields['floors']['raw']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$capacity=filter_var($fields['weight']['raw']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            $material=trim((string)($fields['pitmaterial']['display']??''));$typeText=mb_strtolower((string)($fields['lift_type']['display']??''),'UTF-8');$type=str_contains($typeText,'груз')?'cargo':(str_contains($typeText,'пассаж')?'passenger':null);
            $premium=$floors===false||$capacity===false?null:$norms->premiumCents($type,(int)$floors,(int)$capacity);$shaft=$norms->shaftBasisPoints($material);
            $row['premium_cents']=$premium;$row['shaft_bp']=$shaft;$row['fund_cents']=$premium!==null&&$shaft!==null?intdiv($premium*$shaft,10000):null;
            if($row['fund_cents']!==null){$fund+=(int)$row['fund_cents'];$calculated++;}
            $earned += (int) ($row['accrued_cents']??0);
            $paid += (int) $row['paid_cents'];
            $row['deadline_penalty_cents']=$this->deadlinePenalty($row);
            $penalties += (int) $row['discipline_cents'] + (int)$row['deadline_penalty_cents'];
            $balance += max(0, (int)($row['fund_cents']??0) - (int) $row['paid_cents'] - (int) $row['discipline_cents'] - (int) $row['deadline_cents']);
            if ($row['calculation_state'] === 'blocked') $blocked++;
        }unset($row);

        $body = '<header class="fm2-page-header fm2-otiz-register-head"><div><h1>Экономика объектов</h1><p>Фонд премии, начисления, выплаты и удержания по каждому объекту.</p></div><p class="fm2-otiz-asof">План — по карточкам объектов · начисления — по последнему расчёту</p></header>';
        $body .= $this->primaryTabs('objects');
        $body .= '<section class="fm2-otiz-register-summary" aria-label="Финансовая сводка"><div><span>Фонд премии</span><strong>'.$this->rub($fund).'</strong><small>'.$calculated.' из '.count($rows).' объектов рассчитаны</small></div><div><span>Заработано объёмом</span><strong>'.$this->rub($earned).'</strong><small>По подтверждённому прогрессу</small></div><div><span>Выплачено монтажникам</span><strong>'.$this->rub($paid).'</strong><small>'.($fund > 0 ? number_format($paid * 100 / $fund, 1, ',', ' ').'% фонда' : 'Нет фонда').'</small></div><div><span>Удержано</span><strong>'.$this->rub($penalties).'</strong><small>Дисциплина и сроки</small></div><div class="fm2-otiz-register-summary__balance"><span>Остаток фонда</span><strong>'.$this->rub($balance).'</strong><small>'.$blocked.' начатых объектов требуют данных</small></div></section>';

        $body .= '<section class="fm2-list-surface fm2-otiz-register"><div class="fm2-otiz-register-toolbar"><label class="shlz-field"><span class="shlz-field__label">Найти объект</span><span class="shlz-field__control"><input class="shlz-input" type="search" data-otiz-search placeholder="Регномер или адрес"></span></label><label class="shlz-field"><span class="shlz-field__label">Состояние</span><span class="shlz-field__control"><select class="shlz-select" data-otiz-state><option value="">Все состояния</option><option value="planned">Работы не начаты</option><option value="ready">Готов к расчёту</option><option value="blocked">Требует данных</option><option value="no_new_amount">Без новой суммы</option><option value="completed">Выплата выполнена</option><option value="missing_norm">Нет норматива</option></select></span></label><span class="fm2-result-count" data-otiz-count>'.count($rows).' объектов</span></div><div class="shlz-table-wrap"><table class="shlz-table fm2-otiz-register-table"><caption class="shlz-visually-hidden">Экономика объектов ОТиЗ</caption><thead class="shlz-table__head"><tr><th class="shlz-table__cell" scope="col">Объект</th><th class="shlz-table__cell shlz-table__cell--numeric" scope="col">Прогресс</th><th class="shlz-table__cell shlz-table__cell--numeric" scope="col">Фонд премии</th><th class="shlz-table__cell shlz-table__cell--numeric" scope="col">Кшах</th><th class="shlz-table__cell shlz-table__cell--numeric" scope="col">Заработано</th><th class="shlz-table__cell shlz-table__cell--numeric" scope="col">Выплачено</th><th class="shlz-table__cell shlz-table__cell--numeric" scope="col">Удержано</th><th class="shlz-table__cell shlz-table__cell--numeric" scope="col">Остаток фонда</th><th class="shlz-table__cell" scope="col">Состояние</th></tr></thead><tbody>';
        $nativeStateSelect='<label class="shlz-field"><span class="shlz-field__label">Состояние</span><span class="shlz-field__control"><select class="shlz-select" data-otiz-state><option value="">Все состояния</option><option value="planned">Работы не начаты</option><option value="ready">Готов к расчёту</option><option value="blocked">Требует данных</option><option value="no_new_amount">Без новой суммы</option><option value="completed">Выплата выполнена</option><option value="missing_norm">Нет норматива</option></select></span></label>';
        $body=str_replace($nativeStateSelect,$this->stateFilterSelect(),$body);
        foreach ($rows as $row) {
            $state = $row['fund_cents']===null?'missing_norm':($row['snapshot_id']===null?'planned':(string)$row['calculation_state']);
            if ($state === 'ready' && (int)$row['pool_cents'] > 0 && (int)$row['snapshot_closed_cents'] >= (int)$row['pool_cents']) $state = 'completed';
            $status = $state === 'missing_norm'?['shlz-status--orange','Нет норматива']:($state === 'planned'?['shlz-status--neutral','Работы не начаты']:($state === 'blocked' ? ['shlz-status--orange','Требует данных'] : ($state === 'completed' ? ['shlz-status--green','Выплата выполнена'] : ($state === 'no_new_amount' ? ['shlz-status--neutral','Без новой суммы'] : ['shlz-status--blue','Готов к расчёту']))));
            $rowPaid=(int)$row['paid_cents'];$rowPenalty=(int)$row['discipline_cents']+(int)($row['deadline_penalty_cents']??0);$rowBalance=max(0,(int)$row['fund_cents']-$rowPaid-$rowPenalty);
            $settled=(int)$row['fund_cents']>0?min(100,($rowPaid+$rowPenalty)*100/(int)$row['fund_cents']):0;
            $snapshotDate=$row['report_date']!==null?'Расчёт от '.$this->date($row['report_date']):'Расчёт ещё не подготовлен';$progressDate=$row['progress_fact_date']!==null?'На '.$this->date($row['progress_fact_date']):'Прогресс не подтверждён';
            $money=fn(?int$value):string=>$value===null?'<span class="shlz-table__empty">—</span>':'<strong>'.$this->rub($value).'</strong>';
            $penaltyBreakdown='<small class="fm2-penalty-breakdown"><span>Дисциплина: '.$this->rub((int)$row['discipline_cents']).'</span><span>Сроки: '.$this->rub((int)($row['deadline_penalty_cents']??0)).'</span></small>';
            $body .= '<tr class="shlz-table__row" data-otiz-row data-state="'.$this->e($state).'" data-search="'.$this->e(mb_strtolower($row['regnumber'].' '.$row['address'])).'"><td class="shlz-table__cell"><a class="shlz-link" href="/pilot/objects/'.(int)$row['object_id'].'"><strong>'.$this->e($row['regnumber']?:'Объект №'.$row['object_id']).'</strong></a><small>'.$this->e($row['address']?:'Адрес не указан').'</small><small>'.$snapshotDate.'</small></td><td class="shlz-table__cell shlz-table__cell--numeric"><strong>'.$this->percent((int)($row['current_progress_bp']??0)).'</strong><small>'.$progressDate.'</small></td><td class="shlz-table__cell shlz-table__cell--numeric">'.$money($row['fund_cents']).($row['premium_cents']!==null?'<small>База '.$this->rub((int)$row['premium_cents']).'</small>':'<small>Не найдена норма</small>').'</td><td class="shlz-table__cell shlz-table__cell--numeric">'.($row['shaft_bp']!==null?'<strong>'.number_format((int)$row['shaft_bp']/10000,2,',',' ').'</strong>':'<span class="shlz-table__empty">—</span>').'</td><td class="shlz-table__cell shlz-table__cell--numeric">'.$money($row['snapshot_id']!==null?(int)$row['accrued_cents']:null).($row['snapshot_id']!==null?'<small>Коэффициент сроков '.number_format((int)$row['kss_bp']/10000,2,',',' ').'</small>':'').'</td><td class="shlz-table__cell shlz-table__cell--numeric fm2-money-positive"><strong>'.$this->rub($rowPaid).'</strong></td><td class="shlz-table__cell shlz-table__cell--numeric fm2-money-negative"><strong>'.$this->rub($rowPenalty).'</strong>'.$penaltyBreakdown.'</td><td class="shlz-table__cell shlz-table__cell--numeric">'.$money($row['fund_cents']!==null?$rowBalance:null).($row['fund_cents']!==null?'<span class="fm2-fund-track" aria-label="Использовано '.number_format($settled,0,',',' ').'% фонда"><i style="width:'.number_format($settled,2,'.','').'%"></i></span>':'').'</td><td class="shlz-table__cell"><span class="shlz-status '.$status[0].'">'.$status[1].'</span></td></tr>';
        }
        $body .= '</tbody></table><div class="fm2-otiz-register-empty" data-otiz-empty hidden><h2>Объекты не найдены</h2><p>Измените запрос или статус — данные расчётов не меняются.</p></div></div></section>';
        $this->page('Экономика объектов', $body);
    }

    private function primaryTabs(string $current): string
    {
        $tabs = [
            'objects' => ['/pilot/otiz', 'Объекты'],
            'payments' => ['/pilot/otiz/payments', 'Подготовка выплат'],
            'history' => ['/pilot/otiz/history', 'Архив расчётов'],
        ];
        $html = '<nav class="shlz-tabs fm2-otiz-tabs" aria-label="Раздел ОТиЗ"><div class="shlz-tabs__list">';
        foreach ($tabs as $key => [$href, $label]) {
            $html .= '<a class="shlz-tabs__tab"'.($key === $current ? ' aria-current="page"' : '').' href="'.$href.'">'.$label.'</a>';
        }
        return $html.'</div></nav>';
    }

    private function stateFilterSelect(): string
    {
        $options=[''=>'Все состояния','planned'=>'Работы не начаты','ready'=>'Готов к расчёту','blocked'=>'Требует данных','no_new_amount'=>'Без новой суммы','completed'=>'Выплата выполнена','missing_norm'=>'Нет норматива'];$buttons='';
        foreach($options as$value=>$label)$buttons.='<button class="shlz-select__option" type="button" role="option" aria-selected="'.($value===''?'true':'false').'" data-value="'.$this->e($value).'">'.$this->e($label).'</button>';
        return'<div class="shlz-field shlz-field--select shlz-select-root" data-shlz-select><span class="shlz-field__label" id="otiz-state-label">Состояние</span><button class="shlz-field__control shlz-select__trigger shlz-select__trigger--selected" type="button" role="combobox" aria-haspopup="listbox" aria-expanded="false" aria-controls="otiz-state-options" aria-labelledby="otiz-state-label otiz-state-value"><span id="otiz-state-value" data-shlz-select-value>Все состояния</span><svg class="shlz-select__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8.5 12 15.5 19 8.5"/></svg></button><div class="shlz-select__listbox" id="otiz-state-options" role="listbox" aria-labelledby="otiz-state-label" hidden>'.$buttons.'</div><input type="hidden" name="state" value="" data-otiz-state></div>';
    }

    private function reconciliation(): never
    {
        $allowedStates=['unreviewed','acknowledge','reject_evidence','request_source_correction','map_link'];$allowedClasses=['native_candidate','legacy_active','legacy_historical'];$state=in_array((string)($_GET['state']??''),$allowedStates,true)?(string)$_GET['state']:'';$classification=in_array((string)($_GET['classification']??''),$allowedClasses,true)?(string)$_GET['classification']:'';$quarantine=in_array((string)($_GET['quarantine']??''),['yes','no'],true)?(string)$_GET['quarantine']:'';$candidateConflict=(string)($_GET['conflict']??'');$conflict=preg_match('/^[A-Z0-9_]{1,80}$/D',$candidateConflict)===1?$candidateConflict:'';$scope=(string)($_GET['scope']??'')==='unresolved'?'unresolved':'';$pageSize=50;$page=$this->requestedPage();$projectionPage=(new MigratedEvidenceProjectionStore($this->db,$this->prefix))->reconciliation(compact('state','classification','quarantine','conflict','scope'),$page,$pageSize);$conflictOptions=[];foreach($projectionPage['options']as$code)$conflictOptions[$code]=$this->reasonLabel($code);if($conflict!==''&&!isset($conflictOptions[$conflict])){$conflict='';$projectionPage=(new MigratedEvidenceProjectionStore($this->db,$this->prefix))->reconciliation(compact('state','classification','quarantine','conflict','scope'),$page,$pageSize);}$summary=$projectionPage['summary'];$rows=$projectionPage['rows'];$visible=$rows;$decisionsBySnapshot=$projectionPage['decisions'];$statusCounts=$projectionPage['states'];$conflicted=(int)$summary['conflicted'];$quarantined=(int)$summary['quarantined'];$classes=['native_candidate'=>(int)$summary['native_candidate'],'legacy_active'=>(int)$summary['legacy_active'],'legacy_historical'=>(int)$summary['legacy_historical']];$filteredCount=$projectionPage['total'];$allCount=(int)$summary['total'];$pageCount=max(1,(int)ceil($filteredCount/$pageSize));if($page>$pageCount)$this->fail(404,'Страница не найдена.');
        $body = '<header class="fm2-page-header"><div><h1>Сверка перенесённых свидетельств</h1><p>ОТиЗ видит происхождение и качество legacy-фактов до их использования. Этот экран ничего не исправляет и не меняет расчёт.</p></div></header><nav class="fm2-otiz-subnav" aria-label="Раздел ОТиЗ"><a class="shlz-link" href="/pilot/otiz">Очередь расчёта</a><a class="shlz-link" href="/pilot/otiz/history">История срезов</a><a class="shlz-link" aria-current="page" href="/pilot/otiz/reconciliation">Сверка свидетельств</a><a class="shlz-link" href="/pilot/otiz/reconciliation/quarantine">Pre-import quarantine</a><a class="shlz-link" href="/pilot/otiz/active-baselines">Active baselines</a><a class="shlz-link" href="/pilot/otiz/historical-replay">Historical replay</a></nav>';
        $result=(string)($_GET['decision']??'');$error=(string)($_GET['decisionError']??'');
        if(in_array($result,['accepted','duplicate'],true))$body.='<p class="fm2-recon-notice fm2-recon-notice--success" role="status">'.($result==='accepted'?'Решение добавлено в неизменяемую историю.':'Это решение уже было записано; повтор не создал новую запись.').'</p>';
        if($error!==''){$messages=['invalid'=>'Проверьте действие, основание и формат целевой ссылки.','stale'=>'Проекция изменилась. Обновите страницу и повторно проверьте конфликт.','conflict'=>'Конфликт больше не относится к этой проекции. Обновите страницу.','operation'=>'Идентификатор операции уже использован для другого решения.','forbidden'=>'Недостаточно прав для решения по свидетельству.'];$body.='<p class="fm2-recon-notice fm2-recon-notice--error" role="alert">'.$this->e($messages[$error]??'Решение не записано.').'</p>';}
        if ($allCount === 0) {
            $body .= '<section class="fm2-empty"><h2>Импортированных свидетельств пока нет</h2><p>Выполните dry-run и явно примените проверенный snapshot. Legacy-источник останется доступен только для чтения.</p></section>';
        } else {
            $body .= $this->reconciliationQueueSummary($allCount,$conflicted,$quarantined,$classes,$statusCounts).$this->reconciliationFilters($state,$classification,$quarantine,$conflict,$scope,$conflictOptions,$filteredCount);
            if($filteredCount===0)$body.='<section class="fm2-empty"><h2>По выбранным фильтрам ничего нет</h2><p>Измените один из фильтров или сбросьте их. Исходные свидетельства и решения не изменялись.</p><a class="shlz-link" href="/pilot/otiz/reconciliation">Сбросить фильтры</a></section>';
            else{$body.='<section class="fm2-recon-list" aria-label="Перенесённые свидетельства">';foreach ($visible as $row) $body .= $this->reconciliationItem($row,$decisionsBySnapshot[(int)$row['snapshotId']]??[]);$body .= '</section>'.$this->reconciliationPager($page,$pageCount,$filteredCount,$pageSize,['scope'=>$scope,'state'=>$state,'classification'=>$classification,'quarantine'=>$quarantine,'conflict'=>$conflict]);}
        }
        $this->page('Сверка перенесённых свидетельств', $body);
    }

    private function reconciliationQueueSummary(int$total,int$conflicted,int$quarantined,array$classes,array$states):string
    {
        return '<section class="fm2-recon-queue-summary" aria-label="Сводка очереди сверки"><div><strong>'.$conflicted.'</strong><span>объектов с конфликтами</span></div><div><strong>'.$quarantined.'</strong><span>объектов в quarantine</span></div><div><strong>'.($states['unreviewed']??0).'</strong><span>конфликтов без решения</span></div><dl><div><dt>Нативные кандидаты</dt><dd>'.(int)($classes['native_candidate']??0).'</dd></div><div><dt>Активный legacy</dt><dd>'.(int)($classes['legacy_active']??0).'</dd></div><div><dt>Исторический legacy</dt><dd>'.(int)($classes['legacy_historical']??0).'</dd></div><div><dt>Всего snapshot</dt><dd>'.$total.'</dd></div></dl><p>Статус решения описывает аудит разбора. Он не снимает конфликт или quarantine и не меняет допуск к расчёту.</p></section><section class="fm2-recon-status-counts" aria-label="Решения по конфликтам"><span>Без решения <strong>'.(int)($states['unreviewed']??0).'</strong></span><span>Ознакомлены <strong>'.(int)($states['acknowledge']??0).'</strong></span><span>Отклонены <strong>'.(int)($states['reject_evidence']??0).'</strong></span><span>Исправление запрошено <strong>'.(int)($states['request_source_correction']??0).'</strong></span><span>Намерение сопоставить <strong>'.(int)($states['map_link']??0).'</strong></span></section>';
    }

    private function migrationQuarantine():never
    {
        $category=(string)($_GET['category']??'');$code=(string)($_GET['code']??'');$page=filter_var($_GET['page']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:1;$model=(new MigrationQuarantineReadModel($this->db,$this->prefix))->read(['category'=>$category,'code'=>$code],$page);$category=$model['filters']['category']??'';$code=$model['filters']['code']??'';$history=[];foreach($this->quarantineLedger->all()as$d)$history[$d['source_locator'].'|'.$d['source_cutoff_at'].'|'.$d['source_digest'].'|'.$d['classification_version'].'|'.$d['quarantine_code']][]=$d;
        $option=fn(string$v,string$l,string$c):string=>'<option value="'.$this->e($v).'"'.($v===$c?' selected':'').'>'.$this->e($l).'</option>';$codeOptions=$option('','Все коды',$code);foreach($model['codes']as$c)$codeOptions.=$option($c,$this->reasonLabel($c),$code);
        $body='<header class="fm2-page-header"><div><h1>Pre-import quarantine</h1><p>Объекты остановлены до создания snapshot, operational case или historical history. Здесь показаны только redacted ссылки на read-only наблюдения legacy.</p></div></header><nav class="fm2-otiz-subnav" aria-label="Раздел ОТиЗ"><a class="shlz-link" href="/pilot/otiz">Очередь расчёта</a><a class="shlz-link" href="/pilot/otiz/reconciliation">Сверка свидетельств</a><a class="shlz-link" aria-current="page" href="/pilot/otiz/reconciliation/quarantine">Pre-import quarantine</a><a class="shlz-link" href="/pilot/otiz/active-baselines">Active baselines</a></nav><section class="fm2-recon-notice fm2-recon-notice--error" role="note"><strong>Импорт не выполнялся.</strong> Для этих записей нет snapshot, case, checklist history или допуска к расчёту. Фильтры ничего не меняют в источнике.</section><form class="fm2-recon-filters" method="get" action="/pilot/otiz/reconciliation/quarantine" aria-label="Фильтры pre-import quarantine"><label class="shlz-field"><span class="shlz-field__label">Классификация</span><span class="shlz-field__control"><select class="shlz-input" name="category">'.$option('','Все классы',$category).$option('native_candidate','Нативный кандидат',$category).$option('legacy_active','Активный legacy',$category).$option('legacy_historical','Исторический legacy',$category).'</select></span></label><label class="shlz-field"><span class="shlz-field__label">Quarantine code</span><span class="shlz-field__control"><select class="shlz-input" name="code">'.$codeOptions.'</select></span></label><div class="fm2-recon-filter-actions"><button class="shlz-button shlz-button--primary" type="submit">Применить</button><a class="shlz-link" href="/pilot/otiz/reconciliation/quarantine">Сбросить</a><span class="fm2-result-count">'.(int)$model['total'].' записей</span></div></form>';
        if($model['rows']===[])$body.='<section class="fm2-empty"><h2>Записей не найдено</h2><p>Registry пуст или выбранные фильтры не совпали. Никакие import-операции не выполнялись.</p></section>';else{$body.='<section class="fm2-recon-list" aria-label="Pre-import quarantine registry">';foreach($model['rows']as$r){$reasons=implode(' · ',array_map($this->reasonLabel(...),$r['reasonCodes']));$workflows='';foreach($r['quarantineCodes']as$c){$key=$r['sourceLocator'].'|'.$r['sourceCutoffAt'].'|'.$r['sourceDigest'].'|'.$r['classificationVersion'].'|'.$c;$workflows.=$this->quarantineDecision($r,$c,$history[$key]??[]);}$body.='<article class="fm2-recon-item"><header><div><h2>'.$this->e($r['reference']).'</h2><p>Стабильная redacted reference; raw legacy ID не отображается</p></div><div class="fm2-recon-state"><span class="shlz-status shlz-status--orange">'.$this->e($r['status']).'</span><strong>'.$this->e($r['category']).'</strong></div></header><div class="fm2-recon-grid"><section><h3>Классификация</h3><p>'.$this->e($reasons).'</p><small>'.$this->e($r['classificationVersion']).'</small></section><section><h3>Source provenance</h3><dl><div><dt>Cutoff</dt><dd>'.$this->e($r['sourceCutoffAt']).'</dd></div><div><dt>Source digest</dt><dd>'.$this->e(substr($r['sourceDigest'],0,16)).'…</dd></div><div><dt>Locator hash</dt><dd>'.$this->e(substr($r['locatorHash'],0,16)).'…</dd></div></dl></section></div>'.$workflows.'<p class="fm2-active-readonly">Audit decisions do not import data, clear quarantine, or authorize calculation/payment.</p></article>';}$body.='</section>'.$this->quarantinePager($model,$category,$code);}$this->page('Pre-import quarantine',$body);
    }

    private function quarantineDecision(array$r,string$c,array$h):string{$labels=['acknowledge'=>'Ознакомлены','reject_source_evidence'=>'Источник отклонён','request_source_correction'=>'Исправление запрошено'];$timeline='';foreach($h as$d)$timeline.='<li><strong>'.$this->e($labels[$d['outcome']]??$d['outcome']).'</strong><span>'.$this->e($d['occurred_at']).' · пользователь № '.(int)$d['actor_user_id'].'</span><p>'.$this->e($d['reason']).'</p></li>';$hidden='<input type="hidden" name="csrfToken" value="'.$this->e($this->csrf).'"><input type="hidden" name="operationId" value="'.$this->uuid().'"><input type="hidden" name="sourceLocator" value="'.$this->e($r['sourceLocator']).'"><input type="hidden" name="sourceCutoffAt" value="'.$this->e($r['sourceCutoffAt']).'"><input type="hidden" name="sourceDigest" value="'.$this->e($r['sourceDigest']).'"><input type="hidden" name="classificationVersion" value="'.$this->e($r['classificationVersion']).'"><input type="hidden" name="quarantineCode" value="'.$this->e($c).'">';return'<section class="fm2-recon-decision"><header><h3>'.$this->e($this->reasonLabel($c)).'</h3><span class="shlz-status shlz-status--blue">'.($h===[]?'Без решения':$this->e($labels[$h[array_key_last($h)]['outcome']]??'Рассмотрено')).'</span></header>'.($timeline===''?'':'<ol class="fm2-recon-history">'.$timeline.'</ol>').'<form method="post" action="/pilot/otiz/reconciliation/quarantine/decisions" class="fm2-recon-decision-form">'.$hidden.'<label class="shlz-field"><span class="shlz-field__label">Действие</span><select class="shlz-input" name="outcome"><option value="acknowledge">Зафиксировать ознакомление</option><option value="reject_source_evidence">Отклонить source evidence</option><option value="request_source_correction">Запросить исправление источника</option></select></label><label class="shlz-field fm2-recon-reason"><span class="shlz-field__label">Основание</span><textarea class="shlz-input" name="reason" maxlength="1000" required></textarea></label><button class="shlz-button shlz-button--primary">Добавить audit decision</button></form></section>';}

    private function quarantinePager(array$m,string$category,string$code):string{$page=(int)$m['page'];$pages=(int)$m['pages'];$query=array_filter(['category'=>$category,'code'=>$code],static fn(string$v):bool=>$v!=='');$href=static fn(int$p):string=>'/pilot/otiz/reconciliation/quarantine?'.http_build_query($query+['page'=>$p],'','&',PHP_QUERY_RFC3986);$from=($page-1)*MigrationQuarantineReadModel::PAGE_SIZE+1;$to=min($page*MigrationQuarantineReadModel::PAGE_SIZE,(int)$m['total']);return'<nav class="fm2-recon-pager" aria-label="Страницы pre-import quarantine"><span>Показаны '.$from.'–'.$to.' из '.(int)$m['total'].'</span><div>'.($page>1?'<a class="shlz-button" rel="prev" href="'.$this->e($href($page-1)).'">← Предыдущая</a>':'').'<strong>Страница '.$page.' из '.$pages.'</strong>'.($page<$pages?'<a class="shlz-button" rel="next" href="'.$this->e($href($page+1)).'">Следующая →</a>':'').'</div></nav>';}

    private function reconciliationFilters(string$state,string$classification,string$quarantine,string$conflict,string$scope,array$conflicts,int$count):string
    {
        $option=fn(string$value,string$label,string$current):string=>'<option value="'.$this->e($value).'"'.($value===$current?' selected':'').'>'.$this->e($label).'</option>';$conflictOptions='<option value="">Все коды</option>';foreach($conflicts as$code=>$label)$conflictOptions.=$option($code,$label,$conflict);
        return '<form class="fm2-recon-filters" method="get" action="/pilot/otiz/reconciliation" aria-label="Фильтры очереди сверки"><label class="shlz-field"><span class="shlz-field__label">Охват</span><span class="shlz-field__control"><select class="shlz-input" name="scope">'.$option('','Все snapshot',$scope).$option('unresolved','Требуют разбора',$scope).'</select></span></label><label class="shlz-field"><span class="shlz-field__label">Статус решения</span><span class="shlz-field__control"><select class="shlz-input" name="state">'.$option('','Все статусы',$state).$option('unreviewed','Без решения',$state).$option('acknowledge','Ознакомлены',$state).$option('reject_evidence','Отклонены',$state).$option('request_source_correction','Исправление запрошено',$state).$option('map_link','Намерение сопоставить',$state).'</select></span></label><label class="shlz-field"><span class="shlz-field__label">Классификация</span><span class="shlz-field__control"><select class="shlz-input" name="classification">'.$option('','Все классы',$classification).$option('native_candidate','Нативный кандидат',$classification).$option('legacy_active','Активный legacy',$classification).$option('legacy_historical','Исторический legacy',$classification).'</select></span></label><label class="shlz-field"><span class="shlz-field__label">Quarantine</span><span class="shlz-field__control"><select class="shlz-input" name="quarantine">'.$option('','Любой',$quarantine).$option('yes','Есть quarantine',$quarantine).$option('no','Нет quarantine',$quarantine).'</select></span></label><label class="shlz-field"><span class="shlz-field__label">Код конфликта</span><span class="shlz-field__control"><select class="shlz-input" name="conflict">'.$conflictOptions.'</select></span></label><div class="fm2-recon-filter-actions"><button class="shlz-button shlz-button--primary" type="submit">Применить</button><a class="shlz-link" href="/pilot/otiz/reconciliation">Сбросить</a><span class="fm2-result-count">'.$count.' объектов</span></div></form>';
    }

    private function reconciliationPager(int$page,int$pages,int$total,int$pageSize,array$query):string
    {
        return$this->pager('/pilot/otiz/reconciliation',$page,$pages,$total,$pageSize,$query,'Страницы очереди сверки');
    }

    private function activeBaselines():never
    {
        $state=in_array((string)($_GET['state']??''),['ready','blocked'],true)?(string)$_GET['state']:'';$coverage=in_array((string)($_GET['coverage']??''),['both','partial','none'],true)?(string)$_GET['coverage']:'';$page=$this->requestedPage();$model=(new LegacyActiveBaselineReadModel($this->db,$this->prefix))->read(['state'=>$state,'coverage'=>$coverage],$page);if($model['page']!==$page)$this->fail(404,'Страница не найдена.');
        $s=$model['summary'];$body='<header class="fm2-page-header"><div><h1>Active baselines на cutover</h1><p>Неизменяемые исходные состояния начатых legacy-объектов. Экран проверяет готовность продолжать историю нативными событиями после cutover и ничего не редактирует.</p></div></header><nav class="fm2-otiz-subnav" aria-label="Раздел ОТиЗ"><a class="shlz-link" href="/pilot/otiz">Очередь расчёта</a><a class="shlz-link" href="/pilot/otiz/reconciliation">Сверка свидетельств</a><a class="shlz-link" aria-current="page" href="/pilot/otiz/active-baselines">Active baselines</a><a class="shlz-link" href="/pilot/otiz/historical-replay">Historical replay</a></nav><section class="fm2-active-summary" aria-label="Сводка active baselines"><div><strong>'.(int)$s['total'].'</strong><span>immutable baselines</span></div><div><strong>'.(int)$s['ready'].'</strong><span>готовы к native continuation</span></div><div><strong>'.(int)$s['blocked'].'</strong><span>имеют конфликт</span></div><div><strong>'.(int)$s['quarantined'].'</strong><span>classifier quarantine</span></div><p>Готовность означает только согласованность baseline и versioned template после cutover. Она не подтверждает расчёт премии или выплату.</p></section>'.$this->activeBaselineFilters($state,$coverage,(int)$model['total']);
        if($model['rows']===[])$body.='<section class="fm2-empty"><h2>Active baselines не найдены</h2><p>Входящий batch ещё не применён либо выбранные фильтры не дают результатов.</p></section>';else{$body.='<section class="fm2-active-list" aria-label="Active baseline reconciliation queue">';foreach($model['rows']as$row)$body.=$this->activeBaselineItem($row);$body.='</section>'.$this->activeBaselinePager($model,$state,$coverage);}$this->page('Active baselines на cutover',$body);
    }

    private function activeBaselineFilters(string$state,string$coverage,int$count):string
    {
        $option=fn(string$v,string$l,string$c):string=>'<option value="'.$this->e($v).'"'.($v===$c?' selected':'').'>'.$this->e($l).'</option>';return'<form class="fm2-active-filters" method="get" action="/pilot/otiz/active-baselines" aria-label="Фильтры active baselines"><label class="shlz-field"><span class="shlz-field__label">Native continuation</span><span class="shlz-field__control"><select class="shlz-input" name="state">'.$option('','Любое состояние',$state).$option('ready','Можно начинать после cutover',$state).$option('blocked','Требуется разбор',$state).'</select></span></label><label class="shlz-field"><span class="shlz-field__label">Покрытие evidence</span><span class="shlz-field__control"><select class="shlz-input" name="coverage">'.$option('','Любое покрытие',$coverage).$option('both','Checklist и attribution',$coverage).$option('partial','Только один источник',$coverage).$option('none','Нет обоих источников',$coverage).'</select></span></label><button class="shlz-button shlz-button--primary" type="submit">Применить</button><a class="shlz-link" href="/pilot/otiz/active-baselines">Сбросить</a><span class="fm2-result-count">'.$count.' объектов</span></form>';
    }

    private function activeBaselineItem(array$row):string
    {
        $reasons=implode(' · ',array_map($this->reasonLabel(...),$row['reasonCodes']));$conflicts=$row['conflictCodes']===[]?'<span class="fm2-recon-clear">Конфликтов не обнаружено</span>':'<ul>'.implode('',array_map(fn(string$code):string=>'<li>'.$this->e($this->reasonLabel($code)).'</li>',$row['conflictCodes'])).'</ul>';$template=$row['template']===null?'<p class="fm2-active-missing">Template snapshot не связан.</p>':'<dl><div><dt>Версия</dt><dd>'.$this->e($row['template']['version']).'</dd></div><div><dt>Действует с</dt><dd>'.$this->e($row['template']['validFrom']).'</dd></div><div><dt>Captured at</dt><dd>'.$this->e($row['template']['capturedAt']).'</dd></div><div><dt>Hash</dt><dd title="'.$this->e($row['template']['contentHash']).'">'.$this->e(substr($row['template']['contentHash'],0,16)).'…</dd></div><div><dt>Источник</dt><dd>'.$this->e($row['template']['sourceLabel']).'</dd></div></dl>';
        return'<article class="fm2-active-item"><header><div><h2>'.$this->e($row['regnumber']!==''?$row['regnumber']:'Объект № '.$row['legacyObjectId']).'</h2><p>'.$this->e($row['address']).($row['entrance']===''?'':' · подъезд '.$this->e($row['entrance'])).'</p></div><span class="shlz-status '.($row['nativeReady']?'shlz-status--green':'shlz-status--orange').'">'.($row['nativeReady']?'Можно начинать native events':'Native events заблокированы').'</span></header><div class="fm2-active-grid"><section><h3>Immutable baseline</h3><dl><div><dt>Cutover</dt><dd>'.$this->e($row['cutoverAt']).'</dd></div><div><dt>Контракт</dt><dd>'.$this->e($row['contractVersion']).'</dd></div><div><dt>Hash</dt><dd title="'.$this->e($row['baselineHash']).'">'.$this->e(substr($row['baselineHash'],0,16)).'…</dd></div></dl></section><section><h3>Классификация</h3><strong>'.$this->e($row['classification']).'</strong><p>'.$this->e($reasons).'</p><small>'.$this->e($row['classificationVersion']).'</small></section><section><h3>Evidence coverage</h3><dl><div><dt>Checklist events</dt><dd>'.(int)$row['coverage']['checklist'].'</dd></div><div><dt>Attribution facts</dt><dd>'.(int)$row['coverage']['attribution'].'</dd></div></dl></section><section><h3>Template snapshot</h3>'.$template.'</section><section class="fm2-active-conflicts"><h3>Конфликты и quarantine</h3>'.$conflicts.'</section></div><p class="fm2-active-readonly">Read-only reconciliation: baseline и template association не изменяются на этом экране. Decision ledger не подключён — active baseline не является тем же immutable history snapshot.</p></article>';
    }

    private function activeBaselinePager(array$model,string$state,string$coverage):string
    {
        return$this->pager('/pilot/otiz/active-baselines',(int)$model['page'],(int)$model['pages'],(int)$model['total'],50,['state'=>$state,'coverage'=>$coverage],'Страницы active baselines');
    }

    private function requestedPage():int
    {
        $page=filter_var($_GET['page']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($page===false)$this->fail(400,'Некорректный номер страницы.');return(int)$page;
    }

    private function pager(string$path,int$page,int$pages,int$total,int$pageSize,array$query,string$label):string
    {
        if($pages<=1)return'';$query=array_filter($query,static fn(mixed$value):bool=>$value!=='');$from=($page-1)*$pageSize+1;$to=min($page*$pageSize,$total);return'<div class="fm2-recon-pager"><span>Показаны '.$from.'–'.$to.' из '.$total.'</span>'.\FMonitor2\PilotHttp\PilotView::pagination($path,$page,$pages,$label,$query).'</div>';
    }

    private function reconciliationItem(array $row,array$decisions): string
    {
        $classes = ['native_candidate'=>'Нативный кандидат','legacy_active'=>'Активный legacy','legacy_historical'=>'Исторический legacy'];
        $confidence = ['high'=>'Высокая','medium'=>'Средняя','low'=>'Низкая'];
        $reasons = implode(' · ', array_map($this->reasonLabel(...), $row['reasonCodes']));
        $byIssue=[];foreach($decisions as$decision)$byIssue[(string)$decision['issue_code']][]=$decision;
        $conflicts = $row['conflictCodes'] === [] ? '<span class="fm2-recon-clear">Конфликтов не обнаружено</span>' : '<ul>' . implode('', array_map(fn(string $code): string => '<li>' . $this->e($this->reasonLabel($code)) . '</li>', $row['conflictCodes'])) . '</ul>';
        $workflow='';foreach($row['conflictCodes']as$code)$workflow.=$this->decisionWorkflow($row,$code,$byIssue[$code]??[]);
        return '<article class="fm2-recon-item" id="snapshot-' . (int)$row['snapshotId'] . '"><header><div><h2>' . $this->e($row['regnumber'] !== '' ? $row['regnumber'] : 'Объект № ' . $row['legacyObjectId']) . '</h2><p>' . $this->e($row['address']) . '</p></div><div class="fm2-recon-state"><span class="shlz-status ' . ($row['conflictCodes'] === [] ? 'shlz-status--green' : 'shlz-status--orange') . '">Класс ' . $this->e($row['evidenceGrade']) . '</span><strong>' . $this->e($classes[$row['classification']] ?? $row['classification']) . '</strong></div></header><div class="fm2-recon-grid"><section><h3>Решение маршрута</h3><p>' . $this->e($reasons) . '</p><dl><div><dt>Классификатор</dt><dd>' . $this->e($row['classificationVersion']) . '</dd></div><div><dt>Уверенность</dt><dd>' . $this->e($confidence[$row['confidence']] ?? $row['confidence']) . '</dd></div></dl></section><section><h3>Происхождение</h3><p><strong>' . $this->e($row['sourceLabel']) . '</strong><br>' . $this->e($row['sourceLocator']) . '</p><dl><div><dt>Cutover</dt><dd>' . $this->e($row['cutoffAt']) . '</dd></div><div><dt>Snapshot hash</dt><dd title="' . $this->e($row['contentSha256']) . '">' . $this->e(substr($row['contentSha256'],0,16)) . '…</dd></div><div><dt>Projection hash</dt><dd title="' . $this->e($row['projectionHash']) . '">' . $this->e(substr($row['projectionHash'],0,16)) . '…</dd></div></dl></section><section><h3>Покрытие evidence</h3><dl><div><dt>События чек-листа</dt><dd>' . (int)$row['counts']['checklistEvents'] . '</dd></div><div><dt>Атрибуции работ</dt><dd>' . (int)$row['counts']['attributions'] . '</dd></div><div><dt>Quarantine</dt><dd>' . (int)$row['quarantineCount'] . '</dd></div></dl></section><section class="fm2-recon-conflicts"><h3>Конфликты и quarantine</h3>' . $conflicts . '</section></div>'.$workflow.'</article>';
    }

    private function decisionWorkflow(array$row,string$code,array$history):string
    {
        $labels=['acknowledge'=>'Ознакомление зафиксировано','reject_evidence'=>'Свидетельство отклонено','request_source_correction'=>'Запрошено исправление источника','map_link'=>'Зафиксировано намерение сопоставить'];$latest=$history===[]?null:$history[array_key_last($history)];$timeline='';
        foreach($history as$decision)$timeline.='<li><div><strong>'.$this->e($labels[$decision['outcome']]??$decision['outcome']).'</strong><span>'.$this->dateTime((string)$decision['occurred_at']).' · пользователь № '.(int)$decision['actor_user_id'].'</span></div><p>'.$this->e($decision['reason']).'</p>'.($decision['target_locator']===null?'':'<code>'.$this->e($decision['target_locator']).'</code>').'</li>';
        $operation=$this->uuid();$hidden='<input type="hidden" name="csrfToken" value="'.$this->e($this->csrf).'"><input type="hidden" name="operationId" value="'.$operation.'"><input type="hidden" name="snapshotId" value="'.(int)$row['snapshotId'].'"><input type="hidden" name="snapshotSha256" value="'.$this->e($row['contentSha256']).'"><input type="hidden" name="projectionSha256" value="'.$this->e($row['projectionHash']).'"><input type="hidden" name="sourceLocator" value="'.$this->e($row['sourceLocator']).'"><input type="hidden" name="issueCode" value="'.$this->e($code).'">';
        return '<section class="fm2-recon-decision" aria-labelledby="decision-'.$operation.'"><header><div><h3 id="decision-'.$operation.'">'.$this->e($this->reasonLabel($code)).'</h3><p>Код '.$this->e($code).'</p></div><span class="shlz-status '.($latest===null?'shlz-status--orange':'shlz-status--blue').'">'.($latest===null?'Решение не зафиксировано':$this->e($labels[$latest['outcome']]??$latest['outcome'])).'</span></header>'.($timeline===''?'<p class="fm2-recon-history-empty">История решений пуста.</p>':'<ol class="fm2-recon-history" aria-label="Неизменяемая история решений">'.$timeline.'</ol>').'<form method="post" action="/pilot/otiz/reconciliation/decisions" class="fm2-recon-decision-form">'.$hidden.'<label class="shlz-field"><span class="shlz-field__label">Действие</span><span class="shlz-field__control"><select class="shlz-input" name="outcome" required><option value="acknowledge">Зафиксировать ознакомление</option><option value="reject_evidence">Отклонить свидетельство</option><option value="request_source_correction">Запросить исправление источника</option><option value="map_link">Зафиксировать намерение сопоставить</option></select></span></label><label class="shlz-field"><span class="shlz-field__label">Целевая ссылка для сопоставления</span><span class="shlz-field__control"><input class="shlz-input" name="targetLocator" maxlength="500" placeholder="operational_case:123"></span><span class="shlz-field__secondary">Только для намерения сопоставить: operational_case, workforce_tab или legacy_object.</span></label><label class="shlz-field fm2-recon-reason"><span class="shlz-field__label">Основание решения</span><span class="shlz-field__control"><textarea class="shlz-input" name="reason" maxlength="1000" rows="3" required></textarea></span></label><button class="shlz-button shlz-button--primary" type="submit">Добавить решение</button></form><p class="fm2-recon-guardrail">Решение дополняет аудит. Оно не снимает quarantine, не исправляет legacy-источник и не разрешает расчёт или выплату.</p></section>';
    }

    private function reasonLabel(string $code): string
    {
        return ['PTO_ACT_RECORDED'=>'зафиксирован акт ПТО','LEGACY_FINISHED_STATUS'=>'legacy-статус завершения','ACTUAL_START_RECORDED'=>'зафиксирован фактический старт','CHECKLIST_HISTORY_PRESENT'=>'есть история чек-листа','WORK_ATTRIBUTION_HISTORY_PRESENT'=>'есть атрибуция работ','FACT_PROGRESS_RECORDED'=>'есть фактический прогресс','LEGACY_WORK_STARTED_FLAG'=>'legacy-флаг начала работ','NO_ACTUAL_START_OR_PROGRESS_EVIDENCE'=>'нет свидетельств старта или прогресса','ORPHAN_ATTRIBUTION'=>'атрибуция без связанного значения','ORPHAN_CHECKLIST_EVENT'=>'событие без определения чек-листа','MALFORMED_EVENT_DATE'=>'некорректная дата события','MALFORMED_ATTRIBUTION_DATE'=>'некорректная дата атрибуции','COMPLETION_WITHOUT_START_EVIDENCE'=>'завершение без свидетельства старта','TEMPLATE_ASSOCIATION_ABSENT'=>'нет связи с template snapshot','TEMPLATE_EFFECTIVE_AT_MISMATCH'=>'template association действует не с cutover','TEMPLATE_SNAPSHOT_ABSENT'=>'связанный template snapshot не найден','TEMPLATE_HASH_MISMATCH'=>'hash template association не совпадает со snapshot','TEMPLATE_NOT_VALID_AT_CUTOVER'=>'template ещё не действовал на cutover'][$code] ?? $code;
    }

    private function historicalReplay():never
    {
        $page=$this->requestedPage();$pageSize=50;$result=HistoricalPremiumReplayReadModel::page($this->db,$this->prefix,$page,$pageSize);$pages=max(1,(int)ceil($result['total']/$pageSize));if($page>$pages)$this->fail(404,'Страница не найдена.');$rows=$result['rows'];
        $body='<header class="fm2-page-header"><div><h1>Историческое воспроизведение премии</h1><p>Расчёт появляется только из доказанных operands. Кандидаты, balance assertions и отсутствующие выплаты не заменяются нулями или synthetic-данными.</p></div></header><nav class="fm2-otiz-subnav" aria-label="Раздел ОТиЗ"><a class="shlz-link" href="/pilot/otiz">Очередь расчёта</a><a class="shlz-link" href="/pilot/otiz/history">История срезов</a><a class="shlz-link" href="/pilot/otiz/reconciliation">Сверка свидетельств</a><a class="shlz-link" aria-current="page" href="/pilot/otiz/historical-replay">Historical replay</a></nav>';
        if($rows===[])$body.='<section class="fm2-empty"><h2>Исторические snapshots не импортированы</h2><p>Сначала требуется read-only snapshot и reconciliation без quarantine.</p></section>';
        else{$body.='<section class="fm2-replay-list" aria-label="Исторические расчёты">';foreach($rows as$row){$reasons='';foreach($row['exclusionReasons']as$reason)$reasons.='<li><code>'.$this->e($reason).'</code><span>'.$this->e($this->replayReason($reason)).'</span></li>';
            $candidate=$row['progressCandidate']['candidateProgressBp']===null?'—':$this->percent((int)$row['progressCandidate']['candidateProgressBp']);
            $body.='<article class="fm2-replay-item"><header><div><h2>'.$this->e($row['regnumber']!==''?$row['regnumber']:'Legacy № '.$row['legacyObjectId']).'</h2><p>'.$this->e($row['address']).'</p></div><span class="shlz-status shlz-status--orange">Расчёт недоступен</span></header><div class="fm2-replay-body"><section><h3>Почему исключено</h3><ul>'.$reasons.'</ul></section><section><h3>Progress candidate</h3><strong>'.$candidate.'</strong><p>'.$this->e((string)$row['progressCandidate']['mappingVersion']).'<br>В расчёте: нет</p></section><section><h3>Фактическая выплата</h3><strong>Не найдена</strong><p>Discrepancy не вычисляется. Balance assertions не являются выплатой.</p></section><section><h3>Provenance</h3><p>'.$this->e($row['provenance']['sourceLabel']).'<br>'.$this->e($row['provenance']['sourceLocator']).'</p><code title="'.$this->e($row['provenance']['snapshotHash']).'">'.$this->e(substr($row['provenance']['snapshotHash'],0,16)).'…</code></section></div></article>';}$body.='</section>'.$this->pager('/pilot/otiz/historical-replay',$page,$pages,$result['total'],$pageSize,[],'Страницы historical replay');}
        $this->page('Историческое воспроизведение премии',$body);
    }

    private function replayReason(string$code):string
    {
        return['REPORT_DATE_EVIDENCE_ABSENT'=>'Нет доказанной отчётной даты расчёта.','PREMIUM_EVIDENCE_ABSENT'=>'Нет подтверждённой премиальной базы.','SHAFT_COEFFICIENT_EVIDENCE_ABSENT'=>'Нет подтверждённого коэффициента шахты.','PROGRESS_EVIDENCE_ABSENT'=>'Нет допустимого progress operand.','DEADLINE_EVIDENCE_ABSENT'=>'Нет подтверждённого договорного срока.','COMPLETION_EVIDENCE_ABSENT'=>'Нет подтверждённого completion operand.','DEFINITION_VERSION_UNPROVEN'=>'Вес checklist definition не доказан на дату события.'][$code]??'Evidence требует отдельной сверки.';
    }

    private function export(int $id): never
    {
        $s = $this->snapshotRow($id); if ($s['status'] !== 'accepted') $this->fail(409, 'Сначала подтвердите расчёт, затем скачайте реестр.');
        $objects = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$id} ORDER BY regnumber")->fetch_all(MYSQLI_ASSOC);
        $allocations = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_allocations` WHERE snapshot_id={$id} ORDER BY object_id,full_name")->fetch_all(MYSQLI_ASSOC);
        $issues = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_issues` WHERE snapshot_id={$id} ORDER BY object_id,severity")->fetch_all(MYSQLI_ASSOC);
        $sheets = ['Объекты' => [['Регномер','Адрес','Квып','Ксс','Закрыто ранее','Новый пул','Статус']], 'Работники' => [['Регномер объекта','Табельный номер','ФИО','Должность','КТУ','Доля','Сумма']], 'Контроль' => [['Объект','Уровень','Код','Проблема','Владелец']], 'Приложение к приказу' => [['Табельный номер','ФИО','Сумма','Основание']], 'Метаданные' => [['Поле','Значение']]];
        foreach ($objects as $o) $sheets['Объекты'][] = [$o['regnumber'],$o['address'],$o['current_progress_bp']/100,$o['kss_bp']/10000,$o['closed_before_cents']/100,$o['pool_cents']/100,$o['calculation_state']];
        $reg = []; foreach ($objects as $o) $reg[$o['object_id']] = $o['regnumber'];
        foreach ($allocations as $a) { $sheets['Работники'][] = [$reg[$a['object_id']] ?? '',$a['tab_id'],$a['full_name'],$a['position_name'],$this->allocationKtuBp($a)/10000,$a['share_bp']/100,$a['amount_cents']/100]; $sheets['Приложение к приказу'][] = [$a['tab_id'],$a['full_name'],$a['amount_cents']/100,$a['participation_basis']]; }
        foreach ($issues as $i) $sheets['Контроль'][] = [$reg[$i['object_id']] ?? '',$i['severity'],$i['issue_code'],$i['message'],$i['owner_role']];
        $sheets['Метаданные'][] = ['Отчётная дата',$s['report_date']]; $sheets['Метаданные'][] = ['Версия правил',$s['rules_version']]; $sheets['Метаданные'][] = ['Hash',$s['content_hash']]; $sheets['Метаданные'][] = ['Принят',$s['accepted_at']];
        $files = ['[Content_Types].xml' => $this->xlsxContentTypes(count($sheets)), '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>'];
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'; $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'; $index = 1;
        foreach ($sheets as $name => $rows) { $workbook .= '<sheet name="' . $this->xml($name) . '" sheetId="' . $index . '" r:id="rId' . $index . '"/>'; $rels .= '<Relationship Id="rId' . $index . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $index . '.xml"/>'; $files['xl/worksheets/sheet' . $index . '.xml'] = $this->worksheet($rows); $index++; }
        $files['xl/workbook.xml'] = $workbook . '</sheets></workbook>'; $files['xl/_rels/workbook.xml.rels'] = $rels . '</Relationships>';
        $bytes = $this->zip($files);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="FMonitor-OTIZ-' . $s['report_date'] . '-v' . $id . '.xlsx"'); header('Content-Length: ' . strlen($bytes)); header('Cache-Control: no-store'); echo $bytes; exit;
    }

    private function page(string $title, string $content): never
    {
        $permissions = \FMonitor2\PilotHttp\AccessPolicy::forUser($this->db, $this->prefix, $this->userId);
        $user = new \FMonitor2\PilotHttp\HttpUser($this->userId, $this->userName, (string) ($_SERVER['REMOTE_USER'] ?? ''), $permissions);
        $html = \FMonitor2\PilotHttp\PilotView::document($user, $title, 'Расчёты ОТиЗ', '', '<div class="fm2-otiz">' . $content . '</div>');
        $html = RapidPilotShell::decorate($html, $this->csrf, false, true, true);
        $html = str_replace('</head>', '<script type="module" src="/pilot/assets/otiz.js"></script><link rel="icon" href="/pilot/assets/favicon.svg"></head>', $html);
        header('Content-Type: text/html; charset=UTF-8'); header('Cache-Control: no-store'); header('X-Frame-Options: DENY');
        echo $html; exit;
    }

    private function scenarioOverview(): string
    {
        return '<section class="fm2-otiz-scenarios"><header><h2>Как подготовить выплату</h2><p>Расчёт можно подготовить на любую выбранную дату.</p></header><ol><li><strong>Проверьте начисления</strong><span>Откройте объекты и убедитесь, что прогресс, состав и суммы верны.</span></li><li><strong>Разберите замечания</strong><span>Объекты с недостающими данными не попадут в сумму к выплате.</span></li><li><strong>Подтвердите и выгрузите</strong><span>После проверки скачайте реестр и отметьте выполненную выплату.</span></li></ol></section>';
    }

    private function allocation(int $snapshotId, int $objectId): string
    {
        $rows = $this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_allocations` WHERE snapshot_id={$snapshotId} AND object_id={$objectId} ORDER BY amount_cents DESC,full_name")->fetch_all(MYSQLI_ASSOC); $html = '<div class="fm2-otiz-allocation"><h3>Начисления работникам</h3>';
        foreach ($rows as $r) $html .= '<div><span><strong>' . $this->e($r['full_name']) . '</strong><small>таб. ' . $this->e($r['tab_id']) . ' · КТУ ' . number_format($this->allocationKtuBp($r)/10000,2,',',' ') . ' · доля ' . $this->percent((int)$r['share_bp']) . '</small></span><b>' . $this->rub((int) $r['amount_cents']) . '</b></div>';
        return $html . '</div>';
    }

    private function allocationKtuBp(array $allocation): int
    {
        $base=(int)($allocation['base_ktu_bp']??0);$effective=(int)($allocation['effective_ktu_bp']??0);$contribution=(int)($allocation['contribution_bp']??0);
        if($base===$contribution&&$effective===$contribution&&str_contains((string)($allocation['participation_basis']??''),'коэффициент 1,00'))return 10000;
        return $effective;
    }

    private function closureForm(array $snapshot, array $object): string
    {
        $snapshotId = (int) $snapshot['id']; $objectId = (int) $object['object_id'];
        $closed = (int) $this->db->query("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$snapshotId} AND object_id={$objectId}")->fetch_assoc()['n'];
        $available = max(0, (int) $object['pool_cents'] - $closed);
        if ($snapshot['status'] !== 'accepted' || $object['calculation_state'] === 'blocked') return '';
        $deadlineReduction=$this->deadlinePenalty($object);$disciplineForm=$available>0?'<details class="fm2-otiz-close"><summary>Добавить дисциплинарное удержание</summary><form method="post" action="/pilot/otiz/snapshots/' . (int) $snapshot['id'] . '/closures"><input type="hidden" name="csrfToken" value="' . $this->e($this->csrf) . '"><input type="hidden" name="objectId" value="' . (int) $object['object_id'] . '"><label class="shlz-field"><span class="shlz-field__label">Сумма удержания, ₽</span><input class="shlz-input" name="discipline" inputmode="decimal" value="0" required></label><label class="shlz-field"><span class="shlz-field__label">Основание удержания</span><input class="shlz-input" name="basis" required placeholder="ПВР или служебная записка"></label><label class="shlz-field"><span class="shlz-field__label">Документ</span><input class="shlz-input" name="artifact" placeholder="Номер документа (необязательно)"></label><button class="shlz-button" type="submit">Добавить удержание</button><small>Доступно для удержания не более ' . $this->rub($available) . '</small></form></details>':'<p class="fm2-otiz-paid-note">Выплата по объекту выполнена.</p>';
        return '<div class="fm2-otiz-settlement"><div class="fm2-otiz-system-money" aria-label="Расчёт выплаты"><div><span>Начислено за работы</span><strong>'.$this->rub((int)$object['accrued_cents']+$deadlineReduction).'</strong></div><div><span>Удержано за сроки</span><strong>'.$this->rub($deadlineReduction).'</strong></div><p>Итого по объекту — '.$this->rub((int)$object['accrued_cents']).'.</p></div>'.$disciplineForm.'</div>';
    }

    private function closures(int $snapshotId): string
    {
        $rows = $this->db->query("SELECT c.*,o.regnumber,o.accrued_cents,o.inputs_json FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` c JOIN `{$this->prefix}fm2_pilot_otiz_snapshot_objects` o ON o.snapshot_id=c.snapshot_id AND o.object_id=c.object_id WHERE c.snapshot_id={$snapshotId} ORDER BY c.id DESC")->fetch_all(MYSQLI_ASSOC);
        if ($rows === []) return '';
        $html = '<section class="fm2-otiz-ledger"><h2>Выполненные выплаты и удержания</h2><div class="fm2-table-wrap"><table class="shlz-table fm2-queue-table"><thead><tr><th>Дата</th><th>Объект</th><th>Выплачено</th><th>Удержано по дисциплине</th><th>Удержано за сроки</th><th>Основание</th><th></th></tr></thead><tbody>';
        foreach ($rows as $r) { $reversal = (int) $r['reverses_payment_closure_id'] > 0; $deadline=(int)$r['deadline_cents'];if((int)$r['paid_cents']!==0)$deadline+=(int)($r['paid_cents']>0?1:-1)*$this->deadlinePenalty($r);$html .= '<tr><td>' . $this->date($r['closed_on']) . '</td><td>' . $this->e($r['regnumber']) . '</td><td>' . $this->rub((int) $r['paid_cents']) . '</td><td>' . $this->rub((int) $r['discipline_cents']) . '</td><td>' . $this->rub($deadline) . '</td><td>' . $this->e($r['basis']) . ($reversal ? '<small>Сторно записи № ' . (int) $r['reverses_payment_closure_id'] . '</small>' : '') . '</td><td>' . (!$reversal ? '<form class="fm2-otiz-reverse" method="post" action="/pilot/otiz/closures/' . (int) $r['id'] . '/reverse"><input type="hidden" name="csrfToken" value="' . $this->e($this->csrf) . '"><input class="shlz-input" name="basis" aria-label="Основание сторно" placeholder="Причина сторно" required><button class="shlz-button shlz-button--text shlz-button--sm" type="submit">Сторнировать</button></form>' : '') . '</td></tr>'; }
        return $html . '</tbody></table></div></section>';
    }

    private function evidenceLedger(int $snapshotId): string
    {
        $rows=$this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_evidence` WHERE snapshot_id={$snapshotId} ORDER BY FIELD(admission_state,'excluded','confirmed_not_mapped'),legacy_object_id")->fetch_all(MYSQLI_ASSOC);
        if($rows===[])return '';
        $html='<section class="fm2-otiz-ledger"><h2>Входящие migrated evidence</h2><p>Подтверждённые факты сохранены в срезе, но не меняют сумму до утверждения semantic mapping checklist → progress/КТУ.</p><div class="fm2-table-wrap"><table class="shlz-table fm2-queue-table"><thead><tr><th>Legacy-объект</th><th>Источник</th><th>Класс</th><th>Статус</th><th>Контроль</th></tr></thead><tbody>';
        foreach($rows as$row)$html.='<tr><td>'.(int)$row['legacy_object_id'].'</td><td>'.$this->e($row['source_label']).'<small>'.$this->e($row['source_locator']).'</small></td><td>'.$this->e($row['evidence_grade']).'</td><td><span class="shlz-status '.($row['admission_state']==='excluded'?'shlz-status--orange':'shlz-status--blue').'">'.($row['admission_state']==='excluded'?'Исключено':'Подтверждено, не сопоставлено').'</span></td><td><small title="'.$this->e($row['projection_hash']).'">'.$this->e(substr($row['projection_hash'],0,16)).'…</small></td></tr>';
        return $html.'</tbody></table></div></section>';
    }

    private function calculationTrace(array $object):string
    {
        $inputs=json_decode((string)$object['inputs_json'],true);$calculation=$inputs['premiumCalculation']??null;if(!is_array($calculation))return'';
        $labels=['fund'=>'Премиальный фонд','progress'=>'Начислено за прогресс','deadline'=>'С учётом сроков','closed'=>'Выплачено ранее','pool'=>'К выплате'];$steps='';foreach($calculation['formulaTrace']??[]as$step){$result=array_key_exists('resultCents',$step)?$this->rub((int)$step['resultCents']):$this->percent((int)($step['resultBp']??0));$key=(string)($step['step']??'');$steps.='<li><span>'.$this->e($labels[$key]??'Этап расчёта').'</span><strong>'.$result.'</strong></li>';}
        $exclusions=$calculation['exclusions']??[];$note=$exclusions===[]?'Расчёт допущен к распределению.':'Исключено: '.implode(', ',array_column($exclusions,'code'));
        return'<details class="fm2-otiz-trace"><summary>Как рассчитана сумма</summary><p>'.$this->e($note).'</p><ol>'.$steps.'</ol></details>';
    }

    private function deadlinePenalty(array $object):int
    {
        $inputs=json_decode((string)($object['inputs_json']??''),true);$calculation=is_array($inputs)?($inputs['premiumCalculation']??null):null;if(!is_array($calculation))return 0;$progressAmount=0;foreach($calculation['formulaTrace']??[]as$step)if(($step['step']??'')==='progress')$progressAmount=(int)($step['resultCents']??0);return max(0,$progressAmount-(int)($object['accrued_cents']??0));
    }

    private function ensureSchema(): void
    {
        self::bootstrap($this->db, $this->prefix);
    }

    public static function bootstrap(mysqli $db, string $prefix): void
    {
        if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) throw new RuntimeException('Invalid pilot table prefix');
        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_otiz_snapshots`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,report_date DATE NOT NULL,status ENUM('draft','accepted') NOT NULL,previous_snapshot_id BIGINT UNSIGNED NULL,rules_version VARCHAR(80) NOT NULL,calculated_at VARCHAR(40) NOT NULL,calculated_by_user_id BIGINT UNSIGNED NOT NULL,accepted_at VARCHAR(40) NULL,accepted_by_user_id BIGINT UNSIGNED NULL,total_pool_cents BIGINT NOT NULL,total_closed_cents BIGINT NOT NULL,total_available_cents BIGINT NOT NULL,content_hash CHAR(64) NOT NULL,KEY(report_date,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_otiz_snapshot_objects`(snapshot_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,regnumber VARCHAR(120) NOT NULL,address VARCHAR(500) NOT NULL,previous_progress_bp INT NOT NULL,current_progress_bp INT NOT NULL,progress_fact_date DATE NOT NULL,premium_cents BIGINT NOT NULL,shaft_bp INT NOT NULL,kss_bp INT NOT NULL,accrued_cents BIGINT NOT NULL,fund_cents BIGINT NOT NULL,closed_before_cents BIGINT NOT NULL,remaining_cents BIGINT NOT NULL,pool_cents BIGINT NOT NULL,distributed_cents BIGINT NOT NULL,undistributed_cents BIGINT NOT NULL,calculation_state VARCHAR(40) NOT NULL,inputs_json JSON NOT NULL,PRIMARY KEY(snapshot_id,object_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_otiz_snapshot_allocations`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,tab_id VARCHAR(40) NOT NULL,full_name VARCHAR(300) NOT NULL,position_name VARCHAR(200) NOT NULL,contribution_bp INT NOT NULL,base_ktu_bp INT NOT NULL,adjustment_ktu_bp INT NOT NULL,effective_ktu_bp INT NOT NULL,share_bp INT NOT NULL,amount_cents BIGINT NOT NULL,employment_status VARCHAR(40) NOT NULL,participation_basis VARCHAR(300) NOT NULL,KEY(snapshot_id,object_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_otiz_snapshot_issues`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,severity ENUM('blocker','warning') NOT NULL,issue_code VARCHAR(80) NOT NULL,message VARCHAR(600) NOT NULL,owner_role VARCHAR(120) NOT NULL,state ENUM('open','resolved') NOT NULL DEFAULT 'open',resolution VARCHAR(600) NULL,resolved_by_user_id BIGINT UNSIGNED NULL,resolved_at VARCHAR(40) NULL,KEY(snapshot_id,object_id,severity)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_otiz_snapshot_evidence`(snapshot_id BIGINT UNSIGNED NOT NULL,legacy_object_id BIGINT UNSIGNED NOT NULL,admission_state ENUM('confirmed_not_mapped','excluded') NOT NULL,source_label VARCHAR(160) NOT NULL,source_locator VARCHAR(160) NOT NULL,snapshot_hash CHAR(64) NOT NULL,projection_hash CHAR(64) NOT NULL,evidence_grade CHAR(1) NOT NULL,payload_json JSON NOT NULL,PRIMARY KEY(snapshot_id,legacy_object_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_otiz_payment_closures`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,closed_on DATE NOT NULL,paid_cents BIGINT NOT NULL,discipline_cents BIGINT NOT NULL,deadline_cents BIGINT NOT NULL,basis VARCHAR(500) NOT NULL,artifact VARCHAR(300) NOT NULL,created_by_user_id BIGINT UNSIGNED NOT NULL,created_at VARCHAR(40) NOT NULL,reverses_payment_closure_id BIGINT UNSIGNED NULL,KEY(object_id,closed_on),KEY(snapshot_id),UNIQUE KEY unique_reversal(reverses_payment_closure_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_otiz_events`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NULL,object_id BIGINT UNSIGNED NULL,event_type VARCHAR(80) NOT NULL,payload_json JSON NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,KEY(snapshot_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ]; foreach ($queries as $query) $db->query($query);
        $table = $db->real_escape_string($prefix . 'fm2_pilot_otiz_payment_closures');
        $hasUniqueReversal = (int) $db->query("SELECT COUNT(*) n FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND INDEX_NAME='unique_reversal' AND NON_UNIQUE=0")->fetch_assoc()['n'];
        if ($hasUniqueReversal === 0) $db->query("ALTER TABLE `{$prefix}fm2_pilot_otiz_payment_closures` ADD UNIQUE KEY unique_reversal(reverses_payment_closure_id)");
    }

    private function closedBefore(int $objectId, string $date): int { return (int) $this->db->query("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id={$objectId} AND closed_on<='" . $this->db->real_escape_string($date) . "'")->fetch_assoc()['n']; }
    private function closureEvidence(int $objectId,string $date):array
    {
        $rows=$this->db->query("SELECT id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,reverses_payment_closure_id FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id={$objectId} AND closed_on<='".$this->db->real_escape_string($date)."' ORDER BY id")->fetch_all(MYSQLI_ASSOC);$evidence=[];
        foreach($rows as$row){$canonical=json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$evidence[]=['amountCents'=>(int)$row['paid_cents']+(int)$row['discipline_cents']+(int)$row['deadline_cents'],'closedOn'=>(string)$row['closed_on'],'source'=>['label'=>'Принятое закрытие ОТиЗ','locator'=>'fm2_pilot_otiz_payment_closures/'.(int)$row['id'],'contentSha256'=>hash('sha256',$canonical)]];}return$evidence;
    }
    private function issue(int $snapshotId,int $objectId,string $severity,string $code,string $message,string $owner): void { $s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_snapshot_issues`(snapshot_id,object_id,severity,issue_code,message,owner_role,state) VALUES(?,?,?,?,?,?,'open')"); $s->bind_param('iissss',$snapshotId,$objectId,$severity,$code,$message,$owner); $s->execute(); }
    private function event(?int $snapshotId,?int $objectId,string $type,array $payload): void { $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$now=$this->now();$s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_events`(snapshot_id,object_id,event_type,payload_json,actor_user_id,occurred_at) VALUES(?,?,?,?,?,?)");$s->bind_param('iissis',$snapshotId,$objectId,$type,$json,$this->userId,$now);$s->execute(); }
    private function snapshotRow(int $id): array { $row=$this->db->query("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshots` WHERE id={$id} LIMIT 1")->fetch_assoc(); if(!is_array($row))$this->fail(404,'Расчёт не найден.'); return $row; }
    private function validDate(string $value): bool { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);return$d!==false&&$d->format('Y-m-d')===$value&&$value<='2026-12-31'; }
    private function money(string $value): int { $value=str_replace([' ', ','],['','.'],trim($value));if(!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D',$value))return-1;return(int)round((float)$value*100); }
    private function percent(int $bp): string { return number_format($bp/100,0,',',' ') . '%'; }
    private function rub(int $cents): string { $sign=$cents<0?'−':'';return$sign.number_format(abs($cents)/100,2,',',' ').' ₽'; }
    private function date(string $value): string { return preg_match('/^(\d{4})-(\d{2})-(\d{2})/D',$value,$m)===1?$m[3].'.'.$m[2].'.'.$m[1]:$this->e($value); }
    private function dateTime(string $value): string { try{return(new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Moscow'))->format('d.m.Y H:i');}catch(Throwable){return$this->e($value);} }
    private function now(): string { return(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format(DATE_ATOM); }
    private function uuid(): string { $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&0x0f)|0x40);$bytes[8]=chr((ord($bytes[8])&0x3f)|0x80);$hex=bin2hex($bytes);return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20); }
    private function e(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8'); }
    private function xml(mixed $value): string { return htmlspecialchars((string)$value,ENT_XML1|ENT_QUOTES,'UTF-8'); }
    private function worksheet(array $rows): string { $xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';foreach($rows as$row){$xml.='<row>';foreach($row as$value){if(is_int($value)||is_float($value))$xml.='<c><v>'.$value.'</v></c>';else$xml.='<c t="inlineStr"><is><t>'.$this->xml($value).'</t></is></c>';}$xml.='</row>';}$xml.='</sheetData></worksheet>';return$xml; }
    private function xlsxContentTypes(int $count): string { $x='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';for($i=1;$i<=$count;$i++)$x.='<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';return$x.'</Types>'; }
    private function zip(array $files): string { $body='';$central='';$offset=0;foreach($files as$name=>$data){$crc=(int)sprintf('%u',crc32($data));$length=strlen($data);$nameLength=strlen($name);$local=pack('VvvvvvVVVvv',0x04034b50,20,0,0,0,0,$crc,$length,$length,$nameLength,0).$name.$data;$body.=$local;$central.=pack('VvvvvvvVVVvvvvvVV',0x02014b50,20,20,0,0,0,0,$crc,$length,$length,$nameLength,0,0,0,0,0,$offset).$name;$offset+=strlen($local);}return$body.$central.pack('VvvvvVVv',0x06054b50,0,0,count($files),count($files),strlen($central),strlen($body),0); }
    private function redirect(string $path): never { header('Location: '.$path,true,303);header('Cache-Control: no-store');exit; }
    private function fail(int $status,string $message): never { http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');echo$message."\n";exit; }
}
