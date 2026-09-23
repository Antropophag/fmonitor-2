# Реестр сверки визуального аудита Yii2

Source: `bced877aec8a8802e97037749ca4251d3098df1a` — одновременно audit SHA и
актуальный `origin/main` на старте change. Parent issue: #197. Owner brief от
2026-09-22 расширяет #197 единым проходом и включает ОТиЗ.

Статусы ниже разделяют подтверждаемую структуру исходников и browser outcome.
До воспроизведения в разрешённом runtime визуальные последствия имеют статус
`UNKNOWN`.

| Finding | Source status | Browser status | Evidence / next witness |
|---|---|---|---|
| V01 разные таблицы | REPRODUCED | RESOLVED ON CANDIDATE | Общая data-list разметка и локальный scroll проверены focused visual-contract тестами и снимками objects/construction/certificates/ОТиЗ из `ui-sweep-final2-20260923.1j3EfA`; ширина документа равна viewport. |
| V02 вложенное поле | REPRODUCED | RESOLVED ON CANDIDATE | Фильтры монтажников больше не оборачивают полную field-композицию `ViewSupport::choice()`; structural/forms inventory и installer browser journeys GREEN. |
| V03 selector selection modal | REPRODUCED | RESOLVED ON CANDIDATE | Responsive-правила достигают фактического соседнего dialog; финальный sweep содержит `selection-modal-open.png`, browser assertion GREEN. |
| V04 payment confirmation | REPRODUCED | RESOLVED ON CANDIDATE | Общий overlay controller владеет входом/удержанием/возвратом фокуса, Escape и cancel; focused/browser evidence ОТиЗ GREEN без изменения command form. |
| V05 ОТиЗ владеет shell | REPRODUCED | RESOLVED ON CANDIDATE | Page-owned геометрия `:has(.fm2-otiz)` удалена; на снимках ОТиЗ 320/390/1440 из `geometry-final3-20260923.knuOhU` ширина документа равна viewport. |
| V06 конфликтующие responsive rules | REPRODUCED | RESOLVED ON CANDIDATE | Заменённые responsive/table rules удалены; focused OTIZ/data-list browser checks и финальная геометрия 320/390/1440 сохраняют полные значения/действия и локальный scroll. |
| V07 secondary forms вне общего contract | REPRODUCED | RESOLVED ON CANDIDATE | Secondary forms используют публичные Field/Control, DatePicker и file compositions; forms/overlays inventory и затронутые documentary/browser journeys GREEN. |
| V08 местные overlays/notifications | REPRODUCED | RESOLVED ON CANDIDATE | Checklist, inspection, selection, object-edit и payment overlays используют общую modal surface/controller; финальные checklist/object-edit screenshots и focused journeys GREEN. |
| V09 shell/landmark drift | REPRODUCED | RESOLVED ON CANDIDATE | Shell владеет единственными `main`, skip target и responsive navigation; visual-contract и protected runtime browser journeys GREEN. |

Candidate runtime witness: `ui-sweep-final2-20260923.1j3EfA` (30 captured
states, HTTP 200, JS errors 0, document overflow 0) and
`geometry-final3-20260923.knuOhU` (objects/OTIZ/certificate at 320/390/1440).
Both directories and their `result.json` files are retained outside the checkout
under `/Users/antropophag/.local/share/fmonitor-2/`. Latest reviewed corrections
are additionally captured in `checklist-installer-close-final.png`,
`object-start-datepicker-final.png` and `construction-390-final-0e5df8bd.png`.
These are candidate evidence, not owner checkpoint, independent approval or CI.

## Gate 2 baseline blocker — 2026-09-22

На exact `origin/main` `bced877a` существующие authenticated browser journeys
не достигают новых UI assertions:

- `yii2_installer_directory_browser_001_test.php`: `/pilot/installers` — 503;
- `yii2_user_access_browser_001_test.php`: `/pilot/admin/users` — 503;
- `yii2_preopening_browser_001_test.php`: ранний `INTENDED_RED Yii preopening browser card`;
- `yii2_otiz_shlz_ui_001_test.php`: ранний `shared authenticated shlz shell`,
  ожидаемый root отсутствует.

Новый isolated login browser test достигает публичного seam и даёт корректный
V09 RED на отсутствующем skip-link; structural test даёт корректный V02 RED.
Gate 3 reviewer подтвердил эти два узких evidence, но вернул
`CHANGES_REQUESTED`: они не покрывают A–G. Владелец разрешил включить baseline
restoration. Диагностика установила, что `vendor/` нового worktree был ошибочно
создан как symlink на старый checkout: Composer classmap загружал старый
`MainNavigation` без `icon()`, а текущий `ViewSupport` уже вызывал этот метод.
Это смешивало два source trees и давало 503 при authenticated render.

После удаления только этого symlink и локального `composer install` по текущему
lockfile GREEN: exact-worktree autoload guard, users HTTP/browser, installers
browser, preopening browser и OTIZ browser. Production code для baseline не
менялся; permission/domain contracts не ослаблялись.
