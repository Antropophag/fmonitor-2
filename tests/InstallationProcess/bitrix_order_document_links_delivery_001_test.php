<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\InstallationProcess\{BitrixOrderDocumentDeliveryConfig,NativeBitrixOrderDocumentDelivery};

assertSameValue(true,class_exists(NativeBitrixOrderDocumentDelivery::class),'INTENDED_RED A1 delivery missing');
$json=static fn(array $value):array=>['status'=>200,'headers'=>[],'body'=>json_encode($value,JSON_THROW_ON_ERROR)];
$config=new BitrixOrderDocumentDeliveryConfig('https://tenant.bitrix24.test',6546,1809812,'/online/','synthetic-token','/synthetic-ca.pem',50,100,1048576,30);
$calls=[];
$transport=static function(string $method,string $url,array $options)use(&$calls,$json):array{
    $calls[]=[$method,$url,$options];
    parse_str((string)parse_url($url,PHP_URL_QUERY),$query);
    if(str_contains($url,'getchildren')){
        $start=(int)($query['start']??0);
        return $json(['result'=>$start===0?[['ID'=>'501','NAME'=>'0012.03','TYPE'=>'folder']]:[['ID'=>'502','NAME'=>'1.3-2.3','TYPE'=>'folder']],'total'=>2,'next'=>$start===0?1:null]);
    }
    if(str_contains($url,'getExternalLink'))return $json(['result'=>'https://tenant.bitrix24.test/docs/'.($query['id']??'')]);
    throw new TestFailure('unexpected request '.$method.' '.$url);
};
$result=(new NativeBitrixOrderDocumentDelivery($config,$transport))->fetch();
assertSameValue(['complete',null],[$result->kind,$result->reason],'complete delivery');
assertSameValue(
    [['501','0012.03','0012.03'],['502','1.3-2.3','1.3'],['502','1.3-2.3','2.3']],
    array_map(fn($link)=>[$link['sourceFolderId'],$link['sourceFolderName'],$link['orderNumber']],$result->links),
    'direct folders, pagination and legacy range'
);
assertSameValue([0,1],array_map(static function(array $call):int{parse_str((string)parse_url($call[1],PHP_URL_QUERY),$query);return(int)($query['start']??0);},array_values(array_filter($calls,fn($call)=>str_contains($call[1],'getchildren')))),'monotonic pages');
assertSameValue(false,str_contains(json_encode($result,JSON_THROW_ON_ERROR),'synthetic-token'),'public result hides secrets');

$fetch=static function(array $responses,?BitrixOrderDocumentDeliveryConfig $caseConfig=null)use($config):object{
    $caseConfig??=$config;$counts=[];
    $transport=static function(string $method,string $url,array $options)use(&$counts,$responses):array{
        $key=str_contains($url,'getchildren')?'children':'link';$index=$counts[$key]??0;$counts[$key]=$index+1;
        $response=$responses[$key.'#'.$index]??$responses[$key]??null;
        if($response instanceof Throwable)throw $response;
        return is_array($response)?$response:['status'=>500,'headers'=>[],'body'=>'private upstream bytes'];
    };
    return(new NativeBitrixOrderDocumentDelivery($caseConfig,$transport))->fetch();
};

$failures=[
    'TRANSPORT_FAILED'=>['children'=>new RuntimeException('private network')],
    'SCHEMA_INVALID'=>['children'=>['status'=>200,'headers'=>[],'body'=>'{bad json']],
    'API_FAILED'=>['children'=>['status'=>500,'headers'=>[],'body'=>'private api']],
    'PAGINATION_INVALID'=>['children'=>$json(['result'=>[],'total'=>2,'next'=>0])],
    'AMBIGUOUS_FOLDER_NAME'=>['children'=>$json(['result'=>[['ID'=>'7','NAME'=>'01.3-03.3','TYPE'=>'folder']],'total'=>1])],
    'URL_INVALID'=>['children'=>$json(['result'=>[['ID'=>'7','NAME'=>'ONE','TYPE'=>'folder']],'total'=>1]),'link'=>$json(['result'=>'https://user@tenant.bitrix24.test.evil/docs/7'])],
];
foreach($failures as $reason=>$responses){
    $failed=$fetch($responses);
    assertSameValue(['failed',$reason,[]],[$failed->kind,$failed->reason,$failed->links],'whole delivery fails closed: '.$reason);
    assertSameValue(false,str_contains(json_encode($failed,JSON_THROW_ON_ERROR),'private'),'failure hides upstream details');
}

$limited=new BitrixOrderDocumentDeliveryConfig('https://tenant.bitrix24.test',6546,1809812,'/online/','synthetic-token','/synthetic-ca.pem',1,1,1048576,30);
$failed=$fetch(['children'=>$json(['result'=>[['ID'=>'1','NAME'=>'ONE','TYPE'=>'folder'],['ID'=>'2','NAME'=>'TWO','TYPE'=>'folder']],'total'=>2])],$limited);
assertSameValue(['failed','LIMIT_EXCEEDED',[]],[$failed->kind,$failed->reason,$failed->links],'bounded item count');
$badConfig=new BitrixOrderDocumentDeliveryConfig('http://tenant.bitrix24.test',6546,1809812,'/online/','synthetic-token','/synthetic-ca.pem',50,100,1048576,30);
$failed=$fetch([],$badConfig);assertSameValue(['failed','CONFIGURATION_UNAVAILABLE',[]],[$failed->kind,$failed->reason,$failed->links],'unsafe configuration fails before transport');
$byteLimited=new BitrixOrderDocumentDeliveryConfig('https://tenant.bitrix24.test',6546,1809812,'/online/','synthetic-token','/synthetic-ca.pem',50,100,8,30);
$failed=$fetch(['children'=>['status'=>200,'headers'=>[],'body'=>str_repeat('x',9)]],$byteLimited);assertSameValue(['failed','LIMIT_EXCEEDED',[]],[$failed->kind,$failed->reason,$failed->links],'bounded response bytes');
echo "PASS: BITRIX-ORDER-DOCUMENT-LINKS-001 A1 bounded complete delivery\n";
