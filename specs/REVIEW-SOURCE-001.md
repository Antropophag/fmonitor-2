# REVIEW-SOURCE-001 — воспроизводимый кандидат review

## Простыми словами

Reviewer получает точный исходный код до группового коммита. Инструмент сохраняет
снимок вне репозитория и восстанавливает его в отдельный checkout; это позволяет
объединять мелкие исправления без потери проверяемости. Данные приложения и Git
история исходного checkout не изменяются.

## Contract

Актор — root/executor/reviewer с локальным доступом к исходному репозиторию.
Публичный seam: `python3 tools/delivery/review-source.py capture --repo REPO
--output SNAPSHOT` и `restore --snapshot SNAPSHOT --output DESTINATION`.
Все пути могут содержать пробелы; subprocess аргументы передаются без shell.

| ID | Вход / действие | Наблюдаемый результат |
| --- | --- | --- |
| capture | Git checkout с HEAD, staged/unstaged и untracked файлами | Создаёт новый каталог SNAPSHOT вне checkout; manifest.json содержит version=1, полный base_commit, абсолютный repository, patch_sha256. source.patch — binary Git patch от HEAD к текущим файлам, включая untracked; stdout JSON содержит snapshot и patch_sha256. |
| fidelity | Изменённый tracked текст, staged плюс последующий unstaged, удалённый файл, untracked текст/binary, executable и symlink | restore создаёт отдельный detached Git worktree на base_commit и применяет patch; итоговые bytes, отсутствующие файлы, executable mode и symlink target совпадают с capture. Исходный HEAD/index/files/status не меняются. |
| isolation | ignored файл и чистый tracked файл | ignored файл не попадает в patch/restore; чистый tracked файл берётся из base. SNAPSHOT и DESTINATION не должны существовать; capture внутри исходного checkout отвергается до записи. restore внутрь исходного checkout или SNAPSHOT (включая пути через symlink) отвергается до создания файлов/worktree. |
| integrity | Изменённые patch bytes, неизвестная версия manifest или неверный digest | restore завершается ненулевым кодом до создания DESTINATION; понятная ошибка в stderr; чужие каталоги/файлы не изменяются. |
| failures | capture вне Git, существующий output; restore в существующий destination или недоступный base/repository | Ненулевой код, stderr; существующие файлы не перезаписываются. Ошибка применения удаляет только созданный этим запуском worktree и его регистрацию. |
| replay | Повтор capture/restore в тот же output | Отказ без изменений; восстановление одного снимка в разные новые назначения допустимо. |

Снимок — доверенный локальный артефакт, не формат приёма чужих архивов. SHA-256
выявляет изменение patch, не удостоверяет автора. Manifest хранит источник для
локального worktree; перенос между машинами не входит в этот срез. Полномочия
ОТиЗ, денежные факты, сеть, production DB, secrets и publisher не затрагиваются.
Ожидания тестов определяются заданными literals/bytes/modes, не самим инструментом.
