<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectedOriginalFixture as F;
use FMonitor2\Tests\Support\SelectionNativeFixture as S;
use FMonitor2\Tests\Support\SelectedOriginalInput as Input;
use FMonitor2\AssignmentOrderOriginal as O;
// ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001: distinct reader issuer identity.
function originalIssuerProof(O\AssignmentOrderOriginalApplicationReferenceReader $reader):O\AssignmentOrderOriginalApplicationReference {
    $r=$reader->readCurrent(4512,81);assertSameValue('found',$r->status->value,'healthy native proof');assertSameValue(true,$r->reference!==null,'found reference');return $r->reference;
}
$failures=0;
foreach(['',str_repeat('p',25)] as $prefix)foreach([false,true] as $cloneAfterIssue){$f=null;$errors=[];$label=($cloneAfterIssue?'clone after issuance':'clone before issuance').' prefix'.strlen($prefix);
    try{
        $f=new F($prefix);$db=$f->selection->db;assertSameValue('selected',$f->selection->app()->selectAssignmentOrderComposition(S::command())->status()->value,'native selection setup');
        assertSameValue('accepted',$f->app()->submitAssignmentOrderOriginal(F::command(new Input(F::pdf())))->status()->value,'native accepted original setup');
        $reader=O\AssignmentOrderOriginalApplicationReferenceFactory::create($db,$prefix);$first=$cloneAfterIssue?originalIssuerProof($reader):null;
        $copy=clone $reader;assertSameValue(false,$reader===$copy,'distinct PHP reader instance');$first??=originalIssuerProof($reader);$second=originalIssuerProof($copy);
        assertSameValue($first->metadata(),$second->metadata(),'same document metadata does not share issuer authority');echo "SETUP_OK $label\n";
        $db->query('CREATE TABLE fixture_issuer_sentinel(id INT PRIMARY KEY) ENGINE=InnoDB');$before=$f->selection->rows();$files=$f->privateFiles();$db->begin_transaction();
        try{
            $db->query('INSERT INTO fixture_issuer_sentinel VALUES(1)');
            $outcomes=[$reader->confirmCurrent($first)->value,$copy->confirmCurrent($second)->value,$reader->confirmCurrent($second)->value,$copy->confirmCurrent($first)->value];
            assertSameValue(['1','1'],[$db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],$db->query('SELECT COUNT(*) n FROM fixture_issuer_sentinel')->fetch_assoc()['n']],'all guards preserve active caller and uncommitted write');
        }finally{$db->rollback();}
        assertSameValue($before,$f->selection->rows(),'caller rollback proves no guard commit or fact mutation');assertSameValue($files,$f->privateFiles(),'issuer guards no file effects');
        assertSameValue(['matched','matched','unavailable','unavailable'],$outcomes,'INTENDED_RED: cloned reader is a separate issuer in both directions');
    }catch(Throwable $error){$errors[]=$error->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $label\n";}catch(Throwable $error){$errors[]='cleanup: '.$error->getMessage();}
    if($errors!==[]){$failures++;echo "FAIL $label: ".implode(' | ',$errors)."\n";}else echo "PASS $label\n";
}
exit($failures===0?0:1);
