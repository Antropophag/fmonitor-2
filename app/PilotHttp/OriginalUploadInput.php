<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class OriginalUploadInput
{
    public static function metadata(PilotHttpRequest $r):array
    {
        if(($r->server['CONTENT_TYPE']??null)!=='application/pdf')return ['error'=>'UNSUPPORTED_MEDIA_TYPE','status'=>415];
        if(isset($r->server['HTTP_TRANSFER_ENCODING']))return self::invalid();
        $length=$r->server['CONTENT_LENGTH']??null;
        if($length===null)return ['error'=>'LENGTH_REQUIRED','status'=>411];
        if(!\is_string($length)||\preg_match('/^(0|[1-9][0-9]*)$/D',$length)!==1)return self::invalid();
        if(\strlen($length)>8||(int)$length>20971520)return ['error'=>'REQUEST_TOO_LARGE','status'=>413];
        $encoded=$r->server['HTTP_X_FMONITOR_ORIGINAL']??null;
        if(!\is_string($encoded)||\strlen($encoded)>16384)return self::invalid();
        $json=\base64_decode($encoded,true);if($json===false||\base64_encode($json)!==$encoded)return self::invalid();
        try{$fields=\json_decode($json,true,32,JSON_THROW_ON_ERROR);
            $keys=['csrfToken','requestId','mode','documentDate','compositionConfirmed','rootOriginalId','targetRevisionId','expectedCurrentRevisionId','correctionReason','originalFilename'];
            if(!\is_array($fields)||\array_keys($fields)!==$keys)return self::invalid();
            foreach(['csrfToken','requestId','mode','documentDate','originalFilename'] as $key)if(!\is_string($fields[$key]))return self::invalid();
            foreach(['rootOriginalId','targetRevisionId','expectedCurrentRevisionId','correctionReason'] as $key)if($fields[$key]!==null&&!\is_string($fields[$key]))return self::invalid();
            if(!\is_bool($fields['compositionConfirmed'])||!\in_array($fields['mode'],['initial','correction'],true)
                ||\preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$fields['requestId'])!==1)return self::invalid();
            if(\json_encode($fields,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_LINE_TERMINATORS)!==$json)return self::invalid();
            return ['fields'=>$fields,'length'=>(int)$length];
        }catch(\Throwable){return self::invalid();}
    }
    private static function invalid():array { return ['error'=>'INVALID_REQUEST','status'=>400]; }
}
