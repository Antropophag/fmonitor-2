## 1. Gate 1–2: контракт и RED

- [x] 1.1 Зафиксировать автономную авторизацию владельца, аудит baseline и точный источник публичных `shlz-ui` exports; проверить OpenSpec strict validation и delivery plan.
- [x] 1.2 Добавить быстрый детерминированный browser/DOM тест, который воспроизводит первый кадр при сохранённом `false`, fallback при ошибке/без JS и синхронизацию toggle; запустить его и сохранить INTENDED_RED.
- [x] 1.3 Расширить тест основной навигации и asset provenance для календаря и всех выбранных замен; подтвердить INTENDED_RED только на недостающем поведении.

## 2. Gate 4: минимальная реализация

- [x] 2.1 Реализовать раннее применение состояния и hydration cleanup в общем shell, сохранив no-JS fallback; проверить RED-команду и существующий navigation test.
- [x] 2.2 Воспроизводимо собрать чистый публичный `@shlz/icons` dist, закрепить выбранные exports и заменить подтверждённые ручные/неточные пиктограммы; проверить geometry/provenance assertions.
- [x] 2.3 Запустить один bounded desktop/mobile визуальный проход и `impeccable detect` по изменённым UI-файлам, исправить найденные scope-local дефекты одним пакетом и подтвердить не более чем одним повторным проходом.

## 3. Gate 5 и публикация

- [x] 3.1 Выполнить выбранные planner focused checks без локального полного suite, сохранить команды/результаты и обновить delivery record.
- [x] 3.2 Получить независимый финальный review точного source snapshot, устранить все блокирующие findings и получить APPROVED verdict.
- [ ] 3.3 Создать коммит, push и PR, запустить один exact-source GitHub CI consumer; собрать полный failure inventory при ошибке и довести PR до merge-ready без merge.
