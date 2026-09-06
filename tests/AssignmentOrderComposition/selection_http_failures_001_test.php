<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{SelectionHttpFixture as F,SelectionHttpAssertions as A};
// ASSIGNMENT-ORDER-COMPOSITION-HTTP-001: native domain reasons and retry intent preservation.
$f=null;
try {
    $f=new F();$native=$f->original->selection;$base=A::input($f,100);$counter=100;
    foreach([
        'installer_required'=>['installerTabIds'=>[]],
        'installer_not_in_catalog'=>['installerTabIds'=>['9999']],
        'control_engineer_not_eligible'=>['controlEngineerUserId'=>'99'],
        'invalid_command'=>['installerTabIds'=>['7001','7001']],
    ] as $reason=>$override){
        $input=array_replace($base,$override,['requestId'=>sprintf('11111111-1111-4111-8111-%012d',++$counter)]);$before=$native->rows();
        $r=$f->request('POST',A::PATH,A::body($input));assertSameValue($reason==='invalid_command'?400:422,$r['status'],'INTENDED_RED domain rejection '.$reason);
        assertSameValue(true,str_contains($r['body'],'data-error-code="'.$reason.'"'),'exact business reason');
        A::rowsPreserved($before,$native->rows(),['fm2_assignment_order_selection_requests','fm2_assignment_order_selection_audits']);
    }
    $selected=A::input($f,200);assertSameValue(303,$f->request('POST',A::PATH,A::body($selected))['status'],'native accepted request');
    $changed=array_replace($selected,['installerTabIds'=>['7002']]);$r=$f->request('POST',A::PATH,A::body($changed));
    assertSameValue(409,$r['status'],'request fingerprint conflict');assertSameValue(true,str_contains($r['body'],'request_id_conflict'),'request conflict exact reason');
    $native->db->query('RENAME TABLE fm2_workforce_catalog TO fixture_displaced_workforce');
    try {
        $retry=A::input($f,201,1,7002,'replace_pending');$r=$f->request('POST',A::PATH,A::body($retry));
        assertSameValue(503,$r['status'],'native dependency failure');assertSameValue(true,str_contains($r['body'],'dependency_unavailable'),'dependency reason');
        assertSameValue($retry['requestId'],A::field($r['body'],'requestId'),'retry same request identity');
        assertSameValue('1',A::field($r['body'],'expectedSelectionRevision'),'retry same revision');
        assertSameValue('replace_pending',A::field($r['body'],'mode'),'retry same mode');
        $retryBody=A::submission($r['body']);parse_str($retryBody,$actualIntent);$expectedIntent=$retry;ksort($actualIntent);ksort($expectedIntent);
        assertSameValue($expectedIntent,$actualIntent,'rendered retry preserves entire intent including crew/engineer/confirmation/CSRF');
        assertSameValue(503,$f->request('GET',A::PATH)['status'],'partial read source unavailable, not empty catalog');
    } finally { $native->db->query('RENAME TABLE fixture_displaced_workforce TO fm2_workforce_catalog'); }
    assertSameValue(303,$f->request('POST',A::PATH,$retryBody)['status'],'same rendered intent resumes when dependency restored');
    $native->db->query('RENAME TABLE fm2_process_events TO fixture_displaced_events');
    try {
        $r=$f->request('POST','/pilot/objects/4512/assignment-orders/82/template',http_build_query(['csrfToken'=>$f->csrf]));
        assertSameValue(503,$r['status'],'template source/persistence failure');assertSameValue(false,str_starts_with($r['body'],'%PDF-'),'no PDF success on failure');
        assertSameValue(503,$f->request('GET',A::PATH)['status'],'date-read unavailable is not absent date');
    } finally { $native->db->query('RENAME TABLE fixture_displaced_events TO fm2_process_events'); }
    echo "PASS domain mapping/retry identity/read failure/template failure\n";
}finally{if($f!==null)$f->close();}
