# FMonitor 2.0

Репозиторий нового процесса управления монтажом FMonitor. Legacy-приложение
остаётся в `../fmonitor`, публичные UI-экспорты поставляет закреплённый checkout
`../shlz-ui`.

## Быстрый старт

Поддерживаются Linux и macOS. Нужны PHP 8.5, Node.js 22.22.0, npm,
Python 3.12.11, Git, Make, Bash, ripgrep и запущенный Docker с Compose v2.
Полный список расширений PHP, версии зависимостей и подсказки по установке:
[docs/development-setup.md](docs/development-setup.md).

```bash
mkdir -p ~/code/fmonitor-dev
cd ~/code/fmonitor-dev
git clone https://github.com/Antropophag/fmonitor-2.git fmonitor-2
cd fmonitor-2
make setup
cp .env.example .env
# Задайте в .env уникальный FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD.
make up
```

Пилот будет доступен на <http://127.0.0.1:8092/>. `make setup` можно запускать
повторно: существующие dependency checkout и `vendor` не заменяются молча.
Команда не запускает БД, не меняет `.env` и пользовательские данные.

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
