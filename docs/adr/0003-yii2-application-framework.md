# Единое приложение Yii2

Владелец 2026-09-09 выбрал Yii2 (#76), чтобы FMonitor использовал уже принятый в
организации framework. Изучение других приложений отменено владельцем в текущей
сессии. Yii2 владеет web/console composition, Request/Response, routing, DI,
validation, sessions/User/Security, CSRF, views/assets, errors/logging. Прикладные
модули сохраняют полномочия, транзакции и append-only факты; контроллеры вызывают
их публичные операции. Это supersedes custom native auth направление #71,
но сохраняет решения ADR0002 о владельцах фактов и атомарности ОТиЗ.

Начальный compatibility target — PHP 8.4 и Yii 2.0 >=2.0.50,<2.1 с точной версией
в общем Composer lock. Текущий main использует PHP 8.5; отдельный Yii runtime
должен проверяться на 8.4 до переключения. Согласно проверенной 2026-09-09
[официальной lifecycle таблице](https://www.yiiframework.com/release-cycle),
для Yii >=2.0.50 указаны PHP 7.3–8.4, security-only с 2026-11-23 и EOL 2027-11-23.
Проверять lifecycle и security advisories при каждом обновлении lock; обновления
Yii и PHP проводить отдельным проверенным кандидатом до EOL. Yii3 не выбран.

Сохраняем MariaDB, nginx/PHP-FPM, shlz-ui и существующие domain seams. Новые DB
границы используют Yii DAO/Query Builder; каждую транзакционную границу переносим
целиком, без объединения mysqli и PDO в одну атомарную операцию. Старые adapters
допускаются только по карте входов и условиям удаления в migration inventory.
Production не переключается при появлении каркаса; сохранность sessions, offline
операций, jobs, ledger, PDF/фото и rollback проверяется в отдельном контуре.

Последующее уточнение владельца в той же сессии разрешает повторный вход и
пересоздание тестовых данных/пользователей. Поэтому Yii использует штатную Session
с новым cookie namespace, без переноса legacy cookies/payload и без adapter для
старого custom session storage. Полномочия и audit/history semantics сохраняются;
старые compatibility assertions становятся историей, новые проверяют публичные
Yii lifecycle и запрет обхода admission. Прежнее требование сохранности sessions
выше superseded этим решением; неизвестные нетестовые материалы не удаляются.

## Применение к inspection journey #76 — кандидат 2026-09-10

Общая операция `InspectionEvidence\YiiChecklist::accept`, реализованная
`MariaDbYiiChecklist::accept` через внутренний `MariaDbYiiChecklistMutation` trait, переносит прежние checklist mutations из HTTP
в один application owner. `ChecklistSync` остаётся тонким adapter к тому же owner;
`item_completed` делегируется уже принятому `InspectionRecording::completeItem`.
Для старого adapter сохраняется переданное mysqli-соединение, новый Yii путь
использует Yii DAO; разные соединения не смешиваются в одной атомарной операции.
Это перенос ownership из ADR0003, без новой предметной политики или инфраструктуры.

Для явного state-changing имени предложена регистрация ровно двух записей
interface/implementation в существующем public_seams baseline. SQL/dependency,
hotspot и прочие allowances не расширяются; полная регенерация baseline не
выполняется. Принятие этого дополнения требует явного независимого рассмотрения
owner boundary в обычном Gate5 всего среза, до merge; отдельный аудит не вводится.
Переименование метода ради ухода от scanner не считается исправлением.

Independent Gate5 на snapshot e93a1e3b явно одобрил единственного owner и ровно
две public_seams записи; см. reviews/code/YII2-INSPECTION-JOURNEY-001.md.
Выявленные DAO/projection дефекты исправляются в том же срезе и не отменяют
требований к соединению или истории. Одобрение registration не является
одобрением дефектного кода или переключения стенда.
