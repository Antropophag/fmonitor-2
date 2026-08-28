<?php
declare(strict_types=1);

// Specification: PILOT-HTTP-AUTH-001 v0.12, section 11.2.
require dirname(__DIR__).'/bootstrap.php';

/** @return list<string> */
function unqualifiedDirectFunctionCalls(string $source,string $label):array
{
    $tokens=token_get_all($source);
    $offenders=[];
    foreach($tokens as $index=>$token){
        if(!is_array($token)||!in_array($token[0],[T_STRING,T_NAME_QUALIFIED,T_NAME_FULLY_QUALIFIED,T_NAME_RELATIVE],true))continue;
        for($next=$index+1;isset($tokens[$next])&&is_array($tokens[$next])&&in_array($tokens[$next][0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true);++$next){}
        if(($tokens[$next]??null)!=='(')continue;
        for($previous=$index-1;$previous>=0&&is_array($tokens[$previous])&&in_array($tokens[$previous][0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true);--$previous){}
        $previousId=is_array($tokens[$previous]??null)?$tokens[$previous][0]:null;
        if(in_array($previousId,[T_FUNCTION,T_FN,T_NEW,T_OBJECT_OPERATOR,T_NULLSAFE_OBJECT_OPERATOR,T_DOUBLE_COLON],true))continue;

        // Explicit namespace qualification cannot fall back to a global primitive.
        // Bare and namespace-relative calls resolve through the application namespace.
        if(in_array($token[0],[T_NAME_FULLY_QUALIFIED,T_NAME_QUALIFIED],true))continue;
        $offenders[]=$label.':'.$token[2].':'.$token[1];
    }
    return $offenders;
}

assertSameValue(
    ['unlisted-unqualified-probe.php:1:md5'],
    unqualifiedDirectFunctionCalls('<?php namespace FMonitor2\\PilotHttp; md5("probe");','unlisted-unqualified-probe.php'),
    'an unlisted unqualified runtime call cannot escape the open-world oracle'
);
assertSameValue(
    ['namespace-relative-probe.php:1:namespace\\preg_match'],
    unqualifiedDirectFunctionCalls('<?php namespace FMonitor2\\PilotHttp; namespace\\preg_match("//", "");','namespace-relative-probe.php'),
    'a namespace-relative runtime call is forbidden'
);
assertSameValue(
    [],
    unqualifiedDirectFunctionCalls('<?php namespace FMonitor2\\PilotHttp; function declared() {} $object->method(); $object?->optional(); Type::factory(); new Type(); \\strlen("x"); FMonitor2\\PilotHttp\\helper();','syntax-exclusions-probe.php'),
    'declarations, methods, static calls, constructors, and explicitly qualified calls are excluded structurally'
);

$root=dirname(__DIR__,2);
$remaining=[];
foreach(glob($root.'/app/PilotHttp/*.php')?:[] as $file){
    $remaining=array_merge($remaining,unqualifiedDirectFunctionCalls((string)file_get_contents($file),basename($file)));
}
assertSameValue([], $remaining, "all production runtime/global calls are fully-qualified; complete remaining set:\n".implode("\n",$remaining));

echo "PASS: PILOT-HTTP-AUTH-001 complete global-call qualification\n";
