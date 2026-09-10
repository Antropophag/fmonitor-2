<?php
declare(strict_types=1);
require_once __DIR__.'/PreopeningFixture.php';

/** YII2-INSPECTION-JOURNEY-001: isolated canonical setup; HTTP owns submitted actions. */
final class InspectionFixture
{
    public PreopeningFixture $http;
    public array $cookies=[];
    public function __construct(string $root)
    {
        $this->http=new PreopeningFixture($root);$f=$this->http;
        foreach([2=>['inspection.item.complete','inspection.photo.revoke','checklist.read','construction_control.read'],5=>['checklist.read'],7=>['inspection.item.complete','checklist.read','construction_control.read']]as$role=>$caps)foreach($caps as$cap)$f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>$role,'permission'=>$cap]);
        // Fixed independent 41-item template, matching the accepted pilot weights.
        $ids=[[28,29,30,31,32,33,34,35,36],[37,38,39,40,41],[1,2,3,4,5,6],[7,8,9,10],[11,12,13,14,15],[16,17,18,19,20,21],[22,23,24,25,26,27]];
        $weights=[[2,2,2,2,1,1,2,1,2],[3,3,3,3,2],[2,2,1,2,1,1],[2,5,1,1],[3,2,2,1,2],[4,4,3,3,3,2],[3,2,1,1,1,1]];
        $sections=[];foreach($ids as$i=>$items){$rows=[];foreach($items as$j=>$id)$rows[]=['id'=>$id,'name'=>'Fixture '.$id,'weight'=>$weights[$i][$j]];$sections[]=['id'=>$i+1,'name'=>'Раздел '.($i+1),'items'=>$rows];}
        $json=json_encode(['sections'=>$sections],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $f->db->prepare("UPDATE {$f->p}fm2_checklist_template_snapshots SET payload_json=?,content_sha256=?")->execute([$json,hash('sha256',$json)]);
    }
    public function open():void
    {
        $f=$this->http;$f->start();$fk=[];assertSameValue(303,$f->login($fk)['status'],'fixture real FKR login');
        assertSameValue(303,$f->selection($fk,ids:[7001,7002])['status'],'fixture selected crew');
        $receipt=$f->upload($fk,$f->metadata($fk));assertSameValue(201,$receipt['status'],'fixture original');$r=json_decode($receipt['body'],true,flags:JSON_THROW_ON_ERROR);
        $opened=$f->form('/pilot/objects/4512/execution',['_csrf'=>$f->token($fk),'action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000001','orderId'=>'81','revisionId'=>$r['currentRevisionId'],'sequence'=>'0','actualStartDate'=>'2026-09-02'],$fk);
        assertSameValue(303,$opened['status'],'fixture real separate opening');
        assertSameValue(303,$f->login($this->cookies,73)['status'],'fixture real engineer login');
    }
    public function page(string $path='/pilot/objects/4512/checklist'):array{return$this->http->request('GET',$path,[],$this->cookies);}
    public static function projection(array $page):array
    {
        assertSameValue(200,$page['status'],'INTENDED_RED Yii checklist page');
        assertSameValue(1,preg_match('/data-projection="([^"]+)"/',$page['body'],$m),'public projection');
        return json_decode(base64_decode(html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8'),true),true,flags:JSON_THROW_ON_ERROR);
    }
    public static function csrf(array $page):string
    {
        assertSameValue(1,preg_match('/data-csrf="([^"]+)"/',$page['body'],$m),'page native csrf');return html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
    }
    public static function operation(int $sequence=1,int $revision=0,int $item=28):array
    {
        return ['clientOperationId'=>sprintf('aaaaaaaa-aaaa-4aaa-8aaa-%012d',$sequence),'deviceInstallationId'=>'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb','type'=>'item_completed','deviceTime'=>'2026-09-10T09:00:00+03:00','baseRevision'=>$revision,'sectionId'=>1,'itemId'=>$item,'installerTabIds'=>[7002,7001]];
    }
    public function send(array $operation,string $csrf,?array &$cookies=null,?string $bytes=null):array
    {
        $cookies??=$this->cookies;$f=$this->http;$raw=$bytes??json_encode($operation,JSON_THROW_ON_ERROR);
        $headers=['X-FM2-CSRF: '.$csrf,'Origin: http://127.0.0.1:'.$f->server['port'],'Sec-Fetch-Site: same-origin'];
        if($bytes===null)$headers[]='Content-Type: application/json; charset=UTF-8';
        else{$headers[]='Content-Type: '.$operation['mime'];$headers[]='X-FM2-Operation: '.base64_encode(json_encode($operation,JSON_THROW_ON_ERROR));}
        return$f->request('POST','/pilot/objects/4512/checklist/'.($bytes===null?'operations':'photos'),[],$cookies,$headers,$raw);
    }
    public static function result(array $response,int $status,string $result):array
    {
        assertSameValue($status,$response['status'],'HTTP '.$result.' '.$response['body']);$r=json_decode($response['body'],true,flags:JSON_THROW_ON_ERROR);assertSameValue($result,$r['status'],'typed '.$result);return$r;
    }
    public static function png(int $color=0):string
    {
        $chunk=static fn(string $type,string $bytes):string=>pack('N',strlen($bytes)).$type.$bytes.pack('N',crc32($type.$bytes));
        return "\x89PNG\r\n\x1a\n".$chunk('IHDR',pack('NNCCCCC',1,1,8,6,0,0,0)).$chunk('IDAT',gzcompress("\0".chr($color%256)."\x40\x80\xff")).$chunk('IEND','');
    }
    public function queueFixtures():void
    {
        $f=$this->http;$case=$f->rows('fm2_installation_cases')[0];$object=$f->db->query("SELECT * FROM {$f->p}fm_maintable WHERE id=4512")->fetch_assoc();
        foreach([4513=>94,4514=>73,4515=>73]as$id=>$engineer){$row=$object;$row['id']=$id;$row['regnumber']='QUEUE-'.$id;$row['responsstroicontrol']=$engineer;$f->insert($f->p.'fm_maintable',$row);$row=$case;$row['id']=6101+$id-4512;$row['legacy_installation_object_id']=$id;if($id===4515)$row['process_state']='needs_assignment_change';$f->insert($f->p.'fm2_installation_cases',$row);}
        foreach(['pto_act','declaration']as$type)$f->insert($f->p.'fm2_pilot_completion_facts',['installation_case_id'=>6103,'fact_type'=>$type,'fact_date'=>'2026-09-10','details'=>$type==='declaration'?'Fixture declaration':'','recorded_at'=>'2026-09-10T09:30:00+03:00','recorded_by_user_id'=>18]);
        $f->db->query("UPDATE {$f->p}fm_maintable SET responsstroicontrol=94 WHERE id=4512");
    }
    public function close():void{$this->http->close();}
}
