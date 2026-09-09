<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Transport diagnostics never cross this boundary into durable history or output. */
final class OutboxDeliveryOutcome
{
    public static function safe(mixed $value): array
    {
        if(!is_array($value)||!in_array($value['status']??null,['delivered','permanent','retryable','ambiguous_retryable'],true))
            return ['status'=>'ambiguous_retryable','failureCode'=>'TRANSPORT_RESULT_INVALID'];
        $result=['status'=>$value['status']];
        if($value['status']!=='delivered'){
            $codes=['TRANSPORT_TIMEOUT','TRANSPORT_UNAVAILABLE','TRANSPORT_REJECTED','TRANSPORT_RESULT_INVALID','RECIPIENT_INVALID'];
            $result['failureCode']=in_array($value['failureCode']??null,$codes,true)?$value['failureCode']:'TRANSPORT_UNAVAILABLE';
        }
        $provider=$value['providerReference']??null;
        if(is_string($provider)&&$provider!==''&&mb_check_encoding($provider,'UTF-8')&&mb_strlen($provider,'UTF-8')<=200
            &&preg_match('/[\x00-\x1f\x7f]/u',$provider)===0)$result['providerReference']=$provider;
        return $result;
    }
}
