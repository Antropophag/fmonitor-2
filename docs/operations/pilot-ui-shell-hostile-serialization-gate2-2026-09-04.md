# PILOT-UI-SHELL-001 — hostile serialization Gate 2 correction

- Date: `2026-09-04`
- Production changes: none
- Contract: exact hostile text, no script element, semantically equivalent
  parser serialization accepted

PHP 8.5 `DOMDocument::saveHTML()` over the complete document may encode
Cyrillic text differently from the selected UTF-8 node serialization. The old
assertion searched one byte spelling in the whole document even though the
approved spec explicitly permits semantically equivalent entity serialization.

The corrected test retains exact label textContent
`<script>не имя</script>`, serializes the exact hostile label node, requires the
`&lt;script&gt;...&lt;/script&gt;` boundary there, and independently requires zero
descendant `script` elements. It neither weakens escaping nor reads expected
output from production.

```text
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell
git diff --check: exit 0
```

Fresh independent Gate 3 review is required because test bytes changed.
