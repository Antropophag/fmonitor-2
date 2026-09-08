# Pilot UI-shell v7 blank EOF hygiene note

- Date: `2026-09-04`
- Historical commit: `c5f645fa1813185dca2bd809dd0cc4e97478bb30`
- Historical path:
  `docs/operations/pilot-ui-shell-consolidated-browser-lineage-correction-v7-2026-09-04.md`
- Scope: append-only evidence hygiene

The immutable v7 evidence file ends with an additional blank line. Its exact
SHA-256 is:

```text
564170761c2587562c913d3151c56e8510d61fd288de4e3b0055849821732fcc  docs/operations/pilot-ui-shell-consolidated-browser-lineage-correction-v7-2026-09-04.md
```

Consequently, a diff-check range that introduces that historical file reports:

```text
git diff --check c5f645f^..6bf97f7
docs/operations/pilot-ui-shell-consolidated-browser-lineage-correction-v7-2026-09-04.md:124: new blank line at EOF.
exit 2
```

Append-only policy forbids rewriting the historical record for whitespace
hygiene. The blank EOF does not alter product, test or verification behavior.

The new production range and current worktree were checked independently:

```text
git diff --check 62db85d..6bf97f7
exit 0
git diff --check
exit 0
```

This note preserves the exact historical bytes while making the range-check
exception explicit. New evidence commits must remain diff-clean.
