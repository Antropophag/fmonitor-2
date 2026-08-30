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

## Visual source

For every rapid-pilot frontend change, inspect the Service Desk source at Windows path `C:\Users\Polly\Downloads\ЩЛЗ - фронт ServiceDesk` (WSL path `/mnt/c/Users/Polly/Downloads/ЩЛЗ - фронт ServiceDesk`) and the public exports in `../shlz-ui` before editing. Preserve the Service Desk shell geometry, typography, density, palette, spacing, radii, and interaction-state language while adapting the information architecture to FMonitor's installation workflow.

### Visual contract gate

Treat `../shlz-ui/packages/styles/dist/shlz.css` and `../shlz-ui/docs/components/` as the component API. Application CSS may compose or lay out `.shlz-*` components, while their base geometry, typography, paint, and interaction states remain owned by `shlz-ui`; select variants through documented modifier classes. FMonitor-owned breadcrumbs use the compact `.fm2-breadcrumb-link` pattern because `shlz-ui` has no Breadcrumb contract. Golos Text is self-hosted from the pinned `@fontsource/golos-text` assets under `rapid-pilot/fonts/`.

After every rapid-pilot frontend change, run `php rapid-pilot/verify-visual-contract.php`. The change is complete only when this gate, the relevant syntax checks, `git diff --check`, and the Impeccable detector all pass.

### Focus-state contract

- Every keyboard-focusable control must retain a visible WCAG 2.2 AA focus indicator. Rapid-pilot focus chrome is neutral/dark (`--fm2-focus-ring`, or white on the dark navigation); never use semantic/product blue for an outline, focused border, fill, or halo.
- This contract applies to native inputs, textareas, selects, buttons, links, summaries and custom tabindex controls, plus all `shlz-ui` interactive families. Semantic blue text, status and normal primary-button paint remain valid when they are not focus chrome.
- A focused field wrapper and an expanded `shlz-ui` Select must keep a white surface, a neutral border and a neutral halo. Do not restore browser-blue or inherited `shlz-ui` blue focus/expanded paint.
- After any frontend change, the named focus verifier `php rapid-pilot/verify-focus-contract.php` is mandatory; update its interactive-family inventory when adopting a new public `shlz-ui` control.

## Product invariants

- Preserve append-only domain history.
- Commands change process state; screens do not edit historical facts directly.
- A rapid-pilot shortcut must not weaken authorization, audit, or document-history invariants in shared production code.
