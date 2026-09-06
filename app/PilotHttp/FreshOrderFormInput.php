<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderComposition as C;
final class FreshOrderFormInput
{
    public static function parse(PilotHttpRequest $r,bool $template):array
    {
        $type=$r->server['CONTENT_TYPE']??'';
        if(!is_string($type)||preg_match('~^application/x-www-form-urlencoded(?:\s*;\s*charset=utf-8)?$~iD',$type)!==1)return ['error'=>415,'reason'=>'unsupported_media'];
        $body=$r->body!==''?$r->body:file_get_contents('php://input',false,null,0,32769);
        if(!is_string($body))return ['error'=>503,'reason'=>'dependency_unavailable'];
        if(strlen($body)>32768||(int)($r->server['CONTENT_LENGTH']??0)>32768)return ['error'=>413,'reason'=>'request_too_large'];
        $allowed=$template?['csrfToken']:['csrfToken','requestId','mode','expectedSelectionRevision','controlEngineerUserId','controlEngineerConfirmed','installerTabIds[]'];$fields=[];
        foreach($body===''?[]:explode('&',$body) as $pair){
            if(preg_match('/%(?![0-9a-f]{2})/i',$pair))return ['error'=>400,'reason'=>'invalid_request'];
            $parts=explode('=',$pair,2);$key=urldecode($parts[0]);$value=urldecode($parts[1]??'');
            if(!in_array($key,$allowed,true)||str_contains($value,"\0"))return ['error'=>400,'reason'=>'invalid_request'];
            if($key==='installerTabIds[]'){$fields['installerTabIds'][]=$value;if(count($fields['installerTabIds'])>500)return ['error'=>400,'reason'=>'invalid_request'];}
            elseif(array_key_exists($key,$fields))return ['error'=>400,'reason'=>'invalid_request'];else $fields[$key]=$value;
        }
        return ['fields'=>$fields];
    }
    public static function positive(mixed $v):?int
    { return is_string($v)&&preg_match('/^[1-9][0-9]*$/D',$v)===1&&filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])!==false?(int)$v:null; }
    public static function command(array $f,int $object,int $actor):array
    {
        if(!isset($f['controlEngineerUserId'])||$f['controlEngineerUserId']==='')return ['error'=>422,'reason'=>'control_engineer_required'];
        if(!isset($f['controlEngineerConfirmed']))return ['error'=>422,'reason'=>'confirmation_required'];
        $engineer=self::positive($f['controlEngineerUserId']);$mode=C\AssignmentOrderCompositionMode::tryFrom($f['mode']??'');$revision=$f['expectedSelectionRevision']??null;
        if($engineer===null||$f['controlEngineerConfirmed']!=='yes'||$mode===null||preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$f['requestId']??'')!==1
            ||!is_string($revision)||preg_match('/^(0|[1-9][0-9]*)$/D',$revision)!==1||strlen($revision)>10||(int)$revision>4294967295)return ['error'=>400,'reason'=>'invalid_request'];
        $ids=[];foreach($f['installerTabIds']??[] as $v){$id=self::positive($v);if($id===null)return ['error'=>400,'reason'=>'invalid_request'];$ids[]=new C\InstallerTabId($id);}
        return ['command'=>new C\SelectAssignmentOrderCompositionCommand(new C\SelectionRequestId($f['requestId']),$mode,new C\InstallationObjectId($object),new C\UserId($actor),new C\InstallerTabIdList($ids),new C\UserId($engineer),new C\SelectionRevision((int)$revision))];
    }
}
