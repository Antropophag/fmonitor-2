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
