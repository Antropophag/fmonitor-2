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
        $application=new PilotHttpApplication(new RemoteUserIdentity(),new ProductionPilotShellRenderer(),$dependencies,new ProductionObjectCardRenderer(),$dependencies,new ProductionObjectListRenderer(),$dependencies,new ProductionPrepareFormRenderer(),$dependencies);
        return new PilotHttpEntrypoint(new PilotHttpRequestFactory(),$application,$dependencies,new RandomCorrelationIdSource(),new ErrorLogUnexpectedFailureReporter());
    }
}
