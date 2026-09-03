<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
require_once \dirname(__DIR__) . '/autoload.php';
require_once __DIR__.'/PilotHttp.php';
require_once __DIR__.'/PilotHttpApplication.php';
require_once __DIR__.'/PilotHttpEntrypoint.php';
require_once __DIR__.'/IdentityPrepareFormRendererDecorator.php';
final class ProductionPilotHttpEntrypointFactory
{
    public static function create(
        EnvironmentSource $environment,
        ?PrepareFormRendererDecorator $prepareFormRendererDecorator = null,
    ):PilotHttpEntrypoint
    {
        $closer=new NativePhpStreamCloser(new NativePhpFclosePrimitive());
        $dependencies=new ProductionPilotHttpDependencies($environment,new PhpCssDescriptorOpener($closer));
        $identity=new RemoteUserIdentity();$cards=new ProductionObjectCardRenderer();$lists=new ProductionObjectListRenderer();
        $prepareFormRendererDecorator??=new IdentityPrepareFormRendererDecorator();
        $forms=$prepareFormRendererDecorator->decorate(new ProductionPrepareFormRenderer());
        $checklists=new ProductionChecklistRenderer();
        $reads=new PilotHttpApplication($identity,new ProductionPilotShellRenderer(),$dependencies,$cards,$dependencies,$lists,$dependencies,$forms,$dependencies,$checklists,new ProductionLocalObjectListAuthorization($environment),new ProductionLocalObjectListAuthorization($environment,'assignment_order.prepare'));
        require_once __DIR__.'/PilotE2ECoordinator.php';
        $owner=new LazyPilotSessionStorage($environment,new \FMonitor\IdentityAccess\NativePilotSessionFilesystem(),new \FMonitor\IdentityAccess\SystemPilotSessionClock(),new \FMonitor\IdentityAccess\CsprngPilotSessionEntropy(),new \FMonitor\IdentityAccess\NoOpPilotSessionLifecycleObserver());
        $application=new PilotE2ECoordinator($reads,$identity,$dependencies,$cards,$lists,$forms,$checklists,$owner,$environment);
        return new PilotHttpEntrypoint(new PilotHttpRequestFactory(),$application,$dependencies,new RandomCorrelationIdSource(),new ErrorLogUnexpectedFailureReporter());
    }

    public static function createWithSessionStorageDependencies(
        EnvironmentSource $environment,
        \FMonitor\IdentityAccess\PilotSessionFilesystemPrimitives $filesystem,
        \FMonitor\IdentityAccess\PilotSessionClock $clock,
        \FMonitor\IdentityAccess\PilotSessionEntropy $entropy,
        \FMonitor\IdentityAccess\PilotSessionLifecycleObserver $observer,
    ): PilotHttpEntrypoint {
        $closer=new NativePhpStreamCloser(new NativePhpFclosePrimitive());
        $dependencies=new ProductionPilotHttpDependencies($environment,new PhpCssDescriptorOpener($closer));
        $identity=new RemoteUserIdentity();$cards=new ProductionObjectCardRenderer();$lists=new ProductionObjectListRenderer();$forms=(new IdentityPrepareFormRendererDecorator())->decorate(new ProductionPrepareFormRenderer());$checklists=new ProductionChecklistRenderer();
        $reads=new PilotHttpApplication($identity,new ProductionPilotShellRenderer(),$dependencies,$cards,$dependencies,$lists,$dependencies,$forms,$dependencies,$checklists,new ProductionLocalObjectListAuthorization($environment),new ProductionLocalObjectListAuthorization($environment,'assignment_order.prepare'));
        $owner=new LazyPilotSessionStorage($environment,$filesystem,$clock,$entropy,$observer);
        require_once __DIR__.'/PilotE2ECoordinator.php';$application=new PilotE2ECoordinator($reads,$identity,$dependencies,$cards,$lists,$forms,$checklists,$owner,$environment);
        return new PilotHttpEntrypoint(new PilotHttpRequestFactory(),$application,$dependencies,new RandomCorrelationIdSource(),new ErrorLogUnexpectedFailureReporter());
    }
}
