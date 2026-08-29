<?php
declare(strict_types=1);

$entrypoint = require dirname(__DIR__) . '/app/PilotHttp/production-entrypoint.php';

ini_set('expose_php', '0');
ini_set('default_charset', '');
header_remove('X-Powered-By');
header_remove('Server');

/** @param array<string, string> $headers */
function emitPilotResponse(int $status, array $headers, string $body): never
{
    http_response_code($status);
    header_remove('X-Powered-By');
    header_remove('Server');
    foreach ($headers as $name => $value) header($name . ': ' . $value);
    echo $body;
    exit;
}

/** @return array<string, string> */
function pilotSecurityHeaders(string $body): array
{
    return [
        'Content-Type'=>'text/plain; charset=UTF-8','Content-Length'=>(string)strlen($body),
        'X-Content-Type-Options'=>'nosniff','Referrer-Policy'=>'no-referrer','X-Frame-Options'=>'DENY',
        'Content-Security-Policy'=>"default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",
        'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()',
        'Cross-Origin-Opener-Policy'=>'same-origin','Cache-Control'=>'no-store',
    ];
}

$localServerAddress = $_SERVER['SERVER_ADDR'] ?? $_SERVER['SERVER_NAME'] ?? null;
if (PHP_SAPI === 'cli-server' && in_array($localServerAddress, ['127.0.0.1','::1','localhost'], true)
    && !array_key_exists('REMOTE_USER', $_SERVER)) {
    $loopbackPrincipal=getenv('REMOTE_USER');
    if ($loopbackPrincipal !== false) $_SERVER['REMOTE_USER']=$loopbackPrincipal;
}
if(PHP_SAPI==='cli-server'&&$localServerAddress==='127.0.0.1'){
    $demoNonce=getenv('FMONITOR_DEMO_LOOPBACK_NONCE');
    if(getenv('FMONITOR_DEMO_LOOPBACK')==='1'&&is_string($demoNonce)&&preg_match('/^[0-9a-f]{32}$/D',$demoNonce)===1)$_SERVER['FMONITOR_DEMO_LOOPBACK_NONCE']=$demoNonce;
}

if(PHP_SAPI!=='cli-server')$_SERVER['FMONITOR_TRUSTED_REQUEST_HOST']=getenv('FMONITOR_TRUSTED_REQUEST_HOST');
$response=$entrypoint->handle($_SERVER);$headers=$response->headers;
if(PHP_SAPI==='cli-server'&&$response->status===400&&!array_key_exists('HTTP_HOST',$_SERVER))$headers['Host']='rejected.invalid';
emitPilotResponse($response->status,$headers,$response->body);
