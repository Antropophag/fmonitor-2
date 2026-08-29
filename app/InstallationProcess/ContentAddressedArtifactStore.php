<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class ContentAddressedArtifactStore
{
    private readonly string $root; private readonly string $home; private readonly int $effectiveUid;
    private readonly int $homeDevice; private readonly int $homeInode; private readonly int $rootDevice; private readonly int $rootInode;

    public function __construct(string $root)
    {
        try {
            if(!function_exists('posix_geteuid')||!function_exists('posix_getpwuid')||!function_exists('posix_getpwnam'))throw new \RuntimeException();
            $uid=posix_geteuid();$account=posix_getpwuid($uid);if(!is_array($account)||!isset($account['name'],$account['dir']))throw new \RuntimeException();
            $named=posix_getpwnam((string)$account['name']);if(!is_array($named)||(int)$named['uid']!==$uid)throw new \RuntimeException();
            $home=realpath((string)$account['dir']);if($home===false||$home==='/')throw new \RuntimeException();$environmentHome=getenv('HOME');if($environmentHome!==false&&$environmentHome!==''&&realpath($environmentHome)!==$home)throw new \RuntimeException();
            $real=realpath($root);if($root===''||$root[0]!=='/'||$real===false||$real!==$root||!str_starts_with($real,$home.DIRECTORY_SEPARATOR)||$real==='/tmp'||str_starts_with($real,'/tmp/'))throw new \RuntimeException();
            $homeInfo=@lstat($home);$rootInfo=@lstat($real);if($homeInfo===false||$rootInfo===false)throw new \RuntimeException();
            $this->root=$real;$this->home=$home;$this->effectiveUid=$uid;$this->homeDevice=(int)$homeInfo['dev'];$this->homeInode=(int)$homeInfo['ino'];$this->rootDevice=(int)$rootInfo['dev'];$this->rootInode=(int)$rootInfo['ino'];$this->validateRoot();
        } catch(\Throwable){throw new \InvalidArgumentException('Invalid artifact storage root.');}
    }

    public function store(string $bytes): array
    {
        $hash=hash('sha256',$bytes);$size=strlen($bytes);
        try{$this->validateRoot();$directory=$this->leafDirectory($hash,true);$target=$directory.'/'.$hash;$this->validateRoot();if(file_exists($target)||is_link($target)){$this->verifyExisting($bytes,$hash,$size);return ['size'=>$size,'sha256'=>$hash];}
            $this->validateRoot();$temporary=tempnam($directory,'.artifact-');if($temporary===false)throw new \RuntimeException();
            try{$this->validateRoot();if(!chmod($temporary,0640))throw new \RuntimeException();$handle=fopen($temporary,'wb');if($handle===false)throw new \RuntimeException();try{$offset=0;while($offset<$size){$written=fwrite($handle,substr($bytes,$offset));if($written===false||$written===0)throw new \RuntimeException();$offset+=$written;}if(!fflush($handle))throw new \RuntimeException();if(function_exists('fsync')&&!fsync($handle))throw new \RuntimeException();}finally{fclose($handle);}$this->validateRoot();$this->validateShardChain($hash);if(!@link($temporary,$target)){$this->validateRoot();$this->validateShardChain($hash);if(!file_exists($target)&&!is_link($target))throw new \RuntimeException();$this->verifyExisting($bytes,$hash,$size);}}finally{if(isset($temporary)&&file_exists($temporary))@unlink($temporary);}
            $this->validateRoot();$this->verifyExisting($bytes,$hash,$size);return ['size'=>$size,'sha256'=>$hash];
        }catch(\Throwable $error){if($error instanceof ArtifactStorageException)throw $error;throw new ArtifactStorageException('Assignment order artifact storage failed.');}
    }

    public function read(string $hash,int $expectedSize): string
    {
        if(preg_match('/^[a-f0-9]{64}$/D',$hash)!==1||$expectedSize<0)throw new ArtifactIntegrityException('Assignment order artifact is unavailable.');
        try{$this->validateRoot();}catch(\Throwable){throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');}
        try{$directory=$this->leafDirectory($hash,false);}catch(ArtifactNotFoundException $error){throw $error;}catch(\Throwable){throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');}
        $path=$directory.'/'.$hash;
        try{$this->validateRoot();$this->validateShardChain($hash);}catch(\Throwable){throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');}
        $before=@lstat($path);if($before===false){try{$this->validateRoot();$this->validateShardChain($hash);}catch(\Throwable){throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');}$before=@lstat($path);if($before===false)throw new ArtifactNotFoundException('Assignment order artifact is unavailable.');}
        if(($before['mode']&0170000)!==0100000||is_link($path)||(int)$before['uid']!==$this->effectiveUid||(($before['mode']&0777)&~0640)!==0)throw new ArtifactIntegrityException('Assignment order artifact is unavailable.');
        try{$this->validateRoot();}catch(\Throwable){throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');}
        $handle=@fopen($path,'rb');if($handle===false)throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');
        try{
            $after=fstat($handle);if($after===false)throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');
            if(($after['mode']&0170000)!==0100000||(int)$after['uid']!==$this->effectiveUid||(($after['mode']&0777)&~0640)!==0||(int)$after['dev']!==(int)$before['dev']||(int)$after['ino']!==(int)$before['ino']||(int)$after['size']!==$expectedSize)throw new ArtifactIntegrityException('Assignment order artifact is unavailable.');
            $bytes='';$remaining=$expectedSize+1;while($remaining>0&&!feof($handle)){$chunk=fread($handle,min(8192,$remaining));if($chunk===false)throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');if($chunk==='')break;$bytes.=$chunk;$remaining-=strlen($chunk);}
        }finally{fclose($handle);}
        try{$this->validateRoot();}catch(\Throwable){throw new ArtifactStoreUnavailableException('Assignment order artifact is unavailable.');}
        if(strlen($bytes)!==$expectedSize||!hash_equals($hash,hash('sha256',$bytes)))throw new ArtifactIntegrityException('Assignment order artifact is unavailable.');
        return $bytes;
    }

    private function leafDirectory(string $hash,bool $create): string
    {
        if(preg_match('/^[a-f0-9]{64}$/D',$hash)!==1)throw new \RuntimeException();$path=$this->root;foreach(['sha256',substr($hash,0,2),substr($hash,2,2)] as $component){$this->validateRoot();$parent=$path;$path.='/'.$component;if(!file_exists($path)&&!is_link($path)){if(!$create){$managedParent=$parent!==$this->root;$beforeParent=$this->validateDirectory($parent,true,$managedParent);if(@lstat($path)===false){$this->validateRoot();$afterParent=$this->validateDirectory($parent,true,$managedParent);if((int)$beforeParent['dev']!==(int)$afterParent['dev']||(int)$beforeParent['ino']!==(int)$afterParent['ino']||@lstat($path)!==false)throw new \RuntimeException();throw new ArtifactNotFoundException('Assignment order artifact is unavailable.');}}elseif(!@mkdir($path,0750)&&!file_exists($path))throw new \RuntimeException();$this->validateRoot();}$this->validateDirectory($path,true,true);}return $path;
    }
    private function validateRoot(): void
    {
        $homeInfo=$this->validateDirectory($this->home,false,false);$rootInfo=null;$relative=substr($this->root,strlen($this->home)+1);$path=$this->home;foreach(explode(DIRECTORY_SEPARATOR,$relative) as $component){if($component===''||$component==='.'||$component==='..')throw new \RuntimeException();$path.='/'.$component;$rootInfo=$this->validateDirectory($path,$path===$this->root,false);}if((int)$homeInfo['dev']!==$this->homeDevice||(int)$homeInfo['ino']!==$this->homeInode||$rootInfo===null||(int)$rootInfo['dev']!==$this->rootDevice||(int)$rootInfo['ino']!==$this->rootInode||(int)$rootInfo['uid']!==$this->effectiveUid||!is_readable($this->root)||!is_writable($this->root)||!is_executable($this->root))throw new \RuntimeException();
    }
    private function validateShardChain(string $hash): void{$path=$this->root;foreach(['sha256',substr($hash,0,2),substr($hash,2,2)] as $component){$path.='/'.$component;$this->validateDirectory($path,true,true);}}
    private function validateDirectory(string $path,bool $requireOwner,bool $managed): array{$info=@lstat($path);$permissions=$info===false?0:$info['mode']&0777;$invalidPermissions=$managed?($permissions&~0750)!==0:($permissions&0022)!==0;$inaccessible=$requireOwner&&(!@is_readable($path)||!@is_executable($path));if($info===false||($info['mode']&0170000)!==0040000||is_link($path)||($requireOwner&&(int)$info['uid']!==$this->effectiveUid)||$invalidPermissions||$inaccessible)throw new \RuntimeException();return $info;}
    private function verifyExisting(string $bytes,string $hash,int $size): void{$stored=$this->read($hash,$size);if(!hash_equals($bytes,$stored))throw new ArtifactStorageException('Assignment order artifact storage failed.');}
}
