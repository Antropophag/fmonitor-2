# Canonical pilot stylesheet href — owner decision

Date: 2026-09-02

Владелец продукта согласовал возврат canonical HTML href
`/pilot/assets/pilot.css` вместо versioned alias
`/pilot/assets/pilot-20260829-23.css`.

Решение подтверждает уже утверждённые контракты `PILOT-UI-SHELL-001` и
`PILOT-SHLZ-ASSETS-001`: `shlz.css` загружается первым, затем отдельный
application stylesheet `pilot.css`. HTTP responses уже используют
`Cache-Control: no-store`, поэтому filename-versioning не является обязательным
для TEST-USER release.

Это решение не удаляет существующие alias routes, не меняет CSS bytes и не
расширяет CSP. Отдельное удаление aliases требует собственного проверяемого
изменения, если оно понадобится.
