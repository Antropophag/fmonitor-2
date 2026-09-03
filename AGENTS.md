# FMonitor 2.0 constitution

- Read `PRODUCT.md` and `CONTEXT.md` before product work. For pilot behavior also read `docs/fmonitor-2-pilot-spec.md` and `docs/fmonitor-2-pilot-data-model.md`.
- Every product behavior follows every gate in `docs/development-process.md`: approved executable spec, demonstrated RED, independent test review, minimal GREEN, independent code review. Reviewers are separately tasked agents and never approve their own work.
- New migration slices use the lifecycle under `openspec/`; OpenSpec does not replace executable specs, RED evidence, or review records.
- Preserve append-only history. State changes belong to one explicit public application seam; screens, HTTP, imports, and cron do not own domain facts.
- `rapid-pilot/` is a behavioral oracle and temporary adapter, not a destination for new domain logic. Follow its local boundary instructions.
- `../fmonitor` is read-only evidence. Consume only public exports from `../shlz-ui`. Keep primary evidence and secrets outside this repository.
- Run `make architecture-check` while changing boundaries and `make verify` before declaring integration complete.
- При автономной задаче непрерывно продвигай работу до её проверяемого конечного результата: после промежуточных отчётов, gates, коммитов и известных blockers сразу переходи к следующему безопасному действию. Завершай ход только по явной просьбе владельца либо при недоступности электричества или сети.

## Navigation

- Product truth: `PRODUCT.md`, `CONTEXT.md`
- Pilot contracts: `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`
- Delivery gates: `docs/development-process.md`
- Operations state: `docs/operations/`
- Architecture policy/baseline: `docs/architecture/`
- OpenSpec lifecycle: `openspec/config.yaml`, `openspec/changes/`, `openspec/specs/`
- Reviews: `reviews/tests/`, `reviews/code/`
