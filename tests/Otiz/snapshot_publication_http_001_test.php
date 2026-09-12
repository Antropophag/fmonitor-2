<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionHttpFixture;
use FMonitor2\InstallationProcess\PilotOtizSchemaMigration;

// OTIZ-SNAPSHOT-PUBLICATION-001 / A01. Only disposable fixture DB and server.
$p='otiz_http_'.bin2hex(random_bytes(5)).'_';
    $f=new SelectionHttpFixture(true,static fn():array=>['FMONITOR_LEGACY_TABLE_PREFIX'=>'invalid-prefix!'],$p,dirname(__DIR__,2).'/rapid-pilot/otiz-oracle-router.php');
try{
    $db=$f->original->selection->db;
    PilotOtizSchemaMigration::apply($db,$p);
    foreach(['OtizPublicationSchemaMigration','OtizEvidenceSchemaMigration']as$m){
        $class='FMonitor2\\InstallationProcess\\'.$m;
        if(class_exists($class))$class::apply($db,$p);
    }
    $db->query("INSERT INTO {$p}fm2_pilot_role_permissions(role_id,permission) VALUES(3,'otiz.manage')");
    $password='Synthetic-Test-Login-2026!';
    $hash=password_hash($password,PASSWORD_ARGON2ID);
    $s=$db->prepare("UPDATE {$p}fm2_pilot_auth_credentials SET password_hash=? WHERE user_id=31");$s->bind_param('s',$hash);$s->execute();
    $cookie='';
    $request=static function(string$method,string$path,array$post=[])use($f,&$cookie):array{
        $r=$f->request($method,$path,http_build_query($post),null,$cookie===''?[]:['Cookie'=>$cookie]);
        if(isset($r['headers']['set-cookie']))$cookie=explode(';',$r['headers']['set-cookie'])[0];
        return$r;
    };
    $token=static function(array$r):string{
        if(preg_match('/name="csrfToken"[^>]*value="([^"]+)"/',$r['body'],$m)!==1)throw new RuntimeException('SETUP_FAILURE missing form CSRF');
        return html_entity_decode($m[1],ENT_QUOTES);
    };
    $login=$request('GET','/pilot/login');assertSameValue(200,$login['status'],'anonymous login form');$csrf=$token($login);
    $stage=$request('POST','/pilot/login',['csrfToken'=>$csrf,'email'=>'test31@shlz.ru']);
    assertSameValue(200,$stage['status'],'password stage');$csrf=$token($stage);
    $signed=$request('POST','/pilot/login',['csrfToken'=>$csrf,'email'=>'test31@shlz.ru','password'=>$password]);
    assertSameValue(303,$signed['status'],'real password login');
    $page=$request('GET','/pilot/otiz/payments');assertSameValue(200,$page['status'],'authorized OTIZ page');$csrf=$token($page);
    $calc=$request('POST','/pilot/otiz/calculate',['csrfToken'=>$csrf,'reportDate'=>'2026-09-08','operationId'=>'a0100000-0000-4000-8000-000000000001']);
    assertSameValue(500,$calc['status'],'controlled configured input failure after old draft header');
    $rows=$db->query("SELECT id,status,content_hash FROM {$p}fm2_pilot_otiz_snapshots ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    if($rows!==[]){
        assertSameValue(1,count($rows),'only owned attempted draft');
        $id=(int)$rows[0]['id'];
        $accepted=$request('POST','/pilot/otiz/snapshots/'.$id.'/accept',['csrfToken'=>$csrf]);
        assertSameValue(303,$accepted['status'],'acceptance route reached');
        $state=$db->query("SELECT status FROM {$p}fm2_pilot_otiz_snapshots WHERE id=$id")->fetch_assoc()['status'];
        assertSameValue('draft',$state,'A01: incomplete calculation MUST NOT be accepted');
        throw new TestFailure('A01: failed build MUST rollback newly published draft');
    }
    echo "OTIZ_SNAPSHOT_PUBLICATION_HTTP_OK\n";
}finally{$f->close();}
