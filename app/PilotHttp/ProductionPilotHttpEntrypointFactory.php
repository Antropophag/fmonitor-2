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
        $owner=PilotSessionRequestOwner::native($environment);
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
        $owner=PilotSessionRequestOwner::bind(new LazyPilotSessionStorage($environment,$filesystem,$clock,$entropy,$observer));
        require_once __DIR__.'/PilotE2ECoordinator.php';$application=new PilotE2ECoordinator($reads,$identity,$dependencies,$cards,$lists,$forms,$checklists,$owner,$environment);
        return new PilotHttpEntrypoint(new PilotHttpRequestFactory(),$application,$dependencies,new RandomCorrelationIdSource(),new ErrorLogUnexpectedFailureReporter(),self::localAuth($owner));
    }
    private static function localAuth(\FMonitor\IdentityAccess\PilotSessionStorage$owner):\Closure{return static function(array$server)use($owner):void{if(\is_string($server['REMOTE_USER']??null)&&$server['REMOTE_USER']!=='')return;$path=\parse_url((string)($server['REQUEST_URI']??''),PHP_URL_PATH);if(!\is_string($path)||!PilotRouteAdmission::isKnown($path))return;require_once \dirname(__DIR__,2).'/rapid-pilot/LocalAuth.php';(new \RapidPilotLocalAuth($owner))->handle($path);};}
}
