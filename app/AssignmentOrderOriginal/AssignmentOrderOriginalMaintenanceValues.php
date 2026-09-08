<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Closed maintenance values shared by the owner and persistence adapter. */
final class AssignmentOrderOriginalMaintenanceValues
{
    public static function principal(string $id): bool
    { return preg_match('/^[A-Za-z0-9._:-]{1,160}$/D', $id) === 1; }

    public static function identity(string $id): bool
    { return preg_match('/^[\x20-\x7e]{1,160}$/D', $id) === 1 && !str_contains($id, '/') && !str_contains($id, '\\'); }

    public static function encode(array $pair): string
    { return rtrim(strtr(base64_encode(json_encode(['v'=>1,'at'=>$pair[0],'id'=>$pair[1]], JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)), '+/', '-_'), '='); }

    public static function cursor(?string $cursor): ?array
    {
        if ($cursor === null) return null;
        if (preg_match('/^[A-Za-z0-9_-]{59,484}$/D', $cursor) !== 1) throw new \UnexpectedValueException();
        $bytes = base64_decode(strtr($cursor, '-_', '+/').str_repeat('=', (4-strlen($cursor)%4)%4), true);
        $value = json_decode($bytes === false ? '' : $bytes, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($value) || array_keys($value) !== ['v','at','id'] || $value['v'] !== 1
            || !is_string($value['at']) || !AssignmentOrderOriginalDataScalar::utc($value['at'])
            || !is_string($value['id']) || !self::identity($value['id'])) throw new \UnexpectedValueException();
        $pair = [$value['at'], $value['id']];
        if (self::encode($pair) !== $cursor) throw new \UnexpectedValueException();
        return $pair;
    }

    public static function shape(ReconcileAssignmentOrderOriginalPrivateOrphansCommand $c): bool
    {
        try {
            self::cursor($c->cursor);
            return AssignmentOrderOriginalDataScalar::uuid($c->requestId) && self::principal($c->systemPrincipalId)
                && AssignmentOrderOriginalDataScalar::utc($c->cutoffUtc) && $c->batchLimit >= 1 && $c->batchLimit <= 1000;
        } catch (\Throwable) { return false; }
    }

    public static function tuple(AssignmentOrderOriginalMaintenanceResult $r): array
    { return [$r->status(),$r->reason(),$r->retryable(),$r->scanned(),$r->deleted(),$r->retained(),$r->failed(),$r->nextCursor()]; }

    public static function terminal(array $v): bool
    {
        [$status,$reason,$retry,$scan,$deleted,$retained,$failed,$cursor] = $v;
        foreach ([$scan,$deleted,$retained,$failed] as $count) if ($count < 0 || $count > 1000) return false;
        if ($scan !== $deleted+$retained+$failed) return false;
        try { self::cursor($cursor); } catch (\Throwable) { return false; }
        if ($scan === 0 && $cursor !== null) return false;
        return match ($status) {
            AssignmentOrderOriginalMaintenanceStatus::COMPLETED => $reason === null && !$retry && $failed === 0,
            AssignmentOrderOriginalMaintenanceStatus::REJECTED => !$retry && $scan === 0 && in_array($reason,
                [AssignmentOrderOriginalMaintenanceReason::INVALID_COMMAND,AssignmentOrderOriginalMaintenanceReason::AUTHORIZATION_DENIED], true),
            AssignmentOrderOriginalMaintenanceStatus::PARTIAL => $retry && ($reason === AssignmentOrderOriginalMaintenanceReason::STORAGE_FAILURE
                ? $failed > 0 : $reason === AssignmentOrderOriginalMaintenanceReason::LOCKED && $failed === 0 && $retained > 0),
            default => false,
        };
    }

    public static function page(AssignmentOrderOriginalOrphanPage $page, ReconcileAssignmentOrderOriginalPrivateOrphansCommand $c): array
    {
        $status = $page->status(); $items = $page->candidates(); $cursor = $page->nextCursor();
        if ($status !== AssignmentOrderOriginalStorageStatus::OK || !array_is_list($items) || count($items) > $c->batchLimit) throw new \UnexpectedValueException();
        $previous = self::cursor($c->cursor); $seen = [];
        foreach ($items as $item) {
            if (!$item instanceof AssignmentOrderOriginalOrphanCandidate || !self::identity($item->opaqueIdentity)
                || isset($seen[$item->opaqueIdentity]) || !AssignmentOrderOriginalDataScalar::utc($item->createdOrFinalizedAtUtc)
                || strcmp($item->createdOrFinalizedAtUtc, $c->cutoffUtc) > 0 || $item->byteSize > 20971520) throw new \UnexpectedValueException();
            $stage = $item->kind === AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE;
            if ($item->byteSize < ($stage ? 0 : 1) || ($stage ? $item->sha256 !== null : !AssignmentOrderOriginalDataScalar::hash($item->sha256))) throw new \UnexpectedValueException();
            $pair = [$item->createdOrFinalizedAtUtc,$item->opaqueIdentity];
            if ($previous !== null && (strcmp($pair[0], $previous[0]) < 0 || ($pair[0] === $previous[0] && strcmp($pair[1], $previous[1]) <= 0))) throw new \UnexpectedValueException();
            $previous = $pair; $seen[$item->opaqueIdentity] = true;
        }
        if ($cursor !== null && ($items === [] || self::cursor($cursor) !== $previous)) throw new \UnexpectedValueException();
        return [$items,$cursor];
    }
}
