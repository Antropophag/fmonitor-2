<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

/** Persists the existing calculation inside the publication owner's transaction. */
final class MariaDbSnapshotBuilder
{
    public function __construct(private \mysqli $db, private string $prefix, private \Closure $inputs) {}

    public function populate(int $snapshotId, string $date): void
    {
        $objects = ($this->inputs)($date); $totalPool = 0; $totalClosed = 0; $payload = [];
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

    }

    private function closedBefore(int $objectId, string $date): int { return (int) $this->db->query("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id={$objectId} AND closed_on<='" . $this->db->real_escape_string($date) . "'")->fetch_assoc()['n']; }
    private function closureEvidence(int $objectId,string $date):array
    {
        $rows=$this->db->query("SELECT id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,reverses_payment_closure_id FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id={$objectId} AND closed_on<='".$this->db->real_escape_string($date)."' ORDER BY id")->fetch_all(MYSQLI_ASSOC);$evidence=[];
        foreach($rows as$row){$canonical=json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$evidence[]=['amountCents'=>(int)$row['paid_cents']+(int)$row['discipline_cents']+(int)$row['deadline_cents'],'closedOn'=>(string)$row['closed_on'],'source'=>['label'=>'Принятое закрытие ОТиЗ','locator'=>'fm2_pilot_otiz_payment_closures/'.(int)$row['id'],'contentSha256'=>hash('sha256',$canonical)]];}return$evidence;
    }
    private function issue(int $snapshotId,int $objectId,string $severity,string $code,string $message,string $owner): void { $s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_otiz_snapshot_issues`(snapshot_id,object_id,severity,issue_code,message,owner_role,state) VALUES(?,?,?,?,?,?,'open')"); $s->bind_param('iissss',$snapshotId,$objectId,$severity,$code,$message,$owner); $s->execute(); }
}
