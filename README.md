# FMonitor 2.0

Репозиторий нового процесса управления монтажом FMonitor. Legacy-приложение
остаётся в `../fmonitor`, публичные UI-экспорты поставляет закреплённый checkout
`../shlz-ui`.

## Быстрый старт

Для запуска стенда на Linux или macOS нужны Git, Make и запущенный Docker
с Compose v2. PHP, Node.js, Composer и соседние репозитории на хосте не нужны:
закреплённые зависимости собираются внутри Docker.

```bash
git clone https://github.com/Antropophag/fmonitor-2.git
cd fmonitor-2
cp .env.example .env
# Заполните пароль администратора, вебхук Bitrix и ID отделов в .env.
make up
```

`make up` поднимает пилот вместе с кадровой синхронизацией Bitrix. Пилот доступен
на <http://127.0.0.1:8092/>. Настройка вебхука, безопасные ошибки и обновление
конфигурации описаны в [инструкции Bitrix](docs/bitrix-startup.md).
`make down` останавливает контейнеры, сохраняя данные.

Для **разработки и локальных тестов** нужны дополнительные инструменты:
PHP 8.5, Node.js 22.22.0, npm, Python 3.12.11, Bash и ripgrep.
Выполните `make setup` по [инструкции разработки](docs/development-setup.md).
Эта команда не запускает БД, не меняет `.env` и не заменяет существующие
dependency checkout и `vendor` молча.

Основной ручной маршрут: загрузить или исправить подписанный оригинал распоряжения,
вернуться в карточку и отдельным явным действием открыть работы с фактической
датой начала. Ручной номер/регистрация распоряжения в пилоте отсутствует.

## Проверки

```bash
make test CATEGORY=unit
make test
```

Первая команда даёт быструю обратную связь. Полный `make test` пересоздаёт
одноразовую БД из `compose.test.yaml`; по умолчанию она занимает порт `23306`.
Не запускайте два полных прогона одновременно с одной test DB. Актуальное
свидетельство проверки установки публикуется отдельно:
[docs/operations/development-setup-evidence-2026-09-08.md](docs/operations/development-setup-evidence-2026-09-08.md).

## Документы

- Продукт и границы: [PRODUCT.md](PRODUCT.md), [CONTEXT.md](CONTEXT.md).
- Контракт пилота: [docs/fmonitor-2-pilot-spec.md](docs/fmonitor-2-pilot-spec.md).
- Модель процесса: [docs/fmonitor-2-pilot-data-model.md](docs/fmonitor-2-pilot-data-model.md).
- Процесс разработки: [docs/development-process.md](docs/development-process.md).
- Исторический migration demo: [app/demo/](app/demo/).
