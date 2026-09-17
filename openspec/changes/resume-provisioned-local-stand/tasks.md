## 1. Gate 1 и verification plan

- [x] 1.1 Root обновляет `specs/INITIAL-OWNER-PROVISIONING-001.md` полным acceptance-контрактом clean production provisioning и explicit local resume; проверить traceability к delta-spec и четырём owner-сценариям #185.
- [x] 1.2 Root создаёт `verification-input.json` с public seams, planned paths, observable dimensions и focused tests; выполнить `harness.py prepare`, прочитать все obligations и подтвердить planner-selected STANDARD/CRITICAL (не FAST) до Gate 2.

## 2. Gate 2 и Gate 3

- [x] 2.1 Root расширяет изолированный MariaDB test первого create, exact unchanged replay, resume после login/history/additional user с byte-identical preservation и conflict/ineligible owner zero mutation; focused command должен дать intended RED только по отсутствующему local-resume поведению.
- [x] 2.2 Root добавляет/расширяет ownership/local-up route test, доказывающий цепочку `Makefile up` → CLI explicit local intent → IdentityAccess owner и неизменность production default; при необходимости адресно регистрирует component/tests в `capability_ownership`, не меняя planner algorithms/exceptions.
- [x] 2.3 Независимый `gpt-5.6-sol/low` reviewer проверяет complete spec/tests/RED и prepared obligations, записывает Gate 3 verdict в `reviews/tests/INITIAL-OWNER-PROVISIONING-001.md`; только `APPROVED` разрешает implementation.

## 3. Gate 4

- [x] 3.1 Отдельный `gpt-5.6-sol/low` executor реализует explicit local-resume transport в Make/CLI и read-only owner confirmation в IdentityAccess без второго bootstrap, repair, reset или production weakening; focused tests должны стать GREEN.
- [x] 3.2 Executor выполняет только planner-selected bounded local checks, architecture/placement checks и `git diff --check`, сохраняя полные логи вне checkout; полный локальный `make test`/`make verify` не запускается.

## 4. Gate 5 и PR-ready

- [x] 4.1 Root фиксирует reconstructible exact-source snapshot/commit и передаёт отдельному независимому `gpt-5.6-sol/low` reviewer complete candidate; reviewer записывает Gate 5 verdict в `reviews/code/INITIAL-OWNER-PROVISIONING-001.md`.
- [ ] 4.2 После `APPROVED` root создаёт отдельный PR с `Refs #185`, запускает один требуемый exact-source CI, собирает полную failure inventory при любом failure и доводит тот же bounded slice до GREEN/PR-ready без merge/deploy.
- [ ] 4.3 Финальная delivery запись указывает PR/head, доказанные first/repeat/preservation/rejection сценарии, authorship/reviews/CI и оставшийся объём #185: POSIX modes, file UID, VPN route, import filters, engineers и chunking.
