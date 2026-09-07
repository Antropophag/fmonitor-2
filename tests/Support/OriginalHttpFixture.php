<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
final class OriginalHttpFixture
{
    public readonly SelectionHttpFixture $http;
    public const POST='/pilot/objects/4512/assignment-orders/81/originals';
    public const FORM=self::POST.'/submit';
    public function __construct()
    {
        \assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',hash('sha256',base64_decode(AssignmentOrderOriginalRemainingMatrix::POSITIVE_PDF_BASE64,true)),'SETUP_OK exact normative PDF');
        $this->http=new SelectionHttpFixture(true,static fn(SelectedOriginalFixture $o)=>[
            'FMONITOR_ORIGINAL_SAFE_LOG_FILE'=>$o->safeLog,'FMONITOR_ORIGINAL_DB_PASSWORD_FILE'=>$o->control.'/password']);
        try {
            $f=$this->http->original->selection;
            foreach([1,3] as $role)foreach(['assignment_order.original.read','assignment_order.original.upload','assignment_order.original.correct'] as $cap)$f->db->query("INSERT IGNORE INTO fm2_pilot_role_permissions(role_id,permission) VALUES($role,'$cap')");
            foreach(['assignment_order.original.upload','assignment_order.original.correct'] as $cap)$f->schema->insert('fm2_process_user_capabilities',['user_id'=>31,'capability'=>$cap,'position_snapshot'=>null]);
            $form=$this->http->request('GET',SelectionHttpAssertions::PATH);
            \assertSameValue(200,$form['status'],'SETUP_OK existing native selection form');
            $body=SelectionHttpAssertions::submission($form['body'],['installerTabIds[]'=>['7001'],'controlEngineerUserId'=>['73'],'controlEngineerConfirmed'=>['yes']]);
            \assertSameValue(303,$this->http->request('POST',SelectionHttpAssertions::PATH,$body)['status'],'SETUP_OK order81 selected through HTTP without template');
            \assertSameValue([],$this->http->original->privateFiles(),'SETUP_OK no hidden PDF');
            \assertSameValue([], $f->rows()['fm2_process_events'],'SETUP_OK no template event');
        }catch(\Throwable $error){$this->close();throw $error;}
    }
    public function fields(int $request=1):array
    {
        return ['csrfToken'=>$this->http->csrf,'requestId'=>sprintf('22222222-2222-4222-8222-%012d',$request),'mode'=>'initial','documentDate'=>'2026-09-01','compositionConfirmed'=>true,
            'rootOriginalId'=>null,'targetRevisionId'=>null,'expectedCurrentRevisionId'=>null,'correctionReason'=>null,'originalFilename'=>'signed.pdf'];
    }
    public static function header(array $fields):string
    { return base64_encode(json_encode($fields,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_LINE_TERMINATORS)); }
    public function post(array $fields,?string $bytes=null,array $headers=[],?int $actor=18,string $path=self::POST):array
    { return $this->http->request('POST',$path,$bytes??base64_decode(AssignmentOrderOriginalRemainingMatrix::POSITIVE_PDF_BASE64,true),$actor,array_replace(['Content-Type'=>'application/pdf','X-FMonitor-Original'=>self::header($fields)],$headers)); }
    public static function json(array $response,int $status):array
    {
        \assertSameValue($status,$response['status'],'HTTP response status');\assertSameValue('application/json; charset=UTF-8',$response['headers']['content-type']??null,'JSON media');
        \assertSameValue('no-store',$response['headers']['cache-control']??null,'no-store');\assertSameValue('nosniff',$response['headers']['x-content-type-options']??null,'nosniff');
        \assertSameValue(\FMonitor2\PilotHttp\PilotRouteCsp::BASE,$response['headers']['content-security-policy']??null,'original JSON retains BASE CSP');
        if($status===503)\assertSameValue('60',$response['headers']['retry-after']??null,'retry delay');
        \assertSameValue((string)strlen($response['body']),$response['headers']['content-length']??null,'exact content length');\assertSameValue("\n",substr($response['body'],-1),'final LF');
        return json_decode($response['body'],true,512,JSON_THROW_ON_ERROR);
    }
    public function close():void { $this->http->close(); }
}
