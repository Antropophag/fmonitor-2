<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\AssignmentOrderComposition as C;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001: Gate5 tracer-v1 authority findings.
$tests=[
    'partial local schema never replays through legacy'=>static function(F $f):void {
        $app=$f->app();$command=F::command();assertSameValue('selected',$app->selectAssignmentOrderComposition($command)->status()->value,'accepted native replay target');
        // Fictional alternative authority: no legacy data import or legacy writer migration.
        $f->db->query('CREATE TABLE users_roles(id BIGINT PRIMARY KEY,status INT NOT NULL) ENGINE=InnoDB');
        $f->db->query('CREATE TABLE users(id BIGINT PRIMARY KEY,role_id BIGINT NOT NULL,status INT NOT NULL) ENGINE=InnoDB');
        $f->db->query('INSERT INTO users_roles VALUES(1,1)');$f->db->query('INSERT INTO users VALUES(18,1,1)');
        $f->db->query('ALTER TABLE fm2_process_user_capabilities DROP CONSTRAINT ck_fm2_process_user_capability_v5');
        $f->schema->insert('fm2_process_user_capabilities',['user_id'=>18,'capability'=>'assignment_order.composition.select','position_snapshot'=>'Сотрудник теста']);
        $f->db->query('RENAME TABLE fm2_pilot_users TO fixture_displaced_pilot_users');
        $before=$f->rows();$r=$app->selectAssignmentOrderComposition($command);
        assertSameValue(['failed','dependency_unavailable',null],[$r->status()->value,$r->reasonCode()?->value,$r->success()],'partial local family must not disclose stored success through legacy grant');
        assertSameValue($before,$f->rows(),'authority schema failure writes nothing');
    },
    'fresh wrong database refused and closed'=>static function(F $f):void {
        $other=new F();
        try{authorityFreshRefused($f,fn()=>$other->schema->source->connect($other->schema->source->name));}finally{$other->close();}
    },
    'fresh wrong charset refused and closed'=>static function(F $f):void {
        authorityFreshRefused($f,function()use($f){$db=$f->schema->source->connect($f->schema->source->name);$db->set_charset('latin1');return $db;});
    },
    'fresh wrong user refused and closed'=>static function(F $f):void {
        // Fully granted synthetic read principal; no denied-SQL/permission fault probe.
        authorityFreshRefused($f,fn()=>$f->schema->source->restricted('SELECT'));
    },
    'same authority independent reader works'=>static function(F $f):void {
        $ports=C\AssignmentOrderCompositionNativeVerificationFactory::dependencies($f->db,fn()=>$f->schema->source->connect($f->schema->source->name));
        $reader=$ports->freshReaders->open();try{assertSameValue(C\SelectionLookupStatus::NOT_FOUND,$reader->findTerminalRequest(F::command()->requestId)->status,'valid fresh source works');}finally{$reader->close();}
    },
];
function authorityFreshRefused(F $f,Closure $open):void {
    $candidate=null;$reader=null;$rejected=false;
    $ports=C\AssignmentOrderCompositionNativeVerificationFactory::dependencies($f->db,function()use($open,&$candidate){return $candidate=$open();});
    try {
        try{$reader=$ports->freshReaders->open();}catch(Throwable){$rejected=true;}
        assertSameValue(true,$rejected,'wrong native recovery authority must be rejected');
        assertSameValue(true,$candidate instanceof mysqli,'candidate really opened');
        $closed=false;try{$candidate->query('SELECT 1');}catch(Error){$closed=true;}
        assertSameValue(true,$closed,'rejected candidate connection closed');
        assertSameValue('1',(string)$f->db->query('SELECT 1')->fetch_row()[0],'primary stays owned by caller');
    }finally{if($reader!==null)$reader->close();}
}
$failed=0;
foreach($tests as $name=>$test){$f=null;$errors=[];try{$f=new F();echo "SETUP_OK $name\n";$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}
exit($failed===0?0:1);
