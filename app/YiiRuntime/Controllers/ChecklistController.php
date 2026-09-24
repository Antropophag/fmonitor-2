<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\InspectionEvidence\MariaDbYiiChecklist;
use FMonitor2\Runtime\SafeRuntimeFailure;
use FMonitor2\InspectionEvidence\InspectionRecording;
use FMonitor2\InspectionEvidence\ProductionInspectionEvidenceConfig;
use FMonitor2\InspectionEvidence\ProductionInspectionEvidenceFactory;
use FMonitor2\AssignmentOrderComposition\AssignmentOrderApplicationReaderFactory;
use FMonitor2\YiiRuntime\PreopeningResources;
use FMonitor2\YiiRuntime\ViewSupport;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;

final class ChecklistController extends PilotController
{
    public $layout=false;
    public function behaviors():array{return['access'=>['class'=>AccessControl::class,'rules'=>[['allow'=>true,'roles'=>['@']]],'denyCallback'=>function():void{Yii::$app->user->setReturnUrl(Yii::$app->request->url);
Yii::$app->response->statusCode=303;
Yii::$app->response->headers->set('Location','/pilot/login');
}],'verbs'=>['class'=>VerbFilter::class,'actions'=>['view'=>['GET','HEAD'],'control'=>['GET','HEAD'],'operation'=>['POST'],'photo'=>['POST'],'photo-read'=>['GET','HEAD'],'context'=>['GET','HEAD'],'queue'=>['GET','HEAD'],'inspection-plan'=>['POST']]]];
}

    public function beforeAction($action):bool{if(in_array($action->id,['operation','photo'],true))$this->enableCsrfValidation=false;
return parent::beforeAction($action);
}

    public function actionView(string$id,string$source='object'):string|Response
    {
        $id=$this->id($id);
if($id===null)return$this->plain(404);
try{$owner=$this->owner();
$a=$owner->access($this->actor(),$id);
if(!($a['exists']??false))return$this->plain(404);
if(!($a['read']??false))return$this->plain(403);
$completion=$owner->completion($id);
$opening=($a['ready']??false)&&(($a['roleAccess']??false)||($a['itemComplete']??false))&&Yii::$app->canonicalAccess->checkAccess($this->actor(),'installation.open')?($a['openingIntent']??null):null;
return$this->render('@app/app/YiiRuntime/Views/checklist',['identity'=>Yii::$app->user->identity,'id'=>$id,'access'=>$a,'projection'=>$this->projection($owner,$id),'csrf'=>Yii::$app->request->csrfToken,'fromControl'=>$source==='control','progressCap'=>$completion['cap'],'opening'=>$opening]);
} catch(\Throwable$error)
    {Yii::$app->response->headers->set('X-FMonitor-Error-ID',SafeRuntimeFailure::report($error,'checklist_controller'));return$this->plain(503,true);
}
    }

    public function actionControl(string$id):string|Response{return$this->actionView($id,'control');
}

    public function actionContext(string$id):Response{$id=$this->id($id);
if($id===null)return$this->json(404,['status'=>'rejected']);
try{$o=$this->owner();
$a=$o->access($this->actor(),$id);
if(!($a['exists']??false))return$this->json(404,['status'=>'rejected']);
if(!($a['opened']??false)||!($a['roleAccess']??false)&&!($a['assigned']??false)&&!($a['itemComplete']??false))return$this->json(403,['status'=>'rejected']);
return$this->json(200,['csrf'=>Yii::$app->request->csrfToken,'revision'=>$o->projection($id)['revision']]);
} catch(\Throwable$error)
    {Yii::$app->response->headers->set('X-FMonitor-Error-ID',SafeRuntimeFailure::report($error,'checklist_controller'));return$this->json(503,['status'=>'retryable']);
}}

    public function actionOperation(string$id):Response{return$this->mutate($id,false);
}

    public function actionPhoto(string$id):Response{return$this->mutate($id,true);
}

    public function actionPhotoRead(string$id,string$photoId):Response
    {
        $id=$this->id($id);$photoId=$this->id($photoId);
        if($id===null||$photoId===null)return$this->plain(404);
        try{$owner=$this->owner();$access=$owner->access($this->actor(),$id);
            if(!($access['exists']??false))return$this->plain(404);
            if(!($access['read']??false))return$this->plain(403);
            $photo=$owner->photo($id,$photoId);
            if($photo===null)return$this->plain(404);
            $storage=(string)$photo['storageName'];
            if(preg_match('/^[a-f0-9]{64}\.bin$/D',$storage)!==1||!in_array($photo['mime'],['image/jpeg','image/png','image/webp'],true))return$this->plain(503,true);
            $root=(string)(getenv('FMONITOR_ARTIFACT_STORAGE_ROOT')?:getenv('FMONITOR_DEMO_PRIVATE_ROOT'));
            $directory=$root.'/checklist';$path=$directory.'/'.$storage;
            if(is_link($path)||!is_file($path)||filesize($path)!==(int)$photo['size'])return$this->plain(503,true);
            $bytes=file_get_contents($path);
            if(!is_string($bytes)||strlen($bytes)!==(int)$photo['size'])return$this->plain(503,true);
            $response=Yii::$app->response;$response->statusCode=200;$response->format=Response::FORMAT_RAW;
            $response->headers->set('Content-Type',(string)$photo['mime']);
            $response->headers->set('Content-Length',(string)$photo['size']);
            $response->headers->set('Content-Disposition','inline; filename="checklist-photo.'.(['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][(string)$photo['mime']]??'bin').'"');
            $response->headers->set('X-Content-Type-Options','nosniff');
            $response->headers->set('Cache-Control','private, no-store');
            $response->content=$bytes;
            return$response;
        } catch(\Throwable$error)
        {Yii::$app->response->headers->set('X-FMonitor-Error-ID',SafeRuntimeFailure::report($error,'checklist_controller'));return$this->plain(503,true);}
    }

    private function mutate(string$id,bool$photo):Response
    {
        $id=$this->id($id);
if($id===null)return$this->json(404,['status'=>'rejected']);
$request=Yii::$app->request;
$length=$request->headers->get('Content-Length');
$limit=$photo?5242880:32768;
if(!is_string($length)||!ctype_digit($length)||(int)$length>$limit)return$this->json(413,['status'=>'rejected','message'=>'Размер операции превышает допустимый.']);
$body=$request->rawBody;
if(strlen($body)!==(int)$length)return$this->json(400,['status'=>'rejected']);
$token=(string)$request->headers->get('X-FM2-CSRF');
if($token===''||!Yii::$app->request->validateCsrfToken($token))return$this->json(400,['status'=>'rejected']);
$resources=null;try{if($photo)
    {$raw=$request->headers->get('X-FM2-Operation','');
if(!is_string($raw)||strlen($raw)>4096)return$this->json(400,['status'=>'rejected']);
$decoded=base64_decode($raw,true);
$operation=json_decode(is_string($decoded)?$decoded:'',true,16,JSON_THROW_ON_ERROR);
} else{if(preg_match('#^application/json(?:;\s*charset=UTF-8)?$#iD',(string)$request->contentType)!==1)return$this->json(400,['status'=>'rejected']);
$operation=json_decode($body,true,16,JSON_THROW_ON_ERROR);
}if(!is_array($operation))return$this->json(400,['status'=>'rejected']);
$actor=$this->actor();
$owner=$this->owner();
$access=$owner->access($actor,$id);
if(!($access['exists']??false))return$this->json(404,['status'=>'rejected']);
if(!($access['read']??false))return$this->json(403,['status'=>'rejected']);
if(($operation['itemId']??null)===42&&!($access['itemComplete']??false))return$this->json(403,['status'=>'rejected']);
if(($operation['itemId']??null)===42)return$this->json(409,['status'=>'rejected','message'=>'Последние 15% закрываются актом ПТО и декларацией в карточке объекта.']);
$recording=null;
if(($operation['type']??null)==='item_completed'){
    $resources=new PreopeningResources(Yii::$app->db);
    $composition=AssignmentOrderApplicationReaderFactory::create($resources->db,(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX'))->readCurrent($id);
    $recording=ProductionInspectionEvidenceFactory::create($resources->db,new ProductionInspectionEvidenceConfig((string)getenv('FMONITOR_PROCESS_TABLE_PREFIX')),null,$composition->status==='found'?$composition->value:null);
    $owner=$this->owner($recording);
}
$result=$owner->accept($id,$actor,$operation,$photo?$body:null);
if($result['status']==='not_found')return$this->json(404,['status'=>'rejected']);
if($result['status']==='forbidden')return$this->json(403,['status'=>'rejected']);
$access=$owner->access($actor,$id);
if(!($access['read']??false))return$this->json(403,['status'=>'rejected']);
$result['projection']=$this->projection($owner,$id);
$status=in_array($result['status'],['accepted','duplicate'],true)?200:($result['status']==='conflict'?409:422);
return$this->json($status,$result);
} catch(\JsonException)
    {return$this->json(400,['status'=>'rejected']);
} catch(\Throwable$error)
    {Yii::$app->response->headers->set('X-FMonitor-Error-ID',SafeRuntimeFailure::report($error,'checklist_controller'));return$this->json(503,['status'=>'retryable','message'=>'Сервис временно недоступен.']);
} finally {if($resources instanceof PreopeningResources)$resources->close();
}
    }

    public function actionQueue():string|Response{return$this->renderQueue();}

    public function actionInspectionPlan(string$id):string|Response
    {
        $objectId=$this->id($id);if($objectId===null)return$this->plain(404);$request=Yii::$app->request;
        $raw=['action'=>$request->post('action'),'inspectionDate'=>$request->post('inspectionDate',''),'planId'=>$request->post('planId',''),'expectedVersion'=>$request->post('expectedVersion'),'requestId'=>$request->post('requestId')];
        if(!is_string($raw['action'])||!is_string($raw['inspectionDate'])||!is_string($raw['planId'])||!is_string($raw['expectedVersion'])||!is_string($raw['requestId'])||!in_array($raw['action'],['create','reschedule','cancel'],true)||preg_match('/^[0-9]+$/D',$raw['expectedVersion'])!==1||preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD',$raw['requestId'])!==1||($raw['action']!=='create'&&preg_match('/^[1-9][0-9]*$/D',$raw['planId'])!==1))return$this->renderQueue($raw+$this->returnFilters(),400,'Некорректные данные планирования инспекции.');
        try{$planning=\FMonitor2\YiiRuntime\InstallationProcessFactory::planning(Yii::$app->db,(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX'));$result=match($raw['action']){
            'create'=>$planning->createInspectionPlan($this->actor(),$objectId,(int)$raw['expectedVersion'],$raw['inspectionDate'],$raw['requestId']),
            'reschedule'=>$planning->rescheduleInspectionPlan($this->actor(),$objectId,(int)$raw['planId'],(int)$raw['expectedVersion'],$raw['inspectionDate'],$raw['requestId']),
            'cancel'=>$planning->cancelInspectionPlan($this->actor(),$objectId,(int)$raw['planId'],(int)$raw['expectedVersion'],$raw['requestId']),
        };}catch(\Throwable$error){Yii::$app->response->headers->set('X-FMonitor-Error-ID',SafeRuntimeFailure::report($error,'inspection_planning_controller'));return$this->renderQueue($raw+$this->returnFilters(),503,'Результат сохранения не подтверждён. Проверьте текущий план перед повторной отправкой.');}
        if(in_array($result['status'],['scheduled','cancelled'],true))return$this->redirect303('/pilot/construction-control?'.http_build_query($this->returnFilters(),'','&',PHP_QUERY_RFC3986));
        if($result['status']==='access_denied')return$this->plain(403);if($result['status']==='not_found')return$this->plain(404);
        $status=in_array($result['status'],['stale_plan','request_conflict','conflict'],true)?409:422;$message=match($result['status']){'invalid_date'=>'Некорректная дата: дата инспекции не может быть в прошлом.','stale_plan'=>'План уже изменён. Проверьте текущую дату перед повторным действием.','request_conflict'=>'Этот запрос уже использован для другого действия.','conflict'=>'У объекта уже есть текущий план инспекции.',default=>'Действие отклонено.'};
        return$this->renderQueue($raw+$this->returnFilters(),$status,$message);
    }

    private function renderQueue(array$failed=[],int$status=200,string$message=''):string|Response{try{$request=Yii::$app->request;$raw=[$failed['ownership']??$request->get('ownership','mine'),$failed['query']??$request->get('query',''),$failed['completed']??$request->get('completed','0'),$failed['page']??$request->get('page','1')];
if(array_filter($raw,static fn(mixed$value):bool=>!is_string($value))!==[])return$this->plain(404);
[$ownership,$query,$completed,$page]=$raw;$query=trim($query);$page=filter_var($page,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!in_array($ownership,['mine','all'],true)||!in_array($completed,['0','1'],true)||$page===false||mb_strlen($query)>160)return$this->plain(404);
$filters=['ownership'=>$ownership,'query'=>$query,'completed'=>$completed];$objects=$this->owner()->queue($this->actor(),(int)$page,50,$ownership,$query,$completed==='1');
Yii::$app->response->statusCode=$status;return$this->render('@app/app/YiiRuntime/Views/construction-control',['identity'=>Yii::$app->user->identity,'objects'=>$objects,'filters'=>$filters,'failedInspection'=>$failed,'inspectionMessage'=>$message]);
} catch(\DomainException)
    {return$this->plain(403);
} catch(\Throwable$error)
    {Yii::$app->response->headers->set('X-FMonitor-Error-ID',SafeRuntimeFailure::report($error,'checklist_controller'));return$this->plain(503,true);
}}

    private function returnFilters():array{$request=Yii::$app->request;return['ownership'=>is_string($request->post('ownership'))?(string)$request->post('ownership'):'all','query'=>is_string($request->post('query'))?(string)$request->post('query'):'','completed'=>is_string($request->post('completed'))?(string)$request->post('completed'):'0','page'=>'1'];}

    private function redirect303(string$url):Response{$response=Yii::$app->response;$response->statusCode=303;$response->headers->set('Location',$url);return$response;}

    private function owner(?InspectionRecording$recording=null):MariaDbYiiChecklist{return new MariaDbYiiChecklist(Yii::$app->db,(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX'),(string)getenv('FMONITOR_LEGACY_TABLE_PREFIX'),(string)(getenv('FMONITOR_ARTIFACT_STORAGE_ROOT')?:getenv('FMONITOR_DEMO_PRIVATE_ROOT')),(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format(DATE_ATOM),$recording);
}

    private function actor():int{return(int)Yii::$app->user->id;
}

    private function projection(MariaDbYiiChecklist $owner,int $objectId):array{$projection=$owner->projection($objectId);
foreach($projection['photos']as&$photo)$photo['viewUrl']='/pilot/objects/'.$objectId.'/checklist/photos/'.(int)$photo['id'];
unset($photo);return$projection;
}

    private function id(string$v):?int{return preg_match('/^[1-9][0-9]*$/D',$v)===1&&strlen($v)<19?(int)$v:null;
}

    private function json(int$status,array$value):Response{$r=Yii::$app->response;
$r->statusCode=$status;
$r->format=Response::FORMAT_JSON;
$r->data=$value;
return$r;
}

    private function plain(int$status,bool$retry=false):Response{$r=Yii::$app->response;
$r->statusCode=$status;
$r->format=Response::FORMAT_RAW;
$r->content=$status===503?"Service unavailable.\n":($status===404?"Not found.\n":"Access denied.\n");
if($retry)$r->headers->set('Retry-After','60');
return$r;
}
}
