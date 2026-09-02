<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\IdentityAccess\AuthorizeLocalActor;
use FMonitor2\IdentityAccess\InMemoryLocalAuthorizationFacts;

// Approved public seam: authorizeLocalActor(authenticatedLocalUserId,
// requiredPermission). The in-memory adapter is a public test adapter over the
// same facts contract, not a reimplementation of policy in this test.
assertSameValue(true, class_exists(AuthorizeLocalActor::class), 'INTENTIONAL_RED: application-owned authorization seam exists');
assertSameValue(true, class_exists(InMemoryLocalAuthorizationFacts::class), 'INTENTIONAL_RED: public in-memory facts adapter exists');

$facts = new InMemoryLocalAuthorizationFacts();
$authorize = new AuthorizeLocalActor($facts, static fn(): string => 'a1b2c3d4e5f6');
$facts->replace([
    'users' => [7301 => ['status' => 1, 'activationState' => 'active']],
    'roles' => [701 => ['status' => 1], 702 => ['status' => 1]],
    'assignments' => [[7301, 701]],
    'permissions' => [[701, 'objects.read']],
]);

$result = $authorize->authorizeLocalActor(7301, 'objects.read');
assertSameValue('AUTHORIZED', $result->status, 'full exact grant');
assertSameValue(7301, $result->actorUserId, 'authorized actor is trusted local id');
assertSameValue('ACCESS_DENIED', $authorize->authorizeLocalActor(7301, 'assignment_order.prepare')->status, 'objects.read never authorizes a route mapped to another exact permission');

foreach ([null, 0, -1, '7301'] as $invalidActor) {
    assertSameValue('AUTHENTICATION_REQUIRED', $authorize->authorizeLocalActor($invalidActor, 'objects.read')->status, 'invalid/missing actor requires authentication');
}

$denials = [
    'missing user' => ['users'=>[], 'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]], 'permissions'=>[[701,'objects.read']]],
    'inactive user' => ['users'=>[7301=>['status'=>0,'activationState'=>'active']], 'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]], 'permissions'=>[[701,'objects.read']]],
    'invited activation' => ['users'=>[7301=>['status'=>1,'activationState'=>'invited']], 'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]], 'permissions'=>[[701,'objects.read']]],
    'blocked activation' => ['users'=>[7301=>['status'=>1,'activationState'=>'blocked']], 'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]], 'permissions'=>[[701,'objects.read']]],
    'no assignment' => ['users'=>[7301=>['status'=>1,'activationState'=>'active']], 'roles'=>[701=>['status'=>1]], 'assignments'=>[], 'permissions'=>[[701,'objects.read']]],
    'inactive role' => ['users'=>[7301=>['status'=>1,'activationState'=>'active']], 'roles'=>[701=>['status'=>0]], 'assignments'=>[[7301,701]], 'permissions'=>[[701,'objects.read']]],
    'wrong exact permission' => ['users'=>[7301=>['status'=>1,'activationState'=>'active']], 'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]], 'permissions'=>[[701,'assignment_order.prepare']]],
];
foreach ($denials as $why => $snapshot) {
    $facts->replace($snapshot);
    assertSameValue('ACCESS_DENIED', $authorize->authorizeLocalActor(7301, 'objects.read')->status, $why);
}
foreach (['Objects.Read', 'objects.read ', 'objects.*'] as $nearMatch) {
    $facts->replace(['users'=>[7301=>['status'=>1,'activationState'=>'active']], 'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]], 'permissions'=>[[701,$nearMatch]]]);
    assertSameValue('ACCESS_DENIED', $authorize->authorizeLocalActor(7301, 'objects.read')->status, 'byte-exact denial '.$nearMatch);
}

$facts->replace([
    'users' => [7301 => ['status'=>1,'activationState'=>'active']],
    'roles' => [], 'assignments' => [], 'permissions' => [],
    'legacyEvidence' => ['sameEmailAdministrator' => true, 'sameNameGrant' => 'objects.read'],
]);
assertSameValue('ACCESS_DENIED', $authorize->authorizeLocalActor(7301, 'objects.read')->status, 'legacy/name evidence is never fallback authority');

$facts->replace(['users'=>[7301=>['status'=>1,'activationState'=>'active']], 'roles'=>[701=>['status'=>1],702=>['status'=>1]], 'assignments'=>[[7301,701],[7301,702]], 'permissions'=>[[701,'assignment_order.prepare'],[702,'objects.read']]]);
assertSameValue('AUTHORIZED', $authorize->authorizeLocalActor(7301, 'objects.read')->status, 'permissions union across active assigned roles');
$facts->replace(['users'=>[7301=>['status'=>1,'activationState'=>'active']], 'roles'=>[701=>['status'=>1],702=>['status'=>0]], 'assignments'=>[[7301,701],[7301,702]], 'permissions'=>[[701,'assignment_order.prepare'],[702,'objects.read']]]);
assertSameValue('ACCESS_DENIED', $authorize->authorizeLocalActor(7301, 'objects.read')->status, 'inactive second role contributes no permission');

foreach (['', 'objects.unknown', "objects.read\n"] as $invalidPermission) {
    $unavailable = $authorize->authorizeLocalActor(7301, $invalidPermission);
    assertSameValue('AUTHORIZATION_UNAVAILABLE', $unavailable->status, 'invalid trusted mapping unavailable');
    assertSameValue('AUTHORIZATION_CONFIGURATION_INVALID', $unavailable->category, 'safe invalid mapping category');
    assertSameValue('a1b2c3d4e5f6', $unavailable->correlationId, 'opaque correlation id');
}
$facts->failNextRead('schema');
$schemaUnavailable = $authorize->authorizeLocalActor(7301, 'objects.read');
assertSameValue('AUTHORIZATION_UNAVAILABLE', $schemaUnavailable->status, 'schema incompatibility unavailable');
assertSameValue('AUTHORIZATION_SCHEMA_INVALID', $schemaUnavailable->category, 'safe schema category');
$facts->replace([
    'users' => [
        ['userId'=>7301,'status'=>1,'activationState'=>'active'],
        ['userId'=>7301,'status'=>1,'activationState'=>'active'],
    ],
    'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]],
    'permissions'=>[[701,'objects.read']],
]);
$ambiguousIdentity = $authorize->authorizeLocalActor(7301, 'objects.read');
assertSameValue('AUTHORIZATION_UNAVAILABLE', $ambiguousIdentity->status, 'ambiguous canonical identity fails closed');
assertSameValue('AUTHORIZATION_SCHEMA_INVALID', $ambiguousIdentity->category, 'ambiguous identity is schema-invalid rather than read-failed');
$facts->failNextRead('read');
$readUnavailable = $authorize->authorizeLocalActor(7301, 'objects.read');
assertSameValue('AUTHORIZATION_UNAVAILABLE', $readUnavailable->status, 'RBAC read failure unavailable');
assertSameValue('AUTHORIZATION_READ_FAILED', $readUnavailable->category, 'safe read category');

$facts->replace(['users'=>[7301=>['status'=>1,'activationState'=>'active']], 'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]], 'permissions'=>[[701,'objects.read']]]);
$before = $facts->snapshot();
assertSameValue('AUTHORIZED', $authorize->authorizeLocalActor(7301, 'objects.read')->status, 'first unchanged-snapshot check');
assertSameValue('AUTHORIZED', $authorize->authorizeLocalActor(7301, 'objects.read')->status, 'repeat is deterministic');
assertSameValue($before, $facts->snapshot(), 'authorization is read-only');
$facts->replace(['users'=>[7301=>['status'=>1,'activationState'=>'active']], 'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]], 'permissions'=>[]]);
assertSameValue('ACCESS_DENIED', $authorize->authorizeLocalActor(7301, 'objects.read')->status, 'committed revoke affects next invocation');

// Neither committed state authorizes: before the concurrent commit the actor is
// active but has no grant; afterwards the exact grant exists but the actor is
// inactive.  The public test adapter's deterministic barrier represents the
// point at which a real MariaDB adapter has resolved the user but has not yet
// resolved assigned roles/permissions.  Reading those links from different
// commits would manufacture an authorization that never existed.
$facts->replace([
    'users'=>[7301=>['status'=>1,'activationState'=>'active']],
    'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]],
    'permissions'=>[],
]);
$facts->replaceAtNextReadBarrier('after_identity_before_grants', [
    'users'=>[7301=>['status'=>0,'activationState'=>'active']],
    'roles'=>[701=>['status'=>1]], 'assignments'=>[[7301,701]],
    'permissions'=>[[701,'objects.read']],
]);
$concurrent = $authorize->authorizeLocalActor(7301, 'objects.read');
assertSameValue(
    true,
    in_array($concurrent->status, ['ACCESS_DENIED', 'AUTHORIZATION_UNAVAILABLE'], true),
    'mid-check commit returns one consistent denied snapshot or typed unavailable, never a mixed-state grant'
);
if ($concurrent->status === 'AUTHORIZATION_UNAVAILABLE') {
    assertSameValue('AUTHORIZATION_READ_FAILED', $concurrent->category, 'concurrent snapshot refusal uses safe read-failed category');
    assertSameValue('a1b2c3d4e5f6', $concurrent->correlationId, 'concurrent snapshot refusal keeps opaque correlation');
}

echo "PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract\n";
