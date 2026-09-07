<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\AssignmentOrderOriginal as O;

/** Explicit opening from an applied original; never manufactures legacy registration. */
final readonly class MariaDbOriginalOpening
{
    public function __construct(private \mysqli $db, private string $prefix, private SelectionClock $clock) {}
    public function openInstallation(int $object, string $date, int $expectedApplication, int $actor): array
    {
        $failure = static fn(string $reason): array => ['accepted'=>false,'reasonCode'=>$reason];
        if (min($object,$expectedApplication,$actor)<1 || !SelectionScalar::date($date)) return $failure('invalid_command');
        $s = new MariaDbSelectionSql($this->db, $this->prefix); $owned=false; $committing=false;
        try {
            if (!$s->idle()) return $failure('dependency_unavailable');
            if (!$this->authorized($s,$actor,false)) return $failure('authorization_denied');
            $reader=AssignmentOrderApplicationReaderFactory::create($this->db,$this->prefix);
            $found=$reader->readCurrent($object);
            if ($found->status!=='found' || $found->value===null) return $failure('application_required');
            $application=$found->value['application'];
            if ($application['applicationId']!==$expectedApplication) return $failure('application_changed');
            $original=O\AssignmentOrderOriginalApplicationReferenceFactory::create($this->db,$this->prefix);
            $reference=$original->readCurrent($object,$application['orderId']);
            if ($reference->status!==O\AssignmentOrderOriginalApplicationReferenceStatus::FOUND || $reference->reference===null) return $failure('original_unavailable');
            if ($reference->reference->metadata()['revisionId']!==$application['originalRevisionId']) return $failure('original_requires_reapplication');
            $clock=$this->clock->now(); if ($clock->payload===null) return $failure('dependency_unavailable');
            $at=$clock->payload; $today=SelectionScalar::selectionDate($at);
            if ($date>$today || $date<$application['documentDate']) return $failure('actual_start_before_order_or_future');
            $this->db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');$this->db->begin_transaction();$owned=true;
            $result=$this->openPreparedWithinTransaction($object,$date,$expectedApplication,$actor,$application,$original,$reference->reference,$at);
            $committing=true;$this->db->commit();$owned=false;
            return $result;
        } catch (\Throwable $e) {
            if ($owned) try {$this->db->rollback();} catch (\Throwable) {$committing=true;}
            $allowed=['authorization_denied','application_changed','original_requires_reapplication','already_open','completion_document_exists','object_not_found','installer_not_employed','control_engineer_required'];
            return $failure($committing?'persistence_outcome_unknown':(in_array($e->getMessage(),$allowed,true)?$e->getMessage():'dependency_unavailable'));
        }
    }
    /** @internal Caller owns an already-active transaction and every terminal commit or rollback. */
    public function openPreparedWithinTransaction(int $object,string $date,int $expectedApplication,int $actor,array $application,O\AssignmentOrderOriginalApplicationReferenceReader $referenceReader,O\AssignmentOrderOriginalApplicationReference $reference,SelectionInstant $instant,?array $requestIdentity=null):array
    {
        $s=new MariaDbSelectionSql($this->db,$this->prefix);if($s->idle())throw new \RuntimeException('dependency_unavailable');
        if(min($object,$expectedApplication,$actor)<1||!SelectionScalar::date($date)||!SelectionScalar::utc($instant->utcRfc3339Seconds))throw new \RuntimeException('invalid_command');
        if(($application['applicationId']??null)!==$expectedApplication||($application['objectId']??null)!==$object||!\is_int($application['orderId']??null)||($application['orderId']??0)<1||!\is_string($application['originalRevisionId']??null)||!\is_string($application['documentDate']??null)||!\is_array($application['installerTabIds']??null)||!\is_int($application['engineerUserId']??null))throw new \RuntimeException('application_changed');
        $identity=[];if($requestIdentity!==null){if(\count($requestIdentity)!==2||!\array_key_exists('requestId',$requestIdentity)||!\array_key_exists('fingerprint',$requestIdentity)||!\is_string($requestIdentity['requestId'])||\preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$requestIdentity['requestId'])!==1||!\is_string($requestIdentity['fingerprint'])||\preg_match('/^[0-9a-f]{64}$/D',$requestIdentity['fingerprint'])!==1)throw new \RuntimeException('invalid_command');$identity=['requestId'=>$requestIdentity['requestId'],'fingerprint'=>$requestIdentity['fingerprint']];}
        $today=SelectionScalar::selectionDate($instant);if($date>$today||$date<$application['documentDate'])throw new \RuntimeException('actual_start_before_order_or_future');
        $case=$s->rows('SELECT * FROM '.$s->table('fm2_installation_cases').' WHERE legacy_installation_object_id=? FOR UPDATE',[$object]);
        if(\count($case)!==1)throw new \RuntimeException('object_not_found');
        if(!$this->authorized($s,$actor,true))throw new \RuntimeException('authorization_denied');
        $reader=AssignmentOrderApplicationReaderFactory::create($this->db,$this->prefix);if($reader->confirmCurrent($object,$expectedApplication)!=='matched')throw new \RuntimeException('application_changed');
        if(($reference->metadata()['revisionId']??null)!==$application['originalRevisionId']||$referenceReader->confirmCurrent($reference)!==O\AssignmentOrderOriginalApplicationGuardStatus::MATCHED)throw new \RuntimeException('original_requires_reapplication');
        if($case[0]['actual_start_date']!==null||$case[0]['opened_at']!==null)throw new \RuntimeException('already_open');$id=(int)$case[0]['id'];
        if($s->rows('SELECT fact_type FROM '.$s->table('fm2_pilot_completion_facts').' WHERE installation_case_id=?',[$id])!==[])throw new \RuntimeException('completion_document_exists');
        $legacy=$s->rows('SELECT workdatefinish,ptoactdate FROM '.$s->table('fm_maintable').' WHERE id=?',[$object]);foreach(['workdatefinish','ptoactdate']as$key)if(!empty($legacy[0][$key])&&!\str_starts_with($legacy[0][$key],'0000-00-00'))throw new \RuntimeException('completion_document_exists');
        $eligibility=AssignmentOrderCurrentEligibility::confirm($this->db,$this->prefix,$application['installerTabIds'],$application['engineerUserId'],$application['documentDate']);if($eligibility!=='eligible')throw new \RuntimeException($eligibility);
        $template=(new MariaDbOpeningTemplate($s))->bind($id,$date,$instant->utcRfc3339Seconds);$statement=$this->db->prepare('UPDATE '.$s->table('fm2_installation_cases')." SET process_state='working',actual_start_date=?,opened_at=?,opened_by_user_id=?,updated_at=?,lock_version=lock_version+1 WHERE id=? AND actual_start_date IS NULL");$statement->execute([$date,$instant->utcRfc3339Seconds,$actor,$instant->utcRfc3339Seconds,$id]);if($statement->affected_rows!==1)throw new \RuntimeException('application_changed');$statement->close();
        $payload=['application'=>$application,'actualStartDate'=>$date,'template'=>$template]+$identity;$s->insert('fm2_process_events',['installation_case_id'=>$id,'event_type'=>'installation_opened_from_original','occurred_at'=>$instant->utcRfc3339Seconds,'actor_user_id'=>$actor,'payload_json'=>SelectionScalar::json($payload)]);
        return['accepted'=>true,'processState'=>'working','actualStartDate'=>$date,'applicationId'=>$expectedApplication];
    }
    private function authorized(MariaDbSelectionSql $s,int $actor,bool $lock): bool
    {
        return $s->rows('SELECT u.user_id FROM '.$s->table('fm2_pilot_users').' u JOIN '.$s->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$s->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id JOIN '.$s->table('fm2_pilot_role_permissions')." p ON p.role_id=r.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 AND r.code IN ('fkr_operator','manager') AND p.permission='installation.open'".($lock?' FOR UPDATE':''),[$actor])!==[];
    }
}
