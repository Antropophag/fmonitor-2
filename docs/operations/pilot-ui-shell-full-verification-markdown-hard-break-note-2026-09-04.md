# Pilot UI-shell full-verification Markdown hard-break note

- Date: `2026-09-04`
- Scope: append-only evidence hygiene note
- Related record:
  `docs/operations/pilot-ui-shell-p0-full-verification-2026-09-04-1528.md`

The related immutable full-verification record contains intentional Markdown
hard-break trailing spaces on lines 3 and 4:

```text
Candidate: `eedc855ebd76db8564ef9c86c9f166257a4a4cf6`<two spaces>
Started: `2026-09-04T15:28:54+03:00`<two spaces>
```

Its SHA-256 is:

```text
8c16111650bddd92740e7af5aca2cd3c98b647f6b2edb7d06ccf500b4b41bce4  docs/operations/pilot-ui-shell-p0-full-verification-2026-09-04-1528.md
```

Append-only policy forbids rewriting that historical evidence merely to remove
Markdown hard-break whitespace. The whitespace is not production/test behavior
and does not change the recorded command result.

At current evidence HEAD `b697665b19e783edecc76342bd7292b8ac40ca58`, before
adding this note:

```text
git status --short --branch
clean and synchronized
git diff --check
exit 0
git diff --quiet 5574828..b697665 -- app/PilotHttp
exit 0
```

New working-tree and staged diffs introduced by this note must remain clean;
the historical record remains byte-for-byte unchanged.
