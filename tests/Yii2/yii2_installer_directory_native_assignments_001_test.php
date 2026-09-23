<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';

// YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001 A1-A5: real native workflow then real Yii directory HTTP.
$f=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));$f->countDirectoryQueries=true;
    $f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'installers.read']);
    $f->start();
    $denied=[];assertSameValue(303,$f->login($denied,95)['status'],'plausible user login');
    assertSameValue(403,$f->request('GET','/pilot/installers',[],$denied)['status'],'exact installers.read required');
    $cookies=[];
    assertSameValue(303,$f->login($cookies,18)['status'],'FKR login');

    $beforeApplications=$f->rows('fm2_assignment_order_applications');
    assertSameValue([],$beforeApplications,'no application before native workflow');
    assertSameValue(303,$f->selection($cookies)['status'],'native composition selection');
    $receipt=$f->upload($cookies,$f->metadata($cookies));
    assertSameValue(201,$receipt['status'],'native original upload');
    $original=json_decode($receipt['body'],true,flags:JSON_THROW_ON_ERROR);
    $applied=$f->form('/pilot/objects/4512/execution',[
        '_csrf'=>$f->token($cookies),'action'=>'apply',
        'requestId'=>'44444444-4444-4444-8444-000000000038','orderId'=>'81',
        'revisionId'=>$original['currentRevisionId'],'sequence'=>'0',
    ],$cookies);
    assertSameValue(303,$applied['status'],'native application action');
    $applications=$f->rows('fm2_assignment_order_applications');
    assertSameValue(1,count($applications),'one authoritative application fact');
    $applicationBytes=json_encode($applications[0],JSON_THROW_ON_ERROR);
    $factsBeforeRead=$f->facts();

    $directory=$f->request('GET','/pilot/installers',[],$cookies);
    assertSameValue(200,$directory['status'],'directory after native application');
    $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$directory['body']);$xp=new DOMXPath($dom);
    $row7001=$xp->query('//tr[contains(.,"Монтажник 7001")]')->item(0);$row7002=$xp->query('//tr[contains(.,"Монтажник 7002")]')->item(0);
    assertSameValue(true,$row7001!==null&&str_contains($row7001->textContent,'TEST-4512'),'INTENDED_RED native application object in selected installer row');
    assertSameValue(true,$row7001!==null&&$xp->query('.//a[@href="/pilot/objects/4512"]',$row7001)->length===1,'native application object link');
    assertSameValue(true,$row7002!==null&&str_contains($row7002->textContent,'Нет действующих закреплений'),'free installer scoped empty state');
    assertSameValue(1,$xp->query('//*[@data-directory-summary="assigned" and normalize-space(.)="1"]')->length,'authoritative assigned summary');
    assertSameValue($factsBeforeRead,$f->facts(),'directory GET changes no facts');
    $head=$f->request('HEAD','/pilot/installers',[],$cookies);assertSameValue([200,''],[$head['status'],$head['body']],'HEAD same success with empty body');
    assertSameValue($factsBeforeRead,$f->facts(),'directory HEAD changes no facts');

    $assigned=$f->request('GET','/pilot/installers?availability=assigned',[],$cookies);
    assertSameValue(true,str_contains($assigned['body'],'Монтажник 7001'),'assigned filter uses application');
    assertSameValue(false,str_contains($assigned['body'],'Монтажник 7002'),'assigned excludes free installer');
    $free=$f->request('GET','/pilot/installers?availability=free',[],$cookies);
    assertSameValue(true,str_contains($free['body'],'Монтажник 7002'),'free filter uses application');
    assertSameValue(false,str_contains($free['body'],'Монтажник 7001'),'free excludes assigned installer');
    assertSameValue($factsBeforeRead,$f->facts(),'filtered reads change no facts');

    // Fixture-owned already-produced application #2: prove current replacement without exercising another writer.
    $first=$f->rows('fm2_assignment_order_applications')[0];
    $f->insert($f->p.'fm2_process_events',['installation_case_id'=>6101,'event_type'=>'assignment_order_composition_applied','occurred_at'=>'2026-09-03 09:00:00','actor_user_id'=>18,'payload_json'=>'{}']);$event=(int)$f->db->insert_id;
    $selected=json_decode($first['selected_snapshot_json'],true,flags:JSON_THROW_ON_ERROR);$selected['selectedInstallers'][0]['tabId']=7002;
    $second=$first;unset($second['application_id']);$second['application_sequence']=2;$second['previous_application_id']=(int)$first['application_id'];$second['kind']='reapplication';$second['applied_at_utc']='2026-09-03 06:00:00';$second['request_id']='55555555-5555-4555-8555-000000000038';$second['request_fingerprint']=str_repeat('5',64);$second['expected_application_sequence']=1;$second['process_event_id']=$event;$second['selected_snapshot_json']=json_encode($selected,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$secondComposition=['caseId'=>(int)$second['installation_case_id'],'compositionIdentity'=>$second['composition_identity'],'engineerUserId'=>(int)$selected['selectedEngineer']['userId'],'installers'=>[7002],'orderId'=>(int)$second['assignment_order_id']];$second['composition_sha256']=hash('sha256',json_encode($secondComposition,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    $f->insert($f->p.'fm2_assignment_order_applications',$second);
    $firstAfter=$f->rows('fm2_assignment_order_applications')[0];assertSameValue($applicationBytes,json_encode($firstAfter,JSON_THROW_ON_ERROR),'first application byte-equivalent after replacement setup');
    $replacementFacts=$f->facts();$replacement=$f->request('GET','/pilot/installers',[],$cookies);$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$replacement['body']);$xp=new DOMXPath($dom);$old=$xp->query('//tr[contains(.,"Монтажник 7001")]')->item(0);$current=$xp->query('//tr[contains(.,"Монтажник 7002")]')->item(0);
    assertSameValue(true,$old!==null&&str_contains($old->textContent,'Нет действующих закреплений')&&!str_contains($old->textContent,'TEST-4512'),'replaced installer becomes free without stale legacy mix');
    assertSameValue(true,$current!==null&&str_contains($current->textContent,'TEST-4512'),'latest application installer owns current object');
    assertSameValue($replacementFacts,$f->facts(),'replacement read preserves both application rows');
    $assignedAfter=$f->request('GET','/pilot/installers?availability=assigned',[],$cookies);assertSameValue(true,str_contains($assignedAfter['body'],'Монтажник 7002')&&!str_contains($assignedAfter['body'],'Монтажник 7001'),'replacement assigned filter');
    $freeAfter=$f->request('GET','/pilot/installers?availability=free',[],$cookies);assertSameValue(true,str_contains($freeAfter['body'],'Монтажник 7001')&&!str_contains($freeAfter['body'],'Монтажник 7002'),'replacement free filter');

    // A no-application case retains bounded registered-order compatibility.
    $f->insert($f->p.'fm2_installation_cases',['legacy_installation_object_id'=>4600,'process_state'=>'working','actual_start_date'=>'2026-09-01','opened_at'=>'2026-09-01 09:00:00','opened_by_user_id'=>18,'created_at'=>'2026-09-01 09:00:00','updated_at'=>'2026-09-01 09:00:00','lock_version'=>1]);$fallbackCase=(int)$f->db->insert_id;
    $f->insert($f->p.'fm_maintable',['id'=>4600,'regnumber'=>'LEGACY-FALLBACK-4600','ordadr_address'=>'Fallback address']);
    $f->insert($f->p.'fm2_assignment_orders',['installation_case_id'=>$fallbackCase,'version_no'=>1,'kind'=>'initial','status'=>'registered','order_date'=>'2026-09-01','registration_number'=>'ORDER-4600','registered_at'=>'2026-09-01 10:00:00','registration_actor_type'=>'user','registration_actor_id'=>'18','registration_source'=>'fixture','control_engineer_user_id'=>73,'control_engineer_fio_snapshot'=>'Инженер','control_engineer_position_snapshot'=>'Инженер','organization_form'=>'individual','object_address_snapshot'=>'Fallback address','entrance_snapshot'=>'1','object_registration_number_snapshot'=>'LEGACY-FALLBACK-4600','planned_start_date_snapshot'=>'2026-09-01','planned_finish_date_snapshot'=>'2026-12-01','prepared_at'=>'2026-09-01 09:00:00','prepared_by_user_id'=>18]);$fallbackOrder=(int)$f->db->insert_id;
    $f->insert($f->p.'fm2_order_installers',['assignment_order_id'=>$fallbackOrder,'installer_tab_id'=>7001,'fio_snapshot'=>'Монтажник 7001','position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2020-01-01','workforce_source_snapshot'=>'fixture','workforce_source_updated_at_snapshot'=>'2026-09-01T06:00:00Z','valid_from'=>'2020-01-01','change_action'=>'assign']);
    $fallback=$f->request('GET','/pilot/installers',[],$cookies);assertSameValue(true,str_contains($fallback['body'],'LEGACY-FALLBACK-4600'),'no-application case compatibility visible');$fallbackDom=new DOMDocument();@$fallbackDom->loadHTML('<?xml encoding="UTF-8">'.$fallback['body']);$fallbackXp=new DOMXPath($fallbackDom);assertSameValue(1,$fallbackXp->query('//*[@data-directory-summary="assigned" and normalize-space(.)="2"]')->length,'native plus fallback unique assigned summary');$fallbackAssigned=$f->request('GET','/pilot/installers?availability=assigned',[],$cookies);assertSameValue(true,str_contains($fallbackAssigned['body'],'Монтажник 7001')&&str_contains($fallbackAssigned['body'],'Монтажник 7002'),'assigned filter includes native and fallback union');$fallbackFree=$f->request('GET','/pilot/installers?availability=free',[],$cookies);assertSameValue(true,str_contains($fallbackFree['body'],'Ничего не найдено')&&!str_contains($fallbackFree['body'],'Монтажник 7001')&&!str_contains($fallbackFree['body'],'Монтажник 7002'),'free filter excludes both current assignment owners');

    // A second current native case for the same installer is inserted after the higher object id.
    $f->insert($f->p.'fm2_installation_cases',['id'=>6200,'legacy_installation_object_id'=>4500,'process_state'=>'needs_assignment_order','created_at'=>'2026-09-01 09:00:00','updated_at'=>'2026-09-01 09:00:00','lock_version'=>1]);
    $f->insert($f->p.'fm_maintable',['id'=>4500,'regnumber'=>'NATIVE-SECOND-4500','ordadr_address'=>'Second native address']);
    $identity=$f->rows('fm2_assignment_order_identities')[0];$identity['assignment_order_id']=82;$identity['installation_case_id']=6200;$identity['order_version']=2;$f->insert($f->p.'fm2_assignment_order_identities',$identity);
    $f->insert($f->p.'fm2_process_events',['installation_case_id'=>6200,'event_type'=>'assignment_order_composition_applied','occurred_at'=>'2026-09-02 09:00:00','actor_user_id'=>18,'payload_json'=>'{}']);$secondEvent=(int)$f->db->insert_id;
    $other=$second;unset($other['application_id']);$other['installation_case_id']=6200;$other['object_id']=4500;$other['application_sequence']=1;$other['assignment_order_id']=82;$other['order_version']=2;$other['composition_identity']='composition-82-v2';$otherComposition=['caseId'=>6200,'compositionIdentity'=>$other['composition_identity'],'engineerUserId'=>(int)$selected['selectedEngineer']['userId'],'installers'=>[7002],'orderId'=>82];$other['composition_sha256']=hash('sha256',json_encode($otherComposition,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));$other['previous_application_id']=null;$other['kind']='new_order';$other['request_id']='77777777-7777-4777-8777-000000000038';$other['request_fingerprint']=str_repeat('7',64);$other['expected_application_sequence']=0;$other['process_event_id']=$secondEvent;$f->insert($f->p.'fm2_assignment_order_applications',$other);
    $multiFacts=$f->facts();$multi=$f->request('GET','/pilot/installers',[],$cookies);$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$multi['body']);$xp=new DOMXPath($dom);$multiRow=$xp->query('//tr[contains(.,"Монтажник 7002")]')->item(0);$links=$xp->query('.//a[contains(@class,"fm2-assignment-link")]',$multiRow);
    assertSameValue(['/pilot/objects/4500','/pilot/objects/4512'],[$links->item(0)?->getAttribute('href'),$links->item(1)?->getAttribute('href')],'two native cases exactly once in object order');
    assertSameValue(1,$xp->query('//*[@data-directory-summary="assigned" and normalize-space(.)="2"]')->length,'two unique installers across native cases plus fallback');
    $repeat=$f->request('GET','/pilot/installers',[],$cookies);$repeatDom=new DOMDocument();@$repeatDom->loadHTML('<?xml encoding="UTF-8">'.$repeat['body']);$repeatXp=new DOMXPath($repeatDom);$repeatRow=$repeatXp->query('//tr[contains(.,"Монтажник 7002")]')->item(0);$repeatLinks=$repeatXp->query('.//a[contains(@class,"fm2-assignment-link")]',$repeatRow);assertSameValue([$multiRow?->textContent,'/pilot/objects/4500','/pilot/objects/4512'],[$repeatRow?->textContent,$repeatLinks->item(0)?->getAttribute('href'),$repeatLinks->item(1)?->getAttribute('href')],'repeated directory data stable despite rotating CSRF');assertSameValue($multiFacts,$f->facts(),'multi-case repeated reads preserve facts');

    $f->db->query("UPDATE {$f->p}fm2_workforce_catalog SET reconciliation_state='missing_from_delivery'");$empty=$f->request('GET','/pilot/installers?q=empty-catalog',[],$cookies);assertSameValue(true,str_contains($empty['body'],'Каталог монтажников пока не загружен'),'distinct empty workforce catalog');$f->db->query("UPDATE {$f->p}fm2_workforce_catalog SET reconciliation_state='delivered' WHERE installer_tab_id IN(7001,7002)");

    $validSnapshot=json_decode($second['selected_snapshot_json'],true,flags:JSON_THROW_ON_ERROR);$duplicate=$validSnapshot;$duplicate['selectedInstallers'][]=$duplicate['selectedInstallers'][0];$unsorted=$validSnapshot;$lower=$unsorted['selectedInstallers'][0];$lower['tabId']=7001;$unsorted['selectedInstallers'][]=$lower;$malformed=['top-level'=>[], 'tab-id-type'=>array_replace_recursive($validSnapshot,['selectedInstallers'=>[['tabId'=>'7002junk']]]), 'installer-name'=>array_replace_recursive($validSnapshot,['selectedInstallers'=>[['fullName'=>'']]]), 'installer-position'=>array_replace_recursive($validSnapshot,['selectedInstallers'=>[['position'=>'']]]), 'engineer-container'=>array_replace($validSnapshot,['selectedEngineer'=>null]), 'engineer-id-zero'=>array_replace_recursive($validSnapshot,['selectedEngineer'=>['userId'=>0]]), 'engineer-id-mismatch'=>array_replace_recursive($validSnapshot,['selectedEngineer'=>['userId'=>74]]), 'engineer-name'=>array_replace_recursive($validSnapshot,['selectedEngineer'=>['fullName'=>'']]), 'engineer-position'=>array_replace_recursive($validSnapshot,['selectedEngineer'=>['position'=>'']]), 'duplicate-installers'=>$duplicate, 'unsorted-installers'=>$unsorted];foreach($malformed as$label=>$value){$encoded=$f->db->real_escape_string(json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));$hash=$second['composition_sha256'];if(is_array($value['selectedEngineer']??null)&&is_array($value['selectedInstallers']??null)){$composition=['caseId'=>6101,'compositionIdentity'=>$second['composition_identity'],'engineerUserId'=>(int)($value['selectedEngineer']['userId']??0),'installers'=>array_map(static fn(array$x):int=>(int)($x['tabId']??0),$value['selectedInstallers']),'orderId'=>(int)$second['assignment_order_id']];$hash=hash('sha256',json_encode($composition,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));}$f->db->query("UPDATE {$f->p}fm2_assignment_order_applications SET selected_snapshot_json='$encoded',composition_sha256='$hash' WHERE installation_case_id=6101 AND application_sequence=2");$badFacts=$f->facts();$bad=$f->request('GET','/pilot/installers',[],$cookies);assertSameValue([503,'60'],[$bad['status'],$bad['headers']['retry-after'][0]??null],$label.' malformed current snapshot fails closed');$badHead=$f->request('HEAD','/pilot/installers',[],$cookies);assertSameValue([503,'60',''],[$badHead['status'],$badHead['headers']['retry-after'][0]??null,$badHead['body']],$label.' malformed snapshot HEAD fails closed');assertSameValue(false,str_contains($bad['body'],'TEST-4512')||str_contains($bad['body'],$f->p),$label.' failure hides stale/internal data');assertSameValue($badFacts,$f->facts(),$label.' failure changes no facts');}
    $restoreSnapshot=$f->db->real_escape_string($second['selected_snapshot_json']);$f->db->query("UPDATE {$f->p}fm2_assignment_order_applications SET selected_snapshot_json='$restoreSnapshot',composition_sha256='{$second['composition_sha256']}' WHERE installation_case_id=6101 AND application_sequence=2");
    foreach(['composition_identity'=>"'composition-wrong-v1'",'composition_sha256'=>"'".str_repeat('0',64)."'"]as$column=>$value){$original=$f->db->real_escape_string((string)$second[$column]);$f->db->query("UPDATE {$f->p}fm2_assignment_order_applications SET $column=$value WHERE installation_case_id=6101 AND application_sequence=2");$badFacts=$f->facts();$bad=$f->request('GET','/pilot/installers',[],$cookies);$badHead=$f->request('HEAD','/pilot/installers',[],$cookies);assertSameValue([503,'60',503,'60',''],[$bad['status'],$bad['headers']['retry-after'][0]??null,$badHead['status'],$badHead['headers']['retry-after'][0]??null,$badHead['body']],$column.' mismatch fails closed for GET HEAD');assertSameValue(false,str_contains($bad['body'],'TEST-4512')||str_contains($bad['body'],$f->p),$column.' mismatch sanitized');assertSameValue($badFacts,$f->facts(),$column.' mismatch changes no facts');$f->db->query("UPDATE {$f->p}fm2_assignment_order_applications SET $column='$original' WHERE installation_case_id=6101 AND application_sequence=2");}
    $table=$f->p.'fm2_assignment_order_applications';$f->db->query("RENAME TABLE $table TO {$table}_missing");try{$missingFacts=$f->facts();$missing=$f->request('GET','/pilot/installers',[],$cookies);$missingHead=$f->request('HEAD','/pilot/installers',[],$cookies);assertSameValue([503,'60',503,'60',''],[$missing['status'],$missing['headers']['retry-after'][0]??null,$missingHead['status'],$missingHead['headers']['retry-after'][0]??null,$missingHead['body']],'missing application schema GET HEAD fail closed');assertSameValue(false,str_contains($missing['body'],$f->p),'missing schema sanitized');assertSameValue($missingFacts,$f->facts(),'missing schema no runtime DDL or facts');}finally{$f->db->query("RENAME TABLE {$table}_missing TO $table");}
    $f->noLegacy();
    echo "PASS: YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001 native workflow regression\n";
} finally {if($f instanceof PreopeningFixture)$f->close();}
