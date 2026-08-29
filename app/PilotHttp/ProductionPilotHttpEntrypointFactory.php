<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
require_once __DIR__.'/PilotHttp.php';
require_once __DIR__.'/PilotHttpApplication.php';
require_once __DIR__.'/PilotHttpEntrypoint.php';
final class ProductionPilotHttpEntrypointFactory
{
    public static function create(EnvironmentSource $environment):PilotHttpEntrypoint
    {
        $closer=new NativePhpStreamCloser(new NativePhpFclosePrimitive());
        $dependencies=new ProductionPilotHttpDependencies($environment,new PhpCssDescriptorOpener($closer));
        $identity=new RemoteUserIdentity();$cards=new ProductionObjectCardRenderer();$lists=new ProductionObjectListRenderer();$forms=new ProductionPrepareFormRenderer();$checklists=new ProductionChecklistRenderer();
        $reads=new PilotHttpApplication($identity,new ProductionPilotShellRenderer(),$dependencies,$cards,$dependencies,$lists,$dependencies,$forms,$dependencies,$checklists);
        require_once __DIR__.'/PilotE2ECoordinator.php';
        $application=new PilotE2ECoordinator($reads,$identity,$dependencies,$cards,$lists,$forms);
        return new PilotHttpEntrypoint(new PilotHttpRequestFactory(),$application,$dependencies,new RandomCorrelationIdSource(),new ErrorLogUnexpectedFailureReporter());
    }
}
