## Why

Forensic #20 выявил восемь review-итераций из-за дефектов fixture/oracle, скрытых ранним корректным `INTENDED_RED`: Gate 3 видел доказательство отсутствующего behavior, но не видел, исполнима ли недостигнутая инфраструктурная часть теста. Текущий `harness.py prepare --role reviewer --gate 3` проверяет outcome и identity mapped command, однако отдельного доказательства reachability remainder не требует.

## What Changes

- Для явно applicable intended-RED tests существующий runner/evidence contract получает bounded, deterministic control/probe outcome, который доказывает достижение заявленной fixture/remainder boundary без реализации product behavior.
- Gate-3 preparation допускает такой test candidate только при наличии обоих exact-source evidence: настоящего `INTENDED_RED` и успешного non-destructive reachability control для того же acceptance command.
- `SETUP_FAILURE`, произвольный crash после RED, неверная command/source identity и отсутствующее control evidence блокируют подготовку Gate 3.
- Regression matrix охватывает отсутствующую fixture table/column, неверный helper argument, malformed data-provider/index, invalid CSRF/setup source и broken post-fork DB fixture; sensitivity включает synthetic defective test и realistic fixture класса #20.
- Healthy test сохраняет настоящий RED из-за отсутствующего product behavior и отдельно доказывает достижимость remainder; GREEN product behavior не требуется.
- **Non-goals:** реализация или изменение замороженной #20; новый Gate/test framework; instrumentation всех tests; destructive/product mutations для probe; ослабление независимого Gate 3; #153C/D, T07/#107, LLM analysis, merge/deploy/settings.

## Capabilities

### New Capabilities

- `delivery/intended-red-fixture-reachability`: bounded admission contract для доказательства fixture/remainder reachability перед Gate 3 у applicable intended-RED tests.

### Modified Capabilities

Нет.

## Impact

- Behavior slice: pre-Gate-3 fixture reachability safeguard.
- Actor: root/test author, готовящий test candidate; source oracle — forensic #20 и текущий intended-RED/Gate-3 preparation contract.
- Public seam: существующие `python3 tools/delivery/harness.py run` и `prepare`; retained evidence остаётся во внешнем harness home.
- Ожидаемые изменения ограничены `tools/delivery/`, bounded `tests/Verification/`, новым нормативным tooling contract, OpenSpec/delivery/review records.
- Product/domain state, production implementation, schema migrations, runtime dependencies и новый evidence store не вводятся.
