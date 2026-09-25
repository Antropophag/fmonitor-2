## 1. Контракт и verification plan

- [x] 1.1 Root обновляет current delivery goal для этапа 3 #258, фиксирует owner authorization, clean base `f9b05a29` и фактических root/executor/reviewer; проверить отсутствие смешения с WIP №157 и stage-2 correction.
- [x] 1.2 Root создаёт normative spec `INSTALLER-UTILIZATION-FORECAST-001` с полным public-seam acceptance matrix, точными недельными примерами, UNKNOWN/auth/HEAD/read-only/mobile и non-goals; проверить traceability ко всем OpenSpec scenarios.
- [x] 1.3 Root создаёт `verification-input.json`, запускает `python3 tools/delivery/harness.py prepare`, читает все obligations/commands и фиксирует planner-selected lane/reviews; unresolved coverage блокирует Gate 2.

## 2. RED и независимая проверка

- [x] 2.1 Root пишет focused projection tests для шести границ недель, interval intersection, mid-week start/release, overlap conflict, open end, effective deadline/PTO precedence, draft exclusion и native/legacy ownership; каждый тест должен падать из-за отсутствующего forecast behavior.
- [x] 2.2 Root пишет public `GET|HEAD` HTTP/DOM tests dashboard/detail для complete zero, UNKNOWN/unavailable, authorization/PII, stable ordering и absence of writes; проверить intended RED bounded-командами.
- [x] 2.3 Root пишет browser test desktop/390 px для легенды, keyboard/focus, локального overflow и перехода week bucket → detail; сохранить RED evidence и screenshots вне checkout.
- [x] 2.4 Root добавляет bounded query-count/runtime-closure test для 0/50/125/1000 identities и шести недель без per-row SQL или runtime `rapid-pilot`/`app/PilotHttp`; проверить deterministic RED.
- [x] 2.5 Если verification plan требует Gate 3, независимый gpt-5.6-sol/low reviewer проверяет complete spec/tests/RED source и записывает verdict в `reviews/tests/INSTALLER-UTILIZATION-FORECAST-001.md`; `CHANGES_REQUESTED` возвращает root к контракту/RED.

## 3. Реализация executor

- [x] 3.1 Отдельный gpt-5.6-sol/low executor реализует bounded interval projection в `app/Workforce` с bulk reads, authoritative precedence и conservative UNKNOWN; focused projection tests проходят.
- [x] 3.2 Executor подключает forecast DTO к dashboard и закрытому detail route с единым authorization/HEAD behavior и без writes; focused HTTP tests проходят.
- [x] 3.3 Executor добавляет отдельный `shlz-ui` forecast chart и adaptive detail UI, не меняя historical observation semantics или общий shell; browser/DOM tests проходят на 1440/390.
- [x] 3.4 Executor закрывает planner-selected architecture/runtime/query-count obligations и обновляет OpenSpec task state/delivery evidence; запускать только bounded focused checks, не полный локальный suite.

## 4. Review, CI и публикация

- [x] 4.1 Root проверяет candidate completeness, создаёт reconstructible exact-source snapshot/package и передаёт его независимому gpt-5.6-sol/low final reviewer; verdict записан в `reviews/code/INSTALLER-UTILIZATION-FORECAST-001.md`.
- [ ] 4.2 После `APPROVED` выполнить planner-selected focused regressions и один exact-source GitHub CI run; собрать полный failure inventory до любого исправления и не повторять same-source GREEN без основания.
- [ ] 4.3 Обновить delivery record/handoff точными source/review/CI фактами и проверить Done: forecast независимо от observations, все шесть недель/детали доступны, UNKNOWN fail-safe, no writes, mobile/accessibility green; merge/deploy выполнять только при отдельной разрешённой стадии.
