<?php
declare(strict_types=1);
require_once __DIR__.'/InspectionFixture.php';

/** YII2-DOCUMENTARY-CLOSURE-001: real opening/checklist prerequisites, private canonical DB. */
final class DocumentaryFixture
{
    public InspectionFixture $inspection;
    public PreopeningFixture $http;
    public array $cookies=[];
    public string $csrf;
    public function __construct(string $root)
    {
        $this->inspection=new InspectionFixture($root);$this->http=$this->inspection->http;
        $this->http->db->query("UPDATE {$this->http->p}fm2_pilot_users SET full_name=CASE user_id WHEN 18 THEN 'ФКР Анна' WHEN 97 THEN 'Руководитель Ирина' ELSE full_name END");
        $this->http->insert($this->http->p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'checklist.read']);
        foreach([1,7] as $role)foreach(['pto.record','declaration.record','pto.correct','declaration.correct'] as $cap)$this->http->insert($this->http->p.'fm2_pilot_role_permissions',['role_id'=>$role,'permission'=>'installation.completion.'.$cap]);
    }
    public function open():void
    {
        $this->inspection->open();$h=$this->http;assertSameValue(303,$h->login($this->cookies)['status'],'fixture FKR login');$this->csrf=$h->token($this->cookies);
    }
    public function progress(bool $full=true):void
    {
        $f=$this->inspection;$page=$f->page();$csrf=InspectionFixture::csrf($page);$revision=InspectionFixture::projection($page)['revision'];
        // Literal section membership, independent of production weight evaluation. Omit item32 => 84.
        foreach([[28,29,30,31,32,33,34,35,36],[37,38,39,40,41],[1,2,3,4,5,6],[7,8,9,10],[11,12,13,14,15],[16,17,18,19,20,21],[22,23,24,25,26,27]] as $s=>$ids)foreach($ids as $id){
            if(!$full&&$id===32)continue;$op=InspectionFixture::operation(500+$id,$revision,$id);$op['sectionId']=$s+1;$r=InspectionFixture::result($f->send($op,$csrf),200,'accepted');$revision=$r['revision'];
        }
    }
    public function page(?array &$cookies=null):array
    {
        $cookies??=$this->cookies;return $this->http->request('GET','/pilot/objects/4512',[],$cookies);
    }
    public function post(string $action,array $fields=[],?array &$cookies=null,int $id=4512):array
    {
        $cookies??=$this->cookies;return $this->http->form('/pilot/objects/'.$id.'/completion',['_csrf'=>$this->csrf,'action'=>$action]+$fields,$cookies);
    }
    public static function accepted(array $r):void
    {
        assertSameValue([303,'/pilot/objects/4512#completion',''],[$r['status'],$r['headers']['location'][0]??null,$r['body']],'INTENDED_RED Yii documentary accepted HTTP');
        assertSameValue(true,str_contains(implode(' ',$r['headers']['cache-control']??[]),'no-store'),'no-store success');
    }
    public static function rejected(array $r,int $status,string $message):void
    {
        assertSameValue([$status,$message."\n"],[$r['status'],$r['body']],'exact completion rejection');
        assertSameValue(true,str_contains(implode(' ',$r['headers']['cache-control']??[]),'no-store'),'no-store rejection');
    }
    public function unchangedExcept(array $before,array $suffixes):void
    {
        $after=$this->http->facts();foreach($suffixes as $s){unset($before[$this->http->p.$s],$after[$this->http->p.$s]);}assertSameValue($before,$after,'unrelated inventory byte-equivalent');
    }
    public function close():void{$this->inspection->close();}
}
