## Context

См. `proposal.md`. Checker уже собирает size inventory отдельно от rule buckets, но `compare()` превращает hotspot delta в blocking errors, а общий `--write-baseline` сериализует весь scan.

## Goals / Non-Goals

**Goals:** сохранить threshold и сбор hotspot metadata; разделить result envelope и exit semantics; предоставить безопасный узкий size update; не менять meaningful detectors.

**Non-Goals:** новый analyzer/framework, production decomposition, migration architecture debt и изменение verification architecture.

## Decisions

1. `compare()` возвращает два независимых списка: blocking `errors` и size `advisories`. Альтернатива — фильтровать строки в `main()` — отвергнута, потому что оставляет смешанную внутреннюю модель результата.
2. `ok` и exit вычисляются только из `errors`; human output печатает advisories как при PASS, так и при FAIL. Existing JSON consumers сохраняют прежние поля и получают additive `advisories`.
3. Общий baseline rewrite сужается до явной size-only операции: существующий baseline сначала читается, затем заменяется только `hotspots`. Meaningful exceptions изменяются лишь reviewed explicit edits, не side effect size refresh.
4. Owning module — `tools/architecture`; persistence — checked-in JSON baseline; dependencies остаются Python stdlib/PHP lexer. Rapid-pilot adapter отсутствует, а существующие named scans не меняются.

## Risks / Trade-offs

- [Advisories могут игнорироваться] → они всегда видны в human и JSON output и остаются предметом review.
- [CLI compatibility] → JSON change additive; exit для meaningful failures неизменен; docs описывают narrowed baseline command.
- [Fixture interference] → tests запускают поставляемый checker в isolated temporary repository copy.
