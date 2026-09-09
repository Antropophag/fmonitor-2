# Review of an uncommitted candidate

Use this local tool when a coherent candidate is ready for independent review
before the next meaningful commit. Freeze edits during capture. Use a private
location outside every checkout; ignored files (including `.env`) are excluded.
The snapshot is a trusted local artifact, not a transport for untrusted patches.

```sh
python3 tools/delivery/review-source.py capture --repo "$PWD" \
  --output /private/tmp/fmonitor-review-candidate
python3 tools/delivery/review-source.py restore \
  --snapshot /private/tmp/fmonitor-review-candidate \
  --output /private/tmp/fmonitor-review-checkout
```

Record the base commit, snapshot location and patch SHA-256 from `manifest.json`
in the review record. The restored detached worktree contains the captured
working files (including staged/unstaged edits, additions, deletions and modes).
It does not copy ignored runtime dependencies; prepare only dependencies needed
by the agreed checks. The source repository/base must remain available.

The reviewer checks the restored source and the verification plan, then records
one complete findings list. Group review records and coherent corrections at the
next checkpoint under [the delivery process](../../docs/development-process.md).
Before committing, compare the reviewed artifact bytes with the final candidate;
list added review/documentation records separately. Preserve snapshots while
needed to reproduce review; remove disposable restored worktrees with
`git worktree remove` when finished. CI runs on the final committed source.
