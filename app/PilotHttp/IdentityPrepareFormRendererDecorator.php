<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

interface PrepareFormRendererDecorator
{
    public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer;
}

final class IdentityPrepareFormRendererDecorator implements PrepareFormRendererDecorator
{
    public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer
    {
        return $renderer;
    }
}
