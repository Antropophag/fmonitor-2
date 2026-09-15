# Текущая цель — №38, закрепления в справочнике монтажников

Поручение владельца 2026-09-14: реализовать [№38](https://github.com/Antropophag/fmonitor-2/issues/38)
от актуального `main` `cf0299d8` после merge №40 до PR-ready. Сначала доказать
сквозным regression, что применённое штатным native application workflow закрепление
появляется в `/pilot/installers`, затем согласовать read model с authoritative
append-only application owner.

Текущий состав принадлежит последней `fm2_assignment_order_applications` каждого
дела. Legacy registered-order projection допустима только для дел без application;
rapid-pilot и legacy не становятся источником истины. История сохраняется, свободный
монтажник получает явное empty state.

Не входят redesign №19, управление инженером №52, checklist/offline №131, Bitrix №15
и OTIZ №66. Общие verification/harness files не менять без доказанной необходимости.

Контракт: [YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001](../../specs/YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001.md).
Lifecycle: [show-native-assignments-in-installer-directory](../../openspec/changes/show-native-assignments-in-installer-directory/).
Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые
gpt-5.6-sol/low reviewers решают planner-selected gates. Full local suite запрещён;
используются bounded checks и один exact-source CI run.

Предыдущий pointer и сохранённый WIP записаны в [истории](current-delivery-goal-history-issue-31-2026-09-14-issue38-transition.md).
Фактические source/PR/CI/lane получать через harness state и активный package.

Delivery record: [issue-38-installer-assignments-delivery.md](issue-38-installer-assignments-delivery.md).

Incoming main history for completed delivery №66 remains preserved in
[issue-66-resume-2026-09-14.md](issue-66-resume-2026-09-14.md); it does not replace
this branch's active №38 pointer before PR №144 merges.
