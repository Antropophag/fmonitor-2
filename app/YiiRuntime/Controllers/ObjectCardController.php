<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\YiiRuntime\InstallationProcessFactory;
use FMonitor2\YiiRuntime\PreopeningResources;
use FMonitor2\InstallationProcess\MariaDbYiiCompletionQuery;
use FMonitor2\InstallationProcess\ControlEngineerAssignmentCommand;
use FMonitor2\IdentityAccess\MariaDbYiiLocalIdentityStore;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class ObjectCardController extends PreopeningController
{
    public function beforeAction($action):bool { if($action->id==='assignment')$this->enableCsrfValidation=false;return parent::beforeAction($action); }
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['view' => ['GET', 'HEAD'], 'assignment' => ['POST'], 'prepare' => ['GET', 'HEAD'], 'gone' => ['GET', 'HEAD', 'POST'], 'method' => ['GET', 'HEAD']],
        ]]);
    }

    public function actionView(string $id): string|Response
    {
        $id = $this->canonicalId($id);
        if ($id === null) return $this->status(404);
        if (!$this->cap('objects.read')) return $this->status(403);
        try {
            $card = InstallationProcessFactory::card(
                Yii::$app->db,
                (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX'),
                (string) getenv('FMONITOR_LEGACY_TABLE_PREFIX'),
            )->read($this->actor(), $id);
            if ($card === null) return $this->status(404);
            $documentAccess = $this->documentAccess($id);
            if ($documentAccess['status'] === 'unavailable') return $this->status(503, true);
            $completionQuery = new MariaDbYiiCompletionQuery(Yii::$app->db, (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX'));
            $completion = $completionQuery->read($id);
            $identityStore = Yii::$app->localIdentity;
            if (!$identityStore instanceof MariaDbYiiLocalIdentityStore) throw new \RuntimeException('Identity store unavailable.');
            $resources=new PreopeningResources(Yii::$app->db);try{$assignment=$resources->assignmentReader()->read($id);}finally{$resources->close();}
            if($assignment['status']==='unavailable')return $this->status(503,true);
            $engineers=Yii::$app->db->createCommand("SELECT u.user_id,u.full_name FROM `".(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX')."fm2_pilot_users` u JOIN `".(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX')."fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `".(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX')."fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE u.status=1 AND BINARY u.activation_state='active' AND r.status=1 AND BINARY r.code='construction_control_engineer' ORDER BY u.user_id")->queryAll();
            return $this->render('@app/app/YiiRuntime/Views/object-card', $card + [
                'identity' => Yii::$app->user->identity,
                'canSelect' => $this->processCap('assignment_order.composition.select'),
                'currentEngineerAssignment'=>$assignment,'canAssignEngineer'=>$this->processCap('control_engineer.assign'),'eligibleEngineers'=>$engineers,
                'canOpen' => $this->cap('installation.open'),
                'canCorrect' => $documentAccess['canCorrect'],
                'canReadOriginal' => $documentAccess['canRead'],
                'completion' => $completion,
                // Completion capabilities are not yet in the canonical RBAC registry; grants() is the existing exact active-grant read seam.
                'canRecordPto' => $card['completionWritable'] && $identityStore->grants($this->actor(), 'installation.completion.pto.record'),
                'canRecordDeclaration' => $card['completionWritable'] && $identityStore->grants($this->actor(), 'installation.completion.declaration.record'),
                'canCorrectPto' => $card['completionWritable'] && $identityStore->grants($this->actor(), 'installation.completion.pto.correct'),
                'canCorrectDeclaration' => $card['completionWritable'] && $identityStore->grants($this->actor(), 'installation.completion.declaration.correct'),
                'today' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format('Y-m-d'),
                'csrf' => Yii::$app->request->csrfToken,
            ]);
        } catch (\DomainException) {
            return $this->status(403);
        } catch (\Throwable) {
            return $this->status(503, true);
        }
    }

    public function actionPrepare(string $id): Response
    {
        $id = $this->canonicalId($id);
        return $id === null ? $this->status(404) : $this->redirect303('/pilot/objects/'.$id.'/assignment-order/selection');
    }

    public function actionGone(): Response { return $this->status(410); }

    public function actionAssignment(string$id):Response
    {
        $id=$this->canonicalId($id);if($id===null)return$this->status(404);if(!$this->processCap('control_engineer.assign'))return$this->status(403);
        if(preg_match('~^application/x-www-form-urlencoded(?:\s*;\s*charset=utf-8)?$~iD',(string)Yii::$app->request->contentType)!==1)return$this->status(415);
        $body=Yii::$app->request->rawBody;if(strlen($body)>32768)return$this->status(413);parse_str($body,$f);
        if(!Yii::$app->request->validateCsrfToken((string)($f['_csrf']??'')))return$this->status(400);
        try{if(array_diff(array_keys($f),['_csrf','requestId','engineerUserId','expectedRevision'])!==[])throw new \InvalidArgumentException();$command=new ControlEngineerAssignmentCommand((string)($f['requestId']??''),$id,filter_var($f['engineerUserId']??null,FILTER_VALIDATE_INT)?:0,filter_var($f['expectedRevision']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]])===false?-1:(int)$f['expectedRevision'],$this->actor());}catch(\Throwable){return$this->status(400);}
        $resources=new PreopeningResources(Yii::$app->db);try{$result=$resources->assignEngineer($command);}finally{$resources->close();}
        if(in_array($result['status'],['assigned','replayed'],true))return$this->redirect303('/pilot/objects/'.$id);
        $code=match($result['reasonCode']){'authorization_denied'=>403,'object_not_found'=>404,'assignment_changed','request_id_conflict'=>409,'engineer_not_eligible','no_changes'=>422,default=>503};return$this->status($code,$code===503);
    }

    private function documentAccess(int $id): array
    {
        $resources = new PreopeningResources(Yii::$app->db);
        try { return $resources->originalAccess()->readAccess($this->actor(), $id); }
        finally { $resources->close(); }
    }
    public function actionMethod(string $id): Response
    {
        return $this->canonicalId($id) === null ? $this->status(404) : $this->methodNotAllowed('GET, HEAD');
    }
}
