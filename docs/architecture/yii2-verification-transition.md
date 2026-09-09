# Проверки при переходе на Yii2

Исходная карта построена с foundation581b1f37; companion TSV показывает ссылки
на старую инфраструктуру по test file. Это lexical navigation, не разрешение
выключить найденные тесты и не утверждение, что каждое совпадение устарело.

| Семейство assertions | Внешняя гарантия, которую сохраняем | Как меняется проверка |
|---|---|---|
| public/runtime.php требует rapid router | Реальные URLs, методы, права и текущий UI работают | Yii HTTP/browser tests + отсутствие старых runtime includes; exact include assertion заменяется при cutover |
| Собственный request parser/response DTO | Trusted Host, no spoofed identity, safe error, HEAD/CSP/cache | Yii Request/Response/filters observable matrix; private DTO layout не является API |
| REMOTE_USER/FMONITOR_AUTH_USER_ID bridge | Actor поступает только из серверной authenticated identity | Отдельные spoofed headers/query tests против YiiUser, без legacy fallback |
| fm2auth payload, custom storage primitives/types/fsync filenames | Нет ложного success при storage failure; корректные login/logout/revoke/restart | Yii Session persistence tests через documented native handler fault seam; старый алгоритм не воспроизводится |
| Cookie/session continuity across old runtime | Ранее было обязательным | Владелец явно разрешил новый вход; stale legacy cookies must not authenticate |
| PHP8.5/native compose/bin script names | Поддерживаемый runtime, явные migrations/import, no webDDL, durable jobs | Yii PHP8.4/FPM + common config/console; старые literal names заменяются при соответствующем срезе |
| SQL writer/transaction ownership ratchets | Один application owner, атомарность и append-only факты | Проверки владельцев расширяются на Yii controller/console paths; нельзя exempt новый каталог целиком |
| Exact old fixture users/data counts | Детерминированный независимый expected domain result | Disposable fixtures можно пересоздать; ожидаемые формулы/роли/история не ослабляются |

Каждая замена связывает прежний test/spec с новым public behavioral test и review.
Неиспользуемые исторические implementation tests можно оставить как oracle отдельно
от production contour; перенос в историю отмечается в inventory с причиной. Ни один
боевой route нельзя объявить готовым только потому, что старый assertion удалён.

Особенно сохраняются: accepted snapshot воспроизводимость/неизменность; no partial
publication; запрет повторных выплат через срезы; single-use invitations; permission
byte exactness; opening/original/history правила; offline operation attribution и
replay; schema/upgrade/restart/jobs/restore. Новый framework не заменяет эти доменные
контракты своими internals.
