<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final class LazyPilotSessionStorage implements \FMonitor\IdentityAccess\PilotSessionStorage
{
    private ?\FMonitor\IdentityAccess\PilotSessionStorage $storage = null;

    public function __construct(
        private readonly EnvironmentSource $environment,
        private readonly \FMonitor\IdentityAccess\PilotSessionFilesystemPrimitives $filesystem,
        private readonly \FMonitor\IdentityAccess\PilotSessionClock $clock,
        private readonly \FMonitor\IdentityAccess\PilotSessionEntropy $entropy,
        private readonly \FMonitor\IdentityAccess\PilotSessionLifecycleObserver $observer,
    ) {}

    public function start(?string $suppliedSessionId): \FMonitor\IdentityAccess\PilotSessionOperationResult { return $this->storage()->start($suppliedSessionId); }
    public function writeCommit(string $sessionId, string $data): \FMonitor\IdentityAccess\PilotSessionOperationResult { return $this->storage()->writeCommit($sessionId, $data); }
    public function regenerate(string $oldSessionId, string $data): \FMonitor\IdentityAccess\PilotSessionOperationResult { return $this->storage()->regenerate($oldSessionId, $data); }
    public function destroyCommit(string $sessionId): \FMonitor\IdentityAccess\PilotSessionOperationResult { return $this->storage()->destroyCommit($sessionId); }
    public function close(): \FMonitor\IdentityAccess\PilotSessionOperationResult { return $this->storage()->close(); }

    private function storage(): \FMonitor\IdentityAccess\PilotSessionStorage
    {
        if ($this->storage !== null) return $this->storage;
        $root = $this->environment->read('FMONITOR_SESSION_STATE_ROOT');
        $instance = $this->environment->read('FMONITOR_SESSION_INSTANCE');
        $config = new \FMonitor\IdentityAccess\PilotSessionStorageConfig(
            $root === false ? '/home/fmonitor/.local/state/fmonitor2' : (string) $root,
            $instance === false ? 'pilot' : (string) $instance,
        );
        return $this->storage = (new \FMonitor\IdentityAccess\PilotSessionStorageFactory())
            ->create($config, $this->filesystem, $this->clock, $this->entropy, $this->observer);
    }
}
