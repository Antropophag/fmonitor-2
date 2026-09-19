## Context

См. `proposal.md` и контракт capability. В `tools/verification/suites.tsv` отдельно зарегистрирована PHP-обёртка `yii2_shlz_operational_ui_001_test.php`, которая запускает уже существующий `yii2_preopening_browser_001_test.php`; acceptance mapping №196 также указывает обёртку. Quality Graph имеет PR-trigger и manual dispatch, но используемый delivery route перед dispatch не закрепляет ограниченный reuse уже появившегося штатного run. Процесс требует независимых Gate 3/5 и одного exact-source CI, при этом полный локальный suite запрещён.

## Goals / Non-Goals

**Goals:**

- Удалить одну конкретную двойную регистрацию без общего графа дедупликации тестов.
- Встроить fail-closed reuse-before-dispatch в существующую delivery ownership boundary.
- Сделать correction package дельта-ориентированным, сохранив полный scope/findings и независимость reviews.

**Non-Goals:**

- Не менять Quality Graph workflow capabilities, concurrency, release/schedule/manual entrypoints или branch protection.
- Не проектировать глобальный admission engine, reuse между разными SHA, telemetry или общий анализатор качества тестов.
- Не менять production/pilot application, persistence или rapid-pilot.

## Decisions

### 1. Удалить исполняющую обёртку, перенести mappings на канонический тест

`yii2_preopening_browser_001_test.php` остаётся единственным executable witness. Ссылки verification input/inventory переносятся напрямую, обёртка удаляется. До/после список вызовов фиксируется тестом inventory/selection, а один фактический focused запуск подтверждает сохранённый browser seam.

Альтернатива — оставить обёртку и добавить дедупликацию по транзитивным вызовам — отвергнута как несоразмерный общий граф и новый механизм.

### 2. Тонкий launcher использует существующую проверку применимости и observer

Owning module — `tools/delivery`: launcher только оркестрирует bounded poll → applicability check → reuse или single dispatch → existing observer. GitHub transport вводится как инъецируемый CLI/API adapter, чтобы тесты изолированно проверяли ответы и число dispatch. Он не владеет approval/CI truth и не объединяет jobs/runs; применимость и result остаются у существующих admission/observer seams.

Если в актуальном `main` уже есть подходящий launcher, guard добавляется туда; иначе создаётся одна тонкая команда и подключается к текущим publication instructions. Альтернатива — править workflow concurrency — отвергнута: она может отменить обязательный run и не закрывает agent-side duplicate dispatch.

Allowed dependencies: стандартная библиотека Python, существующие `gh`/GitHub JSON seams и delivery admission/CI modules. Новых runtime dependencies и persistence нет. Architecture check должен подтвердить отсутствие новой policy boundary.

### 3. Fail-closed таблица решений

- applicable queued/in_progress → reuse и wait;
- applicable completed → observe/triage тот же run;
- confirmed absent после bounded wait → один dispatch и capture created identity;
- stale/mismatch/failed/cancelled/API error/UNKNOWN → no blind dispatch, explicit result по контракту.

Poll хранит выбранный run identity в рамках одного процесса; повторный запрос статуса не повторяет dispatch. Это process idempotency, не глобальная exactly-once гарантия.

### 4. Изменить существующие handoff/process templates адресно

`docs/development-process.md`, `tools/delivery/handoff-template.md` и фактически используемая схема package/role prompt обновляются в существующих местах. Новый регламент не создаётся. Package добавляет/выводит last-reviewed source, delta reference и dispositions findings; косметическая post-approval bookkeeping фиксируется внешним delivery record/PR, а не требует само-свидетельствующего CI commit.

Исторические records append-only и не редактируются. Planner/evidence schemas и required review selection не меняются.

### 5. Verification и authorship

Root пишет стабильный контракт, acceptance mapping и RED tests. Planner выбирает lane/reviews после `harness.py prepare`; executor gpt-5.6-sol/low реализует только утверждённый candidate. Независимые gpt-5.6-sol/low reviewers решают Gate 3 и final по prepared packages. Локально выполняются только bounded tests; финальный exact-source CI запускается через новый reuse route один раз.

## Risks / Trade-offs

- [Штатный run появляется после bounded window] → fallback возможен только после достоверного absent; window и poll детерминированы и тестируются, UNKNOWN блокирует dispatch.
- [Совпавший HEAD скрывает изменённую base] → applicability обязана проверять base и остальные существующие dimensions.
- [Удаление обёртки потеряет mapping] → до удаления root переносит каждую acceptance mapping на канонический test и добавляет inventory witness.
- [Косметическая классификация скроет материальное изменение] → allowlist узкая: checkbox/PR typo; любые normative/executable/binding/authority/GREEN bytes требуют обычный review.
- [Package schema разрастается] → переиспользуются текущие поля/links где возможно; новые поля допускаются только для last-reviewed delta и disposition, без нового policy engine.

## Migration Plan

1. Зафиксировать новый контракт, verification input и RED tests для inventory, launcher decision table и review handoff examples; подготовить planner package и пройти Gate 3.
2. Executor удаляет wrapper/переносит mappings, реализует launcher guard и адресно меняет инструкции/templates.
3. Выполнить bounded focused checks, capture exact source, пройти final review.
4. Push/PR использует launcher: переиспользовать штатный PR-run либо выполнить один fallback dispatch; CI/status хранится в PR/внешнем delivery record.
5. Rollback — вернуть единый commit изменения; workflow/branch settings и product data не мигрируются.
