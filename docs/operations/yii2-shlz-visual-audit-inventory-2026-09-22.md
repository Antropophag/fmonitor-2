# Реестр сверки визуального аудита Yii2

Source: `bced877aec8a8802e97037749ca4251d3098df1a` — одновременно audit SHA и
актуальный `origin/main` на старте change. Parent issue: #197. Owner brief от
2026-09-22 расширяет #197 единым проходом и включает ОТиЗ.

Статусы ниже разделяют подтверждаемую структуру исходников и browser outcome.
До воспроизведения в разрешённом runtime визуальные последствия имеют статус
`UNKNOWN`.

| Finding | Source status | Browser status | Evidence / next witness |
|---|---|---|---|
| V01 разные таблицы | REPRODUCED | UNKNOWN | `objects.php`, `construction-control.php`, `deadline-certificates.php` расходятся с полным table contract; representative runtime sweep |
| V02 вложенное поле | REPRODUCED | UNKNOWN | `installers.php` оборачивает результат полного `ViewSupport::choice()`; structural + browser label test |
| V03 selector selection modal | REPRODUCED | UNKNOWN | modal — sibling `[data-selection-picker]`, CSS требует descendant; mobile open-dialog test |
| V04 payment confirmation | REPRODUCED | UNKNOWN | `div[role=dialog]`, JS переключает `aria-hidden`; focus/Escape/cancel browser test |
| V05 ОТиЗ владеет shell | REPRODUCED | UNKNOWN | `.fm2-shell:has(.fm2-otiz)` меняет grid/sidebar/workspace; computed layout sweep |
| V06 конфликтующие responsive rules | REPRODUCED | UNKNOWN | несколько table/card/display/min-width/ellipsis generations в `pilot.css`; boundary-width financial test |
| V07 secondary forms вне общего contract | REPRODUCED | UNKNOWN | plain controls и неполные field compositions; inventory + form assertions |
| V08 местные overlays/notifications | REPRODUCED | UNKNOWN | checklist/inspection/payment families расходятся; overlay/state inventory |
| V09 shell/landmark drift | REPRODUCED | UNKNOWN | views самостоятельно закрывают/создают shell/main composition; landmark/skip-link sweep |

Runtime witness, screenshots и окончательный статус каждого finding будут
добавлены после Gate 1 verification plan и до завершения задачи 1.2. Новые
наблюдения не расширяют scope автоматически.
