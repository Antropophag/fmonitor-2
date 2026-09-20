# Ordinary change process delivery

## Binding и authorship

- Change: `extend-ordinary-change-process`
- Base: `ae0a9596ee3b18952832949015d3d345459dfc05`
- Branch: `codex/ordinary-change-process`
- Contract/test author: root
- Implementation author: `/root/ordinary_executor` (`gpt-5.6-sol`, low)
- Gate 3 reviewers: `/root/ordinary_gate3`, `/root/ordinary_gate3_v3`; historical verdicts retained in `reviews/tests/ORDINARY-CHANGE-PROCESS-001.md`
- Final reviewer: `/root/ordinary_final`; APPROVED candidate source `124a4a9f…`
- Token usage: `UNKNOWN`

## До и после

До change compact ceremony поддерживал только `BOUNDED_FIX`; changed application
tests переводили presentation из FAST, incomplete ownership не отличался от
чувствительного риска, а READ/test-refactor не имели общего ordinary declaration.

После change один existing `COMPACT_MAINTENANCE` route поддерживает:

- `PRESENTATION` — templates/styles/formatting/display/local UI;
- `READ` — search/filter/sort/pagination/read projection;
- `APPLICATION_TEST_OR_REFACTOR` — application regressions/helpers и
  behavior-preserving refactoring.

Qualifying ordinary task использует одного автора test+implementation и один
independent final review. FAST/FULL выбирается отдельно: incomplete mapping даёт
FULL CI без Gate 3. Policy change этой доставки сохраняет Gate 3 + final.

## Artifacts и reviews

Future ordinary task требует один canonical requirement source, regression,
compact delivery record, final review и selected exact-source CI: один review,
без обязательного proposal/design/tasks/delta-spec набора. Новый существенный
risk/contract change пересчитывает процесс. Historical approval не изменяется.

Эта policy-доработка содержит один stable spec и один OpenSpec lifecycle; Gate 3
v4 APPROVED на exact reviewed test source `ee43f8e…`. Final review APPROVED на
candidate source `124a4a9f…`; exact-source CI pending.

## Focused checks текущей реализации

- `python3 tests/Delivery/ordinary_change_process_001_test.py` — GREEN, 11 tests;
  retained harness record `1789924098232465000-8ec2ee1faccc42508c692714d6ad61c7`.
- `python3 tests/Verification/change_verification_001_test.py` — GREEN, 18 tests;
  record `1789924120976152000-664ab69f9c094a7abd842e8ef75ce718`.
- `python3 tests/Delivery/fast_maintenance_lifecycle_162_test.py` — GREEN, 20 tests;
  record `1789924155202573000-de21474f498f4cccb4863f96b672c1ee`.
- `make architecture-check` — GREEN;
  record `1789924187010571000-4e139352537244b1bae65003feb31dcc`.
- Локальный полный `make test`/`make verify` не запускался.

## Positive и negative evidence

Positive fixture routes для presentation, read и application-test/refactor
используют shipped planner/harness, исполняют выбранные малые tests, готовят final
reviewer package и вызывают existing CI selector. Case G меняет regression bytes
относительно base. Presentation FAST выбирает changed regression, consumer и
лёгкую executable environment obligation; controlled missing environment fails.

Permissions, schema/write, unknown sensitivity, protected method в смешанном
файле, admission policy и sensitive post-prepare delta сохраняют Gate 3 + final.
Missing/failed/cancelled/incomplete/unknown mandatory evidence не даёт aggregate
GREEN. Unchanged-contract correction использует final delta-review; changed
contract/new sensitive risk recomputes Gate 3.

## Исторические примеры

- #187: first-parent diff содержит read projections, Yii views/tests и
  verification policy. Product subset соответствует presentation/read, но весь
  historical diff неприменим к shortcut из-за admission-policy change; сохраняет
  Gate 3 + final, object regressions и FULL CI.
- #194: test/helper refactor с сохранением schema expectations; общий класс
  применим к аналогичной application-test delta, но исторические policy artifacts
  оцениваются отдельно. Сохраняются changed schema tests, helper consumers и FULL CI.
- #209: completed-filter read projection и связанные Yii/browser regressions без
  write/schema/access change; общий READ class применим. FAST не предполагается:
  CI breadth определяется полнотой mapping.

Эти PR не переисполнялись и не встроены в policy. Unseen presentation analogue
проходит общий glob-owned route без exact path/issue registration.

## Ограничения

Классификация остаётся закрытой и fail-closed; это не доказательство полной
семантики программы. Bounded diff-aware sensitive-method check дополняет, но не
заменяет ownership boundaries и независимый final review. Нет нового registry,
LLM/универсального AST classifier, supervisor, telemetry, #107/#153 реализации,
изменения product code, merge authority, stand/deploy/settings.
