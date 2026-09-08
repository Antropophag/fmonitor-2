<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderOriginal as O;
require_once __DIR__.'/MariaDbOriginalHistoryAccess.php';
final readonly class OriginalHistoryHttpHandler
{
    public function __construct(private EnvironmentSource $environment){}
    public function handle(PilotHttpRequest $r,int $object,int $order,?string $revision=null):PilotHttpResponse
    {
        if(!\in_array($r->method,['GET','HEAD'],true))return OriginalUploadHttpHandler::error($r,405,'METHOD_NOT_ALLOWED',['Allow'=>'GET, HEAD']);
        $actor=FreshOrderFormInput::positive($r->server['FMONITOR_AUTH_USER_ID']??null);
        if($actor===null)return OriginalUploadHttpHandler::error($r,403,'ACCESS_DENIED');
        $resources=null;
        try{
            $resources=new OriginalUploadResources($this->environment);
            if(!(new MariaDbOriginalHistoryAccess($resources->native->db,$resources->native->prefix))->allowed($actor,$object,$order))
                return OriginalUploadHttpHandler::error($r,403,'ACCESS_DENIED');
            $reader=$resources->history();
            if($revision!==null)return $this->download($r,$reader,$object,$order,$revision);
            $lookup=$reader->readHistory($object,$order,0,100);
            if($lookup->status!==O\AssignmentOrderOriginalHistoryStatus::FOUND||$lookup->page===null)
                return $this->lookupError($r,$lookup->status);
            return FreshOrderHttpHandler::response($r,200,$this->historyHtml($resources->native->user($actor),$lookup->page->metadata()));
        }catch(\Throwable){return OriginalUploadHttpHandler::error($r,503,'SERVICE_UNAVAILABLE');}
        finally{$resources?->close();}
    }
    private function download(PilotHttpRequest $r,O\AssignmentOrderOriginalHistoryReader $reader,int $object,int $order,string $revision):PilotHttpResponse
    {
        $lookup=$reader->prepareDownload($object,$order,$revision);
        if($lookup->status!==O\AssignmentOrderOriginalHistoryStatus::FOUND||$lookup->download===null)return $this->lookupError($r,$lookup->status);
        $bytes=$lookup->download->bytes();
        return FreshOrderHttpHandler::response($r,200,$bytes,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="assignment-order-original.pdf"']);
    }
    private function lookupError(PilotHttpRequest $r,O\AssignmentOrderOriginalHistoryStatus $status):PilotHttpResponse
    {
        return $status===O\AssignmentOrderOriginalHistoryStatus::NOT_FOUND
            ?OriginalUploadHttpHandler::error($r,404,'NOT_FOUND')
            :OriginalUploadHttpHandler::error($r,503,'SERVICE_UNAVAILABLE');
    }
    private function historyHtml(HttpUser $user,array $history):string
    {
        $e=PilotView::e(...);$object=(int)$history['objectId'];$order=(int)$history['orderId'];$rows='';
        foreach($history['revisions']as$revision){
            $href=OriginalUploadView::path($object,$order).'/'.$revision['revisionId'].'/download';
            $modified=(new \DateTimeImmutable($revision['uploadedAt']))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('d.m.Y, H:i');
            $rows.=PilotDocumentView::row('Подписанный оригинал.pdf',$href,'application/pdf',(int)$revision['byteSize'],'Редакция '.(int)$revision['revisionNumber'],'Загружен '.$modified);
            $rows.='<p class="fm2-document-note">Дата распоряжения: '.$e($revision['documentDate']).($revision['correctionReason']===null?'':' · '.$e($revision['correctionReason'])).'</p>';
        }
        if($rows==='')$rows='<p>Принятых редакций пока нет.</p>';
        $body='<div class="fm2-page-header"><div><h1>История оригинала</h1><p>Распоряжение '.$e((string)$history['orderVersion']).'</p></div></div><section class="fm2-order-surface fm2-order-team"><div class="shlz-document-list">'.$rows.'</div></section>';
        $body.='<p><a class="shlz-link" href="'.OriginalUploadView::path($object,$order).'/submit">К оригиналу распоряжения</a></p>';
        return PilotView::document($user,'История оригинала','Объекты монтажа',PilotView::breadcrumb([['Объекты монтажа','/pilot/objects'],['Объект № '.$object,'/pilot/objects/'.$object]],'История оригинала'),$body);
    }
}
