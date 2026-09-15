<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\InstallationProcess\BitrixOrderDocumentFolderMapper;

assertSameValue(true,class_exists(BitrixOrderDocumentFolderMapper::class),'INTENDED_RED A2 folder mapper missing');
$map=static fn(string$name):array=>BitrixOrderDocumentFolderMapper::orderNumbers($name,100);
assertSameValue(['0012.03'],$map('0012.03'),'ordinary name preserves leading zeros');
assertSameValue(['a.b/c'],$map('a.b/c'),'ordinary significant characters preserved');
assertSameValue([' 12.3'],$map(' 12.3'),'surrounding whitespace remains significant');
assertSameValue(['1.3','2.3','3.3'],$map('1.3-3.3'),'canonical inclusive range');
foreach(['01.3-03.3','3.3-1.3','1.3-2.4','1-3','1.3-2.3-3.3',"bad\0name"]as$name){
    try{$map($name);throw new TestFailure('ambiguous name accepted '.$name);}catch(DomainException$e){assertSameValue('AMBIGUOUS_FOLDER_NAME',$e->getMessage(),'typed ambiguity');}
}
try{BitrixOrderDocumentFolderMapper::orderNumbers('1.3-101.3',100);throw new TestFailure('range budget ignored');}catch(DomainException$e){assertSameValue('LIMIT_EXCEEDED',$e->getMessage(),'range budget');}
echo "PASS: BITRIX-ORDER-DOCUMENT-LINKS-001 A2 exact names and ranges\n";
