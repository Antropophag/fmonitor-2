<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// Specification: PILOT-ROUTE-CSP-001, exact allowlist and A4-A9.
// Boundary helper seam required by the approved design: normalized
// method/path/final status/media type -> byte-exact policy.
use FMonitor2\PilotHttp\PilotRouteCsp;

const PRCI_BASE = "default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
const PRCI_SCRIPT = "default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
const PRCI_CHECKLIST = "default-src 'none'; style-src 'self'; script-src 'self'; worker-src 'self'; connect-src 'self'; img-src 'self' blob:; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
const PRCI_WORKER = "default-src 'self'; connect-src 'self'";

if(!class_exists(PilotRouteCsp::class))throw new TestFailure('PILOT-ROUTE-CSP-001 intended RED: HTTP-boundary route/result classifier is missing');

$scripted=[
 '/pilot/login','/pilot/','/pilot/objects','/pilot/objects/4512','/pilot/objects/4512/assignment-order/prepare',
 '/pilot/construction-control','/pilot/installers','/pilot/admin/users','/pilot/admin/roles','/pilot/calendar','/pilot/calendar/',
 '/pilot/otiz','/pilot/otiz/','/pilot/otiz/objects','/pilot/otiz/payments','/pilot/otiz/history','/pilot/otiz/reconciliation',
 '/pilot/otiz/reconciliation/quarantine','/pilot/otiz/active-baselines','/pilot/otiz/historical-replay','/pilot/otiz/snapshots/4512',
];
$checklists=['/pilot/objects/4512/checklist','/pilot/construction-control/objects/4512/checklist'];
$failClosed=[
 '/pilot','/pilot/objects/0','/pilot/objects/04512','/pilot/objects/4512/unknown','/pilot/objects/4512/checklist/extra',
 '/pilot/construction-control/objects/0/checklist','/pilot/construction-control/objects/4512/checklist/extra',
 '/pilot/otiz/unknown','/pilot/otiz/snapshots/0','/pilot/otiz/snapshots/4512/export','/pilot/otiz/export','/pilot/otiz/payments/accept',
 '/pilot/admin/users/invite','/pilot/new-scripted-screen',
];
foreach($scripted as$path)foreach(['GET','HEAD']as$method)assertSameValue(PRCI_SCRIPT,PilotRouteCsp::classify($method,$path,200,'text/html; charset=UTF-8'),"allowlisted $method $path");
foreach($checklists as$path)foreach(['GET','HEAD']as$method)assertSameValue(PRCI_CHECKLIST,PilotRouteCsp::classify($method,$path,200,'text/html; charset=UTF-8'),"checklist $method $path");
assertSameValue(PRCI_SCRIPT,PilotRouteCsp::classify('POST','/pilot/login',200,'text/html; charset=UTF-8'),'only scripted POST result');
foreach([...$scripted,...$checklists]as$path){foreach([401,403,404,409,503]as$status)assertSameValue(PRCI_BASE,PilotRouteCsp::classify('GET',$path,$status,'text/html; charset=UTF-8'),"error $status $path");if($path!=='/pilot/login')assertSameValue(PRCI_BASE,PilotRouteCsp::classify('POST',$path,200,'text/html; charset=UTF-8'),"method boundary $path");assertSameValue(PRCI_BASE,PilotRouteCsp::classify('GET',$path,200,'application/json'),"media boundary $path");}
foreach($failClosed as$path)assertSameValue(PRCI_BASE,PilotRouteCsp::classify('GET',$path,200,'text/html; charset=UTF-8'),"whole-pattern/future route $path");
foreach(['/pilot/assets/pilot.css'=>'text/css','/pilot/assets/checklist.js'=>'text/javascript','/pilot/assets/favicon.svg'=>'image/svg+xml','/pilot/assets/fonts/golos-text-cyrillic-400-normal.woff2'=>'font/woff2','/pilot/orders/4512.pdf'=>'application/pdf']as$path=>$type)assertSameValue(PRCI_BASE,PilotRouteCsp::classify('GET',$path,200,$type),"asset $type");
assertSameValue(PRCI_WORKER,PilotRouteCsp::classify('GET','/pilot/assets/checklist-sw.js',200,'text/javascript; charset=UTF-8'),'exact Service Worker policy');
foreach([PRCI_BASE,PRCI_SCRIPT,PRCI_CHECKLIST,PRCI_WORKER]as$policy)foreach(['unsafe-inline','unsafe-eval','nonce-','sha256-','sha384-','sha512-','*','http:','https:']as$token)assertSameValue(false,str_contains($policy,$token),"forbidden CSP token $token");

echo "pilot_route_csp_inventory_001_test: PASS\n";
