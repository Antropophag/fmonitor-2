# Production safe-log descriptor race — executable Gate 1 observability gap

- Date: `2026-09-05`
- Recorded by: separately tasked test author `/root/safe_log_red_v55`
- Source finding: `G5-SAFELOG-2` in review `3be68a81a220cf24c001f4f2bdaeda06e305625a`
- Exact affected implementation: `813d224ae4ba99a8d685fcfce48d161b27e7a3e4`
- Status: **BLOCKED BEFORE GATE 2**

The approved public seam is
`ProductionAssignmentOrderOriginalFactory::create(mysqli, config)`. The
approved contract requires security attributes to belong to the descriptor
actually opened for append. The current seam exposes neither the opened
descriptor identity/attributes nor a verification-only coordination point
between the constructor's attribute validation and its immediate pre-open
`lstat`.

The repository's existing deterministic preload helper can pause an `lstat`
before that call returns. That is sufficient to test replacement between the
pre-open `lstat` and `fopen`, which current inode comparison already rejects. It
cannot pause after validation has returned but before the next `lstat`; choosing
timing with sleeps or replacement loops would be probabilistic and is rejected.
The factory also validates twice (`canonical()` and logger construction), so a
pause at the earlier validation is caught by the later validation and does not
exercise the reported interval. Extending interception to a different libc
operation would introduce a new unapproved test coordination seam rather than
use the existing one.

Therefore no honest deterministic executable RED for `G5-SAFELOG-2` can be
written at the approved public seam without a Gate 1 decision defining an
observable verification mechanism (or revising the acceptance proof so opened
descriptor `fstat` invariants are directly observable). No probabilistic test,
production hook, config selector, privilege change, or external target was
added. Production implementation for this finding must not proceed until that
Gate 1 gap is resolved and the resulting test completes Gates 2 and 3.

This blocker does not waive the separately demonstrated parent-component
symlink RED.
