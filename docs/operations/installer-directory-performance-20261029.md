# Справочник монтажников: ограниченная коррекция производительности

Владелец29.10.2026: справочник грузится долго; продолжить разрешённый короткий цикл
код → независимое ревью → production. Никаких новых domain rules, cache/stale data,
изменения схемы, прав или бизнес-фактов. Full local make test/verify запрещён.

Production public seam: `MariaDbInstallerUtilization::directory` с фильтрами по
умолчанию.1320сотрудников,309объектов,50строк страницы. Измерено3.271726s всего,
3.247572s в SQL summary (99% времени); остальные5запросов1–4ms.
Primary source/SQL plan/profile вне Git:
`/Users/antropophag/.local/share/fmonitor-2/installer-directory-performance-20261029/`.
План показывает повторный проход cases и correlated JSON_CONTAINS для каждого
workforce employee, несмотря на используемые indexes. JSON_TABLE поддержан liveMariaDB.

## Контракт

- Считать membership/latest-applied/current/assigned/upcoming одним набором по
  объектам, а не повторным обходом объектов для каждого сотрудника каталога.
- Сохранить native precedence над registered fallback; latestapplication/latestorder
  rules, release/valid_from/valid_to, PTO, наличие matching original и latestselection.
  Не исправлять неоднозначности или вводить новые business guards в этом slice.
- Сохранить exact rows, totals, global summary, sort, filters/search, page size50,
  pagination bounds, unknown states и fail-closed malformed native snapshot behavior.
- Использовать серверную фильтрацию/пагинацию. Не загружать весь кадровый каталог в
  PHP и не вводить cache. Не менять card/forecast/offline behavior.
- Root test at1320workers/309cases: directory read <100000 handler reads
  (key+next+rnd_next), независимо от host speed. Existing correctness fixtures retain
  native/legacy/latest/PTO/upcoming semantics. No facts/schema writes.
- Before deployment compare candidate against original on same production snapshot
  using read-only calls: hashes and query timings. Target at least5× improvement;
  response parity required. After deployment repeat public read measurement.

Root authors scope/tests; separate sol/low executor may implement SQL only;
independent sol/low final reviewer. Deploy only reviewed file hashes with baseline
compare/rollback; preserve prior OTIZ/search/password releases and other sessions.
