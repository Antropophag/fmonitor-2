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
    $before=$f->facts();$denied=[];assertSameValue(303,$f->login($denied,'ordinary.person@shlz.ru')['status'],'regular login');
    $r=$f->request('POST','/pilot/admin/users/9403/legacy-link',['_csrf'=>$f->csrf($f->request('GET','/pilot/login',[],$denied)['body']),'requestId'=>'20202020-0002-4020-8020-000000000001','legacyUserId'=>'8001'],$denied);
    assertSameValue(403,$r['status'],'unprivileged link denied');assertSameValue($before,$f->facts(),'denied no identity facts');
    $r=$f->post('/pilot/admin/users/9403/legacy-link',['requestId'=>'20202020-0002-4020-8020-000000000001','legacyUserId'=>'8001'],$admin);
    assertSameValue([303,'/pilot/admin/users'],[$r['status'],$r['headers']['location'][0]??null],'link redirect');
    $linked=$f->page($admin);assertSameValue(true,str_contains($linked['body'],'8001'),'linked ID visible');assertSameValue(true,str_contains($linked['body'],'Legacy &lt;Engineer&gt;'),'snapshot escaped');assertSameValue(false,str_contains($linked['body'],'legacy-secret-hash'),'legacy credential hidden');
    assertSameValue(400,$f->request('POST','/pilot/admin/users/9403/legacy-link',['_csrf'=>'bad','requestId'=>'20202020-0002-4020-8020-000000000002','legacyUserId'=>'8001'],$admin)['status'],'CSRF required');
    echo "PASS: LEGACY-CONTROL-ENGINEER-MIGRATION-001 real Yii link route\n";
} finally {if($f instanceof UserAccessFixture)$f->close();}
