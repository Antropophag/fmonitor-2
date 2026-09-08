## Context

Каталог c7b7406 имеет 257 строк/256 уникальных путей. E2E входит дважды; bootstrap запускает пять независимых contract scripts в новых процессах без передачи своего окружения/данных. Их повтор не проверяет связь с bootstrap fixture.

## Goals / Non-Goals

Один PR с измеримым сокращением повторов, явными категориями и прозрачным CI. Сохранить все поведенческие assertions и exact source evidence. Не менять доменную модель, protection, publisher permissions или архитектурный baseline.

## Decisions

Сохраняем четырёхгрупповой suites.tsv как совместимый маршрут make verify. categories.json — исчерпывающий path->category mapping; ci.py проверяет равенство множеств с публичными списками и отсутствие дублей между старыми группами. Неизвестная категория/путь/несоответствие — SETUP_FAILURE до исполнения.

run.sh category NAME передаёт ci.py уже существующие DB env defaults. ci.py run исполняет ровно назначенные записи с сохранением stdout/stderr, продолжает после отказов, выводит timing каждого test и всей категории. Не создаёт parallel test processes внутри runner. БД готовит CI job (reset+migrate), teardown в always. Unit/governance требуют только доказанных dependencies; integration/E2E — прежний ci-setup на разных VM.

plan читает git diff --name-only --no-renames -z base...HEAD; force full для событий кроме pull_request, отсутствующего/невалидного base или пустого/неизвестного change set. Только .md/.txt внутри docs и корневой README допускают docs-only; docs/architecture, specs, openspec, policy/process instructions остаются conservative full. Даже docs-only выполняет syntax/architecture/catalog/CI contract. plan публикует JSON с files, reason, full, categories. Выбор основан на полном diff, без GitHub API truncation и без выполнения PR текста как shell.

Workflow retains verify job name как fail-closed aggregator. Четыре category jobs, fast и plan на exact checkout SHA; без двойного full harness. Всегда публикуются timing/результаты в job summary. Полный local->CI дубль не обязателен; локально focused, CI authoritative перед merge. Baseline старого полного harness сохраняется исторически; parity означает сохранение множества проверок за вычетом вызовов-дублей, не новый publisher.

## Assertion mapping

- E2E file и все его fixtures/assertions retained byte-for-byte. DB копия superseded единственным E2E job/stage.
- bootstrap private pdbContract wrapper и цикл пяти children superseded каталогом: production_migration_runner, pilot_case_import, artifact_store, pilot_shlz_assets, pilot_e2e_flow. Каждый исполняется один раз в full union; bootstrap собственные lifecycle/persistence/CLI/CSS assertions retained.
- native inventory baseline digest retained: тест реконструирует старый DB список добавлением единственного удалённого E2E и сравнивает старый digest. Новые CI governance contracts явны и отдельно проверяются.
- Makefile nine-stage aggregation/failure semantics retained.

## Risks / Trade-offs

CI jobs увеличивают setup runner minutes. Поэтому только integration/E2E используют тяжёлую подготовку; бюджеты и реальные durations фиксируются после Actions, ожидание пользователя отдельно от суммы runner minutes. Никакой параллельной работы с одной testDB. Category mapping независим от source heuristics и reviewed явно. Bootstrap alone теперь проверяет bootstrap, для всех inherited contracts используется полный harness.

## Open Questions

Матрица согласована владельцем. Нужен измеренный результат Actions перед готовностью к merge. Quality Graph PR37 ещё не в main; этот PR не сливает чужую publisher работу, документация задаёт его будущий переход на те же команды.
