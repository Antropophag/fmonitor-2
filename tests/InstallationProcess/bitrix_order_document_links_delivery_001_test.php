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
    if(str_contains($url,'batch')){
        $result=[];foreach(($options['form']['cmd']??[])as$key=>$command){parse_str((string)parse_url($command,PHP_URL_QUERY),$parameters);$result[$key]='https://bitrix24public.com/docs/'.($parameters['id']??'');}
        return $json(['result'=>['result'=>$result,'result_error'=>[]]]);
    }
    throw new TestFailure('unexpected request '.$method.' '.$url);
};
$result=(new NativeBitrixOrderDocumentDelivery($config,$transport))->fetch([
    ['sourceFolderId'=>'501','sourceFolderName'=>'0012.03','orderNumber'=>'0012.03','url'=>'https://tenant.bitrix24.test/docs/501'],
]);
$childrenCalls=array_values(array_filter($calls,fn($call)=>str_contains($call[1],'getchildren')));
foreach($childrenCalls as $call){parse_str((string)parse_url($call[1],PHP_URL_QUERY),$query);assertSameValue('1809812',(string)($query['id']??''),'configured root id reaches every children request');}
assertSameValue(['complete',null],[$result->kind,$result->reason],'complete delivery');
assertSameValue(
    [['501','0012.03','0012.03'],['502','1.3-2.3','1.3'],['502','1.3-2.3','2.3']],
    array_map(fn($link)=>[$link['sourceFolderId'],$link['sourceFolderName'],$link['orderNumber']],$result->links),
    'direct folders, pagination and legacy range'
);
assertSameValue([0,1],array_map(static function(array $call):int{parse_str((string)parse_url($call[1],PHP_URL_QUERY),$query);return(int)($query['start']??0);},$childrenCalls),'monotonic pages');
assertSameValue(1,count(array_filter($calls,fn($call)=>str_contains($call[1],'batch'))),'only the new folder uses one batch');
assertSameValue(0,count(array_filter($calls,fn($call)=>str_contains($call[1],'getExternalLink'))),'external links are batch-only');
assertSameValue(false,str_contains(json_encode($result,JSON_THROW_ON_ERROR),'synthetic-token'),'public result hides secrets');

$fetch=static function(array $responses,?BitrixOrderDocumentDeliveryConfig $caseConfig=null)use($config):object{
    $caseConfig??=$config;$counts=[];
    $transport=static function(string $method,string $url,array $options)use(&$counts,$responses):array{
        $key=str_contains($url,'getchildren')?'children':'batch';$index=$counts[$key]??0;$counts[$key]=$index+1;
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
    'URL_INVALID'=>['children'=>$json(['result'=>[['ID'=>'7','NAME'=>'ONE','TYPE'=>'folder']],'total'=>1]),'batch'=>$json(['result'=>['result'=>['folder_0'=>'https://user@tenant.bitrix24.test.evil/docs/7'],'result_error'=>[]]])],
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

$largeConfig=new BitrixOrderDocumentDeliveryConfig('https://tenant.bitrix24.test',6546,1809812,'/online/','synthetic-token','/synthetic-ca.pem',50,25000,1048576,30);
$largeFolders=[];for($i=1;$i<=101;$i++)$largeFolders[]=['ID'=>(string)$i,'NAME'=>'ORDER-'.$i,'TYPE'=>'folder'];$batchCalls=[];
$largeTransport=static function(string$method,string$url,array$options)use($json,$largeFolders,&$batchCalls):array{
    if(str_contains($url,'getchildren'))return$json(['result'=>$largeFolders,'total'=>101]);
    if(!str_contains($url,'batch'))throw new TestFailure('large delivery bypassed batch');
    $commands=$options['form']['cmd']??[];$batchCalls[]=count($commands);$result=[];foreach($commands as$key=>$command){parse_str((string)parse_url($command,PHP_URL_QUERY),$parameters);$result[$key]='https://bitrix24public.com/docs/'.$parameters['id'];}return$json(['result'=>['result'=>$result,'result_error'=>[]]]);
};
$large=(new NativeBitrixOrderDocumentDelivery($largeConfig,$largeTransport))->fetch();
assertSameValue(['complete',101,[50,50,1]],[$large->kind,count($large->links),$batchCalls],'large root uses bounded batches of fifty');

$boundaryConfig=new BitrixOrderDocumentDeliveryConfig('https://tenant.bitrix24.test',6546,1809812,'/online/','synthetic-token','/synthetic-ca.pem',50,25000,16777216,30);
$boundaryFolders=[];$boundaryCurrent=[];for($i=1;$i<=24999;$i++){$name='O'.$i;$url='https://tenant.bitrix24.test/docs/'.$i;$boundaryFolders[]=['ID'=>(string)$i,'NAME'=>$name,'TYPE'=>'folder'];$boundaryCurrent[]=['sourceFolderId'=>(string)$i,'sourceFolderName'=>$name,'orderNumber'=>$name,'url'=>$url];}
$boundaryFolders[]=['ID'=>'25000','NAME'=>'1.3-2.3','TYPE'=>'folder'];$boundaryCurrent[]=['sourceFolderId'=>'25000','sourceFolderName'=>'1.3-2.3','orderNumber'=>'1.3','url'=>'https://tenant.bitrix24.test/docs/25000'];
$boundaryTransport=static function(string$method,string$url,array$options)use($json,$boundaryFolders):array{if(!str_contains($url,'getchildren'))throw new TestFailure('unchanged boundary root reached upstream link delivery');return$json(['result'=>$boundaryFolders,'total'=>25000]);};
$boundary=(new NativeBitrixOrderDocumentDelivery($boundaryConfig,$boundaryTransport))->fetch($boundaryCurrent);
assertSameValue(['complete',25001],[$boundary->kind,count($boundary->links)],'twenty-five thousand direct folders retain bounded range expansion');
echo "PASS: BITRIX-ORDER-DOCUMENT-LINKS-001 A1 bounded complete delivery\n";
