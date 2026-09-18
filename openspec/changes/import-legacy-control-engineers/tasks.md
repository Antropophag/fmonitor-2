## 1. Scope, evidence and verification plan

- [x] 1.1 Снять read-only inventory referenced `responsstroicontrol`, legacy users/roles, пустых ссылок и target email/identity collisions без сохранения PII в checkout; проверить, что inventory содержит coverage totals и устойчивые role identifiers.
- [ ] 1.2 Зафиксировать нормативный контракт TASK/acceptance для identity import, pending invitation, case assignment, replay/conflicts и authenticated reads; проверить независимым Gate 1 review полноту security, history и user-return path.
- [x] 1.3 Создать `verification-input.json`, выполнить `python3 tools/delivery/harness.py prepare`, прочитать обязательства Quality Graph и устранить все unresolved coverage до Gate 2.

## 2. Root-authored RED acceptance

- [ ] 2.1 Добавить source snapshot RED cases для exact referenced users, нулевой ссылки, missing/inactive/wrong-role/duplicate identity и consistent-snapshot drift; проверить ожидаемые failures bounded source test.
- [ ] 2.2 Добавить identity RED cases для pending invitation без credential/secret/session, exact role/capability, email collision, identical replay, concurrent replay и preserved manual correction; проверить ожидаемые failures focused identity test.
- [x] 2.3 Добавить installation assignment RED cases для linked/unassigned/conflicted objects, changed source fact, native/manual override и aggregate receipt coverage; проверить ожидаемые failures focused import application test.
- [x] 2.4 Добавить authenticated HTTP RED cases для admin directory, object queue/card, forbidden reader и последующей явной генерации invitation; проверить ожидаемые failures focused Yii2 HTTP tests.
- [x] 2.5 Добавить clean-stand console RED для `legacy-import/run` и повторного импорта, доказывающий atomicity, terminal counts и отсутствие `rapid-pilot`; получить независимый Gate 3 verdict на полный RED candidate.

## 3. Schema and domain implementation

- [x] 3.1 Executor добавляет forward-only migration для pending-invitation semantics, identity provenance и append-only case-engineer source relation на актуальном frontier; проверить fresh/apply/reapply и hostile partial schema focused tests.
- [x] 3.2 Executor реализует IdentityAccess import owner с stable legacy ID, collision fail-safe, role/capability setup и отсутствием auth material; проверить identity RED suite до GREEN.
- [x] 3.3 Executor реализует InstallationProcess assignment owner с revision/provenance, native/manual precedence и concurrency/replay contract; проверить assignment RED suite до GREEN.
- [x] 3.4 Executor расширяет единый target import transaction и terminal receipt без прямых writes из console/source adapters; проверить rollback injection и architecture boundaries.

## 4. Source adapter and public import seam

- [x] 4.1 Executor расширяет read-only legacy snapshot referenced-user graph в одном consistent snapshot и сохраняет cutoff semantics; проверить source RED suite до GREEN.
- [x] 4.2 Executor подключает graph к `legacy-import/run`, добавляет aggregate counts и сохраняет idempotent existing object/template/detail behavior; проверить console import и same-snapshot replay до GREEN.
- [ ] 4.3 Проверить недоступный source/target, malformed identity, positive unresolved reference и concurrent invocation: каждый случай возвращает stable non-zero/conflict без partial facts и утечки PII/secrets.

## 5. User-visible reads and invitation flow

- [x] 5.1 Executor обновляет admin directory, чтобы pending engineer был виден как «Ожидает приглашения» и существующее явное действие создавало первое invitation secret; проверить authorized/forbidden/replay HTTP cases.
- [x] 5.2 Executor обновляет canonical object queue/card read models для отображения engineer identity/status и явного unassigned state без runtime legacy fallback; проверить authenticated HTTP RED suite до GREEN.
- [x] 5.3 Проверить, что pending engineer не может войти, после явной активации может пройти штатный auth flow, а смена source snapshot не отменяет activation или admin correction.

## 6. Delivery and stand acceptance

- [x] 6.1 Выполнить planner-selected bounded checks, обязательный `make architecture-check` при затронутом HTTP boundary и сохранить полную failure inventory; локальный full `make test`/`make verify` не запускать.
- [x] 6.2 Передать exact complete candidate независимому final reviewer, устранить findings и получить требуемые planner Gate 3/5 verdicts без self-approval.
- [ ] 6.3 Запустить один exact-source GitHub CI consumer для полного выбранного matrix и сверить source SHA, все failed jobs и `REGRESSION_FAILURE` inventory.
- [ ] 6.4 На чистом disposable stand выполнить fresh legacy import, authenticated UI proof и restart/replay; Done: каждый eligible object учтён как linked либо unassigned, unresolved conflicts равны нулю, imported engineers ожидают явного приглашения, а queue/card показывают canonical assignment.
