<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

function checklistPhotoViewingFiles(string $root):array
{
    $files=[];
    if(!is_dir($root))return $files;
    $walk=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
    foreach($walk as$file)if($file->isFile())$files[substr($file->getPathname(),strlen($root)+1)]=hash_file('sha256',$file->getPathname());
    ksort($files);return $files;
}

// YII2-CHECKLIST-PHOTO-VIEWING-001: authorized original-byte read is durable and read-only.
$fixture=null;
try {
    $fixture=new InspectionFixture(dirname(__DIR__,2));
    $fixture->open();
    $http=$fixture->http;
    $page=$fixture->page();
    $csrf=InspectionFixture::csrf($page);
    $png=InspectionFixture::png(17);
    $operation=array_replace(InspectionFixture::operation(701),[
        'type'=>'photo_uploaded','mime'=>'image/png','size'=>strlen($png),
        'sha256'=>hash('sha256',$png),'originalName'=>'cross-device.png',
    ]);
    unset($operation['itemId'],$operation['installerTabIds']);
    $accepted=InspectionFixture::result($fixture->send($operation,$csrf,bytes:$png),200,'accepted');
    $photo=$accepted['projection']['photos'][0]??null;
    assertSameValue(true,is_array($photo),'accepted photo projection');
    assertSameValue('/pilot/objects/4512/checklist/photos/'.$photo['id'],$photo['viewUrl']??null,'INTENDED_RED durable same-origin view URL');
    $url=(string)$photo['viewUrl'];
    $files=checklistPhotoViewingFiles($http->base->privateRoot);

    $same=$http->request('GET',$url,[],$fixture->cookies);
    assertSameValue([200,$png,'image/png',(string)strlen($png),'nosniff'],[
        $same['status'],$same['body'],$same['headers']['content-type'][0]??null,
        $same['headers']['content-length'][0]??null,$same['headers']['x-content-type-options'][0]??null,
    ],'clean request returns exact original bytes and safe metadata');
    assertSameValue('inline; filename="checklist-photo.png"',$same['headers']['content-disposition'][0]??null,'independent generic disposition');
    assertSameValue(true,str_contains($same['headers']['cache-control'][0]??'','private')&&str_contains($same['headers']['cache-control'][0]??'','no-store'),'private no-store response');
    $head=$http->request('HEAD',$url,[],$fixture->cookies);
    assertSameValue([200,''],[$head['status'],$head['body']],'HEAD without body');
    foreach(['content-type','content-length','content-disposition','x-content-type-options','cache-control']as$header)assertSameValue($same['headers'][$header][0]??null,$head['headers'][$header][0]??null,'GET/HEAD parity '.$header);

    $reader=[];assertSameValue(303,$http->login($reader,95)['status'],'allowed reader login');
    $other=$http->request('GET',$url,[],$reader);
    assertSameValue([200,$png],[$other['status'],$other['body']],'different read-only user sees exact bytes');
    $http->db->query("DELETE FROM {$http->p}fm2_pilot_role_permissions WHERE role_id=2 AND permission='checklist.read'");assertSameValue(200,$http->request('HEAD',$url,[],$fixture->cookies)['status'],'inspection.item.complete independently permits HEAD');
    $role=[];assertSameValue(303,$http->login($role,97)['status'],'role-access reader login');$http->db->query("DELETE FROM {$http->p}fm2_pilot_role_permissions WHERE role_id=7 AND permission='checklist.read'");assertSameValue(200,$http->request('HEAD',$url,[],$role)['status'],'role access independently permits HEAD without checklist.read or photo mutation permission');
    $http->insert($http->p.'fm2_pilot_role_permissions',['role_id'=>2,'permission'=>'checklist.read']);$http->insert($http->p.'fm2_pilot_role_permissions',['role_id'=>7,'permission'=>'checklist.read']);$before=$http->facts();
    $denied=[];assertSameValue(303,$http->login($denied,96)['status'],'denied user login');
    $forbidden=$http->request('GET',$url,[],$denied);
    assertSameValue([403,false,false],[$forbidden['status'],str_contains($forbidden['body'],'cross-device'),str_contains($forbidden['body'],'.bin')],'denial leaks no photo metadata');
    $guest=[];$guestResponse=$http->request('GET',$url,[],$guest);
    assertSameValue([303,'/pilot/login'],[$guestResponse['status'],$guestResponse['headers']['location'][0]??null],'guest follows authentication flow');
    assertSameValue(303,$http->request('HEAD',$url,[],$guest)['status'],'guest HEAD follows authentication flow');
    $http->db->query("UPDATE {$http->p}fm2_pilot_users SET status=0 WHERE user_id=95");
    try{assertSameValue(303,$http->request('GET',$url,[],$reader)['status'],'inactive GET reauthenticates');assertSameValue(303,$http->request('HEAD',$url,[],$reader)['status'],'inactive HEAD reauthenticates');}finally{$http->db->query("UPDATE {$http->p}fm2_pilot_users SET status=1 WHERE user_id=95");}
    $reader=[];assertSameValue(303,$http->login($reader,95)['status'],'reactivated reader obtains a fresh session');
    assertSameValue(403,$http->request('HEAD',$url,[],$denied)['status'],'denied HEAD parity');
    assertSameValue(404,$http->request('GET','/pilot/objects/4513/checklist/photos/'.$photo['id'],[],$fixture->cookies)['status'],'cross-object photo is not disclosed');
    assertSameValue(404,$http->request('GET','/pilot/objects/4512/checklist/photos/999999',[],$fixture->cookies)['status'],'unknown photo');
    foreach(['0','01','-1','1x','999999999999999999999999']as$id)assertSameValue(404,$http->request('GET','/pilot/objects/4512/checklist/photos/'.$id,[],$fixture->cookies)['status'],'noncanonical photo id '.$id);
    foreach(['0','04512','-1','4512x','999999999999999999999999']as$id)assertSameValue(404,$http->request('GET','/pilot/objects/'.$id.'/checklist/photos/'.$photo['id'],[],$fixture->cookies)['status'],'noncanonical object id '.$id);
    foreach(['POST','PUT','DELETE']as$method){$r=$http->request($method,$url,[],$fixture->cookies);assertSameValue([405,'GET, HEAD'],[$r['status'],$r['headers']['allow'][0]??null],'read route methods '.$method);}
    assertSameValue($before,$http->facts(),'all photo reads preserve facts');
    assertSameValue($files,checklistPhotoViewingFiles($http->base->privateRoot),'all photo reads preserve private files');

    $row=$http->rows('fm2_checklist_photos')[0];$path=$http->base->privateRoot.'/checklist/'.$row['storage_name'];$original=file_get_contents($path);
    foreach(['missing','size','unsafe','symlink']as$failure){
        if($failure==='missing')rename($path,$path.'.saved');
        elseif($failure==='size')file_put_contents($path,$original.'x');
        elseif($failure==='unsafe')$http->db->query("UPDATE {$http->p}fm2_checklist_photos SET storage_name='../escape.bin' WHERE id=".(int)$photo['id']);
        else{rename($path,$path.'.saved');symlink($path.'.saved',$path);}
        $facts=$http->facts();$artifactFingerprint=checklistPhotoViewingFiles($http->base->privateRoot);
        foreach(['GET','HEAD']as$method){$failed=$http->request($method,$url,[],$fixture->cookies);assertSameValue([503,'60'],[$failed['status'],$failed['headers']['retry-after'][0]??null],$failure.' safe retryable response');foreach([$row['storage_name'],'cross-device.png',$http->base->privateRoot]as$secret)assertSameValue(false,str_contains($failed['body'],$secret),$failure.' redacted');}
        assertSameValue($facts,$http->facts(),$failure.' no fact mutation');assertSameValue($artifactFingerprint,checklistPhotoViewingFiles($http->base->privateRoot),$failure.' no file mutation');
        if($failure==='missing')rename($path.'.saved',$path);elseif($failure==='size')file_put_contents($path,$original);elseif($failure==='unsafe')$http->db->prepare("UPDATE {$http->p}fm2_checklist_photos SET storage_name=? WHERE id=?")->execute([$row['storage_name'],$photo['id']]);else{unlink($path);rename($path.'.saved',$path);}
    }

    $beforeRow=$http->rows('fm2_checklist_photos')[0];$beforeOperations=$http->rows('fm2_checklist_operations');$revoke=array_replace(InspectionFixture::operation(702,$accepted['revision']),['type'=>'photo_revoked','photoId'=>$photo['id'],'reason'=>'Fixture revoke']);
    unset($revoke['itemId'],$revoke['installerTabIds']);
    $revoked=InspectionFixture::result($fixture->send($revoke,$csrf),200,'accepted');
    assertSameValue([],array_column($revoked['projection']['photos'],'id'),'revoked photo absent from active projection');
    assertSameValue(404,$http->request('GET',$url,[],$reader)['status'],'revoked URL no longer returns bytes');
    $afterRow=$http->rows('fm2_checklist_photos')[0];$expectedRow=$beforeRow;$expectedRow['revoked_at']=$afterRow['revoked_at'];assertSameValue($expectedRow,$afterRow,'revoke changes only revoked_at on photo row');assertSameValue(count($beforeOperations)+1,count($http->rows('fm2_checklist_operations')),'revoke appends exactly one history fact');
    assertSameValue($png,file_get_contents($http->base->privateRoot.'/checklist/'.$http->rows('fm2_checklist_photos')[0]['storage_name']),'revocation retains historical bytes');
    $http->noLegacy();
    echo "PASS: YII2-CHECKLIST-PHOTO-VIEWING-001 authorized durable read\n";
} finally {
    if($fixture instanceof InspectionFixture)$fixture->close();
}
