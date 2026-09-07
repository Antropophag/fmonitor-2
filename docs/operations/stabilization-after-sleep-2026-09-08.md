# Продолжение стабилизации после сна — 8 сентября 2026

Работа начата по `continue-after-sleep-2026-09-07.txt` на HEAD `8c0cc63`.
Исходный WIP `bin/fmonitor2-pilot-demo.php` совпал с handoff SHA256
`b6c91db186aef321359464d97b4b7439df8fc5a2fcdd41a8fa399f0baf6596a1`;
он продолжен, не сброшен. Чужой architecture audit и `.DS_Store` не изменялись.
Persistent goal в новой сессии отсутствовала (`get_goal: null`), поэтому по
инструкции владельца восстановлена ACTIVE-цель без token budget.

Установленный owner stand остаётся `a6d3a7f`, healthy; реальные данные1450/966,
образ и volumes не изменялись. Ни CI publication, ни PR10 merge, ни внешние
Bitrix/import действия не выполнялись. Полный новый verify ещё не запускался. Migration18 исправление и независимые
reviews сохранены в commit `58e2595`.

## Demo bootstrap: подтверждённые причины и работа

Private evidence: `~/.local/state/fmonitor2/manual-pilot-20260907/runtime/`.
Логи `bootstrap-body-001.log`… — последовательные отдельные файлы, не перезапись
одного терминального лога. Tight body diagnostics исключают обязательный child
loop только в private копии; committed test не имеет skip flags. Whole bootstrap
с обязательными children остаётся необходимым перед final approval.

- Исходный launcher падал до HTTP: отсутствовало разрешение имени
  `CanonicalMigrationApplication`. Короткий provision diagnostic это воспроизвёл.
- Тест создавал случайные БД через admin, затем подключал обычного пользователя
  без grants к этим БД. Harness теперь использует creator credential только
  для своих disposable БД; права существующих пользователей не расширяются.
- Expanded workforce schema требует явного column list и текущей synthetic
  provenance. Local actor/roles/permissions и object detail нужны current routes.
- Fresh selection factory требует единый process/legacy prefix. Новые fictional
  поколения используют52 таблицы в одном namespace; прежние split generations
  не переписываются. Их автоматическая миграция/удаление не заявляется.
- `app/demo/PilotDemoDatabase.php` владеет созданием только пустых synthetic
  generations и cleanup с двумя независимыми nonce anchors. Runtime HTTP не
  использует этот модуль. Canonical migrations и case importer переиспользуются.
- `app/demo/router.php` передаёт configured actor и случайный per-server CSRF
  через доверенный контекст в неизменённый native router. Для POST сохранена
  точная demo Host/Origin/Sec-Fetch граница. Подмена identity headers не действует.
- Нативное хранилище сессий использовало отсутствующий здесь Docker path
  `/home/fmonitor`; demo теперь задаёт собственный root внутри поколения.
- Проверка работающего процесса поддерживает macOS через argv-form `ps`,
  сохраняя Linux `/proc`. Running/reset/cleanup assertions проходят.
- Native card показывает ссылку `/prepare`, current coordinator перенаправляет
  её303 на `/selection`. Проверяется реальный переход, без изменения native UI.
- Current selection POST, exact stored members/engineer и restart persistence
  прошли private body. Host/Origin, spoofed actor, canonical catalogue, status
  missing-table sensitivity, occupied reset, foreign marker и prefix collision
  также достигнуты и проходят. После исправления migration10 private body целиком PASS (лог017, SHA256
  `026b91a851303ff21e44b51a915c6d8c56bb28c9e3c144d7a50ab7d4bcab73b6`).

Spec/test reconciliation имеет явную retained/superseded mapping в change
`reconcile-protected-pilot-e2e-current-flow`. Полный original/checklist/completion
journey остаётся обязательным protected child. Старый manual-registration
walkthrough не возвращается в продукт. Независимый reviewer проверяет новые bytes.

## Migration18 collation

Fresh canonical migrations1–17 в `utf8mb4_bin` проходили, migration18 после DDL
не подтверждала readiness. Fingerprint ошибочно заменял literal column collation
`utf8mb4_bin` на `@collation`, когда она совпадала с default collation БД.
Теперь placeholder нормализуется только для колонок с таким expected contract.
Новый public-seam regression (general_ci + bin, apply/readiness/no-op) и соседний
unknown-employment test GREEN; независимые test/code reviews APPROVED:
`MIGRATION-18-COLLATION-REGRESSION-INDEPENDENT-2026-09-07.md` в обоих review каталогах.
Unmodified binary-collation demo provision затем PASS1.6s.

## Migration10 reset

`demo-reset-per-migration.log` доказывает: первое поколение canonical19 проходит,
второе в той же disposable БД падает на migration10 с MariaDB errno121:
фиксированные имена FK completion corrections конфликтуют между namespaces.
Это не timeout и не повод ослабить reset/preservation assertions.
Исправление создаёт prefix-scoped имена для новых namespaces и сохраняет
совместимость существующих populated schemas без изменения их данных. Public-seam
regression, полный existing completion schema test и исходный двухпоколенный
provision diagnostic GREEN; independent test/code reviews APPROVED. Исправление сохранено в commit `f3e6239`.

Whole original bootstrap001 упёрся в60s deadline migration child. Отдельный
тот же child PASS90.67s. Test-only MariaDB (tmpfs, запущенная3дня назад) содержала
38 disposable schemas/2332tables и занимала5.485GiB; long DB locks не наблюдались.
Пересоздан только project `fmonitor2-test` через `make test-env-down/up`, затем
canonical `make migrate` PASS19. Owner project/volumes не затрагивались.

Whole original bootstrap002 с неизменёнными deadlines и всеми mandatory children
PASS exit0 за144.03s. Лог `runtime/bootstrap-whole-002.log`, SHA256
`9f7ed5164ea7d95e7280d7f4e1f781acf03fdb8e98758213b9e088d16517feaf`.
`tools/architecture/check`: `ARCHITECTURE CHECK PASSED (7 rules)`;
`git diff --check`: PASS. Final independent bootstrap test/code review APPROVED:
`reviews/{tests,code}/PILOT-DEMO-BOOTSTRAP-CURRENT-INDEPENDENT-2026-09-08.md`.
Этот результат не подменяет ещё не запущенный full verify.

## Следующие обязательные результаты

1. Зафиксировать reviewed candidate, перевести clean prepared verify checkout
   на его SHA с pinned vendor и выполнить полный `make verify`.
2. Только после literal VERIFY_OK — exact image, backup/migration19/deployment,
   restart/golden доказательства и дальнейшая CI/Quality Graph интеграция в рамках
   исходных разрешений. Глобальная цель остаётся ACTIVE.

## Exact-SHA full verify — запущен

Reviewed implementation commit `97519bb`, последующий documentation/task-status
commit — candidate `ccc8dfbfd24765f509ae7e95113fb7ea8f0ee6b4`.
Clean checkout `/Users/antropophag/code/fmonitor-2-verify-stabilization` переведён
с795ac3e на этот SHA; tracked/untracked status перед запуском пустой. Pinned
TCPDF6.11.4 сохранён. Запущен ровно `make verify` с `/opt/homebrew/bin` в PATH.

Private log: `runtime/verify-ccc8dfb-001.log`; receipt:
`runtime/verify-ccc8dfb-001.json` (source, checkout, command, start time; final
exit/time добавляются runner после завершения). Первые stages test-db-reset,
migrate и architecture-check PASS. Итог ещё не получен; не запускать параллельный
DB/full verify поверх активного прогона. Наличие этого checkpoint не завершает цель
и не даёт права объявить VERIFY_OK до literal результата.

## Full verify ccc8dfb — завершён, один setup failure

`make verify` завершился exit2 за1169.41s; log SHA256
`f89fe3aadd0cc7fd4ded53b576abc13a957add538d1d40cb16bc7bbc01a166cb`.
PASS: test-db-reset, migrate, architecture-check, lint, unit-test, db-test,
e2e-test, diff-check. Единственный FAIL — characterization-test:
`rapid-pilot/verify-checklist-current-crew.php` не загрузил
`InspectionPhotoContentIndexSchemaMigration` перед `ChecklistSync::ensureSchema()`.
Это setup failure отдельного verifier, не новая ошибка domain behavior.

Исправление — один require canonical `app/autoload.php` в verifier. Историческая
v8 fixture и assertions current crew202 / historical item installer101 не менялись.
Focused run PASS0.2s; log `runtime/checklist-current-crew-autoload-green-001.log`,
SHA256 `883381616a1484cecd6828431a22231f02b3682c2910243ffc2090916d782ff7`.
Independent supplemental test/code reviews APPROVED:
`reviews/{tests,code}/CHECKLIST-CURRENT-CREW-AUTOLOAD-SUPPLEMENTAL-2026-09-08.md`.
Далее новый commit с этим fix и новый полный exact-SHA verify. CCC не объявляется
VERIFY_OK; установленный a6d3a7f и данные владельца остаются прежними.
