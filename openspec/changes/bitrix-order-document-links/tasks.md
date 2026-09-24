## 1. Contract and tests

- [x] 1.1 Зафиксировать read-only legacy evidence, exact `zavnumber`, range grammar и строгие non-goals #15.
- [x] 1.2 Привести normative spec, verification input и focused RED tests к минимальному контракту без runs/snapshots/replay/recovery framework; verification: Gate 1/3 review подтверждает scope и sensitivity.

## 2. Native implementation

- [x] 2.1 Реализовать bounded Bitrix delivery и parser/HTTPS-origin validation.
- [x] 2.2 Реализовать одну current links table и transactional full replacement с preservation on failure.
- [x] 2.3 Перенести nullable `zavnumber` через existing mirror/import и реализовать exact read owner.
- [x] 2.4 Подключить console trigger и секцию object card под `objects.read`.
- [x] 2.5 Зарегистрировать hourly sync в existing native Jobs scheduler/worker без нового framework; проверить один job на московский час, repeat и no-backlog behavior.
- [x] 2.6 Поддержать реальный root из 19 299 папок: reuse текущих folder ID/name, batch по 50 только для новых/изменённых и fail-closed replacement.
- [x] 2.7 Оформить техническую документацию в Documents tab как доступный список с количеством, понятным source label и полноценными empty/error states.
- [x] 2.8 Дать стройконтролю mobile-first доступ: count в queue и touch-friendly ссылки непосредственно в checklist, сохранив authorization и fail-soft states.
- [x] 2.9 Скомпактить стройконтроль: одна effective ссылка, чёрная `shlz-ui/folder-file-open` рядом с отгрузкой и icon-button в checklist без счётчиков и большой document-секции.
- [x] 2.10 Убрать вложенную интерактивность: folder-file-open оставить индикатором queue, а в checklist дать полноширинную mobile secondary-кнопку без иконки.
- [x] 2.11 Вернуть legacy-affordance в checklist: заводской номер над процентом является ссылкой, отдельную document-кнопку убрать; видимую подпись даты открытия скрыть.

## 3. Verification and PR

- [x] 3.1 Пересчитать planner, получить требуемый Gate 3 и передать отдельному executor только production scope.
- [x] 3.2 Запустить bounded focused checks и применимые existing architecture/import/card regressions; full local suite не запускать.
- [ ] 3.3 Получить exact-source final review и GitHub CI GREEN, подготовить PR #15 без merge/deploy.
