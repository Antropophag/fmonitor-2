# Safe-log owner — technical amendment candidate

Дата2026-09-06. Author `/root`.
Owner policy остаётся exact resolution2026-09-05; дополнительное продуктовое
решение не запрашивается. Новая technical autonomy перед сном записана в
launch-deadline-and-priorities-2026-09-05.md.

Новый candidate:
`specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md`, v0.1,
SHA256 `5f87fc98c57436261e15293dcbe7a78b6c1d8e3c71bec2104cf516d623c41199`.
Parent original-upload v60:
`d23b9cd924be6ce9deb905a0c742e7b0449eb8755fdb5af8d9caac094934fbf3`.

Pending observer/permission-transition declaration и test method заменены в
active parent spec и всех четырёх OpenSpec artifacts. Старые rejection/review
records не редактировались; replacement не утверждает их завершёнными и не
разрешает повторять механизмы. Никакого safe-log production/test/OS изменения
ещё нет. Pure-policy examples — literal data, не substituted syscall result.

Candidate фиксирует:

- private opaque owner с normal non-creating r+b acquisition и real fstat
  retained handle; one policy для type/UID/access-mode/device/inode;
- retained-handle count и locked seek-to-end/one-write/flush/unlock append,
  exact existing JSON/correlation/per-owner sequence;
- explicit idempotent close, destructor fallback, запрет clone/serialization/
  raw-handle adoption, compatibility facade без второго I/O implementation;
- unchanged production config и fixed exception before DB/private root;
- stable behavioral tests и обязательный independent exact-SHA structural
  proof — без false claim, что stable-file black-box test доказывает fstat.

Feasibility record:
`safe-log-shared-owner-feasibility-review-2026-09-05.md`,
hash `e5eba523ea22514020338678fa206dc817bdc98c8042ba757527af644542feeb`.
Он разрешает drafting направления, не Gate1/RED. Fresh independent Gate1 ещё
требуется. PHP declaration blocks lint PASS с inherited interface stub;
OpenSpec strict и git diff --check PASS — только constructibility checks.
G5-SAFELOG-2 и combined original-command Gate5 по-прежнему не закрыты.
