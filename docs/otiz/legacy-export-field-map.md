# Legacy export field map — OTIZ v2

Primary read-only source: sibling `../fmonitor`, commit `0a7efa37af456f1cea1e1df13e9919e99f373f57`. Generator: `application/controllers/Integration.php`, SHA-256 `d97c135f475bb0c0673813789c2bf303a47ec076757b7f34ce9b36baeee14b99`, method `excel_prem_form`, lines 1484–2183. Form: `application/views/integration/calcprem.php`, SHA-256 prefix `5ec1f53e`; mode JS: `common/j/class_integration.js`, SHA-256 prefix `40b43e80`. No workbook template exists: PHPExcel creates one sheet in memory and writes `Премия.xlsx`.

Row level is object × calendar month × installer; legacy also emits an `Итого за <месяц>` row per installer and repeats object money, so rows cannot be summed without duplication. Mode «Все изменения» adds change × installer detail and columns 18–21. Mode «Итоги за период» keeps columns 1–17. Values below are preserved as evidence, not adopted as a second financial engine.

| № | Exact legacy header | Legacy source / known semantics | OTIZ v2 destination and canonical source | Unknown / aggregation rule |
|---:|---|---|---|---|
| 1 | Расчетный период | Month text; current main loses year | Расчёт ОТиЗ / saved report date and optional explicit legacy-period evidence | Never infer missing year |
| 2 | Рег. номер | current `fm_maintable.regnumber` | saved object row / canonical object identity overlay | text, preserve leading zeros; one object identity |
| 3 | Коэффициент типа шахты | current `pitmaterial` mapping 86/112→1.15, 123→1.25, else 1 | saved Кшах evidence | unknown remains unknown; no legacy default 1 |
| 4 | Плановая премия за монтаж, руб. | hard-coded 780000 | saved norm/base/fund | numeric cents; never copy hard-coded value |
| 5 | Фамилия, имя, отчество работника | current installer fields, sometimes changelog | saved stable recipient snapshot | one employee-object row |
| 6 | Таб. № | current main numeric `tab_id`; dev had `tab_id_char` | saved workforce identity | text; preserve leading zeros |
| 7 | Квып | cumulative completed checklist share | saved confirmed/recognized/new volume evidence | object value appears once in object sheet |
| 8 | Премия за монтаж, выплаченная ранее, руб. | cumulative calculated `prem`, not payment facts | `Рассчитано ранее (legacy)` evidence; actual paid from payment ledger | MUST NOT map to `Выплачено` |
| 9 | Количество дней просрочки, дн. | absolute date diff; early completion also positive | saved deadline evidence | use canonical signed/as-of rule; unknown blank |
| 10 | Ксобл.срок. | `1 - days*0.01`, unbounded | saved canonical Ксс | fixed point, validate range |
| 11 | Сумма удержания за несоблюдение сроков, руб. | formatted derived string | saved deadline reduction | numeric cents, object-level once |
| 12 | Расчетная премия к распределению, руб. | monthly delta from legacy formula | saved gross new-volume amount | numeric cents; no workbook recomputation |
| 13 | КТУ работника | checklist sum of `1/count_installers` | saved original contribution and applied KTU | employee-object; unknown blocks when required |
| 14 | Премия с учетом КТУ, руб. | legacy KTU allocation; dismissed becomes zero | saved pre-decision allocation | default pay dismissed; legacy value only evidence |
| 15 | Дисциплинарная поправка, руб. | current object value repeated per installer/month | `Удержания` + employee/object detail | aggregate each deduction fact once |
| 16 | Премия за монтаж к выплате, руб. | row formula with repeated deductions | saved final recipient obligation | employee-object numeric cents |
| 17 | Ответственный производитель работ | current control responsible/name | saved responsible snapshot | blank + control issue if unavailable |
| 18 | Статус работника | current undated status | saved workforce snapshot + live export-time status separately | no invented dismissal date |
| 19 | Изменение | changelog field label, current main all fields | audit/evidence explanation | keep source wording; not a financial fact by itself |
| 20 | Дата внесения изменения | changelog `ctime` | typed audit/evidence date | blank when unavailable |
| 21 | Статус объекта | current object status label | saved object state snapshot | do not conflate lifecycle/admission/payment |

The new workbook retains useful sheets `Объекты`, `Работники`, `Контроль`, `Приложение к приказу`, `Метаданные`, adds primary `Расчёт ОТиЗ`, `Удержания`, and `Решения по выплате`, and may add `К выплате` if the existing appendix contract must remain unchanged. User-controlled strings are text cells; there are no macros or external links.
