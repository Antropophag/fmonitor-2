<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';
// YII2-PREOPENING-JOURNEY-001: every transport rechecks current server-side authority.
$f=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));
    assertSameValue('selected',$f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'native selection setup');
    $original=$f->nativeOriginal();
    assertSameValue('accepted',$original->status()->value,'native original setup');
    $f->start();$cookies=[];$f->login($cookies);
    $card=$f->request('GET','/pilot/objects/4512',[],$cookies);
    assertSameValue(200,$card['status'],'INTENDED_RED authorized Yii card');
    $base='/pilot/objects/4512';$order=$base.'/assignment-orders/81';
    $routes=[$base,$base.'/assignment-order/selection',$base.'/assignment-order/installers?q=7001&page=1',$order.'/originals/submit',$order.'/originals/history',$order.'/originals/'.$original->currentRevisionId().'/download',$base.'/execution'];
    foreach([
        [$base.'/assignment-order/selection','DELETE','GET, HEAD, POST'],
        [$base.'/assignment-order/installers?q=7001&page=1','POST','GET, HEAD'],
        [$order.'/template','GET','POST'],
        [$order.'/originals/submit','POST','GET, HEAD'],
        [$order.'/originals','GET','POST'],
        [$order.'/originals/history','POST','GET, HEAD'],
        [$order.'/originals/'.$original->currentRevisionId().'/download','POST','GET, HEAD'],
        [$base.'/execution','DELETE','GET, HEAD, POST'],
    ] as [$route,$method,$allow]) {
        $before=$f->facts();$response=$f->request($method,$route,[],$cookies,['X-CSRF-Token: '.$f->token($cookies)]);
        assertSameValue(405,$response['status'],'recognized method rejection '.$route);
        $actual=array_map('trim',explode(',',$response['headers']['allow'][0]??''));$expected=explode(', ',$allow);sort($actual);sort($expected);
        assertSameValue($expected,$actual,'complete Allow methods');assertSameValue($before,$f->facts(),'wrong method no facts');
    }
    foreach(['/assignment-order/selection','/assignment-order/installers?q=7001&page=1','/assignment-orders/81/template','/assignment-orders/81/originals/submit','/assignment-orders/81/originals/history','/execution'] as $suffix) {
        foreach(['04512','-1','9223372036854775808'] as $id)
            assertSameValue(404,$f->request('GET','/pilot/objects/'.$id.$suffix,[],$cookies)['status'],'strict identity across route families');
    }
    foreach(['blocked','invited','session-version','inactive-role'] as $change) {
        $session=[];$f->login($session);$csrf=$f->token($session);
        $sql=match($change){
            'blocked'=>"UPDATE {$f->p}fm2_pilot_users SET status=0 WHERE user_id=18",
            'invited'=>"UPDATE {$f->p}fm2_pilot_users SET activation_state='invited' WHERE user_id=18",
            'session-version'=>"UPDATE {$f->p}fm2_pilot_users SET session_version=session_version+1 WHERE user_id=18",
            default=>"UPDATE {$f->p}fm2_pilot_roles SET status=0 WHERE role_id=1",
        };
        $f->db->query($sql);$before=$f->facts();$files=$f->base->privateFiles();
        try {
            foreach($routes as $route) {
                $response=$f->request('GET',$route,[],$session);
                assertSameValue($change==='inactive-role'?403:303,$response['status'],'current identity revoked '.$change.' '.$route);
                if($change!=='inactive-role')assertSameValue(true,str_starts_with($response['headers']['location'][0]??'','/pilot/login'),'safe login return');
            }
            foreach([$base.'/assignment-order/selection',$order.'/template',$base.'/execution'] as $route)
                assertSameValue($change==='inactive-role'?403:303,$f->form($route,['_csrf'=>$csrf,'actorId'=>'97'],$session)['status'],'revoked command admission '.$change);
            $rawMetadata=['csrfToken'=>$csrf,'requestId'=>'22222222-2222-4222-8222-000000000097','mode'=>'initial','documentDate'=>'2026-09-01','compositionConfirmed'=>true,'rootOriginalId'=>null,'targetRevisionId'=>null,'expectedCurrentRevisionId'=>null,'correctionReason'=>null,'originalFilename'=>'signed.pdf'];
            $raw=$f->request('POST',$order.'/originals',[],$session,['Content-Type: application/pdf','X-CSRF-Token: '.$csrf,'X-FMonitor-Original: '.base64_encode(json_encode($rawMetadata,JSON_THROW_ON_ERROR))],FMonitor2\Tests\Support\SelectedOriginalFixture::pdf());assertSameValue($change==='inactive-role'?403:303,$raw['status'],'revoked raw upload admission');
            assertSameValue($before,$f->facts(),'revocation changes no process facts');
            assertSameValue($files,$f->base->privateFiles(),'revocation preserves private bytes');
        } finally {
            $f->db->query("UPDATE {$f->p}fm2_pilot_users SET status=1,activation_state='active' WHERE user_id=18");
            $f->db->query("UPDATE {$f->p}fm2_pilot_roles SET status=1 WHERE role_id=1");
        }
    }
    $cookies=[];$f->login($cookies);
    foreach([
        ['assignment_order.composition.select',$base.'/assignment-order/selection'],
        ['assignment_order.original.read',$order.'/originals/history'],
        ['objects.read',$base],
    ] as [$permission,$route]) {
        $s=$f->db->prepare("DELETE FROM {$f->p}fm2_pilot_role_permissions WHERE role_id=1 AND permission=?");$s->execute([$permission]);
        $f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>$permission.'.extra']);
        try {$before=$f->facts();assertSameValue(403,$f->request('GET',$route,[],$cookies)['status'],'exact capability required '.$permission);assertSameValue($before,$f->facts(),'capability denial no facts');}
        finally {$s->execute([$permission.'.extra']);$f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>$permission]);}
    }
    $f->db->query("UPDATE {$f->p}fm2_pilot_roles SET code='custom_fkr_operator' WHERE role_id=1");
    try {$before=$f->facts();assertSameValue(403,$f->request('GET',$base.'/assignment-order/selection',[],$cookies)['status'],'custom role label and exact cap do not grant builtin process policy');assertSameValue(403,$f->selection($cookies)['status'],'custom role cannot save');assertSameValue(403,$f->form($order.'/template',['_csrf'=>$f->token($cookies)],$cookies)['status'],'custom role cannot generate');assertSameValue(403,$f->upload($cookies,$f->metadata($cookies))['status'],'custom role cannot upload');assertSameValue($before,$f->facts(),'custom role denial no facts');}
    finally {$f->db->query("UPDATE {$f->p}fm2_pilot_roles SET code='fkr_operator' WHERE role_id=1");}
    $opening=['_csrf'=>$f->token($cookies),'action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000098','orderId'=>'81','revisionId'=>$original->currentRevisionId(),'sequence'=>'0','actualStartDate'=>'2026-09-02'];
    foreach(['assignment_order.composition.select','assignment_order.original.upload','assignment_order.original.correct','installation.open']as$permission) {
        $remove=$f->db->prepare("DELETE FROM {$f->p}fm2_pilot_role_permissions WHERE role_id=1 AND permission=?");$remove->execute([$permission]);
        $f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>$permission.'.extra']);
        try {
            $before=$f->facts();$files=$f->base->privateFiles();
            if($permission==='assignment_order.composition.select') {
                $result=$f->form($order.'/template',['_csrf'=>$f->token($cookies)],$cookies);
                assertSameValue(403,$f->selection($cookies)['status'],'selection save exact capability');
            } elseif($permission==='installation.open')$result=$f->form($base.'/execution',$opening,$cookies);
            else {
                $metadata=$f->metadata($cookies,'22222222-2222-4222-8222-000000000090');
                if($permission==='assignment_order.original.correct')$metadata=array_replace($metadata,['mode'=>'correction','rootOriginalId'=>$original->rootOriginalId(),'targetRevisionId'=>$original->currentRevisionId(),'expectedCurrentRevisionId'=>$original->currentRevisionId(),'correctionReason'=>'Проверка прав']);
                $result=$f->upload($cookies,$metadata);
            }
            assertSameValue(403,$result['status'],'write requires exact capability '.$permission);assertSameValue($before,$f->facts(),'write denial zero facts');assertSameValue($files,$f->base->privateFiles(),'write denial zero files');
        }finally{$remove->execute([$permission.'.extra']);$f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>$permission]);}
    }
    $manager=[];$f->login($manager,97);
    foreach([$base.'/assignment-order/selection',$order.'/originals/submit',$order.'/originals/history'] as $route)
        assertSameValue(200,$f->request('GET',$route,[],$manager)['status'],'builtin manager admitted '.$route);
    // Compound opening needs installation.open, not the standalone application grant.
    $f->db->query("DELETE FROM {$f->p}fm2_pilot_role_permissions WHERE role_id=1 AND permission IN('assignment_order.composition.select','assignment_order.composition.apply')");
    $card=$f->request('GET',$base,[],$cookies);assertSameValue(200,$card['status'],'open-only actor card');
    $open=['_csrf'=>$f->csrf($card['body']),'action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000099','orderId'=>'81','revisionId'=>$original->currentRevisionId(),'sequence'=>'0','actualStartDate'=>'2026-09-02'];
    assertSameValue(303,$f->form($base.'/execution',$open,$cookies)['status'],'compound opening exact capability');
    assertSameValue('18',$f->rows('fm2_installation_cases')[0]['opened_by_user_id'],'session actor owns opening');
    $f->noLegacy();
    echo "PASS: YII2-PREOPENING-JOURNEY-001 current authority and open-only compound command\n";
} finally {if($f instanceof PreopeningFixture)$f->close();}
