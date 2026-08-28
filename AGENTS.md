# FMonitor 2.0 agent instructions

Read `PRODUCT.md` and `CONTEXT.md` before product work. For a pilot behavior, also read `docs/fmonitor-2-pilot-spec.md` and `docs/fmonitor-2-pilot-data-model.md`.

## Required delivery workflow

All product behavior is developed through the SSD + TDD workflow in `docs/development-process.md`. Treat every gate as mandatory:

1. approved executable specification;
2. failing test derived from that specification;
3. independent test review recorded under `reviews/tests/`;
4. minimal implementation that makes the reviewed test pass;
5. independent code review recorded under `reviews/code/`.

Proceed to the next gate only after the current gate is approved. Work in one vertical behavior slice at a time. Preserve test-review independence: the implementation author cannot approve their own test or code review, and implementation context or assertions cannot be used as the source of expected test values.

Always assign mandatory test and code reviews to separately tasked agents. Use an applicable review skill when one matches the review type; otherwise follow the project review templates and gates directly.

## Boundaries

- `../fmonitor` is the legacy production application and a read-only integration source. Product implementation belongs in this repository.
- `../shlz-ui` is the corporate UI dependency. Consume its public exports; do not copy or locally imitate its components and tokens.
- Primary `.msg`, `.pdf`, database dumps, and other source evidence remain outside this repository. Commit only derived, redacted product contracts needed to implement FMonitor 2.0.
- Preserve append-only domain history. Commands change process state; screens do not edit historical facts directly.
