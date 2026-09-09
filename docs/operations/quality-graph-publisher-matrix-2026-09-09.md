# Фактическая проверка publisher Quality Graph

Основной код поставлен PR #73, merge
`f34320ad2f488a02fbe64b4f10863f47f0d97ddf`. Полный CI
[34350407125](https://github.com/Antropophag/fmonitor-2/actions/runs/34350407125)
прошёл на `b6512ba8f370ceb8bfbc388b60785346da647fd0`: девять успешных jobs,
literal `VERIFY_OK`. Независимые reviews и исходный отказ сохранены в delivery record.

Проверка publisher выполняется в draft [PR #74](https://github.com/Antropophag/fmonitor-2/pull/74),
который запрещено сливать. P0 использует настоящий docs-only CI. Последующие heads
явно помечены `TEST FIXTURE ONLY`: короткие synthetic jobs проверяют транспорт,
формат и обработку результатов, а не прохождение продуктовых тестов.
Trusted publisher и graph всегда берутся из main; runtime и бизнес-данные не меняются.

## Наблюдения и ограничения

- P0 публикует три `passed` и четыре `skipped`, без подмены пропуска полным тестированием.
- Повтор P0 обновляет тот же dashboard-комментарий `5601910947` теми же bytes.
  Stock 0.1.7 создаёт дополнительный успешный Check Run; стабильный Check Run ID не обещается.
- Ошибка исходной категории даёт stock check `failure` и собственную метку
  `quality-graph:failed`. Следующий успех удаляет её, сохраняя чужую `documentation`.
- При повторе первоначального stale-only fixture API вернул **ноль** artifacts.
  Это доказательство отказа при отсутствии результатов, не полноценного stale набора.
  Для старого номера попытки используется отдельный явно сконструированный fixture.
- При отказе preflight штатный writer не вызывается. Ошибка видна в publisher workflow;
  исторические успешные checks не удаляются и не объявляются новым доказательством.
  При rerun GitHub также отразил прежний custom check в своём списке jobs до старта publisher.
- F7: GitHub отклонил повторную загрузку с `409`, оставив семь уникальных artifacts.
  Отказавший reporting-job заблокировал writer. Это проверка платформенной границы
  и reporting admission; два одноимённых descriptors в реальном API не создавались.
- F8: в текущей попытке 2 загружены семь файлов с именами и JSON-метаданными попытки 1.
  Head, run ID и digest сохранены. Preflight отклонил этот полный устаревший набор.
- F9: новый run создан в `13:21:38Z`, пока старый ещё выполнялся до `13:22:32Z`.
  Старый head не получил stock check, новый получил ровно один успешный check.
  Немедленное чтение PR после push ещё возвращало старый head; этот снимок сохранён
  как `f9-immediate-pr-read-old-head.json` и не используется как доказательство нового head.

## Матрица

Матрица выполнена. Draft PR #74 закрыт без merge после завершения обоих последних
publisher runs. Независимый actual-proof review — APPROVED WITH RECORDED EVIDENCE LIMITS
в `reviews/code/QUALITY-GRAPH-CURRENT-CI-001.md`; блокирующих замечаний нет.
Финальный архивный PR закрывает #25 после CI и merge.

<!-- MATRIX_START -->
| Case | Source run / attempt | Publisher run / attempt | Observed | Evidence SHA-256 |
|---|---|---|---|---|
| p0-docs-positive | [34351537845/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34351537845) | [34351827368/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34351827368) | success; 7 artifacts | `4d2e33fb0d1b42c8b3296ea2b84e0a1df177414203b3d249ec5adcebb11d3fa9` |
| p0-publisher-replay | [34351537845/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34351537845) | [34351827368/2](https://github.com/Antropophag/fmonitor-2/actions/runs/34351827368) | success; 7 artifacts | `3d975d2e2f80540b5579d2a3c0dc6135aa2182522063fc24678954e4bf893c55` |
| f1-category-failure | [34352469527/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34352469527) | [34352612973/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34352612973) | failure; 7 artifacts | `2f604faa5297e2be1a3dd0446195513f06bb8b654ada547f01f74dd3040d0717` |
| f2-first-attempt | [34352785002/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34352785002) | [34352893342/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34352893342) | success; 7 artifacts | `2482f02f295903bcaaefe2f2047cf546a22e41ba8081fbd844143c77386a5225` |
| f2-stale-attempt | [34352785002/2](https://github.com/Antropophag/fmonitor-2/actions/runs/34352785002) | [34353268189/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34353268189) | writer skipped; 0 artifacts | `c62d46d2877a3acaf634a64e4ab09490c0ab7a16d1a6c691f3ba1acf1627046d` |
| f3-missing-current | [34353526975/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34353526975) | [34353679187/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34353679187) | writer skipped; 6 artifacts | `a53842a0166248f1a3943d1b2b7abc85318ad1b9f0b0bad59d7fc5b2a4d1883f` |
| f4-malformed | [34354183267/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34354183267) | [34354259815/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34354259815) | failure; 7 artifacts | `dd06ee7e6276a7c161d1427714fb44c28c85f561ff9faa01679809eb7c2f3431` |
| f5-wrong-head | [34354540919/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34354540919) | [34354638982/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34354638982) | failure; 7 artifacts | `3aa4a382c30dc01c729e6ae609b89febc8282afc010b58a32ac80d15e38a081e` |
| f6-wrong-digest | [34354834869/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34354834869) | [34354940292/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34354940292) | failure; 7 artifacts | `1b5188b131a92645a19e6af581663a273d3a13759fb830a4ce635f8689c9b9e1` |
| f7-duplicate | [34355094963/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34355094963) | [34355174872/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34355174872) | writer skipped; 7 artifacts | `222abba3328b82d852dbbec3f229943717352f58fc8e1146feab82b38a8e3db9` |
| f8-stale-preparation | [34355501023/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34355501023) | [34355578444/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34355578444) | writer skipped; 0 artifacts | `3a6ebb9f1f6cd0c0859f925e0beb2352f13833b5584250fb8d985c25e9b5b5b6` |
| f8-stale-artifacts | [34355501023/2](https://github.com/Antropophag/fmonitor-2/actions/runs/34355501023) | [34356083567/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34356083567) | writer skipped; 7 artifacts | `8cfba1438e8acd6b35b3307265f6d4f305e7469e43860557055a853bb7767ccb` |
| f9-old-head | [34356441771/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34356441771) | [34356675035/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34356675035) | no stock check; 7 artifacts | `8e55ff41f2d678ef87cb15c7a75033781ea1f74bbd4b4a48e4bdcf5a938f05ef` |
| f9-new-head | [34356576048/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34356576048) | [34356748686/1](https://github.com/Antropophag/fmonitor-2/actions/runs/34356748686) | success; 7 artifacts | `c144ff95913d99633725adc76008e6f4a0137c70a2da8886f653e5b6946e9688` |
<!-- MATRIX_END -->

## Evidence

Полные read-only снимки API, IDs, комментарии, labels, jobs и artifacts сохранены вне
репозитория: `/Users/antropophag/.local/state/fmonitor2/qg-phase-b-20260909/`.
Файлы не перезаписываются после фиксации. Генератор и GET-only collector проверены
независимо; private review — `/tmp/fmonitor-qg-phase-b-review.md`.

Upstream defect отправлен по поручению владельца:
[alchemmist/quality-graph#69](https://github.com/alchemmist/quality-graph/issues/69).
Проверка перед publisher не патчит штатную библиотеку и сама не выполняет GitHub writes.

По отдельному поручению владельца создан запрос штатной поддержки GitLab:
[alchemmist/quality-graph#70](https://github.com/alchemmist/quality-graph/issues/70).
Текущая поставка проверена для GitHub; GitLab-интеграция здесь не заявляется.
