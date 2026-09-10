<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\YiiRuntime\InstallationProcessFactory;
use FMonitor2\YiiRuntime\PreopeningResources;
use FMonitor2\InstallationProcess\MariaDbYiiCompletionQuery;
use FMonitor2\IdentityAccess\MariaDbYiiLocalIdentityStore;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class ObjectCardController extends PreopeningController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['view' => ['GET', 'HEAD'], 'prepare' => ['GET', 'HEAD'], 'gone' => ['GET', 'HEAD', 'POST'], 'method' => ['GET', 'HEAD']],
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
            return $this->render('@app/app/YiiRuntime/Views/object-card', $card + [
                'identity' => Yii::$app->user->identity,
                'canSelect' => $this->processCap('assignment_order.composition.select'),
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
