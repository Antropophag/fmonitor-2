# Importer test v1 — volume evidence correction

Дата: 2026-09-05. Автор: `/root`.

V1 test ошибочно создавал MariaDB без tmpfs override и удалял container без
anonymous-volume cleanup. Image объявляет VOLUME `/var/lib/mysql`. Поэтому
исходная формулировка «no volume» и проверка только labeled containers были
недостаточны. Gate3 supplemental record сохраняет этот blocking finding.

Read-only Docker metadata выявила шесть новых anonymous volumes времени
15:31–15:40 с единственными non-system directory names `fmonitor2_demo` и
`fm2_odci_<12hex>`, соответствующими test namespace. SQL rows и содержимое data
files не читались. Более старый volume от04.09 не исследовался и не удалялся.
Docker event ring уже вытеснен регулярными healthcheck events, поэтому полные
container↔volume associations восстановить этим API нельзя.

Автор смог подтвердить по retained tool output только token `1853f0c3f2e7`.
Ему соответствует exact volume
`c64bd9a90ae5ff0aa6d9c1d5b8dd5f527066fe6aff146a26a2bac5261b3f259e`.
После повторной проверки unchanged CreatedAt, anonymous metadata, exact source
directory name и отсутствия consumers удалён только этот подтверждённый volume;
absence проверена. Другие пять новых candidates не удалялись без достаточного
ownership proof. Это не wildcard/dangling cleanup и не утверждение их очистки.

Raw metadata/receipt вне repository:
`/Users/antropophag/.local/state/fmonitor2-verification/object-detail-volume-audit-e9ga5dka`.
`one-confirmed-volume-cleanup.json` SHA-256
`bb0013f54bbfa40175464bb6b6a36178a0f79ce26442b7a121b0ac9f8fa22233`.

V2 test использует explicit tmpfs и проверку actual mounts; его before/after
volume inventory unchanged. Новые runs не создают volume debt, но это не
стирает v1 cleanup finding и не разрешает удаление неподтверждённых ресурсов.
