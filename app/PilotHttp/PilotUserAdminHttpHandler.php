<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final class PilotUserAdminHttpHandler
{
    public function __construct(
        private readonly TrustedServerIdentity $identity,
        private readonly ProductionPilotHttpDependencies $dependencies,
    ) {}

    public function handle(PilotHttpRequest $request, array $route, PilotUserAdminSession $session): PilotHttpResponse
    {
        $isInvite = $request->path === '/pilot/admin/users/invite';
        $isReissue = \preg_match('#^/pilot/admin/users/([1-9][0-9]*)/invitation$#D', $request->path, $invitationRoute) === 1;
        $isCommand = $route !== [] || $isInvite || $isReissue;
        $allow = $isCommand ? 'POST' : 'GET, HEAD';
        if (!\in_array($request->method, \explode(', ', $allow), true)) return $this->response($request, 405, "Method not allowed.\n", ['Allow' => $allow]);
        try { $principal = $this->identity->resolve($request->serverIdentity); }
        catch (InvalidServerIdentity) { return $this->response($request, 401, "Authentication required.\n"); }
        try {
            $this->dependencies->css()->readBytes();
            $this->dependencies->pilotCss()->readBytes();
            $trustedActor = $request->server['FMONITOR_AUTH_USER_ID'] ?? null;
            if (\is_string($trustedActor) && PilotE2ECoordinator::positive($trustedActor)) {
                [$db, $prefix, , , $now] = $this->dependencies->commandResources();
                $actor = (new MariaDbLocalUserProfile($db, $prefix))->read((int) $trustedActor);
            } else {
                $actor = $this->dependencies->users()->resolveActiveUser($principal);
                [$db, $prefix, , , $now] = $this->dependencies->commandResources();
            }
            if ($actor === null || !$this->dependencies->hasCapability($actor->id, AccessPolicy::ADMINISTER_ACCESS)) return $this->response($request, 403, "Access denied.\n");
            $directory = new MariaDbPilotUserDirectory($db, $prefix);
            if (!$isCommand) return $this->read($request, $actor, $directory, $session);
            $request = new PilotHttpRequest($request->method, $request->path, $request->host, $request->serverIdentity, $request->server, (string) \file_get_contents('php://input'));
            [$state] = $session->open($request, $actor, false);
            if ($state === null || !$session->validRequest($request, $state, $actor)) return $this->response($request, 403, "Invalid request.\n");
            if ($isReissue) return $this->reissue($request, (int) $invitationRoute[1], $actor, new \FMonitor2\IdentityAccess\MariaDbReissueUserInvitation($db, $prefix), $session, $state);
            if ($isInvite) return $this->invite($request, $actor, $directory, $session, $state, $now);
            return $this->changeRole($request, $route, $actor, $directory, $session, $state, $now);
        } catch (PilotHttpInfrastructureUnavailable|CssAssetUnavailable) {
            return PilotUserAccessResponse::unavailable($request);
        } catch (\Throwable) {
            return PilotUserAccessResponse::unavailable($request);
        }
    }

    private function read(PilotHttpRequest $request, HttpUser $actor, MariaDbPilotUserDirectory $directory, PilotUserAdminSession $session): PilotHttpResponse
    {
        $data = $directory->read();
        if ($request->path === '/pilot/admin/roles') return $this->response($request, 200, (new ProductionUserDirectoryRenderer())->renderRoles($actor, $data), ['Content-Type' => 'text/html; charset=UTF-8']);
        [$state, $headers] = $session->open($request, $actor, true);
        $tokens = [];
        foreach ($data['users'] as $user) $tokens[$user['id']] = $session->token($state, $actor, $user['id']);
        return $this->response($request, 200, (new ProductionUserDirectoryRenderer())->renderUsers($actor, $data, $tokens), ['Content-Type' => 'text/html; charset=UTF-8'] + $headers);
    }

    private function invite(PilotHttpRequest $request, HttpUser $actor, MariaDbPilotUserDirectory $directory, PilotUserAdminSession $session, array &$state, string $now): PilotHttpResponse
    {
        try { $fields = $session->body($request, ['csrfToken', 'email', 'fullName']); }
        catch (InvalidCsrfRequest) { return $this->response($request, 403, "Invalid request.\n"); }
        if ($fields === null || !$session->consume($state, $fields['csrfToken'][0] ?? '', $actor, $actor->id)) return $this->response($request, 403, "Invalid request.\n");
        $created = $directory->inviteUser($fields['email'][0] ?? '', $fields['fullName'][0] ?? '', $actor->id, $now);
        if ($created === null) return $this->response($request, 400, "Bad request.\n");
        return $this->response($request, 201, "Invitation: /pilot/activate?token={$created['token']}\n", ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function reissue(PilotHttpRequest $request, int $userId, HttpUser $actor, \FMonitor2\IdentityAccess\ReissueUserInvitation $application, PilotUserAdminSession $session, array &$state): PilotHttpResponse
    {
        try { $fields = $session->body($request, ['csrfToken']); }
        catch (InvalidCsrfRequest) { return $this->response($request, 403, "Invalid request.\n"); }
        if ($fields === null || !$session->consume($state, $fields['csrfToken'][0] ?? '', $actor, $userId)) return $this->response($request, 403, "Invalid request.\n");
        $result = $application->reissue($actor->id, $userId);
        if ($result['status'] === 'access_denied') return $this->response($request, 403, "Access denied.\n");
        if ($result['status'] !== 'issued') return $this->response($request, 400, "User is not awaiting activation.\n");
        return $this->response($request, 201, "Invitation: /pilot/activate?token={$result['token']}\n", ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function changeRole(PilotHttpRequest $request, array $route, HttpUser $actor, MariaDbPilotUserDirectory $directory, PilotUserAdminSession $session, array &$state, string $now): PilotHttpResponse
    {
        $userId = (int) $route[1];
        $specificRole = isset($route[2]) && $route[2] !== '';
        try { $fields = $session->body($request, $specificRole ? ['csrfToken', 'action'] : ['csrfToken', 'action', 'roleId']); }
        catch (InvalidCsrfRequest) { return $this->response($request, 403, "Invalid request.\n"); }
        if ($fields === null || !$session->consume($state, $fields['csrfToken'][0] ?? '', $actor, $userId)) return $this->response($request, 403, "Invalid request.\n");
        $action = $fields['action'][0] ?? '';
        $roleId = (int) ($specificRole ? $route[2] : ($fields['roleId'][0] ?? 0));
        if ($roleId < 1 || !$directory->changeRole($userId, $roleId, $action, $actor->id, $now)) return $this->response($request, 400, "Bad request.\n");
        return $this->response($request, 303, '', ['Location' => '/pilot/admin/users']);
    }

    private function response(PilotHttpRequest $request, int $status, string $body, array $extra = []): PilotHttpResponse
    {
        return PilotUserAccessResponse::create($request, $status, $body, $extra);
    }
}
