## Context

См. `proposal.md`. `deploy/runtime/Dockerfile` уже запускает Yii2 HTTP/console, но
копирует `rapid-pilot` только для `verify-visual-contract.php`. Runtime assets уже
принадлежат `app/YiiRuntime/Assets`; repository demo остаётся отдельным oracle.

## Goals / Non-Goals

**Goals:** минимальный production build context, отсутствие rapid-pilot bytes,
воспроизводимый build и сохранение принятых Yii2 HTTP/CLI contracts.

**Non-Goals:** изменение `Dockerfile` pilot demo, удаление oracle из repository,
переписывание bootstrap стенда, изменение доменной логики, deployment или restore.

## Decisions

1. Удалить `COPY rapid-pilot` и visual verifier только из
   `tools/delivery/Dockerfile.runtime.in`, затем регенерировать committed target.
   Копирование отдельных oracle assets отвергнуто: production assets уже имеют
   Yii2 owner и byte-contract.
2. Проверять и template, и generated Dockerfile, затем файловую систему freshly
   built image. Один static assertion недостаточен из-за риска drift генератора.
3. Выполнить packaged console closed-failure и live HTTP/include smoke. Полные
   route/domain regressions остаются обязательствами generated Quality Graph, но
   не дублируются локально.
4. Persistence owner отсутствует: срез не выполняет DDL/DML. Architecture ratchet
   усиливается executable image contract; rapid-pilot остаётся только oracle вне
   production artifact.

## Risks / Trade-offs

- [Скрытая runtime-зависимость обнаружится только после удаления каталога] → fresh
  image запускает representative web и console seams.
- [Generated Dockerfile разойдётся с template] → проверяются оба файла и renderer.
- [Срез случайно удалит demo tooling] → planned paths ограничены production recipe,
  тестом и delivery metadata.

## Migration Plan

После approved RED удалить два production recipe шага, регенерировать Dockerfile,
получить focused GREEN и независимый Gate 5. Rollback — предыдущий immutable image;
schema и данные не меняются. Stand deployment и общий restore rehearsal отдельно.
