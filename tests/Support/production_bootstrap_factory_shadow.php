<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp { final class ProductionPilotHttpEntrypointFactory{} }
namespace { $entrypoint=require dirname(__DIR__,2).'/app/PilotHttp/production-entrypoint.php';echo "SHADOW-SERVED:",get_debug_type($entrypoint),"\n"; }
