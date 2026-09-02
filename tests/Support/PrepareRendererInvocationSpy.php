<?php
declare(strict_types=1);

use FMonitor2\PilotHttp\PrepareFormRenderer;

if (interface_exists('FMonitor2\\PilotHttp\\PrepareFormRendererDecorator')) {
    final class PrepareRendererInvocationSpyDecorator implements \FMonitor2\PilotHttp\PrepareFormRendererDecorator
    {
        public function __construct(
            private readonly string $decorateCounter,
            private readonly string $renderCounter,
            private readonly bool $requireProductionRenderer = false,
        ) {}

        public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer
        {
            PrepareRendererSpyCounter::hit($this->decorateCounter);
            if ($this->requireProductionRenderer && !$renderer instanceof \FMonitor2\PilotHttp\ProductionPrepareFormRenderer) {
                throw new RuntimeException('canonical factory did not supply its real production renderer');
            }

            return new PrepareRendererInvocationSpy($renderer, $this->renderCounter);
        }
    }
} else {
    final class PrepareRendererInvocationSpyDecorator
    {
        public function __construct(
            private readonly string $decorateCounter,
            private readonly string $renderCounter,
            private readonly bool $requireProductionRenderer = false,
        ) {}

        public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer
        {
            PrepareRendererSpyCounter::hit($this->decorateCounter);
            if ($this->requireProductionRenderer && !$renderer instanceof \FMonitor2\PilotHttp\ProductionPrepareFormRenderer) {
                throw new RuntimeException('canonical factory did not supply its real production renderer');
            }

            return new PrepareRendererInvocationSpy($renderer, $this->renderCounter);
        }
    }
}

final class PrepareRendererInvocationSpy implements PrepareFormRenderer, \FMonitor2\PilotHttp\CompatibilityPrepareFormRenderer
{
    public function __construct(
        private readonly PrepareFormRenderer $real,
        private readonly string $renderCounter,
    ) {}

    public function render(\FMonitor2\PilotHttp\HttpUser $user, array $form): string
    {
        PrepareRendererSpyCounter::hit($this->renderCounter);

        return $this->real->render($user, $form);
    }

    public function renderCompatibility(\FMonitor2\PilotHttp\HttpUser $user, array $form): string
    {
        PrepareRendererSpyCounter::hit($this->renderCounter);
        if (!$this->real instanceof \FMonitor2\PilotHttp\CompatibilityPrepareFormRenderer) {
            throw new RuntimeException('canonical renderer lost compatibility contract');
        }

        return $this->real->renderCompatibility($user, $form);
    }
}

final class PrepareRendererSpyCounter
{
    public static function hit(string $counter): void
    {
        $handle = fopen($counter, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            throw new RuntimeException('cannot lock renderer spy counter');
        }
        $bytes = stream_get_contents($handle);
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) (((int) $bytes) + 1));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
