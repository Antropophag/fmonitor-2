<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/InspectionFixture.php';
// YII2-INSPECTION-JOURNEY-001 A1-A9: literal expectations; SQL is independent history audit/fault setup only.
// CHECKLIST-HTTP-AUTHORIZATION-001 A1-A6: real authenticated requests, no shared inventory addition.
function checklistPrivateFiles(string $root):array
{
 $files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS))as$file)if($file->isFile())$files[substr($file->getPathname(),strlen($root))]=hash_file('sha256',$file->getPathname());ksort($files);return$files;
}
function checklistSafeDenial(array $response):void
{
 assertSameValue(403,$response['status'],'INTENDED_RED #130 authorization is HTTP 403');
 $body=json_decode($response['body'],true,flags:JSON_THROW_ON_ERROR);
 assertSameValue('rejected',$body['status'],'safe authorization result');
 foreach(['projection','revision','crew','items','photos','completedSections','clientOperationId','actorUserId','installers','fio','7001','7002','fixture.png']as$protected)assertSameValue(false,str_contains($response['body'],'"'.$protected.'"'),'no protected field/content '.$protected);
}
$f=null;
try {
 $f=new InspectionFixture(dirname(__DIR__,2));$f->open();$h=$f->http;
 $before=$h->facts();$page=$f->page();$projection=InspectionFixture::projection($page);$csrf=InspectionFixture::csrf($page);
 assertSameValue(0,$projection['revision'],'initial revision');assertSameValue($before,$h->facts(),'GET read-only full inventory');
 foreach(['data-checklist','data-user-id="73"','/pilot/objects/4512','data-check-item="28"','data-photo-input','data-installer-edit','data-sync-banner']as$marker)assertSameValue(true,str_contains($page['body'],$marker),'complete UI '.$marker);
 assertSameValue(false,str_contains($page['body'],'data-check-item="42"'),'documentary last15 are not item42');
 assertSameValue(true,str_contains($page['body'],'data-progress-cap="85"'),'85 cap');
 $alias=$f->page('/pilot/construction-control/objects/4512/checklist');assertSameValue(200,$alias['status'],'alias page');assertSameValue(true,str_contains($alias['body'],'href="/pilot/construction-control"'),'alias return');
 $card=$f->page('/pilot/objects/4512');assertSameValue(true,str_contains($card['body'],'/pilot/objects/4512/checklist'),'card enters checklist');
 $queue=$f->page('/pilot/construction-control');assertSameValue(200,$queue['status'],'return queue');foreach(['data-control-queue','data-control-search','data-show-completed','/pilot/construction-control/objects/4512/checklist']as$m)assertSameValue(true,str_contains($queue['body'],$m),'queue '.$m);
 $context=$f->page('/pilot/construction-control/objects/4512/sync-context');assertSameValue(200,$context['status'],'sync-context');assertSameValue(0,json_decode($context['body'],true,flags:JSON_THROW_ON_ERROR)['revision'],'context revision');
 $head=$h->request('HEAD','/pilot/objects/4512/checklist',[],$f->cookies);assertSameValue([200,''],[$head['status'],$head['body']],'HEAD');
 $guest=[];$r=$h->request('GET','/pilot/objects/4512/checklist',[],$guest);assertSameValue([303,'/pilot/login'],[$r['status'],$r['headers']['location'][0]??null],'guest login');$returned=$h->login($guest,73);assertSameValue([303,'/pilot/objects/4512/checklist'],[$returned['status'],$returned['headers']['location'][0]??null],'guest authenticates and returns safely');
 assertSameValue(404,$f->page('/pilot/objects/999999/checklist')['status'],'unknown object');assertSameValue(405,$h->request('POST','/pilot/objects/4512/checklist',[],$f->cookies)['status'],'method rejected');
 $op=InspectionFixture::operation();$op['actorUserId']=18;$r=InspectionFixture::result($f->send($op,$csrf),200,'accepted');assertSameValue(1,$r['revision'],'accepted revision1');assertSameValue(73,$r['projection']['items']['28']['actorUserId'],'server actor');
 $rows=$h->rows('fm2_checklist_operations');assertSameValue(1,count($rows),'single operation');assertSameValue(['6101','73','item_completed','28','0','1'],array_map('strval',[$rows[0]['installation_case_id'],$rows[0]['actor_user_id'],$rows[0]['operation_type'],$rows[0]['item_id'],$rows[0]['base_revision'],$rows[0]['accepted_revision']]),'literal operation identity and revision');
 assertSameValue($op['deviceTime'],$rows[0]['device_time'],'device audit retained');$installers=$h->rows('fm2_checklist_operation_installers');assertSameValue(['7001','7002'],array_column($installers,'installer_tab_id'),'independent normalized crew');
 $history=$h->facts();$duplicate=InspectionFixture::result($f->send($op,$csrf),200,'duplicate');assertSameValue(1,$duplicate['projection']['revision'],'authorized duplicate projection');assertSameValue($history,$h->facts(),'replay preserves full inventory');
 $changed=$op;$changed['itemId']=29;InspectionFixture::result($f->send($changed,$csrf),409,'conflict');assertSameValue($history,$h->facts(),'payload conflict no facts');
 $conflict=InspectionFixture::result($f->send(InspectionFixture::operation(2,0,29),$csrf),409,'conflict');assertSameValue(73,$conflict['projection']['items']['28']['actorUserId'],'authorized conflict retains current facts');assertSameValue($history,$h->facts(),'stale no facts');
 $bad=InspectionFixture::operation(3,1);$bad['installerTabIds']=[99999];$business=InspectionFixture::result($f->send($bad,$csrf),422,'rejected');assertSameValue(1,$business['projection']['revision'],'business rejection retains authorized projection');assertSameValue($history,$h->facts(),'unknown crew no facts');
 $bad=InspectionFixture::operation(3,1);$bad['itemId']=42;$blocked42=InspectionFixture::result($f->send($bad,$csrf),409,'rejected');assertSameValue('Последние 15% закрываются актом ПТО и декларацией в карточке объекта.',$blocked42['message'],'exact item42 business message');assertSameValue($history,$h->facts(),'last15 protected');
 $badCsrf=$f->send(InspectionFixture::operation(3,1),'invalid');assertSameValue(true,in_array($badCsrf['status'],[400,403],true),'native CSRF failure');assertSameValue($history,$h->facts(),'csrf no facts');
 $reader=[];assertSameValue(303,$h->login($reader,95)['status'],'read-only login');$read=$h->request('GET','/pilot/objects/4512/checklist',[],$reader);assertSameValue(200,$read['status'],'checklist.read only');assertSameValue(true,str_contains($read['body'],'data-item-completion-enabled="false"'),'read only controls disabled');
 $readerToken=InspectionFixture::csrf($read);$before=$h->facts();$denied=$f->send(InspectionFixture::operation(3,1),$readerToken,$reader);checklistSafeDenial($denied);assertSameValue($before,$h->facts(),'reader auth refusal has no facts');
 checklistSafeDenial($f->send(InspectionFixture::operation(137,1,42),$readerToken,$reader));assertSameValue($before,$h->facts(),'reader item42 authorization precedes documentary business shortcut');
 foreach(['item_installers_changed','section_completed','completion_retracted','photo_revoked']as$type){
  $attempt=array_replace(InspectionFixture::operation(140,1),['type'=>$type,'originalClientOperationId'=>$op['clientOperationId'],'photoId'=>999,'reason'=>'fixture refusal']);
  checklistSafeDenial($f->send($attempt,$readerToken,$reader));assertSameValue($before,$h->facts(),'reader '.$type.' authorization refusal has no facts');
 }
 $none=[];$h->login($none,96);assertSameValue(403,$h->request('GET','/pilot/objects/4512/checklist',[],$none)['status'],'neighbor permission no read');
 $noneToken=$h->token($none); // Valid CSRF obtained from allowed objects page, never from forbidden checklist.
 $before=$h->facts();$privateBefore=checklistPrivateFiles($h->base->privateRoot);
 $invalidCrew=InspectionFixture::operation(131,1);$invalidCrew['installerTabIds']=[99999];
 foreach([$op,InspectionFixture::operation(132,1,29),InspectionFixture::operation(133,0,29),$invalidCrew,InspectionFixture::operation(138,1,42)]as$attempt){
  foreach(['/pilot/objects/4512/checklist/operations','/pilot/construction-control/objects/4512/checklist/operations']as$path){
   checklistSafeDenial($h->request('POST',$path,[],$none,['Content-Type: application/json','X-FM2-CSRF: '.$noneToken],json_encode($attempt,JSON_THROW_ON_ERROR)));
   assertSameValue($before,$h->facts(),'no-read request preserves all tables');assertSameValue($privateBefore,checklistPrivateFiles($h->base->privateRoot),'no-read request preserves private artifacts');
  }
 }
 // Formal assignment/action capability do not grant read. This reaches response branches even after command authorization is fixed.
 $assignedEngineer=$h->rows('fm2_assignment_order_applications')[0]['control_engineer_user_id'];
 $h->insert($h->p.'fm2_pilot_role_permissions',['role_id'=>6,'permission'=>'inspection.photo.revoke']);
 $h->db->query("UPDATE {$h->p}fm2_assignment_order_applications SET control_engineer_user_id=96 WHERE installation_case_id=6101");
 try{
  assertSameValue(403,$h->request('GET','/pilot/objects/4512/checklist',[],$none)['status'],'photo revoke grant does not grant read');$before=$h->facts();
  foreach(['photo_revoked','completion_retracted']as$type){
   foreach([[$op['clientOperationId'],1],[InspectionFixture::operation(134)['clientOperationId'],999],[InspectionFixture::operation(135)['clientOperationId'],1]]as[$identity,$base]){
    $attempt=array_replace(InspectionFixture::operation(136),['type'=>$type,'clientOperationId'=>$identity,'baseRevision'=>$base,'photoId'=>999,'originalClientOperationId'=>InspectionFixture::operation(139)['clientOperationId'],'reason'=>'fixture denial']);
    checklistSafeDenial($f->send($attempt,$noneToken,$none));assertSameValue($before,$h->facts(),'no-read '.$type.' response has no new facts');assertSameValue($privateBefore,checklistPrivateFiles($h->base->privateRoot),'no-read '.$type.' preserves artifacts');
   }
  }
 }finally{$h->db->query("DELETE FROM {$h->p}fm2_pilot_role_permissions WHERE role_id=6 AND permission='inspection.photo.revoke'");$h->db->prepare("UPDATE {$h->p}fm2_assignment_order_applications SET control_engineer_user_id=? WHERE installation_case_id=6101")->execute([$assignedEngineer]);}
 $before=$h->facts();$malformed=$h->request('POST','/pilot/objects/4512/checklist/operations',[],$f->cookies,['Content-Type: application/json','X-FM2-CSRF: '.$csrf],'{');assertSameValue(400,$malformed['status'],'malformed JSON');assertSameValue($before,$h->facts(),'malformed no facts');
 // The complete route cohort uses one permission/session/transport contract.
 foreach(['/pilot/construction-control','/pilot/construction-control/objects/4512/sync-context','/pilot/construction-control/objects/4512/checklist']as$path){$r=$h->request('HEAD',$path,[],$f->cookies);assertSameValue([200,''],[$r['status'],$r['body']],'cohort HEAD '.$path);}
 foreach(['/pilot/objects/4512/checklist/operations','/pilot/objects/4512/checklist/photos','/pilot/construction-control/objects/4512/checklist/operations','/pilot/construction-control/objects/4512/checklist/photos']as$path){$r=$h->request('GET',$path,[],$f->cookies);assertSameValue([405,'POST'],[$r['status'],$r['headers']['allow'][0]??null],'mutation method Allow '.$path);}
 foreach(['0','04512','-1','4512x','999999999999999999999999']as$id)assertSameValue(404,$f->page('/pilot/objects/'.$id.'/checklist')['status'],'canonical ID '.$id);
 $before=$h->facts();assertSameValue(403,$h->request('GET','/pilot/construction-control',[],$reader)['status'],'queue exact permission');assertSameValue($before,$h->facts(),'queue denial no facts');
 foreach([['application/json','{}',[],[400,403]],['text/plain','{}',['X-FM2-CSRF: '.$csrf],[400]],['application/json',str_repeat('x',32769),['X-FM2-CSRF: '.$csrf],[413]]]as[$type,$raw,$headers,$statuses]){$before=$h->facts();$r=$h->request('POST','/pilot/objects/4512/checklist/operations',[],$f->cookies,array_merge(['Content-Type: '.$type],$headers),$raw);assertSameValue(true,in_array($r['status'],$statuses,true),'bounded request '.$type.' '.strlen($raw));assertSameValue($before,$h->facts(),'transport failure no facts');}
 $before=$h->facts();$invalid=InspectionFixture::operation(90,1);$invalid['deviceInstallationId']='bad';InspectionFixture::result($f->send($invalid,$csrf),422,'rejected');assertSameValue($before,$h->facts(),'invalid command no facts');
 $unknown=InspectionFixture::operation(91,1,999);InspectionFixture::result($f->send($unknown,$csrf),422,'rejected');assertSameValue($before,$h->facts(),'unknown template item no facts');
 $association=$h->rows('fm2_checklist_template_associations');$h->db->query("DELETE FROM {$h->p}fm2_checklist_template_associations WHERE subject_kind='operational_case' AND subject_id='6101'");
 try{$before=$h->facts();InspectionFixture::result($f->send(InspectionFixture::operation(92,1),$csrf),422,'rejected');assertSameValue($before,$h->facts(),'missing template no facts');}finally{foreach($association as$row)$h->insert($h->p.'fm2_checklist_template_associations',$row);}
 $h->db->query("UPDATE {$h->p}fm2_installation_cases SET legacy_installation_object_id=999999 WHERE id=6101");
 try{$before=$h->facts();$missingCase=$f->send(InspectionFixture::operation(94,1),$csrf);assertSameValue(404,$missingCase['status'],'no current public installation case');assertSameValue($before,$h->facts(),'no-current-case no facts');}finally{$h->db->query("UPDATE {$h->p}fm2_installation_cases SET legacy_installation_object_id=4512 WHERE id=6101");}
 // Native owner replay precedence is also exercised after mutable crew drift through the same HTTP path.
 $application=$h->rows('fm2_assignment_order_applications')[0];
 $caseState=$h->rows('fm2_installation_cases')[0]['process_state'];$h->db->query("UPDATE {$h->p}fm2_installation_cases SET process_state='needs_assignment_change' WHERE id=6101");
 try{$before=$h->facts();InspectionFixture::result($f->send(InspectionFixture::operation(93,1),$csrf),422,'rejected');InspectionFixture::result($f->send($op,$csrf),200,'duplicate');assertSameValue($before,$h->facts(),'nonworking first receipt rejected, existing replay immutable');}finally{$h->db->prepare("UPDATE {$h->p}fm2_installation_cases SET process_state=? WHERE id=6101")->execute([$caseState]);}
 // Coherent neighboring corrections/photo/section path, preserving original evidence.
 $correction=InspectionFixture::operation(4,1);$correction['type']='item_installers_changed';$correction['installerTabIds']=[7002];$r=InspectionFixture::result($f->send($correction,$csrf),200,'accepted');assertSameValue(2,$r['revision'],'correction revision');assertSameValue($rows[0],$h->rows('fm2_checklist_operations')[0],'completion original immutable');
 $retract=InspectionFixture::operation(5,2);$retract['type']='completion_retracted';$retract['originalClientOperationId']=$op['clientOperationId'];$retract['reason']='Ошибка отметки';$r=InspectionFixture::result($f->send($retract,$csrf),200,'accepted');assertSameValue(false,isset($r['projection']['items']['28']),'retracted item absent');
 $revision=$r['revision'];foreach(range(28,36)as$item){$r=InspectionFixture::result($f->send(InspectionFixture::operation(10+$item,$revision,$item),$csrf),200,'accepted');$revision=$r['revision'];}
 $section=InspectionFixture::operation(70,$revision);$section['type']='section_completed';unset($section['itemId'],$section['installerTabIds']);$before=$h->facts();InspectionFixture::result($f->send($section,$csrf),422,'rejected');assertSameValue($before,$h->facts(),'section requires photo');
 $png=InspectionFixture::png();$photo=InspectionFixture::operation(71,$revision);$photo=array_replace($photo,['type'=>'photo_uploaded','mime'=>'image/png','size'=>strlen($png),'sha256'=>hash('sha256',$png),'originalName'=>'fixture.png']);unset($photo['itemId'],$photo['installerTabIds']);
 $badPhoto=$photo;$badPhoto['sha256']=str_repeat('0',64);InspectionFixture::result($f->send($badPhoto,$csrf,bytes:$png),422,'rejected');assertSameValue($before,$h->facts(),'bad hash no facts');
 $r=InspectionFixture::result($f->send($photo,$csrf,bytes:$png),200,'accepted');$revision=$r['revision'];assertSameValue(1,count($r['projection']['photos']),'photo projection');$photoId=$r['projection']['photos'][0]['id'];$state=$h->facts();InspectionFixture::result($f->send($photo,$csrf,bytes:$png),200,'duplicate');assertSameValue($state,$h->facts(),'photo replay no facts');
 $photoRows=$h->rows('fm2_checklist_photos');assertSameValue([hash('sha256',$png),(string)strlen($png),'73'],[$photoRows[0]['sha256'],(string)$photoRows[0]['byte_size'],(string)$photoRows[0]['actor_user_id']],'persisted private photo evidence');assertSameValue($png,file_get_contents($h->base->privateRoot.'/checklist/'.$photoRows[0]['storage_name']),'exact private photo bytes');
 $before=$h->facts();$privateBefore=checklistPrivateFiles($h->base->privateRoot);
 foreach(['/pilot/objects/4512/checklist/photos','/pilot/construction-control/objects/4512/checklist/photos']as$path){
  checklistSafeDenial($h->request('POST',$path,[],$none,['Content-Type: image/png','X-FM2-CSRF: '.$noneToken,'X-FM2-Operation: '.base64_encode(json_encode($photo,JSON_THROW_ON_ERROR))],$png));
  assertSameValue($before,$h->facts(),'forbidden photo replay preserves facts');assertSameValue($privateBefore,checklistPrivateFiles($h->base->privateRoot),'forbidden photo preserves bytes');
 }
 $distinct=$photo;$distinct['clientOperationId']=InspectionFixture::operation(73)['clientOperationId'];$state=$h->facts();InspectionFixture::result($f->send($distinct,$csrf,bytes:$png),200,'duplicate');assertSameValue($state,$h->facts(),'same bytes distinct intention deduplicated');
 $wrongMime=$photo;$wrongMime['clientOperationId']=InspectionFixture::operation(74)['clientOperationId'];$wrongMime['mime']='image/jpeg';InspectionFixture::result($f->send($wrongMime,$csrf,bytes:$png),422,'rejected');assertSameValue($state,$h->facts(),'MIME mismatch no facts');
 $large=$photo;$large['size']=5*1024*1024+1;$large['clientOperationId']=InspectionFixture::operation(75)['clientOperationId'];$largeBytes=str_repeat('x',$large['size']);$large['sha256']=hash('sha256',$largeBytes);$tooLarge=$f->send($large,$csrf,bytes:$largeBytes);assertSameValue(413,$tooLarge['status'],'photo transport size');assertSameValue($state,$h->facts(),'oversize no facts');
 // Both write aliases hit the same persisted operation family.
 $aliasResult=$h->request('POST','/pilot/construction-control/objects/4512/checklist/operations',[],$f->cookies,['Content-Type: application/json','X-FM2-CSRF: '.$csrf],json_encode($op,JSON_THROW_ON_ERROR));InspectionFixture::result($aliasResult,200,'duplicate');
 $aliasPhoto=$h->request('POST','/pilot/construction-control/objects/4512/checklist/photos',[],$f->cookies,['Content-Type: image/png','X-FM2-CSRF: '.$csrf,'X-FM2-Operation: '.base64_encode(json_encode($photo,JSON_THROW_ON_ERROR))],$png);InspectionFixture::result($aliasPhoto,200,'duplicate');assertSameValue($state,$h->facts(),'aliases same evidence');
 $section['baseRevision']=$revision;$r=InspectionFixture::result($f->send($section,$csrf),200,'accepted');assertSameValue(true,isset($r['projection']['completedSections']['1']),'section complete');
 $revoke=InspectionFixture::operation(72,$r['revision']);$revoke=array_replace($revoke,['type'=>'photo_revoked','photoId'=>$photoId,'reason'=>'Заменить снимок']);$before=$h->facts();InspectionFixture::result($f->send($revoke,$csrf),422,'rejected');assertSameValue($before,$h->facts(),'last completed photo protected');
 // Ten-active limit, positive revoke, exact capability and assignment are separate observable obligations.
 $revision=$r['revision'];for($i=2;$i<=10;$i++){$bytes=InspectionFixture::png($i);$next=array_replace($photo,['clientOperationId'=>InspectionFixture::operation(100+$i)['clientOperationId'],'baseRevision'=>$revision,'sha256'=>hash('sha256',$bytes),'size'=>strlen($bytes)]);$photoResult=InspectionFixture::result($f->send($next,$csrf,bytes:$bytes),200,'accepted');$revision=$photoResult['revision'];}
 $bytes=InspectionFixture::png(11);$eleven=array_replace($photo,['clientOperationId'=>InspectionFixture::operation(111)['clientOperationId'],'baseRevision'=>$revision,'sha256'=>hash('sha256',$bytes),'size'=>strlen($bytes)]);$before=$h->facts();InspectionFixture::result($f->send($eleven,$csrf,bytes:$bytes),422,'rejected');assertSameValue($before,$h->facts(),'ten active photo limit');
 $photoDenied=$f->send($eleven,$readerToken,$reader,$bytes);assertSameValue(403,$photoDenied['status'],'read-only photo denied');assertSameValue($before,$h->facts(),'photo admission no facts');
 $revoke['baseRevision']=$revision;$h->db->query("DELETE FROM {$h->p}fm2_pilot_role_permissions WHERE role_id=2 AND permission='inspection.photo.revoke'");$before=$h->facts();checklistSafeDenial($f->send($revoke,$csrf));assertSameValue($before,$h->facts(),'assigned engineer still requires revoke capability');$h->insert($h->p.'fm2_pilot_role_permissions',['role_id'=>2,'permission'=>'inspection.photo.revoke']);
 $manager=[];$h->login($manager,97);$managerPage=$h->request('GET','/pilot/objects/4512/checklist',[],$manager);$managerToken=InspectionFixture::csrf($managerPage);$h->insert($h->p.'fm2_pilot_role_permissions',['role_id'=>7,'permission'=>'inspection.photo.revoke']);$before=$h->facts();checklistSafeDenial($f->send($revoke,$managerToken,$manager));assertSameValue($before,$h->facts(),'unassigned manager cannot revoke despite capability');
 $r=InspectionFixture::result($f->send($revoke,$csrf),200,'accepted');assertSameValue(9,count($r['projection']['photos']),'assigned authorized revoke succeeds with replacement');assertSameValue($png,file_get_contents($h->base->privateRoot.'/checklist/'.$photoRows[0]['storage_name']),'revocation retains private bytes');
 $eleven['baseRevision']=$r['revision'];$r=InspectionFixture::result($f->send($eleven,$managerToken,$manager,$bytes),200,'accepted');assertSameValue(10,count($r['projection']['photos']),'active manager can upload after one revoke');
 $before=$h->facts();$fresh=InspectionFixture::projection($f->page());assertSameValue($r['revision'],$fresh['revision'],'refresh retained');assertSameValue($before,$h->facts(),'refresh no writes');
 // DB fault proves rollback of the full item mutation, not just response status.
 $trigger=$h->p.'inspection_fault';$h->db->query("CREATE TRIGGER $trigger BEFORE INSERT ON {$h->p}fm2_checklist_operation_installers FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic inspection failure'");
 try{$before=$h->facts();$fault=InspectionFixture::operation(80,$fresh['revision'],37);$fault['sectionId']=2;InspectionFixture::result($f->send($fault,$csrf),503,'retryable');assertSameValue($before,$h->facts(),'transaction failure rollback');}finally{$h->db->query("DROP TRIGGER $trigger");}
 // Process restart preserves identity and sync-context supplies a usable Yii CSRF token.
 $before=$h->facts();proc_terminate($h->server['process']);proc_close($h->server['process']);$h->server=null;$h->start();
 $context=$f->page('/pilot/construction-control/objects/4512/sync-context');assertSameValue(200,$context['status'],'checklist session survives process restart');$context=json_decode($context['body'],true,flags:JSON_THROW_ON_ERROR);assertSameValue($fresh['revision'],$context['revision'],'restart retains revision');$csrf=$context['csrf'];InspectionFixture::result($f->send($op,$csrf),200,'duplicate');assertSameValue($before,$h->facts(),'restart sync token replay no facts');
 $h->db->query("DELETE FROM {$h->p}fm2_pilot_role_permissions WHERE role_id=2 AND permission='inspection.item.complete'");$before=$h->facts();$denied=$f->send($op,$csrf);checklistSafeDenial($denied);assertSameValue($before,$h->facts(),'revoked replay no facts');
 // Required storage absence fails closed with the current facts retained and no repair.
 $table=$h->p.'fm2_checklist_photos';$saved=$table.'_saved';$h->db->query("RENAME TABLE `$table` TO `$saved`");
 try{$before=$h->facts();assertSameValue(503,$f->page()['status'],'required schema unavailable');assertSameValue($before,$h->facts(),'read never repairs schema');}finally{$h->db->query("RENAME TABLE `$saved` TO `$table`");}
 // Queue pagination is exercised on the real Yii route, independently fixed 51 rows.
 $case=$h->rows('fm2_installation_cases')[0];$object=$h->db->query("SELECT * FROM {$h->p}fm_maintable WHERE id=4512")->fetch_assoc();
 for($i=1;$i<=50;$i++){$copy=$object;$copy['id']=4512+$i;$copy['regnumber']='QUEUE-'.$i;$h->insert($h->p.'fm_maintable',$copy);$copy=$case;$copy['id']=6101+$i;$copy['legacy_installation_object_id']=4512+$i;$h->insert($h->p.'fm2_installation_cases',$copy);}
 $activity=$h->rows('fm2_checklist_operations')[0];$activity['id']=99999;$activity['installation_case_id']=6102;$activity['client_operation_id']=InspectionFixture::operation(999)['clientOperationId'];$activity['device_time']='2099-01-01T00:00:00+03:00';$activity['server_received_at']='2000-01-01T00:00:00+03:00';$h->insert($h->p.'fm2_checklist_operations',$activity);
 $queue2=$f->page('/pilot/construction-control?ownership=all&completed=1&page=2');assertSameValue(200,$queue2['status'],'queue second page');assertSameValue(1,preg_match_all('/data-control-row\b/',$queue2['body']),'one remaining queue row');assertSameValue(true,str_contains($queue2['body'],'data-object-id="4513"'),'queue orders by device activity, not server receipt');assertSameValue(true,str_contains($queue2['body'],'page=1'),'queue previous page');
 assertSameValue(true,str_contains($queue2['body'],'class="fm2-activity-date">01.01.2099<'),'queue renders human Moscow date');assertSameValue(true,str_contains($queue2['body'],'class="fm2-activity-time">00:00<'),'queue renders time separately');assertSameValue(false,str_contains($queue2['body'],'>2099-01-01T00:00:00+03:00<'),'queue hides technical timestamp');foreach(['shlz-segment__input','shlz-segment__label']as$marker)assertSameValue(true,str_contains($queue2['body'],$marker),'queue uses shlz segment contract '.$marker);
 $guest=[];$guestPage=$h->request('GET','/pilot/login',[],$guest);$guestToken=$h->csrf($guestPage['body']);
 $h->db->query("UPDATE {$h->p}fm2_pilot_users SET status=0 WHERE user_id=73");$before=$h->facts();$privateBefore=checklistPrivateFiles($h->base->privateRoot);
 foreach([[$f->cookies,$csrf],[$guest,$guestToken]]as[$jar,$token]){
  $blocked=$f->send($op,$token,$jar);assertSameValue([303,'/pilot/login'],[$blocked['status'],$blocked['headers']['location'][0]??null],'inactive or guest redirected');
  foreach(['projection','crew','photos','7001','7002','fixture.png']as$protected)assertSameValue(false,str_contains($blocked['body'],$protected),'inactive or guest no protected content');
  assertSameValue($before,$h->facts(),'inactive or guest no facts');assertSameValue($privateBefore,checklistPrivateFiles($h->base->privateRoot),'inactive or guest no artifact change');
 }
 $h->noLegacy();echo "PASS: YII2-INSPECTION-JOURNEY-001 complete HTTP cohort\n";
}finally{if($f instanceof InspectionFixture)$f->close();}
