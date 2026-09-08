<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

/** Complete snapshot publication and explicit acceptance share one application owner. */
final class SnapshotPublication
{
    public function __construct(
        private MariaDbSnapshotStore $store,
        private \Closure $inputs,
        private \Closure $clock,
    ) {}

    public function buildAndPublish(int $actor, string $reportDate, string $operationId): int
    {
        $this->store->authorize($actor);
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$reportDate);
        if(!$date || $date->format('Y-m-d')!==$reportDate) throw new \DomainException('INVALID_DATE');
        if(!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D',$operationId)) {
            throw new \DomainException('INVALID_OPERATION_ID');
        }
        $fingerprint=hash('sha256','otiz-build-v1:'.$reportDate);
        $existing=$this->store->replay($actor,$operationId,$fingerprint);
        if($existing!==null) return $existing;
        try {
            return $this->store->transaction(function()use($actor,$reportDate,$operationId,$fingerprint):int {
                $this->store->authorize($actor);
                $now=($this->clock)();
                $id=$this->store->createDraft($actor,$reportDate,$now);
                $this->store->populate($id,$reportDate,$this->inputs);
                $this->store->publish($id,$actor,$operationId,$fingerprint,$now);
                return $id;
            });
        } catch(\mysqli_sql_exception $error) {
            if($error->getCode()!==1062) throw $error;
            $existing=$this->store->replay($actor,$operationId,$fingerprint);
            if($existing===null) throw $error;
            return $existing;
        }
    }

    public function accept(int $actor,int $snapshotId): void
    {
        $this->store->authorize($actor);
        $this->store->transaction(function()use($actor,$snapshotId):void {
            $this->store->authorize($actor);
            $snapshot=$this->store->lockedSnapshot($snapshotId);
            if($snapshot['status']!=='draft') throw new \DomainException('IMMUTABLE');
            $this->store->assertPublished($snapshotId);
            $this->store->acceptPublished($snapshotId,$actor,($this->clock)());
        });
    }

    public function read(int $actor,int $snapshotId): array
    {
        $this->store->authorize($actor);
        return $this->store->read($snapshotId);
    }

    public function history(int $actor): array
    {
        $this->store->authorize($actor);
        return $this->store->history();
    }
}
