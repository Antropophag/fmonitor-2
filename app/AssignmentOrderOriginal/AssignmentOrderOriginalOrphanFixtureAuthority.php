<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Immutable authority for a verifier-owned root, never an application selector. */
final class AssignmentOrderOriginalOrphanFixtureAuthority
{
    private array $identity;
    public function __construct(private string $root,private string $token,private AssignmentOrderOriginalProductionConfig $production)
    { $this->identity=$this->inspect(); }
    public function validate():void
    {if($this->inspect()!==$this->identity)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();}
    private function inspect():array
    {
        if(preg_match('/^[a-f0-9]{32}$/D',$this->token)!==1)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
        AssignmentOrderOriginalFileStorage::validateRoot($this->production->privateStorageRoot);
        $production=(string)realpath($this->production->privateStorageRoot);$productionStat=@lstat($production);if($productionStat===false)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
        $repository=dirname(__DIR__,2);
        if($this->root===''||$this->root[0]!=='/'||preg_match('/[\x00-\x1f\x7f]/',$this->root)!==0
            ||in_array('..',explode('/',$this->root),true)||@realpath($this->root)!==$this->root
            ||$this->root===$repository||str_starts_with($this->root,$repository.'/')
            ||$this->root===$production||str_starts_with($this->root,$production.'/')||str_starts_with($production,$this->root.'/'))throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
        AssignmentOrderOriginalFileStorage::validateRoot($this->root);
        $root=$this->stat($this->root,true);
        $marker=$this->root.'/.aoou-verifier-owner';$mark=$this->stat($marker,false);
        if($mark['uid']!==$root['uid']||@file_get_contents($marker)!=="aoou-private-orphan-fixture-v1\n{$this->token}\n")throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
        $this->members($this->root,$root['uid']);
        return [$root['dev'],$root['ino'],$root['uid'],$mark['dev'],$mark['ino'],$productionStat['dev'],$productionStat['ino']];
    }
    private function stat(string $path,bool $directory):array
    {
        clearstatcache(true,$path);$s=@lstat($path);$uid=function_exists('posix_geteuid')?posix_geteuid():getmyuid();
        if($s===false||($s['mode']&0170000)!==($directory?0040000:0100000)
            ||($s['mode']&07777)!==($directory?0700:0600)||!in_array($s['uid'],[0,$uid],true)
            ||(!$directory&&$s['nlink']!==1))throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
        return $s;
    }
    private function members(string $directory,int $owner):void
    {
        $entries=@scandir($directory);if($entries===false)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
        foreach($entries as $entry){
            if($entry==='.'||$entry==='..')continue;
            $path=$directory.'/'.$entry;clearstatcache(true,$path);$s=@lstat($path);
            if($s===false)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
            $dir=($s['mode']&0170000)===0040000;$s=$this->stat($path,$dir);
            if($s['uid']!==$owner)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
            if($dir)$this->members($path,$owner);
        }
    }
}
