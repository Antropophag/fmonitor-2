<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/UserAccessFixture.php';
$f=null;
try {
    $f=new UserAccessFixture(dirname(__DIR__,2));$p=$f->p;
    $f->start();$admin=[];assertSameValue(303,$f->login($admin)['status'],'admin login');
    $f->db->query("CREATE TABLE {$p}users_roles(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(100),status TINYINT NOT NULL)");
    $f->db->query("CREATE TABLE {$p}users(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(300),email VARCHAR(300),role_id BIGINT UNSIGNED,status TINYINT NOT NULL,password VARCHAR(300) NULL)");
    $f->db->query("INSERT INTO {$p}users_roles VALUES(42,'Строительный контроль',1)");
    $f->db->query("INSERT INTO {$p}users VALUES(8001,'Legacy <Engineer>','old@example.test',42,1,'legacy-secret-hash')");
    $page=$f->page($admin);
    assertSameValue(true,str_contains($page['body'],'legacyUserId'),'INTENDED_RED link control rendered');
    foreach(['GET','HEAD'] as $method)assertSameValue(405,$f->request($method,'/pilot/admin/users/9403/legacy-link',[],$admin)['status'],'mutation method '.$method);
    $before=$f->facts();$denied=[];assertSameValue(303,$f->login($denied,'ordinary.person@shlz.ru')['status'],'regular login');
    $r=$f->request('POST','/pilot/admin/users/9403/legacy-link',['_csrf'=>$f->csrf($f->request('GET','/pilot/login',[],$denied)['body']),'requestId'=>'20202020-0002-4020-8020-000000000001','legacyUserId'=>'8001'],$denied);
    assertSameValue(403,$r['status'],'unprivileged link denied');assertSameValue($before,$f->facts(),'denied no identity facts');
    $r=$f->post('/pilot/admin/users/9403/legacy-link',['requestId'=>'20202020-0002-4020-8020-000000000001','legacyUserId'=>'8001'],$admin);
    assertSameValue([303,'/pilot/admin/users'],[$r['status'],$r['headers']['location'][0]??null],'link redirect');
    $linked=$f->page($admin);assertSameValue(true,str_contains($linked['body'],'8001'),'linked ID visible');assertSameValue(true,str_contains($linked['body'],'Legacy &lt;Engineer&gt;'),'snapshot escaped');assertSameValue(false,str_contains($linked['body'],'legacy-secret-hash'),'legacy credential hidden');
    assertSameValue(400,$f->request('POST','/pilot/admin/users/9403/legacy-link',['_csrf'=>'bad','requestId'=>'20202020-0002-4020-8020-000000000002','legacyUserId'=>'8001'],$admin)['status'],'CSRF required');
    foreach([['requestId'=>'bad','legacyUserId'=>'8001',400],['requestId'=>'20202020-0002-4020-8020-000000000003','legacyUserId'=>'0',422],['requestId'=>'20202020-0002-4020-8020-000000000004','legacyUserId'=>'9999',422]] as $case){[$fields,$expected]=[array_slice($case,0,2,true),$case[2]];$before=$f->facts();assertSameValue($expected,$f->post('/pilot/admin/users/9403/legacy-link',$fields,$admin)['status'],'HTTP rejection mapping');assertSameValue($before,$f->facts(),'HTTP rejection atomic');}
    $owner=$f->owner();$issued=$owner->invite(9101,'new.engineer@shlz.ru','Новый инженер');assertSameValue('issued',$issued['status'],'existing invitation owner');$newId=$issued['userId'];assertSameValue('changed',$owner->changeRole(9101,$newId,9212,'attach')['status'],'construction-control role attached');
    $f->db->query("INSERT INTO {$p}users VALUES(8002,'Новый инженер','legacy.new@example.test',42,1,'NEVER-READ-LEGACY-CREDENTIAL')");
    assertSameValue(303,$f->post("/pilot/admin/users/$newId/legacy-link",['requestId'=>'20202020-0002-4020-8020-000000000005','legacyUserId'=>'8002'],$admin)['status'],'invited user linked before activation');
    assertSameValue(null,$f->db->query("SELECT password_hash FROM {$p}fm2_pilot_auth_credentials WHERE user_id=$newId")->fetch_column(),'local credential still unset');$password='Independent local activation secret 2026';assertSameValue('activated',$owner->activate($issued['token'],$password,$password)['status'],'existing activation owner');$localHash=(string)$f->db->query("SELECT password_hash FROM {$p}fm2_pilot_auth_credentials WHERE user_id=$newId")->fetch_column();assertSameValue(true,password_verify($password,$localHash),'new local credential');assertSameValue(false,str_contains($localHash,'NEVER-READ'),'legacy credential not copied');
    echo "PASS: LEGACY-CONTROL-ENGINEER-MIGRATION-001 real Yii link route\n";
} finally {if($f instanceof UserAccessFixture)$f->close();}
