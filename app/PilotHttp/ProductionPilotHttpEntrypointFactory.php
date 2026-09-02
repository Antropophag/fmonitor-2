<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
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
        $root=$environment->read('FMONITOR_SESSION_STATE_ROOT');$instance=$environment->read('FMONITOR_SESSION_INSTANCE');$config=new \FMonitor\IdentityAccess\PilotSessionStorageConfig($root===false?'/home/fmonitor/.local/state/fmonitor2':(string)$root,$instance===false?'pilot':(string)$instance);$owner=(new \FMonitor\IdentityAccess\PilotSessionStorageFactory())->create($config,new \FMonitor\IdentityAccess\NativePilotSessionFilesystem(),new \FMonitor\IdentityAccess\SystemPilotSessionClock(),new \FMonitor\IdentityAccess\CsprngPilotSessionEntropy(),new \FMonitor\IdentityAccess\NoOpPilotSessionLifecycleObserver());
        $application=new PilotE2ECoordinator($reads,$identity,$dependencies,$cards,$lists,$forms,$checklists,$owner,$environment->read('FMONITOR_TRUSTED_REQUEST_SCHEME')==='https');
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
        $root=$environment->read('FMONITOR_SESSION_STATE_ROOT');$instance=$environment->read('FMONITOR_SESSION_INSTANCE');
        $config=new \FMonitor\IdentityAccess\PilotSessionStorageConfig(is_string($root)?$root:'',is_string($instance)?$instance:'pilot');
        $owner=(new \FMonitor\IdentityAccess\PilotSessionStorageFactory())->create($config,$filesystem,$clock,$entropy,$observer);
        require_once __DIR__.'/PilotE2ECoordinator.php';$application=new PilotE2ECoordinator($reads,$identity,$dependencies,$cards,$lists,$forms,$checklists,$owner,$environment->read('FMONITOR_TRUSTED_REQUEST_SCHEME')==='https');
        return new PilotHttpEntrypoint(new PilotHttpRequestFactory(),$application,$dependencies,new RandomCorrelationIdSource(),new ErrorLogUnexpectedFailureReporter());
    }
}
