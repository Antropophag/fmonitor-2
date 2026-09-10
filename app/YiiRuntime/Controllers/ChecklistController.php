<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\InspectionEvidence\MariaDbYiiChecklist;
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
}],'verbs'=>['class'=>VerbFilter::class,'actions'=>['view'=>['GET','HEAD'],'control'=>['GET','HEAD'],'operation'=>['POST'],'photo'=>['POST'],'context'=>['GET','HEAD'],'queue'=>['GET','HEAD']]]];
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
return$this->render('@app/app/YiiRuntime/Views/checklist',['identity'=>Yii::$app->user->identity,'id'=>$id,'access'=>$a,'projection'=>$owner->projection($id),'csrf'=>Yii::$app->request->csrfToken,'fromControl'=>$source==='control','progressCap'=>$completion['cap']]);
} catch(\Throwable)
    {return$this->plain(503,true);
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
} catch(\Throwable)
    {return$this->json(503,['status'=>'retryable']);
}}

    public function actionOperation(string$id):Response{return$this->mutate($id,false);
}

    public function actionPhoto(string$id):Response{return$this->mutate($id,true);
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
if(($operation['itemId']??null)===42)return$this->json(409,['status'=>'rejected','message'=>'Последние 15% закрываются актом ПТО и декларацией в карточке объекта.']);
$recording=null;
if(($operation['type']??null)==='item_completed'){
    $resources=new PreopeningResources(Yii::$app->db);
    $composition=AssignmentOrderApplicationReaderFactory::create($resources->db,(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX'))->readCurrent($id);
    $recording=ProductionInspectionEvidenceFactory::create($resources->db,new ProductionInspectionEvidenceConfig((string)getenv('FMONITOR_PROCESS_TABLE_PREFIX')),null,$composition->status==='found'?$composition->value:null);
}
$owner=$this->owner($recording);
$result=$owner->accept($id,$this->actor(),$operation,$photo?$body:null);
if($result['status']==='not_found')return$this->json(404,['status'=>'rejected']);
if($result['status']==='forbidden')return$this->json(403,['status'=>'rejected']);
$result['projection']=$owner->projection($id);
$status=in_array($result['status'],['accepted','duplicate'],true)?200:($result['status']==='conflict'?409:422);
return$this->json($status,$result);
} catch(\JsonException)
    {return$this->json(400,['status'=>'rejected']);
} catch(\Throwable)
    {return$this->json(503,['status'=>'retryable','message'=>'Сервис временно недоступен.']);
} finally {if($resources instanceof PreopeningResources)$resources->close();
}
    }

    public function actionQueue():string|Response{try{$page=filter_var(Yii::$app->request->get('page','1'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if($page===false)return$this->plain(404);
$objects=$this->owner()->queue($this->actor(),(int)$page);
return$this->render('@app/app/YiiRuntime/Views/construction-control',['identity'=>Yii::$app->user->identity,'objects'=>$objects]);
} catch(\DomainException)
    {return$this->plain(403);
} catch(\Throwable)
    {return$this->plain(503,true);
}}

    private function owner(?InspectionRecording$recording=null):MariaDbYiiChecklist{return new MariaDbYiiChecklist(Yii::$app->db,(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX'),(string)getenv('FMONITOR_LEGACY_TABLE_PREFIX'),(string)(getenv('FMONITOR_ARTIFACT_STORAGE_ROOT')?:getenv('FMONITOR_DEMO_PRIVATE_ROOT')),(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format(DATE_ATOM),$recording);
}

    private function actor():int{return(int)Yii::$app->user->id;
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
