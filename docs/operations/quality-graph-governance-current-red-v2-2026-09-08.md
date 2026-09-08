```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root/bootstrap_contract","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"b5fca7d4df56404a4ac4eba802d497066a42b6c8","tests":[{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"в текущей линии отсутствует публичный seam check-evidence.php, поэтому первая исправная изолированная Git fixture не получает обязательную классификацию missing_receipt","recordedAt":"2026-09-08T02:55:47+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 — Gate 2 RED v2 текущей линии

Exact lineage base `b5fca7d4df56404a4ac4eba802d497066a42b6c8` — более поздний
documentation checkpoint, следующий после установленного и прошедшего
`VERIFY_OK` source `4990cf1afd90813c60f155297f427eb822ae78e9`. Утверждённая
спецификация v0.6 не изменена. Полный bytewise-ordered test set состоит из семи
файлов: пяти Quality Graph tests и двух helpers текущей CI portability. Четыре
QG-файла взяты exact из final ref; governance test добавляет актуальную матрицу
evidence envelope.

Envelope fixture допускает только следующие актуальные именованные operations
records дополнительно к receipt, exact code-review record и OpenSpec task status,
уже заданным v0.6:

- `quality-graph-governance-final-verification-2026-09-08.md`;
- `quality-graph-representative-pr-phase-a-2026-09-08.md`;
- `quality-graph-publisher-phase-b-2026-09-08.md`.

Отдельные cases требуют deterministic `commit_mismatch` для несвязанного
operations record и post-review изменений implementation, test и executable spec.
Это behavioral lineage assertions, а не зеркальная проверка hashes импортируемых
файлов.

Пять дополнительных representative negatives клонируют уже валидный lineage и
вносят по одному well-formed изменению: совпадение test reviewer с test author и
code reviewer с implementation author требуют `non_independent_review`;
нестрогая Gate ancestry требует `gate_order`; две superseding receipts с одним
предком требуют `invalid_history`; полное согласованное удаление test либо
implementation entry из metadata/receipt при сохранённом Git diff требует
`metadata_mismatch`. Каждый case требует ровно одну целевую ошибку и запрещает
success marker. Комбинационный matrix не вводится.

Ещё три core v0.6 cases фиксируют exact-byte/history/aggregation semantics:

- valid chronological lineage с binary, leading/trailing whitespace и no-final-LF
  bytes одновременно в test и implementation artifact обязан пройти по точным
  Git blob SHA-256;
- byte edit уже committed единственного leaf receipt без нового `supersedes`
  обязан дать `invalid_history`;
- два независимо malformed receipt JSON обязаны дать две `invalid_schema` ошибки
  в bytewise path order и ни одного success marker.

## Фактический RED

Изолированный Git repository успешно инициализируется и фиксирует base. Первый
вызов обязательной публичной команды затем достигает целевого отсутствующего seam:

```text
$ php tests/Verification/quality_graph_governance_001_test.php
Could not open input file: .../tools/delivery/check-evidence.php
TestFailure: RED_ASSERTION: isolated test seam must classify the absent opt-in receipt root
Expected: 1
Actual: 0
exit=255
```

Это требуемый missing-public-command RED, а не setup failure PHP/Git fixture.
Последующие valid-lineage и evidence-envelope cases до него не исполняются и пока
не заявлены как динамически продемонстрированный RED.

## Private sensitivity против exact final-ref checker

Для проверки constructibility без импорта production seam в current line exact
`tools/delivery/check-evidence.php` из ref
`f07548135fe930e7a8fb9bb97271c9f05a8ebfc1` помещён только в приватный
изолированный Git checkout. Текущий test прошёл все новые representative cases:

- оба reviewer/author совпадения дали exact `non_independent_review`;
- нарушенная строгая ancestry дала exact `gate_order`;
- две receipt leaves дали exact `invalid_history`;
- скрытый Git-derived test path и скрытый implementation path дали по одному
  exact `metadata_mismatch`.

Первое падение всего private run произошло позже на current именованном envelope:
final-ref checker правильно отверг новый `...final-verification-2026-09-08.md`
как `commit_mismatch`, поскольку он знает только старый exact allowlist. Exit255;
raw log SHA-256
`c7cc20f34ace0d29a81dbfe069cc157bc10dcf79155d38088386b57447337337`.
Таким образом core negative fixtures доказали целевые категории до отдельного
current-envelope RED. Приватный checker не добавлялся в repository и не является
current implementation.

После добавления exact-byte/history/aggregation cases новый private run первым
упал на exact-byte positive: final-ref checker вернул `metadata_mismatch` с
`receipt test set differs`. Это доказывает известную trim/newline hash ошибку;
in-place leaf и aggregation cases остаются downstream за этим первым RED и пока
не заявлены как динамически выполненные. Private raw log SHA-256:
`afb16bf5af634fd286a2bd17f0614a5c888669aaca85343425c42f9ebd37031d`.

## Выравнивание setup для текущей CI portability

Прежний `SelectedOriginalFixture` содержал macOS-only absolute private parent.
Focused запуск внутри immutable image
`sha256:2bc0b0803182e0ac522fdfb6a90f60d8d4ed5ac0a90cdbdaf601e77cd78977ac`
в dedicated test network достиг точного portability blocker:

```text
mkdir(): No such file or directory
RuntimeException: Private fixture creation failed.
exit=255
```

Helper теперь создаёт непредсказуемый каталог 0700 непосредственно внутри
`sys_get_temp_dir()` и сохраняет прежние realpath, storage и exact-cleanup checks.
С read-only test/spec mounts тот же Linux focused selection HTTP test проходит.
Host-запуск также проходит с существующим pinned TCPDF vendor из exact
verification checkout.

Construction-control browser helper теперь читает
`FMONITOR_TEST_PLAYWRIGHT_MODULE`, falling back to the sibling `shlz-ui` checkout
относительно `__dirname`; существующий host filter test проходит без изменения.
Fake macOS path, global HOME rewrite и CI alias не вводились.

Hashes приватных evidence:

- целевой Linux path RED v2:
  `d32efdeca7b824b2cb97154c6cbe5cf1609d0a278b21571c4b0ba15147e93ec0`;
- исправленный Linux focused GREEN:
  `f20021d7d255c200e157d4831763076b51c0d8e9071b011268b7c04a5c5db979`;
- более ранний missing-spec mount setup failure, сохранённый и не названный RED:
  `99ad0d58b02496e3b1f514e1ae0eca14a16200c791583cf698a6f835fd6aa338`.

Приватный raw log находится вне repository:
`~/.local/state/fmonitor2/quality-graph-current-20260908/governance-red-v4.raw.log`,
mode0600, SHA-256
`856b056c54b6e28228f0a8db6398e9f721609719409a0075a6f77e0475c09bcb`.
Он не содержит credentials или production data.

Static checks проходят для четырёх PHP tests, Python AST parsing и
`git diff --check`. Основной governance RED command не использует DB, Docker или
network. Portability evidence использует только isolated test DB, immutable
candidate image и host Playwright; live stand/volumes/objects не затрагивались.
Workflow и remote actions не выполнялись. Этот record является только Gate 2
evidence; Gate 3 и implementation остаются pending.
