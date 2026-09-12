<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use DomainException;
use InvalidArgumentException;
use FMonitor2\YiiRuntime\OtizEvidenceHtml;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServiceUnavailableHttpException;

final class OtizEvidenceController extends PilotController
{
    public $layout = false;
    private ?\mysqli $mysqli = null;

    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => [
                'reconciliation' => ['GET'], 'quarantine' => ['GET'],
                'active-baselines' => ['GET'], 'historical-replay' => ['GET'],
                'reconciliation-decision' => ['POST'], 'quarantine-decision' => ['POST'],
            ]],
            'access' => ['class' => AccessControl::class, 'rules' => [[
                'allow' => true, 'roles' => ['otiz.manage'],
            ]], 'denyCallback' => function (): void {
                if (Yii::$app->user->isGuest) {
                    Yii::$app->user->setReturnUrl(str_contains(Yii::$app->request->pathInfo,'quarantine')?'/pilot/otiz/reconciliation/quarantine':'/pilot/otiz/reconciliation');
                    Yii::$app->response->statusCode = 303;
                    Yii::$app->response->headers->set('Location', '/pilot/login');
                    return;
                }
                throw new ForbiddenHttpException();
            }],
        ];
    }

    public function actionReconciliation(): string
    {
        $this->loadEvidenceOwners();
        $filters = [
            'classification' => $this->oneOf('classification', ['native_candidate','legacy_active','legacy_historical']),
            'state' => $this->oneOf('state', ['unreviewed','acknowledge','reject_evidence','request_source_correction','map_link']),
            'quarantine' => $this->oneOf('quarantine', ['yes','no']),
            'conflict' => preg_match('/^[A-Z0-9_]{1,80}$/D', (string) Yii::$app->request->get('conflict', '')) ? (string) Yii::$app->request->get('conflict') : '',
            'scope' => Yii::$app->request->get('scope') === 'unresolved' ? 'unresolved' : '',
        ];
        $model = (new \MigratedEvidenceProjectionStore($this->db(), $this->prefix()))->reconciliation($filters, $this->page(), 50);
        return OtizEvidenceHtml::reconciliation($model,$filters,Yii::$app->request->csrfToken);
    }

    public function actionQuarantine(): string
    {
        $this->loadEvidenceOwners();
        $model = (new \MigrationQuarantineReadModel($this->db(), $this->prefix()))->read([
            'category' => (string) Yii::$app->request->get('category', ''),
            'code' => (string) Yii::$app->request->get('code', ''),
        ], $this->page());
        $history=[];foreach((new \MigrationQuarantineDecisionLedger($this->db(),$this->prefix()))->all()as$d)$history[$d['source_locator'].'|'.$d['source_cutoff_at'].'|'.$d['source_digest'].'|'.$d['classification_version'].'|'.$d['quarantine_code']][]=$d;
        return OtizEvidenceHtml::quarantine($model,$history,Yii::$app->request->csrfToken);
    }

    public function actionActiveBaselines(): string
    {
        $this->loadEvidenceOwners();
        $model=(new \LegacyActiveBaselineReadModel($this->db(),$this->prefix()))->read(['state'=>$this->oneOf('state',['ready','blocked']),'coverage'=>$this->oneOf('coverage',['both','partial','none'])], $this->page());
        $body='<h1>Active baselines</h1>'.$this->nav();
        foreach($model['rows'] as$row)$body.='<article>'.$this->e((string)($row['regnumber']??'')).'</article>';
        if($model['rows']===[])$body.='<p>Нет active baselines</p>';
        return $this->pageHtml('Active baselines',$body);
    }

    public function actionHistoricalReplay(): string
    {
        $this->loadEvidenceOwners();
        try{$model=\HistoricalPremiumReplayReadModel::page($this->db(),$this->prefix(),$this->page(),50);}
        catch(\Throwable$e){throw new ServiceUnavailableHttpException('Historical replay temporarily unavailable',0,$e);}
        $body='<h1>Historical replay</h1>'.$this->nav();
        foreach($model['rows'] as$row)$body.='<article>'.$this->e((string)($row['regnumber']??'')).'</article>';
        $body.='<p>'.($model['rows']===[]?'Нет historical replay':'Нет подтверждённого пересчёта без полного evidence').'</p>';
        return $this->pageHtml('Historical replay',$body);
    }

    public function actionReconciliationDecision(): Response
    {
        $this->loadEvidenceOwners(); $snapshot=(int)Yii::$app->request->post('snapshotId',0); $outcome=(string)Yii::$app->request->post('outcome','');
        try {$r=(new \MigratedEvidenceDecisionLedger($this->db(),$this->prefix()))->decide([
            'operationId'=>(string)Yii::$app->request->post('operationId',''),'snapshotId'=>$snapshot,
            'snapshotSha256'=>(string)Yii::$app->request->post('snapshotSha256',''),'projectionSha256'=>(string)Yii::$app->request->post('projectionSha256',''),
            'sourceLocator'=>(string)Yii::$app->request->post('sourceLocator',''),'issueCode'=>(string)Yii::$app->request->post('issueCode',''),
            'outcome'=>$outcome,'targetLocator'=>$outcome==='map_link'?trim((string)Yii::$app->request->post('targetLocator','')):null,
            'reason'=>trim((string)Yii::$app->request->post('reason','')),'actorUserId'=>$this->actor(),'occurredAt'=>$this->now(),
        ]); return $this->seeOther('/pilot/otiz/reconciliation?decision='.$r['status'].'#snapshot-'.$snapshot);
        } catch (InvalidArgumentException) { return $this->seeOther('/pilot/otiz/reconciliation?decisionError=invalid#snapshot-'.$snapshot); }
        catch (DomainException $e) { $m=$e->getMessage();$code=str_contains($m,'stale or unavailable')?'stale':(str_contains($m,'issue reference')?'conflict':(str_contains($m,'Operation id')?'operation':'forbidden'));return $this->seeOther('/pilot/otiz/reconciliation?decisionError='.$code.'#snapshot-'.$snapshot); }
    }

    public function actionQuarantineDecision(): Response
    {
        $this->loadEvidenceOwners();
        try {$r=(new \MigrationQuarantineDecisionLedger($this->db(),$this->prefix()))->decide([
            'operationId'=>(string)Yii::$app->request->post('operationId',''),'sourceLocator'=>(string)Yii::$app->request->post('sourceLocator',''),
            'sourceCutoffAt'=>(string)Yii::$app->request->post('sourceCutoffAt',''),'sourceDigest'=>(string)Yii::$app->request->post('sourceDigest',''),
            'classificationVersion'=>(string)Yii::$app->request->post('classificationVersion',''),'quarantineCode'=>(string)Yii::$app->request->post('quarantineCode',''),
            'outcome'=>(string)Yii::$app->request->post('outcome',''),'reason'=>(string)Yii::$app->request->post('reason',''),
            'actorUserId'=>$this->actor(),'occurredAt'=>$this->now(),
        ]);return $this->seeOther('/pilot/otiz/reconciliation/quarantine?decision='.$r['status']);
        } catch(InvalidArgumentException){return $this->seeOther('/pilot/otiz/reconciliation/quarantine?decisionError=invalid');}
        catch(DomainException$e){return $this->seeOther('/pilot/otiz/reconciliation/quarantine?decisionError='.(str_contains($e->getMessage(),'STALE')?'stale':'forbidden'));}
    }

    private function db(): \mysqli { if($this->mysqli instanceof \mysqli)return$this->mysqli;mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$this->mysqli=new \mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:'',getenv('FMONITOR_DB_PASSWORD')?:'',getenv('FMONITOR_DB_NAME')?:'',(int)(getenv('FMONITOR_DB_PORT')?:3306));$this->mysqli->set_charset('utf8mb4');return$this->mysqli; }
    private function loadEvidenceOwners():void{$root=dirname(__DIR__,2).'/Otiz';foreach(['MariaDbMigratedEvidenceProjectionStore.php','MariaDbMigratedEvidenceDecisionLedger.php','MariaDbMigrationQuarantineReadModel.php','MariaDbMigrationQuarantineDecisionLedger.php','MariaDbLegacyActiveBaselineReadModel.php','MariaDbHistoricalPremiumReplayReadModel.php']as$f)require_once$root.'/'.$f;}
    private function actor():int{return(int)Yii::$app->user->id;}private function prefix():string{$p=(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');if(preg_match('/^[A-Za-z0-9_]{1,25}$/D',$p)!==1)throw new \RuntimeException('Invalid prefix');return$p;}
    private function now():string{return(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d\TH:i:sP');}
    private function seeOther(string$p):Response{$r=Yii::$app->response;$r->statusCode=303;$r->headers->set('Location',$p);return$r;}
    private function page():int{return max(1,(int)Yii::$app->request->get('page',1));}private function oneOf(string$n,array$a):string{$v=(string)Yii::$app->request->get($n,'');return in_array($v,$a,true)?$v:'';}
    private function e(string$v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_HTML5,'UTF-8');}private function nav():string{return'<nav><a href="/pilot/otiz">Объекты</a> <a href="/pilot/otiz/reconciliation">Сверка свидетельств</a> <a href="/pilot/otiz/reconciliation/quarantine">Quarantine</a> <a href="/pilot/otiz/active-baselines">Active baselines</a> <a href="/pilot/otiz/historical-replay">Historical replay</a></nav>';}
    private function pageHtml(string$title,string$body):string{return'<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>'.$this->e($title).'</title></head><body>'.$body.'</body></html>';}
}
