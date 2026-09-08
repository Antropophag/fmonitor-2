```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/bootstrap_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b6ac9768cb02674ea5a50b0c68382660b3c3e550d21b9bf0df4cc95840b8cefc"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"38949ce9c8cdf2160b9d838472d2d60901a1ec84","recordedAt":"2026-09-08T02:34:15+03:00"}
```

# Независимый Gate 3 review — текущий Quality Graph governance

Вердикт: **CHANGES_REQUESTED** для RED commit
`QUALITY-GRAPH-GOVERNANCE-001` v0.6 от RED commit
`38949ce9c8cdf2160b9d838472d2d60901a1ec84`.

Reviewer `/root/bootstrap_review` не является автором тестов
`/root/bootstrap_contract` и не назначен Gate 5 code reviewer этого slice.

## Проверенная граница

Git-derived `base..RED -- tests/` содержит ровно семь записей из metadata выше в
bytewise порядке: два изменённых portability helper и пять добавленных Quality
Graph tests. Их статусы и SHA-256 совпадают с commit. Исполнимая спецификация не
изменена и имеет SHA-256
`189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859`.

Основной governance test использует только разрешённый test seam `--repo` над
каноническими временными Git repositories. Positive fixture создаёт раздельные
RED, test-review, GREEN/implementation и code-review commits, после чего проверяет
точный terminal success. Representative negative cases требуют стабильные
категории, ровно одну failure строку и отсутствие success для:

- отсутствующего receipt root, unsafe/missing/hash-drift artifact;
- неизвестных metadata fields и несовпадения authoritative metadata/receipt;
- stale spec, duplicate slice и запрещённого post-review drift;
- совпадения каждого reviewer с соответствующим author;
- нестрогой Gate ancestry и двух current supersession leaves;
- согласованно скрытого test либо implementation path вопреки полному Git diff.

Тесты намеревались проверять ключевые fail-closed границы
`non_independent_review`, `gate_order`, `invalid_history`, `metadata_mismatch` и
`commit_mismatch`. Publisher tests отдельно фиксируют exact
minimal workflow, запрет лишних triggers/permissions/checkout, pinned generated
comparison, полный Result provenance и отсутствие missing/extra/duplicate/replayed
artifacts. Runner и toolchain tests проверяют read-only checkout, exact pins и
generated drift.

## RED и portability

Зафиксированный RED возникает после успешной инициализации Git fixture: публичный
`tools/delivery/check-evidence.php` отсутствует, поэтому обязательная
`missing_receipt` классификация не появляется. Exit 255 является intended missing
public seam RED. Downstream cases ещё не выполнены и evidence этого не заявляет.

`SelectedOriginalFixture` теперь создаёт непредсказуемый каталог 0700 внутри
системного temp root, сохраняя realpath, storage и точную очистку.
Construction-control browser helper принимает явный test module path либо
checkout-relative sibling fallback; существующие browser assertions не менялись.
Обе правки принадлежат только тестовому setup и устраняют доказанные Linux CI
path blockers без изменения runtime/domain поведения.

## Блокирующее замечание

Два exact-set negative case не изолировали заявленное правило. Они клонировали
готовый valid lineage, затем изменяли уже добавленные RED, test-review, GREEN и
code-review metadata blobs. По v0.6 commit каждого exact evidence blob выводится
как его unique first-add commit с проверкой hash. У изменённых blobs отсутствовал
соответствующий `--diff-filter=A` commit; checker обязан был отклонить chronology
раньше, чем мог сравнить Git-derived test/implementation inventories.

Следовательно, эти cases могли пройти только при неправильном порядке либо
ослаблении checker и не доказывали защиту от согласованно скрытого path. Требуется
строить свежую хронологическую fixture, где неполный declared set присутствует уже
в исходных RED/GREEN metadata, а дополнительный реальный path входит в Git diff на
том же stage. Исправление должно получить новый append-only RED artifact и новый
Gate 3 review; этот record и старый RED нельзя переписывать.

PHP lint, Python AST parsing и `git diff --check` прошли, но найденный дефект
блокирует APPROVED Gate 3 для commit `38949ce`.
