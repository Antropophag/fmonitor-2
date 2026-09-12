<?php

declare(strict_types=1);

final class LegacyChecklistProgressMapping
{
    public const VERSION = 'legacy-checklist-progress-mapping-v1';

    public static function profile(array $payload): array
    {
        $events=is_array($payload['checklistEvents']??null)?$payload['checklistEvents']:[];
        $definitions=[];$latest=[];$conflicts=[];
        foreach($events as$index=>$event){
            $definitionId=filter_var($event['checklist_definition_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            $partId=filter_var($event['part_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            $share=filter_var($event['share']??null,FILTER_VALIDATE_FLOAT);
            if($definitionId===false){$conflicts[]='UNKNOWN_CHECKLIST_DEFINITION';continue;}
            if($partId===false||$share===false||$share<0||$share>100){$conflicts[]='INVALID_CHECKLIST_DEFINITION';continue;}
            $signature=$partId.':'.self::decimal((float)$share);
            if(isset($definitions[$definitionId])&&$definitions[$definitionId]!==$signature)$conflicts[]='DEFINITION_CHANGED_WITHIN_SNAPSHOT';
            $definitions[$definitionId]=$signature;
            $key=[(string)($event['ctime']??''),(int)($event['id']??$index)];
            if(!isset($latest[$definitionId])||$key>$latest[$definitionId]['key'])$latest[$definitionId]=['key'=>$key,'value'=>(string)($event['value']??''),'share'=>(float)$share];
        }
        ksort($definitions,SORT_NUMERIC);$share=0.0;
        foreach($latest as$event)if($event['value']==='1')$share+=$event['share'];
        if($share<0||$share>100)$conflicts[]='REPLAYED_PROGRESS_OUT_OF_RANGE';
        $definitionVersion=trim((string)($payload['checklistDefinitionVersion']??''));
        if($definitionVersion==='')$conflicts[]='DEFINITION_VERSION_UNPROVEN';
        $conflicts=array_values(array_unique($conflicts));sort($conflicts,SORT_STRING);
        $fingerprint=hash('sha256',json_encode($definitions,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return ['mappingVersion'=>self::VERSION,'method'=>'latest_event_per_definition_then_sum_enabled_share','candidateProgressBp'=>(int)round($share*100),
            'definitionCount'=>count($definitions),'definitionFingerprint'=>$fingerprint,'sourceDefinitionVersion'=>$definitionVersion!==''?$definitionVersion:null,
            'eligibleForCalculation'=>$conflicts===[],'conflictCodes'=>$conflicts];
    }

    private static function decimal(float $value): string{return rtrim(rtrim(number_format($value,6,'.',''),'0'),'.');}
}
