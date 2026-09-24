# INSTALLER-UTILIZATION-STAGE-ONE-001 — текущая и предстоящая загрузка монтажников

## Простыми словами

Первый этап #258 даёт руководителю рабочий маршрут: справочник показывает текущую и следующую загрузку, карточка раскрывает доступные периоды участия, а picker распоряжения показывает тот же компактный контекст. Текущей считается только фактически начатая работа по применённому составу без ПТО; подтверждённый оригинал до открытия даёт план. Ежедневные снимки и два графика остаются следующими этапами.

## Actor и public seams

- Actor: авторизованный пользователь штатного справочника монтажников и существующего picker состава распоряжения в пределах своей области доступа.
- Public seams: GET справочника, GET карточки монтажника и GET/рендер существующего picker.
- Все seams read-only: они не применяют состав, не открывают работы, не создают ПТО и не записывают наблюдения.

## Нормативный контракт

1. Current work для пары «монтажник—лифт» существует тогда и только тогда, когда монтажник входит в фактически применённую редакцию состава этого лифта, фактическое начало зафиксировано и действующего ПТО нет. Плановая дата сама не открывает работу. ПТО освобождает по своему лифту без декларации, выплаты и справки ОТиЗ.
2. Upcoming assignment существует только для монтажника подтверждённого оригинала соответствующего лифта до фактического открытия. Черновик, шаблон и неприменённый выбор не создают upcoming assignment.
3. Current и upcoming независимы: человек может иметь несколько текущих лифтов и одно или несколько будущих назначений одновременно.
4. Идентичность и joins используют effective object/lift requisites из канонических фактов. Адрес — отображаемый реквизит, не ключ группировки.
5. Для трёх текущих лифтов последовательные действующие ПТО дают 3→2→1→0. Человек в людском count считается один раз, но его карточка сохраняет отдельные строки лифтов.
6. Справочник выполняет поиск, filters, count, stable ordering и pagination серверно на полном наборе. Минимальные фильтры: current present/absent и upcoming present/absent; комбинации применяются conjunctively.
7. Строка справочника показывает ФИО, доступные кадровые реквизиты, число current works, компактный upcoming context и ссылку на карточку. Ноль допустим только при достоверно прочитанных обязательных источниках.
8. Карточка различает current, upcoming и доступные completed periods; показывает отдельный лифт, effective object requisites, основание и известные даты. Unknown date отображается как неизвестная. Ссылка возврата сохраняет только allowlisted search/filter/page.
9. При штатно применённой замене состава старый current period заканчивается доступной границей изменения, новый начинается с доступной фактической/применённой границы; прошлые периоды не переписываются ожидающим оригиналом.
10. Picker показывает ту же семантическую проекцию, что справочник и карточка, но не меняет eligibility, не сортирует кандидатов по скрытому score, не выбирает и не записывает состав.
11. Guest получает существующий authentication outcome; denied/limited actor не видит данные вне области. Все пользовательские и внешние строки экранируются. Ошибка обязательного источника обозначается как unavailable, не как пустой набор.
12. GET/replay/concurrent GET детерминированы и не создают business facts. Существующие append-only факты состава/открытия/ПТО остаются единственными основаниями.
13. Этап не создаёт daily snapshots, dashboard analytics, historic/forecast charts, migration v34 или шестинедельный forecast.

## Acceptance matrix

| Case | Given / action | Expected |
|---|---|---|
| A | Applied composition + factual opening, no active PTO | One current lift |
| B | Same lift receives active PTO | Current count decrements; completed participation remains available |
| C | Three distinct lifts, sequential PTO | 3→2→1→0, no address grouping |
| D | Confirmed original before opening | Upcoming shown with known effective date or explicit unknown |
| E | Draft/template only | No upcoming assignment |
| F | Current lift plus another confirmed original | Both contexts shown |
| G | Planned date passed without factual opening | Not current |
| H | Applied composition changes | Old/new participation boundaries preserved; pending original does not replace current |
| I | Dataset exceeds page; filter match is later | Correct server rows/count/pages |
| J | Same person in directory/card/picker | Equal current count and upcoming context |
| K | Missing date or unavailable source | Unknown/unavailable, never invented date/false zero |
| L | Guest/denied/scoped actor and hostile strings | Existing auth/scope, no leak, escaped output |
| M | Repeated/concurrent reads | Same result, no new business facts |

## Explicit remainder of #258

- Stage 2: daily immutable observations, current summary, historical grouped chart and reproducible drill-down.
- Stage 3: six-week forecast, exceptions, forecast grouped chart and unified acceptance of both blocks.
- This specification and its PR MUST NOT close issue #258.
