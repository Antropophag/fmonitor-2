# VERIFICATION-PR-CYCLE-001

Простыми словами: обычная правка получает один полный CI с отдельными результатами; документы не ждут тяжёлых тестов. Каждый E2E исполняется один раз, защиты остаются.

## ADDED Requirements

### Requirement: Conservative visible selection
`python3 tools/verification/ci.py plan --base REF --event EVENT` SHALL вернуть JSON full/reason/files/categories. Все события кроме pull_request, невалидный base, пустой diff и любой code/test/CI/unknown path SHALL выбирать полный набор. Только README.md и текстовые docs вне docs/architecture и docs/development-process.md допускают docs-only. Для pull_request сравнивается merge-base...HEAD с --no-renames; deleted/renamed code SHALL не маскироваться новой документацией. Принятый owner matrix не меняет прав доступа, историю или секреты.

#### Scenario: Docs versus unknown
- **WHEN** только README.md или docs/operations/note.md изменены
- **THEN** full=false, reason=docs-only, categories=[]; fast остаётся обязательным.
- **WHEN** изменён app/x.php, tests/x.py, openspec/spec.md, .github/x.yml, docs/architecture/x.md, docs/development-process.md или неизвестный путь
- **THEN** full=true и все четыре категории выбраны с причиной.

#### Scenario: Invalid base and rename
- **WHEN** base отсутствует/не существует или code переименован в docs
- **THEN** выбирается полный набор, ошибки git не выдают пустой docs-only PASS.

### Requirement: Complete categories and once-only full composition
`ci.py list CATEGORY` SHALL вернуть runtime TAB path без исполнения; категории unit/integration/e2e/governance — полное непересекающееся покрытие union suites.tsv. Неполный/лишний mapping, неизвестная category, duplicate path в старых группах или conflicting runtime SHALL завершаться SETUP_FAILURE до тестов. `run.sh category CATEGORY` SHALL выполнять этот список ровно один раз, с прежними DB defaults, видимыми VERIFY/TIMING, сохранением child stdout/stderr и ненулевым итогом при любом отказе. Все назначенные tests SHALL продолжаться после отказа. Никакого общего изменяемого timing-файла. DB preflight сохраняется для integration/e2e.

#### Scenario: Full E2E and bootstrap coverage
- **WHEN** исполнен full union
- **THEN** pilot_e2e_flow только в e2e, другие четыре прежних bootstrap child tests и сам bootstrap остаются в union ровно один раз; bootstrap не запускает их повторно. Все business assertions этих файлов неизменны.

### Requirement: Fail-closed CI aggregation
`ci.py aggregate --full true|false --results JSON` SHALL принимать только success для fast и plan. Для full все четыре category results SHALL быть success; для docs-only все четыре SHALL быть skipped. Failure/cancelled/неожиданный skip/отсутствие результата/invalid full/JSON SHALL давать ненулевой итог. Успех full печатает VERIFY_OK, успех docs-only — DOCS_VERIFY_OK и не VERIFY_OK. Workflow SHALL создавать отдельные category jobs на отдельных VM; integration/e2e setup reset+migrate и always teardown; не запускать второй полный harness.

#### Scenario: Missing or failed evidence
- **WHEN** integration failure, e2e skipped при full или отсутствует plan result
- **THEN** verify fail, VERIFY_OK отсутствует.

#### Scenario: Approved docs-only policy
- **WHEN** plan/fast success, full=false, все категории skipped
- **THEN** DOCS_VERIFY_OK, без заявления full verification.

### Requirement: Canonical test command
Категорийный запуск SHALL очищать управляющие переменные родительского make (CATEGORY, MAKEFLAGS, MFLAGS, MAKEOVERRIDES) перед исполнением тестов. Вложенный make внутри harness-теста SHALL исполнять свой явно заданный target без повторного выбора внешней категории. Сама выбранная категория сохраняется аргументом runner.
По решению владельца основная команда SHALL называться `make test` и сохранять девять этапов и отказ при любой ошибке. `make verify` SHALL оставаться совместимым alias без повторного исполнения. `make fresh-test` SHALL выполнять test и обязательный teardown; fresh-test-verify остаётся alias. Исторический VERIFY_OK сохраняется как маркер evidence. CI и текущие инструкции SHALL использовать test.

#### Scenario: Test and compatibility alias
- **WHEN** все fixture stages успешны и вызван make test либо make verify
- **THEN** каждый из девяти этапов выполнен один раз и выводится ровно один VERIFY_OK.
