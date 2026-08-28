# HARNESS-ARTIFACT-ISOLATION-001 — изолированная проверка файловой неизменности HTTP-чтений

- Статус: `APPROVED`
- Версия: `0.4`
- Дата: `2026-08-28`
- Актор: автор или reviewer HTTP acceptance-теста
- Публичный seam: test-support API `HttpReadOnlyFilesystemGuard::observe(...)`
- Мигрируемые callers: `PILOT-HTTP-AUTH-001` (`pha`), `PILOT-OBJECT-LIST-001` (`pol`), `PILOT-OBJECT-CARD-001` (`poc`), `PILOT-PREPARE-FORM-001` (`ppf`)

## 1. Цель и единственный acceptance tracer

Устранить ложные падения read-only HTTP-тестов, когда параллельный тест создаёт или удаляет собственный файл в общей `.test-artifacts`, не ослабляя доказательство отсутствия реальных записей HTTP-запроса.

Единственное acceptance statement:

> Один быстрый deterministic harness tracer создаёт два task-owned root и один task-owned защищённый artifact-store root, запускает запись конкурентного процесса только в его root одновременно с наблюдаемым callback и получает `PASS`; затем тот же tracer выполняет callback, изменяющий защищённый artifact-store sentinel, и guard обязан получить exact mutation verdict. Для отдельной regular-file sensitivity фазы tracer создаёт в собственном `mutable/` exact byte-copy production `../shlz-ui` CSS export, передаёт canonical path этой копии через `FMONITOR_SHLZ_CSS_PATH` и как `protectedPath`, намеренно изменяет именно эту configured task-owned копию внутри callback и получает тот же exact mutation verdict. Production `../shlz-ui` export никогда не изменяется. Во всех фазах guard проверяет configured CSS file и защищённый artifact-store root; чужие task roots не входят в fingerprint, а restore и cleanup затрагивают только roots этого tracer.

Это инфраструктурный срез. Он не меняет product routes, HTTP contracts, production code, DB assertions или разрешённые приложению записи.

## 2. Контракт test-support seam

`tests/Support/HttpReadOnlyFilesystemGuard.php` предоставляет один namespaced final helper с публичным статическим seam:

```php
HttpReadOnlyFilesystemGuard::observe(
    callable $operation,
    array $protectedPaths,
    array $ownedMutableRoots = [],
): mixed
```

До `operation` и после неё guard строит byte-and-metadata fingerprint каждого `protectedPath`; при равенстве возвращает exact результат callback. При различии выбрасывает `TestFailure` с redacted сообщением `Protected HTTP read-only path changed.`. Callback вызывается ровно один раз. Исключение callback не преобразуется; post-snapshot всё равно выполняется, и filesystem mutation имеет отдельный диагностический verdict без раскрытия пути или содержимого.

`protectedPaths` принимает два вида существующих объектов: real non-symlink directory root и real non-symlink regular file. `ownedMutableRoots` принимает только real non-symlink directory roots. Все пути абсолютные и находятся под разрешёнными test/workspace boundaries. Protected directory roots и mutable roots обязаны быть попарно непересекающимися: root не может быть равен, быть предком или потомком другого root. Exact protected regular file разрешён внутри caller-owned mutable root как явное исключение: mutable tree целиком не обходится, но этот конкретный configured file fingerprintится. Protected file не может находиться внутри protected directory root, а два entries не могут обозначать один объект. Duplicate, missing, symlink, unsupported type, запрещённый overlap и путь вне boundaries отклоняются до callback точным `TestFailure`; guard ничего не создаёт, не chmod-ит и не удаляет.

Fingerprint directory root включает относительно root, в стабильном bytewise порядке:

- сам root и каждый descendant без перехода по symlink;
- тип entry (`directory`, `regular file`, `symlink`);
- permission bits;
- для regular file — размер и SHA-256 bytes;
- для symlink — exact link target bytes.

Fingerprint отдельного regular file включает exact canonical absolute path, тип `regular file`, permission bits, размер и SHA-256 bytes. Поэтому замена configured CSS другим файлом, смена типа, прав, размера или bytes даёт mutation verdict, но несвязанные файлы и история соседнего `shlz-ui` checkout не читаются и не влияют на timing/verdict.

Не включаются timestamps, inode, uid/gid и порядок обхода: они не являются продуктовым фактом и нестабильны между допустимыми чтениями. Невалидный initial input — duplicate, отсутствующий объект, symlink, unsupported type, запрещённый overlap либо путь вне boundaries — остаётся validation failure до callback. После успешного before-snapshot любое исчезновение или удаление protected entry, замена его типа, ошибка `lstat`/чтения либо изменение дерева во время after-snapshot считается наблюдаемой мутацией и даёт тот же exact redacted verdict `TestFailure("Protected HTTP read-only path changed.")`, а не отдельное сообщение о невозможности чтения и не retry-loop.

## 3. Task-owned roots и границы наблюдения

Каждый caller до запуска сервера создаёт cryptographically unique directory:

```text
<repo>/.test-artifacts/<caller>-<random-token>/
```

Root создаётся с `0700` под уже утверждённым trusted-parent правилом: `.test-artifacts` — real directory, принадлежит effective UID, не group/other-writable и находится под home. Caller хранит exact canonical path; cleanup разрешён только для этого строгого descendant после повторной проверки границы. Общий trusted parent `<repo>/.test-artifacts` всегда сохраняется: его нельзя удалять, переименовывать, chmod-ить или иным образом изменять независимо от того, существовал ли он до запуска или был создан текущим запуском. Cleanup удаляет только exact caller-owned child roots и их содержимое; попытка очистить parent, соседний owner root или иной ancestor запрещена.

Внутри caller root пути делятся заранее:

- `mutable/` — fixture files, synchronization markers, compiled probes и прочие файлы, которыми владеет сам тест; это `ownedMutableRoots` и они не fingerprintятся;
- `protected-artifact-store/` — отдельный seeded root, представляющий production artifact store для read-only HTTP-запроса; он входит в `protectedPaths`, не меняется fixture-кодом между snapshots и проверяется на каждом success/rejection read;
- exact canonical regular file, переданный конкретному HTTP server через `FMONITOR_SHLZ_CSS_PATH`, — task-owned exact byte-copy production corporate CSS export внутри caller `mutable/`; только эта configured копия входит отдельным `protectedPath`. Её snapshot включает canonical path, type, permissions, size и SHA-256 bytes. Production export в `../shlz-ui` используется только как read-only source bytes при setup и никогда не изменяется. Весь sibling checkout `../shlz-ui` не входит в fingerprint: его прочие 708 MiB/10968 entries не потребляются запущенным приложением и не относятся к наблюдаемому HTTP outcome.

Вся общая `<repo>/.test-artifacts` никогда не является directory entry в `protectedPaths`: она лишь namespace независимых owners. Root другого теста не включается ни явно, ни через ancestor. Поэтому параллельное создание, изменение и удаление `<repo>/.test-artifacts/<other-owner>-<token>` не меняет verdict текущего теста.

Configured fixture CSS размещается в caller `mutable/` как exact byte-copy production export, но exact canonical file из `FMONITOR_SHLZ_CSS_PATH` передаётся guard как protected regular file для каждого HTTP outcome. Явная file-защита имеет приоритет над исключением остального mutable tree и не разрешает приложению менять CSS. Только deterministic sensitivity phase намеренно изменяет эту task-owned копию внутри наблюдаемого callback; verdict фиксируется до восстановления исходных bytes, а production export остаётся нетронутым.

## 4. Concurrency, sensitivity и regression outcome

Acceptance tracer использует отдельный subprocess и handshake (`ready` / `release`) внутри root конкурентного owner. Порядок фиксирован:

1. guard завершил before-snapshot защищённых paths и только затем вызывает callback;
2. в первой инструкции callback публикует `release`, устанавливает monotonic deadline не более 5 секунд и разрешает subprocess создать, изменить и удалить sibling-owned sentinel в общей `.test-artifacts`;
3. subprocess подтверждает завершение; callback возвращает fixed literal;
4. after-snapshot равен before-snapshot, результат callback exact, фаза `PASS`;
5. новая независимая фаза изменяет bytes seeded sentinel внутри `protected-artifact-store` из callback;
6. guard выдаёт exact mutation verdict;
7. отдельная фаза передаёт task-owned exact CSS copy одновременно как configured file внутри `mutable/` и как explicit protected regular file, изменяет её bytes из callback и получает тот же exact mutation verdict, доказывая regular-file sensitivity и приоритет file-защиты над mutable-root exclusion.

Handshake timeout измеряется только от публикации `release` внутри уже вызванного callback до `done`; он не запускается перед before-snapshot и потому не может истечь из-за snapshot work. Ожидание `ready` до вызова guard имеет отдельный monotonic deadline не более 5 секунд. Tracer не использует sleep как синхронизацию, сеть, MariaDB или production router и завершается за секунды. Он доказывает одновременно отсутствие прежнего false positive и сохранение red-capability для настоящей записи. Изменённые protected sentinel и configured CSS copy восстанавливаются только после зафиксированного для каждого verdict, затем все task-owned roots удаляются exact cleanup.

Production CSS export в `../shlz-ui` не изменяется ни в одной фазе. Намеренная запись regular-file sensitivity фазы выполняется только в task-owned exact copy, реально выбранную через `FMONITOR_SHLZ_CSS_PATH`; после verdict copy восстанавливается и удаляется вместе с root владельца.

## 5. Узкая миграция существующих HTTP helpers

Gate 4 меняет только test support и четыре HTTP tests:

- `ppfFiles()` удаляется; `ppfRead()` сохраняет полный DB snapshot и вызывает общий guard для task-owned `protected-artifact-store` и exact canonical file из `FMONITOR_SHLZ_CSS_PATH`; CSS fixture может оставаться descendant `mutable/`, поскольку guard явно защищает этот file без обхода остального mutable root;
- `pocTreeSnapshot()`/`pocOwnedFileSnapshot()` заменяются общим guard с exact configured CSS file без изменения HTTP/DOM/DB expectations;
- `polReadOnly()` сохраняет полный DB snapshot и добавляет общий guard с exact configured CSS file;
- `pha` сохраняет полный catalog/rows/views/`AUTO_INCREMENT` snapshot, а filesystem invariants переводит на те же task-owned roots и exact configured CSS file в общем guard.

Имена route-specific request, DB snapshot и assertion helpers могут сохраниться. Не переносятся в общий helper HTTP logic, DB logic, server lifecycle или cleanup. Не мигрируются прочие artifact-producing domain tests: у них иной write contract.

Для каждого `pha`/`pol`/`poc`/`ppf` вызова, который уже утверждает read-only outcome или иным образом наблюдает filesystem при HTTP-чтении, protected fingerprint выполняется непосредственно вокруг HTTP operation. Это обязательное правило охватывает без исключений все успешные filesystem-observing HTTP reads, включая successful raw socket request внутри `phaTrustedServerFixture()` и обе concurrent success-операции внутри `pocConcurrentGets()`: ни одна из них не может выполнять HTTP request вне `HttpReadOnlyFilesystemGuard::observe(...)`. Success, HEAD, authorization rejection, route/method rejection и fail-closed response сохраняют прежние zero-mutation assertions. Ни один существующий product expected value, порядок проверок или assertion не удаляется и не заменяется только правами SELECT.

## 6. Gate 2 observable example

Независимо заданные literals:

```text
operation result: observed-result
concurrent sibling bytes: other-owner-write
protected sentinel before: immutable-production-artifact
protected sentinel malicious after: forbidden-write
configured CSS copy malicious after: forbidden-css-write
expected isolated phase: returns observed-result
expected protected-write phase: TestFailure("Protected HTTP read-only path changed.")
expected configured-CSS-write phase: TestFailure("Protected HTTP read-only path changed.")
expected cleanup: no tracer-owned roots remain
```

Gate 2 — один executable tracer файла support seam. Он обязан сначала RED на отсутствующем seam (не из-за setup), затем после Gate 4 пройти многократно и параллельно с самим собой. Relevant regression suite — четыре перечисленных HTTP tests последовательно и параллельно. Любое удаление DB, exact configured CSS file или production artifact-store неизменности является ослаблением контракта и требует возврата в Gate 1. Изменения других файлов sibling `shlz-ui` checkout намеренно вне этого HTTP read-only boundary.

## 7. Не входит в срез

- изменение product behavior или production artifact storage;
- ускорение либо декомпозиция самих больших HTTP acceptance tests;
- общий framework для всех тестов репозитория;
- retry, quarantine или игнорирование filesystem failures;
- cleanup чужих task roots либо stale workspace data.

## 8. Gate 1 approval

Публичный seam, единственный acceptance tracer, independently literal outcomes, ownership boundaries, bounded concurrency handshake, fail-closed sensitivity, cleanup и узкая migration scope определены полностью. Исключение общей `.test-artifacts` компенсировано явными защищёнными artifact-store roots и exact configured CSS file checks; реальная запись HTTP-запроса не скрывается, а полный несвязанный `shlz-ui` checkout не замедляет snapshot.

Версия `0.4` заменяет `0.3`: общий trusted `.test-artifacts` parent теперь всегда сохраняется, cleanup ограничен exact owned child roots; все успешные filesystem-observing HTTP reads четырёх callers явно охвачены guard, включая `phaTrustedServerFixture()` и `pocConcurrentGets()`; post-snapshot исчезновение, удаление, type replacement или read failure protected object дают единый exact redacted mutation verdict, тогда как невалидный initial input остаётся validation failure. Единственный tracer, публичный seam и общий scope среза не изменены. Все Gate 2/3 evidence для v0.3 относятся к superseded input и должны быть обновлены независимо до Gate 4.

**Gate 1: `APPROVED`. Gate 2 может создавать только failing tracer, выведенный из разделов 1, 4 и 6.**
