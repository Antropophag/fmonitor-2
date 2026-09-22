<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\YiiRuntime\InstallationProcessFactory;
use FMonitor2\Workforce\MariaDbYiiInstallerDirectory;
use FMonitor2\YiiRuntime\PreopeningResources;
use FMonitor2\InstallationProcess\MariaDbYiiCompletionQuery;
use FMonitor2\InstallationProcess\ControlEngineerAssignmentCommand;
use FMonitor2\IdentityAccess\MariaDbYiiLocalIdentityStore;
use FMonitor2\InspectionEvidence\MariaDbYiiChecklist;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;
use FMonitor2\InstallationProcess\{ObjectDetailsEditCommand,ObjectDetailsFieldRegistry};

final class ObjectCardController extends PreopeningController
{
    private ?array $detailsValidation=null;
    public function beforeAction($action):bool { if(in_array($action->id,['assignment','details'],true))$this->enableCsrfValidation=false;return parent::beforeAction($action); }
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['view' => ['GET', 'HEAD'], 'assignment' => ['POST'], 'details'=>['POST'], 'details-method'=>['POST'], 'prepare' => ['GET', 'HEAD'], 'gone' => ['GET', 'HEAD', 'POST'], 'method' => ['GET', 'HEAD']],
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
            )->read($this->actor(), $id,is_string(Yii::$app->request->get('history'))?Yii::$app->request->get('history'):null);
            if ($card === null) return $this->status(404);
            $card['detailEditor']['allowed']=$this->processCap('objects.details.edit');
            if($this->detailsValidation!==null){$card['detailEditor']['values']=$this->detailsValidation['values'];$card['detailEditor']['errors']=$this->detailsValidation['errors'];$card['detailEditor']['open']=true;}
            $card = $this->withCurrentInstallerStatuses($card);
            $documentAccess = $this->documentAccess($id);
            if ($documentAccess['status'] === 'unavailable') return $this->status(503, true);
            $completionQuery = new MariaDbYiiCompletionQuery(Yii::$app->db, (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX'));
            $completion = $completionQuery->read($id);
            $checklistAccess = $this->checklistOwner()->access($this->actor(), $id);
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
                'canUpload' => $documentAccess['canUpload'],
                'canCorrect' => $documentAccess['canCorrect'],
                'canReadOriginal' => $documentAccess['canRead'],
                'canReadChecklist' => (bool) ($checklistAccess['read'] ?? false),
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

    public function actionDetails(string$id):string|Response
    {
        $id=$this->canonicalId($id);if($id===null)return$this->status(404);if(!$this->cap('objects.read'))return$this->status(403);
        if(preg_match('~^application/x-www-form-urlencoded(?:\s*;\s*charset=utf-8)?$~iD',(string)Yii::$app->request->contentType)!==1)return$this->status(415);
        $body=Yii::$app->request->rawBody;if(strlen($body)>32768)return$this->status(413);$wireKeys=[];foreach(explode('&',$body)as$pair){$key=rawurldecode(str_replace('+',' ',explode('=',$pair,2)[0]));if($key===''||str_contains($key,'[')||isset($wireKeys[$key]))return$this->status(400);$wireKeys[$key]=true;}parse_str($body,$fields);if(!Yii::$app->request->validateCsrfToken((string)($fields['_csrf']??'')))return$this->status(400);
        $meta=['_csrf','requestId','expectedRevision'];$patch=array_diff_key($fields,array_flip($meta));foreach(['floors','weight','speed','pittype','pitmaterial','lift_type','paired']as$key)if(($patch[$key]??null)==='')unset($patch[$key]);if(array_diff($meta,array_keys($fields))!==[])return$this->status(400);
        try{$expected=filter_var($fields['expectedRevision'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);if($expected===false)throw new \InvalidArgumentException();$registry=new ObjectDetailsFieldRegistry();$definitions=$registry->definitions();$errors=[];foreach($patch as$field=>$value){try{$registry->normalizePatch([$field=>$value]);}catch(\InvalidArgumentException){$errors[isset($definitions[$field])?$field:'_form']='Некорректное значение. Проверьте поле.';}}if($errors!==[])return$this->detailsValidation($id,$patch,$errors);$command=new ObjectDetailsEditCommand((string)$fields['requestId'],$id,$this->actor(),(int)$expected,$patch);
            $resources=new PreopeningResources(Yii::$app->db);try{$result=$resources->editObjectDetails($command);}finally{$resources->close();}
        }catch(\InvalidArgumentException){return$this->status(400);}catch(\Throwable){return$this->status(503,true);}
        return match($result['status']){'applied','replayed','noop'=>$this->redirect303('/pilot/objects/'.$id.'#history'),'invalid'=>$this->detailsValidation($id,$patch,['_form'=>'Проверьте введённые значения.']),'rejected'=>$this->status(403),'conflict'=>$this->status(409),default=>$this->status(503,true)};
    }
    public function actionDetailsMethod(string$id):Response{return$this->canonicalId($id)===null?$this->status(404):$this->methodNotAllowed('POST');}

    private function detailsValidation(int$id,array$values,array$errors):string|Response
    {
        $this->detailsValidation=['values'=>$values,'errors'=>$errors];Yii::$app->response->statusCode=422;return$this->actionView((string)$id);
    }

    private function documentAccess(int $id): array
    {
        $resources = new PreopeningResources(Yii::$app->db);
        try { return $resources->originalAccess()->readAccess($this->actor(), $id); }
        finally { $resources->close(); }
    }
    private function checklistOwner(): MariaDbYiiChecklist
    {
        return new MariaDbYiiChecklist(
            Yii::$app->db,
            (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX'),
            (string) getenv('FMONITOR_LEGACY_TABLE_PREFIX'),
            (string) (getenv('FMONITOR_ARTIFACT_STORAGE_ROOT') ?: getenv('FMONITOR_DEMO_PRIVATE_ROOT')),
            (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format(DATE_ATOM),
        );
    }

    private function withCurrentInstallerStatuses(array $card): array
    {
        $installers = array_merge(
            $card['order']['installers'] ?? [],
            $card['pendingComposition']['installers'] ?? [],
        );
        if ($installers === []) return $card;
        $tabIds = array_values(array_unique(array_map(
            static fn (array $installer): int => (int) ($installer['tabId'] ?? 0),
            $installers,
        )));
        $directory = new MariaDbYiiInstallerDirectory(
            Yii::$app->db,
            (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX'),
            (string) getenv('FMONITOR_LEGACY_TABLE_PREFIX'),
        );
        $statuses = [];
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format('Y-m-d');
        foreach ($tabIds as $tabId) {
            $result = $directory->read($today, [
                'q' => (string) $tabId,
                'tab' => (string) $tabId,
                'status' => '',
                'availability' => '',
                'page' => 1,
            ]);
            $matches = array_values(array_filter(
                $result['rows'],
                static fn (array $row): bool => (int) ($row['installer_tab_id'] ?? 0) === $tabId,
            ));
            if (count($matches) !== 1 || !in_array($matches[0]['employment_status'] ?? null, ['employed', 'dismissed'], true)) {
                throw new \RuntimeException('Object card workforce status unavailable.');
            }
            $statuses[$tabId] = $matches[0]['employment_status'];
        }
        $withStatuses = static function (array $installer) use ($statuses): array {
            $installer['employmentStatus'] = $statuses[(int) $installer['tabId']];
            return $installer;
        };
        if (($card['order']['installers'] ?? []) !== []) {
            $card['order']['installers'] = array_map($withStatuses, $card['order']['installers']);
        }
        if (($card['pendingComposition']['installers'] ?? []) !== []) {
            $card['pendingComposition']['installers'] = array_map($withStatuses, $card['pendingComposition']['installers']);
        }
        return $card;
    }

    public function actionMethod(string $id): Response
    {
        return $this->canonicalId($id) === null ? $this->status(404) : $this->methodNotAllowed('GET, HEAD');
    }
}
