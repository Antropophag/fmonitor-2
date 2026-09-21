# Аудит рабочих SVG-иконок — baseline 2026-09-21

Источник baseline: FMonitor `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce`; область: исполняемые `app/YiiRuntime` и `rapid-pilot`, без статических прототипов. Всего классифицировано 29 вхождений. Стабильный ключ — путь плюс порядковый номер SVG-вхождения внутри файла; baseline line дан только для навигации. `REPLACE` означает точное публичное соответствие `shlz-ui origin/main 45a99e7`; `SHLZ` — уже переведено; `RETAIN` — брендовая/структурная/data-графика либо нет однозначного action-match.

| № | Стабильный ключ | Baseline | Решение | Причина |
|---:|---|---|---|---|
| 1 | `rapid-pilot/Shell.php#1` | `rapid-pilot/Shell.php:20` | SHLZ | уже использует публичный shlz-ui export `logout` |
| 2 | `rapid-pilot/Shell.php#2` | `rapid-pilot/Shell.php:23` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 3 | `rapid-pilot/Shell.php#3` | `rapid-pilot/Shell.php:34` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 4 | `app/YiiRuntime/Assets/navigation.js#1` | `app/YiiRuntime/Assets/navigation.js:51` | SHLZ | уже использует публичный shlz-ui export |
| 5 | `rapid-pilot/Calendar.php#1` | `rapid-pilot/Calendar.php:38` | REPLACE | точная ссылка раздела → calendar-sidebar |
| 6 | `rapid-pilot/Otiz.php#1` | `rapid-pilot/Otiz.php:239` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 7 | `rapid-pilot/Otiz.php#2` | `rapid-pilot/Otiz.php:265` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 8 | `rapid-pilot/ObjectQueue.php#1` | `rapid-pilot/ObjectQueue.php:34` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 9 | `rapid-pilot/InspectionSchedule.php#1` | `rapid-pilot/InspectionSchedule.php:71` | REPLACE | точное календарное действие → calendar-interface |
| 10 | `rapid-pilot/InspectionSchedule.php#2` | `rapid-pilot/InspectionSchedule.php:86` | REPLACE | точное календарное действие → calendar-interface |
| 11 | `app/YiiRuntime/ViewSupport.php#1` | `app/YiiRuntime/ViewSupport.php:39` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 12 | `app/YiiRuntime/ViewSupport.php#2` | `app/YiiRuntime/ViewSupport.php:47` | REPLACE | точное действие выхода → logout |
| 13 | `app/YiiRuntime/ViewSupport.php#3` | `app/YiiRuntime/ViewSupport.php:105` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 14 | `app/YiiRuntime/MainNavigation.php#1` | `app/YiiRuntime/MainNavigation.php:46` | SHLZ | уже использует публичный shlz-ui export |
| 15 | `app/YiiRuntime/MainNavigation.php#2` | `app/YiiRuntime/MainNavigation.php:62` | SHLZ | уже использует публичный shlz-ui export |
| 16 | `app/YiiRuntime/MainNavigation.php#3` | `app/YiiRuntime/MainNavigation.php:68` | SHLZ | уже использует публичный shlz-ui export |
| 17 | `app/YiiRuntime/Views/users.php#1` | `app/YiiRuntime/Views/users.php:13` | REPLACE | точное действие выхода → logout |
| 18 | `app/YiiRuntime/Views/users.php#2` | `app/YiiRuntime/Views/users.php:21` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 19 | `app/YiiRuntime/Views/selection.php#1` | `app/YiiRuntime/Views/selection.php:47` | REPLACE | точное добавление → plus-alt-2 |
| 20 | `app/YiiRuntime/Views/roles.php#1` | `app/YiiRuntime/Views/roles.php:12` | REPLACE | точное действие выхода → logout |
| 21 | `app/YiiRuntime/Views/original.php#1` | `app/YiiRuntime/Views/original.php:25` | REPLACE | точная загрузка → cloud-upload |
| 22 | `app/YiiRuntime/Views/original.php#2` | `app/YiiRuntime/Views/original.php:30` | REPLACE | точное добавление → plus-alt-2 |
| 23 | `app/YiiRuntime/Views/construction-control.php#1` | `app/YiiRuntime/Views/construction-control.php:9` | REPLACE | точное действие поиска → search |
| 24 | `app/YiiRuntime/Views/construction-control.php#2` | `app/YiiRuntime/Views/construction-control.php:34` | REPLACE | точный переход → chevron-right-duo |
| 25 | `app/YiiRuntime/Views/objects.php#1` | `app/YiiRuntime/Views/objects.php:16` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 26 | `app/YiiRuntime/Views/objects.php#2` | `app/YiiRuntime/Views/objects.php:17` | REPLACE | точное действие выхода → logout |
| 27 | `app/YiiRuntime/Views/objects.php#3` | `app/YiiRuntime/Views/objects.php:26` | REPLACE | точное календарное действие → calendar-interface |
| 28 | `app/YiiRuntime/Views/calendar.php#1` | `app/YiiRuntime/Views/calendar.php:21` | RETAIN | брендовая, структурная или data-графика; точного action-match не требуется |
| 29 | `app/YiiRuntime/Views/calendar.php#2` | `app/YiiRuntime/Views/calendar.php:22` | REPLACE | точное действие выхода → logout |

## Выбранные замены и immutable provenance

| Export | SHA-256 public dist |
|---|---|
| `calendar-sidebar` | `e48dfea86a132c25b44344ee52b4244853280ddbbf7794d1552ca26e0194f54c` |
| `calendar-interface` | `f916a796bdc803cbf0ef8496c7a297a8fb5fa53f278ccc8e18c00f0c9d001d7a` |
| `logout` | `dbb83663d0845b828208a5ee7344498e7c60d286572aa7ebaa11e24a04bdd647` |
| `search` | `384000e3538f5244a0ed9dc8b64ee03d7f439eb5abeeafad9174a12a65b3d77f` |
| `chevron-right-duo` | `95af60d983a376e4b61ccf073ff449881d0e8c3ccc38ec78fa4a3d904e85e496` |
| `cloud-upload` | `e5884ecb92beb5c0308155676f724e5b8567a86a9379f8be61f31b467cd17f4d` |
| `plus-alt-2` | `2feae18ce61dd877cbd01d1f31378bb83455e489aa60930e75bd3d0d99a08538` |

Отдельно проверяется декларативная матрица `MainNavigation`: восемь ссылок имеют закреплённые `shlz-ui` имена; Calendar меняется с `circle-grid-interface-sidebar` на `calendar-sidebar`, остальные семь уже используют точные публичные exports. Все `RETAIN` не заменяются приблизительными символами. Доменные/data SVG не являются action icons.
