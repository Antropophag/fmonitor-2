<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class AssignmentOrderArtifactService
{
    public function __construct(private readonly object $process,private readonly MariaDbProcessUserDirectory $users,private readonly ContentAddressedArtifactStore $store){}

    public function download(int $installationObjectId,int $version,string $type,int $actorId):array
    {
        if(!$this->users->actorCanPrepareAssignmentOrder($actorId))return ['accepted'=>false,'violations'=>[['code'=>'FORBIDDEN','message'=>'У вас нет права скачивать артефакты распоряжения.','field'=>null]]];
        if($installationObjectId<=0||$version<=0||!in_array($type,['order','appendix'],true))throw new \InvalidArgumentException('Invalid artifact request.');
        try{$projection=$this->process->getInstallationObjectProcess($installationObjectId);$matches=[];foreach($projection['assignmentOrders'] as $order){if(($order['version']??null)===$version){foreach($order['artifacts']??[] as $artifact)if(($artifact['type']??null)===$type)$matches[]=$artifact;}}if(count($matches)!==1)throw new \RuntimeException();$metadata=$matches[0];$filename=$metadata['filename']??null;$media=$metadata['mediaType']??null;$size=$metadata['size']??null;$hash=$metadata['sha256']??null;if(!is_string($filename)||preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,254}$/D',$filename)!==1||str_contains($filename,'..')||basename($filename)!==$filename||$media!=='text/html'||!is_int($size)||$size<0||!is_string($hash)||preg_match('/^[a-f0-9]{64}$/D',$hash)!==1)throw new \RuntimeException();$bytes=$this->store->read($hash,$size);return ['accepted'=>true,'filename'=>$filename,'mediaType'=>$media,'size'=>$size,'sha256'=>$hash,'bytes'=>$bytes];}catch(ArtifactUnavailableException $error){throw $error;}catch(\Throwable){throw new ArtifactUnavailableException('Assignment order artifact is unavailable.');}
    }
}
