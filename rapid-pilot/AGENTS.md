# Rapid pilot instructions

This directory is an isolated workspace for the fast functional FMonitor 2.0 pilot.

## Delivery mode

- Work directly in this directory without the SSD + TDD Gate 1–5 workflow from `docs/development-process.md`.
- Executable specifications, red tests, independent test reviews, and independent code reviews are not mandatory for changes contained entirely in this directory.
- Prefer short implementation cycles, live browser checks, and checkpoint commits on the current feature branch.
- Work in one agent session without subagents unless the user explicitly changes that instruction.

## Scope boundary

- This exception applies only to files under `rapid-pilot/`.
- Treat existing specifications, domain code, and earlier pilot work as a foundation that may be read and reused through public interfaces.
- Do not change production behavior outside this directory as part of a rapid-pilot iteration. Changes outside this directory remain governed by the repository-level `AGENTS.md` and its mandatory SSD + TDD workflow.
- Keep primary evidence and sensitive source artifacts outside the repository. Commit only derived, redacted pilot assets and contracts.
- Continue to consume public `../shlz-ui` exports; do not copy or imitate its private component implementations or tokens.

## Product invariants

- Preserve append-only domain history.
- Commands change process state; screens do not edit historical facts directly.
- A rapid-pilot shortcut must not weaken authorization, audit, or document-history invariants in shared production code.
