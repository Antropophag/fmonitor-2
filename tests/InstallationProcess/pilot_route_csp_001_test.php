<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__, 2) . '/app/PilotHttp/PilotView.php';
require_once dirname(__DIR__, 2) . '/app/PilotHttp/ChecklistView.php';

use FMonitor2\PilotHttp\CssAsset;
use FMonitor2\PilotHttp\HttpUser;
use FMonitor2\PilotHttp\HttpUserDirectory;
use FMonitor2\PilotHttp\InvalidServerIdentity;
use FMonitor2\PilotHttp\ObjectCardReader;
use FMonitor2\PilotHttp\ObjectCardReaderProvider;
use FMonitor2\PilotHttp\PilotHttpCoordinator;
use FMonitor2\PilotHttp\PilotHttpDependencies;
use FMonitor2\PilotHttp\PilotHttpRequest;
use FMonitor2\PilotHttp\PilotHttpResponse;
use FMonitor2\PilotHttp\PilotShellRenderer;
use FMonitor2\PilotHttp\ProductionChecklistRenderer;
use FMonitor2\PilotHttp\TrustedServerIdentity;

// Specification: PILOT-ROUTE-CSP-001, A1-A9 and A11-A12.
// Confirmed public seam: PilotHttpCoordinator::handle request -> complete HTTP response.

const PRC_BASE = "default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
const PRC_SCRIPT = "default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
const PRC_CHECKLIST = "default-src 'none'; style-src 'self'; script-src 'self'; worker-src 'self'; connect-src 'self'; img-src 'self' blob:; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
const PRC_WORKER = "default-src 'self'; connect-src 'self'";

final class PrcAsset implements CssAsset { public function readBytes():string{return '.fixture{}';} public function close():void{} }
final class PrcIdentity implements TrustedServerIdentity { public function resolve(mixed $value):string{if($value!=='actor@example.test')throw new InvalidServerIdentity();return$value;} }
final class PrcUsers implements HttpUserDirectory { public function resolveActiveUser(string $principal):?HttpUser{return new HttpUser(17,'Тестовый Пользователь',$principal);} }
final class PrcDependencies implements PilotHttpDependencies { public function css():CssAsset{return new PrcAsset();} public function users():HttpUserDirectory{return new PrcUsers();} public function close():void{} }
final class PrcShell implements PilotShellRenderer { public function __construct(private string $body){} public function render(HttpUser $user):string{return$this->body;} }
final class PrcCards implements ObjectCardReaderProvider,ObjectCardReader {
    public function objectCards():ObjectCardReader{return$this;}
    public function read(int $installationObjectId):?array{return$installationObjectId===4512?['id'=>4512,'address'=>'г. Тестоград, ул. Примерная, д. 1','entrance'=>'2','registrationNumber'=>'TEST-LIFT-4512','opened'=>false]:null;}
}

function prcRequest(PilotHttpCoordinator $app,string $method,string $path,mixed $identity='actor@example.test'):PilotHttpResponse
{
    return $app->handle(new PilotHttpRequest($method,$path,'pilot.example.test',$identity));
}
function prcCheck(array &$failures,string $scenario,PilotHttpResponse $response,int $status,string $csp,bool $emptyBody=false):void
{
    $actual=$response->headers['Content-Security-Policy']??null;
    if($response->status!==$status)$failures[]="$scenario setup/status: expected $status, actual {$response->status}";
    elseif($actual!==$csp)$failures[]="$scenario CSP: expected [$csp], actual [$actual]";
    elseif($emptyBody&&$response->body!=='')$failures[]="$scenario HEAD body is not empty";
    foreach(['unsafe-inline','unsafe-eval','nonce-','sha256-','sha384-','sha512-','*','http:','https:']as$token)if(is_string($actual)&&str_contains($actual,$token))$failures[]="$scenario CSP contains forbidden token $token";
}
function prcSafeHtml(array &$failures,string $scenario,string $html):void
{
    if(preg_match('#<script(?![^>]+\bsrc="/pilot/)[^>]*>#i',$html)===1)$failures[]="$scenario has inline/non-local script";
    if(preg_match('/\son[a-z]+\s*=/i',$html)===1)$failures[]="$scenario has inline event attribute";
    if(preg_match('/(?:href|src)\s*=\s*["\']\s*javascript:/i',$html)===1)$failures[]="$scenario has javascript URL";
    if(preg_match('/\b(?:eval|Function)\s*\(/',$html)===1)$failures[]="$scenario has executable string evaluation";
    if(preg_match('#<script[^>]+src=["\'](?:https?:)?//#i',$html)===1)$failures[]="$scenario has third-party script";
}
function prcPreserve(array &$failures,string $scenario,PilotHttpResponse $response,string $type,string $getBytes,array $extra=[]):void
{
    $expected=['Content-Type'=>$type,'Content-Length'=>(string)strlen($getBytes),'X-Content-Type-Options'=>'nosniff','Referrer-Policy'=>'no-referrer','X-Frame-Options'=>'DENY','Permissions-Policy'=>'camera=(), microphone=(), geolocation=()','Cross-Origin-Opener-Policy'=>'same-origin','Cache-Control'=>'no-store']+$extra;
    foreach($expected as$name=>$value)if(($response->headers[$name]??null)!==$value)$failures[]="$scenario exact header $name changed";
}

$scripted='<!doctype html><html><body><script src="/pilot/assets/navigation.js"></script></body></html>';
$scriptFree='<!doctype html><html><body><main>Активация</main></body></html>';
$dependencies=new PrcDependencies();$cards=new PrcCards();$failures=[];
$scriptedApp=new PilotHttpCoordinator(new PrcIdentity(),new PrcShell($scripted),$dependencies,null,$cards,null,null,null,null,new ProductionChecklistRenderer());
$scriptFreeApp=new PilotHttpCoordinator(new PrcIdentity(),new PrcShell($scriptFree),$dependencies);

$get=prcRequest($scriptedApp,'GET','/pilot/');$head=prcRequest($scriptedApp,'HEAD','/pilot/');
prcCheck($failures,'A1 scripted GET',$get,200,PRC_SCRIPT);prcSafeHtml($failures,'A1 scripted GET final HTML',$get->body);
prcPreserve($failures,'A1 scripted GET',$get,'text/html; charset=UTF-8',$scripted);
prcCheck($failures,'A2 scripted HEAD',$head,200,PRC_SCRIPT,true);
prcPreserve($failures,'A2 scripted HEAD',$head,'text/html; charset=UTF-8',$scripted);
if(($head->headers['Content-Security-Policy']??null)!==($get->headers['Content-Security-Policy']??null))$failures[]='A2 HEAD CSP differs from GET';
if(($head->headers['Content-Length']??null)!==($get->headers['Content-Length']??null))$failures[]='A2 HEAD Content-Length differs from GET';
if(($get->headers['Content-Length']??null)!==(string)strlen($get->body))$failures[]='A2 GET Content-Length differs from body bytes';
$unauthorized=prcRequest($scriptedApp,'GET','/pilot/',null);prcCheck($failures,'A4 unauthorized scripted route',$unauthorized,401,PRC_BASE);prcPreserve($failures,'A4 unauthorized',$unauthorized,'text/plain; charset=UTF-8',"Authentication required.\n");
$rootRedirect=prcRequest($scriptedApp,'GET','/pilot');prcCheck($failures,'A5 redirect',$rootRedirect,308,PRC_BASE);prcPreserve($failures,'A5 redirect',$rootRedirect,'text/plain; charset=UTF-8','',['Location'=>'/pilot/']);
$jsAsset=prcRequest($scriptedApp,'GET','/pilot/assets/checklist.js');prcCheck($failures,'A6 JavaScript asset',$jsAsset,200,PRC_BASE);$jsBytes=(string)file_get_contents(dirname(__DIR__,2).'/app/PilotHttp/checklist.js');if(!hash_equals(hash('sha256',$jsBytes),hash('sha256',$jsAsset->body)))$failures[]='A6 JavaScript bytes differ from independent source';prcPreserve($failures,'A6 JavaScript asset',$jsAsset,'text/javascript; charset=UTF-8',$jsBytes);
prcCheck($failures,'A6 CSS asset',prcRequest($scriptedApp,'GET','/pilot/assets/shlz.css'),200,PRC_BASE);
prcCheck($failures,'A6 exact Service Worker asset',prcRequest($scriptedApp,'GET','/pilot/assets/checklist-sw.js'),200,PRC_WORKER);
prcCheck($failures,'A7 exact near-miss zero id',prcRequest($scriptedApp,'GET','/pilot/objects/0'),404,PRC_BASE);
prcCheck($failures,'A7 exact near-miss suffix',prcRequest($scriptedApp,'GET','/pilot/objects/4512/unknown'),404,PRC_BASE);
$free=prcRequest($scriptFreeApp,'GET','/pilot/');prcCheck($failures,'A3 script-free successful HTML',$free,200,PRC_BASE);prcSafeHtml($failures,'A3 script-free final HTML',$free->body);
$checklist=prcRequest($scriptedApp,'GET','/pilot/objects/4512/checklist');prcCheck($failures,'A8 checklist success',$checklist,200,PRC_CHECKLIST);prcSafeHtml($failures,'A8 checklist final HTML',$checklist->body);
prcCheck($failures,'A9 checklist error',prcRequest($scriptedApp,'GET','/pilot/objects/9999/checklist'),404,PRC_BASE);
$unavailable=prcRequest($scriptedApp,'GET','/pilot/objects');prcCheck($failures,'A4 operational 503',$unavailable,503,PRC_BASE);
prcPreserve($failures,'A4 operational 503',$unavailable,'text/plain; charset=UTF-8',"Service unavailable.\n",['Retry-After'=>'60']);
if([$unavailable->headers['Content-Type']??null,$unavailable->headers['Content-Length']??null,$unavailable->headers['Retry-After']??null,$unavailable->headers['Cache-Control']??null,$unavailable->body]!==['text/plain; charset=UTF-8','21','60','no-store',"Service unavailable.\n"])$failures[]='A4 operational 503 response contract changed';
$redirect=prcRequest($scriptedApp,'GET','/pilot');if([$redirect->headers['Location']??null,$redirect->headers['Cache-Control']??null,$redirect->headers['Content-Length']??null,$redirect->body]!==['/pilot/','no-store','0',''])$failures[]='A5 redirect response contract changed';
$repeat=prcRequest($scriptedApp,'GET','/pilot/');if($repeat->headers!==$get->headers||$repeat->body!==$get->body)$failures[]='A11 repeated safe read response differs';

if($failures!==[])throw new TestFailure("PILOT-ROUTE-CSP-001 intended RED (setup reached public seam):\n- ".implode("\n- ",$failures));
echo "pilot_route_csp_001_test: PASS\n";
