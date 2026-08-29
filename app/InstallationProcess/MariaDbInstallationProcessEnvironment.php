<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class MariaDbInstallationProcessEnvironment
{
    public function __construct(
        private readonly \mysqli $connection,
        private readonly object $externalFacts,
        private readonly string $tablePrefix = '',
    ) {
        if (!preg_match('/^[a-zA-Z0-9_]*$/', $tablePrefix)) {
            throw new \InvalidArgumentException('Table prefix contains unsupported characters.');
        }
    }

    public function actorCanPrepareAssignmentOrder(int $actorId): bool { return $this->externalFacts->actorCanPrepareAssignmentOrder($actorId); }
    public function actorCanConfirmOrderRegistration(int $actorId): bool { return $this->externalFacts->actorCanConfirmOrderRegistration($actorId); }
    public function actorCanOpenInstallation(int $actorId): bool { return $this->externalFacts->actorCanOpenInstallation($actorId); }
    public function getInstallationObjectSnapshot(int $id): array { return $this->externalFacts->getInstallationObjectSnapshot($id); }
    public function findInstallerSnapshot(int|string $id): ?array { return $this->externalFacts->findInstallerSnapshot($id); }
    public function findCurrentInstallerSnapshot(int|string $id): ?array { return $this->externalFacts->findCurrentInstallerSnapshot($id); }
    public function findEngineerSnapshot(int $id): ?array { return $this->externalFacts->findEngineerSnapshot($id); }
    public function renderAssignmentOrder(array $input): array { return $this->externalFacts->renderAssignmentOrder($input); }
    public function now(): string { return $this->externalFacts->now(); }
    public function assignmentOrderDate(string $occurredAt):string{return \method_exists($this->externalFacts,'assignmentOrderDate')?$this->externalFacts->assignmentOrderDate($occurredAt):(new \DateTimeImmutable($occurredAt))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d');}
    public function newPreparationOperationId(): string { return bin2hex(random_bytes(16)); }
    public function findPreparationResult(string $operationId): ?array { return null; }

    public function loadInstallationObjectProcessAtRevision(int $id): array
    {
        $statement = $this->connection->prepare("SELECT lock_version FROM {$this->tablePrefix}fm2_installation_cases WHERE legacy_installation_object_id=?");
        $statement->bind_param('i', $id);
        $statement->execute();
        $revision = $statement->get_result()->fetch_column();
        if ($revision === false) throw new \RuntimeException('Installation object process not found.');
        return ['process' => $this->getInstallationObjectProcess($id), 'revision' => (int) $revision];
    }

    public function replaceInstallationObjectProcessAtRevision(int $id, int $expectedRevision, array $process, ?string $operationId = null): array
    {
        $this->connection->begin_transaction();
        try {
            $statement = $this->connection->prepare("SELECT id,lock_version FROM {$this->tablePrefix}fm2_installation_cases WHERE legacy_installation_object_id=? FOR UPDATE");
            $statement->bind_param('i', $id);
            $statement->execute();
            $case = $statement->get_result()->fetch_assoc();
            if ($case === null) throw new \RuntimeException('Installation object process not found.');
            $revision = (int) $case['lock_version'];
            if ($revision !== $expectedRevision) {
                $this->connection->rollback();
                throw new \LogicException('MariaDB concurrency handling is outside PERSISTENCE-PREPARE-001.');
            }

            $caseId = (int) $case['id'];
            $statement = $this->connection->prepare("SELECT id,version_no,status FROM {$this->tablePrefix}fm2_assignment_orders WHERE installation_case_id=? ORDER BY version_no DESC LIMIT 1 FOR UPDATE");
            $statement->bind_param('i', $caseId);
            $statement->execute();
            $storedOrder = $statement->get_result()->fetch_assoc();
            if ($storedOrder === null) $this->persistPreparation($caseId, $process);
            elseif ($storedOrder['status'] === 'prepared') $this->persistRegistration($caseId, $storedOrder, $process);
            elseif ($storedOrder['status'] === 'registered') $this->persistOpening($caseId, $storedOrder, $process);
            else throw new \LogicException('Stored assignment order transition is unsupported.');

            $event = $process['events'][array_key_last($process['events'])];
            $this->appendProcessEvent($caseId, $event);
            $this->persistCaseTransition($caseId, $revision + 1, $process, (string) $event['occurredAt']);
        } catch (\Throwable $error) {
            try { $this->connection->rollback(); }
            catch (\Throwable $rollbackError) { throw new \RuntimeException('Transaction failed and rollback was not confirmed.',0,$rollbackError); }
            throw new \RuntimeException('MariaDB process transition persistence failed.',0,$error);
        }
        try { $this->connection->commit(); }
        catch (\Throwable $error) { throw new \RuntimeException('MariaDB process transition commit outcome is unknown.',0,$error); }
        return ['replaced'=>true,'currentRevision'=>$expectedRevision+1];
    }

    private function persistPreparation(int $caseId, array $process): void
    {
        $order=$process['assignmentOrders'][0]; $engineer=$order['controlEngineer']; $snapshot=$order['installationObjectSnapshot']; $event=$process['events'][array_key_last($process['events'])];
        $values=[$caseId,(int)$order['version'],(string)$order['status'],(string)$order['assignmentOrderDate'],(int)$engineer['userId'],(string)$engineer['fullName'],(string)$engineer['position'],(string)$order['organizationType'],(string)$snapshot['address'],(string)$snapshot['entrance'],(string)$snapshot['objectRegistrationNumber'],(string)$snapshot['plannedStartDate'],(string)$snapshot['plannedFinishDate'],$snapshot['ptoActDate'],(string)$event['occurredAt'],(int)$event['actorId']];
        $statement=$this->connection->prepare("INSERT INTO {$this->tablePrefix}fm2_assignment_orders (installation_case_id,version_no,kind,status,registration_number,order_date,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,previous_assignment_order_id,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,pto_act_date_snapshot,prepared_at,prepared_by_user_id) VALUES (?,?,'initial',?,NULL,?,?,?,?,?,NULL,?,?,?,?,?,?,?,?)");
        $statement->bind_param('iississssssssssi',...$values); $statement->execute(); $orderId=(int)$this->connection->insert_id;
        foreach($order['installers'] as $installer){$tabId=(string)$installer['tabId'];$name=(string)$installer['fullName'];$position=(string)$installer['position'];$status=(string)$installer['status'];$from=(string)$installer['employedFrom'];$to=$installer['employedTo'];$source=(string)$installer['source'];$updated=(string)$installer['sourceUpdatedAt'];$validFrom=(string)$order['assignmentOrderDate'];$statement=$this->connection->prepare("INSERT INTO {$this->tablePrefix}fm2_order_installers (assignment_order_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,employed_from_snapshot,employed_to_snapshot,workforce_source_snapshot,workforce_source_updated_at_snapshot,valid_from,valid_to,change_action) VALUES (?,?,?,?,?,?,?,?,?,?,NULL,'assign')");$statement->bind_param('isssssssss',$orderId,$tabId,$name,$position,$status,$from,$to,$source,$updated,$validFrom);$statement->execute();}
        foreach($order['artifacts'] as $artifact){$type=(string)$artifact['type'];$filename=(string)$artifact['filename'];$media=(string)$artifact['mediaType'];$size=(int)$artifact['size'];$sha=(string)$artifact['sha256'];$statement=$this->connection->prepare("INSERT INTO {$this->tablePrefix}fm2_order_artifacts (assignment_order_id,artifact_type,filename,media_type,byte_size,sha256) VALUES (?,?,?,?,?,?)");$statement->bind_param('isssis',$orderId,$type,$filename,$media,$size,$sha);$statement->execute();}
    }

    private function persistRegistration(int $caseId,array $storedOrder,array $process): void
    {
        $orders=$process['assignmentOrders']??[];$order=$orders===[]?null:$orders[array_key_last($orders)];$event=$process['events'][array_key_last($process['events'])]??null;
        if($order===null||$event===null||(int)$storedOrder['version_no']!==(int)($order['version']??0)||($order['status']??null)!=='registered'||($event['type']??null)!=='assignment_order_registered')throw new \LogicException('Stored assignment order is not eligible for registration update.');
        $status='registered';$number=(string)$order['registrationNumber'];$registeredAt=(string)$order['registeredAt'];$actorType=(string)$order['registrationActorType'];$actorId=(string)$order['registrationActorId'];$source=(string)$order['registrationSource'];$externalId=$order['externalRegistrationId'];$orderId=(int)$storedOrder['id'];$version=(int)$order['version'];
        $statement=$this->connection->prepare("UPDATE {$this->tablePrefix}fm2_assignment_orders SET status=?,registration_number=?,registered_at=?,registration_actor_type=?,registration_actor_id=?,registration_source=?,external_registration_id=? WHERE id=? AND installation_case_id=? AND version_no=? AND status='prepared'");$statement->bind_param('sssssssiii',$status,$number,$registeredAt,$actorType,$actorId,$source,$externalId,$orderId,$caseId,$version);$statement->execute();if($statement->affected_rows!==1)throw new \LogicException('Exact prepared assignment order was not updated.');
    }

    private function persistOpening(int $caseId,array $storedOrder,array $process): void
    {
        $orders=$process['assignmentOrders']??[];$order=$orders===[]?null:$orders[array_key_last($orders)];$event=$process['events'][array_key_last($process['events'])]??null;
        if($order===null||$event===null||(int)$storedOrder['version_no']!==(int)($order['version']??0)||($order['status']??null)!=='registered'||($process['processState']??null)!=='working'||($event['type']??null)!=='installation_opened')throw new \LogicException('Stored assignment order is not eligible for opening.');
        $orderId=(int)$storedOrder['id'];$statement=$this->connection->prepare("SELECT installer_tab_id FROM {$this->tablePrefix}fm2_order_installers WHERE assignment_order_id=? LIMIT 1 FOR UPDATE");$statement->bind_param('i',$orderId);$statement->execute();if($statement->get_result()->fetch_assoc()===null)throw new \LogicException('Registered assignment order has no stored installer composition.');
    }

    private function appendProcessEvent(int $caseId,array $event): void
    {
        $type=(string)$event['type'];$at=(string)$event['occurredAt'];$actor=(int)$event['actorId'];$payload=$this->json($event['payload']);$statement=$this->connection->prepare("INSERT INTO {$this->tablePrefix}fm2_process_events (installation_case_id,event_type,occurred_at,actor_user_id,payload_json) VALUES (?,?,?,?,?)");$statement->bind_param('issis',$caseId,$type,$at,$actor,$payload);$statement->execute();
    }

    private function persistCaseTransition(int $caseId,int $next,array $process,string $at): void
    {
        $state=(string)$process['processState'];
        if($state==='working'){$date=(string)$process['actualStartDate'];$openedAt=(string)$process['openedAt'];$actor=(int)$process['openedByUserId'];$statement=$this->connection->prepare("UPDATE {$this->tablePrefix}fm2_installation_cases SET process_state=?,actual_start_date=?,opened_at=?,opened_by_user_id=?,updated_at=?,lock_version=? WHERE id=?");$statement->bind_param('sssissi',$state,$date,$openedAt,$actor,$at,$next,$caseId);}
        else{$statement=$this->connection->prepare("UPDATE {$this->tablePrefix}fm2_installation_cases SET process_state=?,updated_at=?,lock_version=? WHERE id=?");$statement->bind_param('ssii',$state,$at,$next,$caseId);}
        $statement->execute();if($statement->affected_rows!==1)throw new \LogicException('Installation case transition was not persisted.');
    }

    public function getInstallationObjectProcess(int $id): array
    {
        $statement=$this->connection->prepare("SELECT id,process_state,actual_start_date,opened_at,opened_by_user_id FROM {$this->tablePrefix}fm2_installation_cases WHERE legacy_installation_object_id=?");
        $statement->bind_param('i',$id); $statement->execute(); $case=$statement->get_result()->fetch_assoc();
        if ($case===null) throw new \RuntimeException('Installation object process not found.');
        $caseId=(int)$case['id']; $orders=[]; $assignments=[];
        $statement=$this->connection->prepare("SELECT * FROM {$this->tablePrefix}fm2_assignment_orders WHERE installation_case_id=? ORDER BY version_no");
        $statement->bind_param('i',$caseId); $statement->execute(); $rows=$statement->get_result();
        while ($row=$rows->fetch_assoc()) {
            $orderId=(int)$row['id']; $version=(int)$row['version_no']; $installers=[];
            $s=$this->connection->prepare("SELECT * FROM {$this->tablePrefix}fm2_order_installers WHERE assignment_order_id=? ORDER BY installer_tab_id");
            $s->bind_param('i',$orderId); $s->execute(); $installerRows=$s->get_result();
            while ($i=$installerRows->fetch_assoc()) {
                $installer=['tabId'=>(int)$i['installer_tab_id'],'fullName'=>$i['fio_snapshot'],'position'=>$i['position_snapshot'],'status'=>$i['employment_status_snapshot'],'employedFrom'=>$i['employed_from_snapshot'],'employedTo'=>$i['employed_to_snapshot'],'source'=>$i['workforce_source_snapshot'],'sourceUpdatedAt'=>$i['workforce_source_updated_at_snapshot']];
                $installers[]=$installer; $assignments[]=['role'=>'installer','tabId'=>$installer['tabId'],'assignmentOrderVersion'=>$version,'status'=>'preliminary'];
            }
            $artifacts=[]; $s=$this->connection->prepare("SELECT artifact_type,filename,media_type,byte_size,sha256 FROM {$this->tablePrefix}fm2_order_artifacts WHERE assignment_order_id=? ORDER BY FIELD(artifact_type,'order','appendix'),artifact_type");
            $s->bind_param('i',$orderId); $s->execute(); $artifactRows=$s->get_result();
            while($a=$artifactRows->fetch_assoc()) $artifacts[]=['type'=>$a['artifact_type'],'filename'=>$a['filename'],'mediaType'=>$a['media_type'],'size'=>(int)$a['byte_size'],'sha256'=>$a['sha256']];
            $engineer=['userId'=>(int)$row['control_engineer_user_id'],'fullName'=>$row['control_engineer_fio_snapshot'],'position'=>$row['control_engineer_position_snapshot'],'active'=>true,'role'=>'construction_control_engineer'];
            $assignments[]=['role'=>'control_engineer','userId'=>$engineer['userId'],'assignmentOrderVersion'=>$version,'status'=>'preliminary'];
            $projectedOrder=['version'=>$version,'status'=>$row['status'],'registrationNumber'=>$row['registration_number']];
            if ($row['status']==='registered') {
                $projectedOrder['registeredAt']=$row['registered_at'];
                $projectedOrder['registrationActorType']=$row['registration_actor_type'];
                $projectedOrder['registrationActorId']=(int)$row['registration_actor_id'];
                $projectedOrder['registrationSource']=$row['registration_source'];
                $projectedOrder['externalRegistrationId']=$row['external_registration_id'];
            }
            $projectedOrder+=['assignmentOrderDate'=>$row['order_date'],'organizationType'=>$row['organization_form'],'installationObjectSnapshot'=>['address'=>$row['object_address_snapshot'],'entrance'=>$row['entrance_snapshot'],'objectRegistrationNumber'=>$row['object_registration_number_snapshot'],'plannedStartDate'=>$row['planned_start_date_snapshot'],'plannedFinishDate'=>$row['planned_finish_date_snapshot'],'ptoActDate'=>$row['pto_act_date_snapshot']],'installers'=>$installers,'controlEngineer'=>$engineer,'artifacts'=>$artifacts];
            $orders[]=$projectedOrder;
        }
        $events=[]; $statement=$this->connection->prepare("SELECT event_type,occurred_at,actor_user_id,payload_json FROM {$this->tablePrefix}fm2_process_events WHERE installation_case_id=? ORDER BY id");
        $statement->bind_param('i',$caseId); $statement->execute(); $rows=$statement->get_result();
        while($e=$rows->fetch_assoc()) $events[]=['type'=>$e['event_type'],'occurredAt'=>$e['occurred_at'],'actorId'=>(int)$e['actor_user_id'],'payload'=>$this->decode($e['payload_json'])];
        $projection=['installationObjectId'=>$id,'processState'=>$case['process_state']];
        if ($case['actual_start_date']!==null) {
            $projection+=['actualStartDate'=>$case['actual_start_date'],'openedAt'=>$case['opened_at'],'openedByUserId'=>(int)$case['opened_by_user_id']];
        }
        return $projection+['assignmentOrders'=>$orders,'assignments'=>$assignments,'openTasks'=>[],'installationOpened'=>$case['actual_start_date']!==null,'checklistAvailable'=>$case['actual_start_date']!==null,'events'=>$events];
    }

    public function appendEvent(int $id,array $event): void
    {
        $statement=$this->connection->prepare("SELECT id FROM {$this->tablePrefix}fm2_installation_cases WHERE legacy_installation_object_id=?");
        $statement->bind_param('i',$id);$statement->execute();$caseId=$statement->get_result()->fetch_column();
        if($caseId===false)throw new \RuntimeException('Installation object process not found.');
        $this->appendProcessEvent((int)$caseId,$event);
    }
    public function appendSecurityEvent(int $id,array $event): void { throw new \LogicException('Security-event persistence is outside PERSISTENCE-PREPARE-001.'); }
    public function actorCanReadSecurityAudit(int $actorId): bool { return false; }
    public function getSecurityEvents(int $id): array { return []; }
    private function json(mixed $value): string { return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); }
    private function decode(string $json): array { return json_decode($json,true,512,JSON_THROW_ON_ERROR); }
}
