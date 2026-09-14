<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class StandBackupBundle
{
    private const AUTH = 'owner-2026-09-13-issue-76-stand-backup';
    private const SOURCE = '404aa6858b8fdd61ac0e8151f09a67000a387ead';
    private const IMAGE = 'fmonitor2-runtime@sha256:2222222222222222222222222222222222222222222222222222222222222222';
    private const MEMBERS = ['database.sql', 'artifacts.tar', 'sessions.json'];
    private const VOLUMES = ['database'=>['name'=>'test-fm2-db','observed_id'=>'db-volume-id'],'artifacts'=>['name'=>'test-fm2-artifacts','observed_id'=>'artifact-volume-id'],'sessions'=>['name'=>'test-fm2-sessions','observed_id'=>'session-volume-id']];

    /** @return array{manifest:array,evidence:string,target_digest:string} */
    public static function target(string $path): array
    {
        $bytes = StandBackupFilesystem::regularBytes($path);
        $value = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        $keys = ['authorization_id','compose_file','database','evidence_root','image','inventory_digest','project','source','version','volumes'];
        if (!self::keys($value, $keys) || StandBackupFilesystem::canonical($value) !== $bytes || $value['version'] !== 1) throw new \RuntimeException();
        foreach (['authorization_id','compose_file','image','inventory_digest','project','source'] as $key) if (!is_string($value[$key]) || $value[$key] === '' || str_contains($value[$key], '${')) throw new \RuntimeException();
        if (!preg_match('/^[0-9a-f]{40}$/D', $value['source']) || !preg_match('/^fmonitor2-runtime@sha256:[0-9a-f]{64}$/D',$value['image']) || !self::hex($value['inventory_digest']) || in_array($value['project'], ['default','neighbor'], true)) throw new \RuntimeException();
        $compose = realpath($value['compose_file']);
        $expected = realpath(dirname(__DIR__, 2).'/deploy/runtime/compose.yaml');
        if ($compose === false || $compose !== $value['compose_file'] || $compose !== $expected || self::symlinkPath($value['compose_file'])) throw new \RuntimeException();
        $legacy=$value['authorization_id']===self::AUTH&&$value['source']===self::SOURCE&&$value['image']===self::IMAGE&&in_array($value['project'],['test-fmonitor2-backup','production-fmonitor2'],true);
        if (!self::keys($value['database'], ['name','observed_id']) || !self::keys($value['volumes'], ['artifacts','database','sessions'])) throw new \RuntimeException();
        if($legacy){if($value['database']!==['name'=>'test_fmonitor2','observed_id'=>'db-id'])throw new \RuntimeException();foreach(self::VOLUMES as$role=>$expectedVolume)if(!self::keys($value['volumes'][$role]??null,['name','observed_id'])||$value['volumes'][$role]!==$expectedVolume)throw new \RuntimeException();}
        else{if(!str_starts_with($value['project'],'fm2-disposable-')||!str_starts_with((string)$value['database']['name'],'fm2_disposable_')||!is_string($value['database']['observed_id'])||$value['database']['observed_id']==='')throw new \RuntimeException();foreach(['database','artifacts','sessions']as$role){$volume=$value['volumes'][$role]??null;if(!self::keys($volume,['name','observed_id'])||!is_string($volume['name'])||$volume['name']===''||!is_string($volume['observed_id'])||$volume['observed_id']==='')throw new \RuntimeException();}}
        $evidence = self::evidence($value['evidence_root']);
        return ['manifest'=>$value, 'evidence'=>$evidence, 'target_digest'=>StandBackupFilesystem::digest(StandBackupFilesystem::canonical($value))];
    }

    /** @return array{manifest:array,payloads:array<string,string>} */
    public static function verified(array $target, string $digest): array
    {
        if (!self::hex($digest)) throw new \RuntimeException();
        $evidence = $target['evidence']; $manifest = $target['manifest']; $targetDigest = $target['target_digest'];
        $dir = $evidence.'/bundles/'.$digest;
        $stat = @lstat($dir);
        if ($stat === false || ($stat['mode'] & 0170000) !== 0040000 || is_link($dir)) throw new \RuntimeException();
        $names = array_values(array_diff(scandir($dir) ?: [], ['.','..'])); sort($names);
        $wanted = [...self::MEMBERS, 'manifest.json']; sort($wanted);
        if ($names !== $wanted) throw new \RuntimeException();
        $bytes = StandBackupFilesystem::regularBytes($dir.'/manifest.json');
        $bundle = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        if (!self::keys($bundle, ['database','files','image','operation_id','source','target_digest','version','volumes']) || $bundle['version'] !== 1 || $bundle['target_digest'] !== $targetDigest || $bundle['source'] !== $manifest['source'] || $bundle['image'] !== $manifest['image'] || $bundle['database'] !== $manifest['database'] || $bundle['volumes'] !== $manifest['volumes'] || !self::uuid($bundle['operation_id'] ?? null) || !self::keys($bundle['files'], self::MEMBERS) || StandBackupFilesystem::canonical($bundle) !== $bytes || StandBackupFilesystem::digest($bytes) !== $digest) throw new \RuntimeException();
        $payloads = [];
        foreach (self::MEMBERS as $name) {
            if (!self::keys($bundle['files'][$name] ?? null, ['sha256','size'])) throw new \RuntimeException();
            $payloads[$name] = StandBackupFilesystem::regularBytes($dir.'/'.$name);
            if ($bundle['files'][$name] != ['size'=>strlen($payloads[$name]), 'sha256'=>StandBackupFilesystem::digest($payloads[$name])]) throw new \RuntimeException();
        }
        $pointerBytes = StandBackupFilesystem::regularBytes($evidence.'/verified.json');
        $pointer = json_decode($pointerBytes, true, 512, JSON_THROW_ON_ERROR);
        if ($pointer != ['version'=>1,'bundle_digest'=>$digest,'target_digest'=>$targetDigest,'operation_id'=>$bundle['operation_id']] || StandBackupFilesystem::canonical($pointer) !== $pointerBytes) throw new \RuntimeException();
        return ['manifest'=>$bundle, 'payloads'=>$payloads];
    }

    public static function uuid(mixed $value): bool { return is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $value) === 1 && $value !== '00000000-0000-0000-0000-000000000000'; }
    public static function hex(mixed $value): bool { return is_string($value) && preg_match('/^[0-9a-f]{64}$/D', $value) === 1; }
    private static function keys(mixed $value, array $keys): bool { if (!is_array($value) || array_is_list($value)) return false; $actual=array_keys($value); sort($actual); sort($keys); return $actual === $keys; }
    private static function evidence(mixed $path): string { if (!is_string($path) || $path === '' || $path[0] !== '/' || $path === '/') throw new \RuntimeException(); $real=realpath($path); if ($real === false || $real !== $path || !is_dir($real) || self::symlinkPath($path)) throw new \RuntimeException(); $root=realpath(dirname(__DIR__,2)); $account=function_exists('posix_getpwuid')?posix_getpwuid(posix_geteuid()):false; $home=is_array($account)?realpath($account['dir']):false; if ($real===$root || ($home!==false&&$real===$home) || str_starts_with($real,$root.'/') || str_starts_with($root,$real.'/')) throw new \RuntimeException(); return $real; }
    private static function symlinkPath(string $path): bool { while ($path !== dirname($path)) { if (is_link($path)) return true; $path=dirname($path); } return false; }
}
