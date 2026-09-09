# Выплаты ОТиЗ — кандидат PR по #70 / #76

## Результат и граница

Три операции — удержание, выплата остатка среза и сторно — принадлежат
`app/Otiz/OtizSettlement`. Yii и временный rapid-адаптер вызывают одного владельца;
прежние финансовые INSERT/транзакции и неиспользуемый event helper удалены из HTTP.
Остаток сериализован по финансовому объекту через срезы:100000→150000 разрешает
доплатить только50000. Права проверяются перед повтором, история сохраняется
append-only, успешный no-op имеет receipt, сторно добавляет точные отрицательные
компоненты и не переписывает исходную запись.

Новый Yii-экран сохраняет финансовую информацию и формы. Его финансовая навигация
и экспорт читают существующие данные. Publication/calculate/accept остаются
прежними native-входами; полный переход приложения по #76 этим PR не объявляется.
Нормативный контракт: [OTIZ-SETTLEMENT-001](../../specs/OTIZ-SETTLEMENT-001.md).

## Схема и эксплуатация

Миграция24 добавляет только блокировки объектов и receipts. Точные проверки
текущей схемы обновлены до24; самостоятельные исторические миграции не переименованы.
Демо-запуск и частные тестовые префиксы используют новую схему явно, до HTTP.
Backup/restore поддерживает точные71 таблицу и прежние39 AUTO_INCREMENT; исторические
профили22/23 неизменны. Репетиция восстанавливает копии их собственными exact images,
затем обновляет схему с сохранением старых записей, счётчиков и файлов.

Временный runtime собирает настоящие Composer production dependencies из общего
lock, включая Yii/TCPDF и PDO MySQL. Container smoke использует только image-owned
app/vendor и DML-принципал через настоящий retained HTTP seam. Переключение рабочего
стенда в этот PR не входит.

Откат: не удалять добавленные таблицы и финансовые факты. Для восстановления применять
копию вместе с её точной версией образа; старый образ не принимает новую копию.
После появления новых выплат откат на прежний финансовый writer не является
безопасным способом продолжить запись. Для переключения/возврата сохраняется отдельный
эксплуатационный порядок остановки writers и согласованного backup/restore.

## Независимые проверки

- Ядро: [Gate5](../../reviews/code/OTIZ-SETTLEMENT-001-core-v2.md), source9ec3f6c1.
- Backup/restore: [Gate5](../../reviews/code/OTIZ-SETTLEMENT-001-v24-recovery-v1.md), sourcebd709838.
- Deployment mapping: [Gate5](../../reviews/code/CHANGE-VERIFICATION-001-deployment.md), source64dddf3b.
- Коррекция полного пользовательского пути: [RED evidence](otiz-final-corrections-red-2026-09-10.md), root-authored tests4b7d551f; независимый reviewer подтвердил Gate3.
- [Текущая схема и регрессии](otiz-v24-regression-amendment-2026-09-10.md),
  [browser evidence](otiz-settlement-browser-red-2026-09-10.md),
  [runtime evidence](otiz-runtime-dependencies-red-2026-09-10.md).

Исторические CHANGES_REQUESTED сохранены; они не считаются текущим одобрением.
После уточнения владельца root пишет спецификацию/тесты, отдельно назначенные агенты
пишут код и выполняют независимое review. Прежнее авторство не переписано.

## Проверки кандидата

До финальной коррекции прошли focused schema/owner/concurrency, Yii HTTP/browser,
publication/HTTP/register, premium-calculation, architecture и qualification.
Полная группа изменённых current-frontier тестов пройдена с исправлением настроек
изолированных fixtures; демо-запуск и private-prefix harness также GREEN.
Оба container recovery теста GREEN, включая exact v22/v23 forward-переходы.
Runtime package/compatibility, renderer drift и production packaging — GREEN.

Объединённая финальная коррекция `c1a7c614` прошла три непосредственно затронутых
HTTP/browser/container теста, architecture PASS7 и изолированный compatibility
harness. Последний runtime log: `fmonitor-runtime-settlement-bd743569f4.log`
в приватном временном каталоге. Код заморожен для финального review. Обязательный план:
`openspec/changes/otiz-settlement-owner/verification-input.json`; генерируемый файл
`.local/verification/otiz-settlement-owner-plan.json`. План не заменяет approvals.

На момент записи финальный Gate5 и один authoritative full CI ещё ожидаются.
Их exact source/ссылка будут указаны в PR. Локальный полный прогон не дублируется;
#70 не объявляется влитым, #76 не закрывается и финальная production-интеграция
всего приложения не заявляется этим документом.
