<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

use FMonitor2\Tests\Support\SelectionHttpFixture;
use FMonitor2\InstallationProcess as I;

// Owner feedback 2026-09-07: entering a card must preserve the actor's sidebar.
$fixture=new SelectionHttpFixture(true,static fn()=>['FMONITOR_NOW'=>'2026-09-07T12:00:00+03:00']);
try {
    $native=$fixture->original->selection;
    I\AssignmentOrderApplicationSchemaMigration::apply($native->db);
    I\ChecklistTemplateSchemaMigration::apply($native->db);
    I\InspectionEvidenceSchemaMigration::apply($native->db);
    I\InstallationCompletionDetailsSchemaMigration::apply($native->db);
    I\ObjectDetailSnapshotSchemaMigration::apply($native->db);
    foreach(['objects.read','installers.read','construction_control.read','otiz.manage','access.administer'] as $permission) {
        $native->schema->insert('fm2_pilot_role_permissions',['role_id'=>1,'permission'=>$permission]);
    }
    $links=static function(array $response):array {
        assertSameValue(200,$response['status'],'ordinary page is available');
        assertSameValue(1,preg_match('#<nav class="fm2-primary-nav"[^>]*>(.*?)</nav>#s',$response['body'],$nav),'shared sidebar exists');
        preg_match_all('#<a[^>]* href="([^"]+)"#',$nav[1],$matches);
        $paths=$matches[1];sort($paths);return $paths;
    };
    $expected=['/pilot/objects','/pilot/calendar','/pilot/installers','/pilot/construction-control','/pilot/admin/users','/pilot/admin/roles'];
    sort($expected);
    foreach(['/pilot/objects/4512'] as $path) {
        $response=$fixture->request('GET',$path);
        assertSameValue($expected,$links($response),'all authorized sidebar links remain on '.$path);
    }
    assertSameValue(403,$fixture->request('GET','/pilot/objects/4512','',99)['status'],'navigation fix does not grant object access to another actor');
    echo "PASS local card navigation preserves all role permissions and denied access\n";
} finally {
    $fixture->close();
}
