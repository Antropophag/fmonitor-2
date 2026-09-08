# Supplemental test review: CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2 v1 oracle

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/object_detail_import_gate3_v02`
- Supplements immutable review:
  `reviews/tests/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001-v1.md`
- Original review SHA-256:
  `f083317ef3cb06118f57f8a459c8c9bd809d9f8c7592a9dad2102c3ee6974f1c`
- Reviewed v1 test SHA-256:
  `22b2fc642c247d5f1d748534524aceed82eac7c727f153cb2527cbf1e82d82a2`
- Verdict remains: `CHANGES_REQUESTED`

This supplement records additional findings against the exact v1 bytes already
reviewed. The verifier was not rerun. The current edited test is outside this
task and is not reviewed here. No Docker volume was removed and no SQL or data
file content was inspected.

## Additional blocking findings

### 5. HIGH — every v1 run leaks the image-declared anonymous database volume

The pinned MariaDB image declares `/var/lib/mysql` as a `VOLUME`. The v1 Docker
create argv neither mounts a test-owned `tmpfs` there nor names an owned volume.
Its cleanup runs `docker rm -f <container>` without `--volumes`/`-v`. Docker
therefore allocates an anonymous persistent volume which container removal does
not remove. The v1 test's claim that a run leaves no owned artifact is false,
and the volume may retain the complete synthetic database and fixture password.

Required correction: prevent persistent allocation with an explicit in-memory
mount for `/var/lib/mysql`, or create an explicitly named, token-bound volume
whose identity is proved and removed with the exact container. Assert the exact
mount identity before fixture creation and prove its absence after cleanup.

### 6. HIGH — artifact-root collision can delete foreign state

The outer `try` refuses a pre-existing exact artifact root, but its unconditional
`finally` still deletes `$artifactRoot/ambient-decoy.txt` and attempts to remove
the root. Thus a collision correctly classified before ownership is established
can delete a foreign file whose name happens to match the test decoy. This is
more serious than the already recorded common-root contract mismatch because it
crosses the ownership boundary on a failure path.

Required correction: track exact successful creation and byte/identity proof of
the decoy separately. Delete it only when that ownership proof exists; never
mutate a root or child rejected as occupied.

### 7. HIGH — sharing the setup-root password defeats the DDL-denied oracle's credential boundary

`ODCI_PASSWORD` is used for the MariaDB root account and both source and target
child users, and that same value is supplied to the importer. Although the v1
argv names the restricted users, an implementation regression that selects the
root username can authenticate with the child-visible password and execute DDL.
The test could then pass the no-DDL clean path while the runtime principal is
privileged. Distinct `CURRENT_USER()` checks made through test-owned connections
do not prove which identity the later importer actually used.

Required correction: use three unrelated credentials, never place the setup
root credential in child environment, and add importer-side independently
observable connection-identity proof or a sensitivity case that fails if the
child substitutes a privileged identity.

### 8. HIGH — fixed capture argv contradicts the approved scenario contract

Specification lines 37–43 require the first capture value for every action
except serial replay. The v1 test passes `ODCI_CAPTURE_REPEAT` to conflict,
metadata rejection, dictionary rejection, and all eight schema-refusal calls.
This changes the exact approved argv and weakens traceability even where the
current rejection happens before a capture is persisted.

Required correction: pass `2026-09-01T10:15:00+03:00` to clean, dry-run,
conflict, both source rejections, and every schema refusal. Reserve
`2026-09-02T11:45:00+03:00` exclusively for the serial replay call, and retain
exact argv assertions.

## Read-only anonymous-volume provenance audit

The metadata archive at
`/Users/antropophag/.local/state/fmonitor2-verification/object-detail-volume-audit-e9ga5dka`
records:

- the image-declared `/var/lib/mysql` volume;
- six unused anonymous volumes created between `2026-09-05T15:31:58Z` and
  `2026-09-05T15:40:21Z`;
- for each, top-level directory names containing `fmonitor2_demo` and one exact
  `fm2_odci_<12-hex-token>` name;
- no retained container event or container-to-volume association.

The six metadata pairs are:

```text
c64bd9a90ae5ff0aa6d9c1d5b8dd5f527066fe6aff146a26a2bac5261b3f259e  fm2_odci_1853f0c3f2e7  2026-09-05T15:31:58Z
6df53b6e40b74f9d9ad370b88e3167ee8c2c942afb8fde8fb45ac439d6826654  fm2_odci_a3b8f345f61b  2026-09-05T15:33:30Z
56c3727c23b9224ea833348cf0b7f39c5b4e0e20e3895330e42d5548f9c5bf30  fm2_odci_1efc7952d8ed  2026-09-05T15:33:44Z
f0042e8fd5a1e5c2b92f4f02bc8169e7a3efdea967cb8db30c8ee646751c54eb  fm2_odci_27e1a7bb3357  2026-09-05T15:33:57Z
c135845f1247513d0fd4cbe8f63203b0dec54d4877ebf54ea1dbc6aec025fb77  fm2_odci_a6bffb188ea0  2026-09-05T15:35:10Z
1575a37ab4c473eb9536aa1ed7ba771b449a57efa74dca47e580fe0da5e27a22  fm2_odci_984965900539  2026-09-05T15:40:21Z
```

This metadata strongly attributes all six volumes to executions of the exact
ODCI v1 harness: the database naming grammar and companion `fmonitor2_demo`
directory match its fixture, and their creation window matches the review
activity. It does not prove which removed container or which reviewer invocation
owned a particular volume. My retained tool output contains the v1 process exit,
duration, and failure category but does not expose its generated token or a
container/volume ID. Therefore I cannot independently corroborate that token
`984965900539` belongs to my `15:40:21Z` run. Timing alone is not an exact
ownership proof.

The metadata is sufficient to diagnose v1 leakage, but insufficient by itself
to authorize destructive cleanup of any named volume. Exact cleanup requires a
retained generated token or container mount/event association tying a particular
run to a particular volume. The separately retained author token may establish
ownership for its exact matching volume when independently verified by the
cleanup owner; this reviewer makes no claim about evidence not present in the
archive or this reviewer's captured output. The older anonymous volume created
on `2026-09-04` has no matching directory-metadata record in the archive and is
entirely outside this review.

## Verdict

`CHANGES_REQUESTED` remains unchanged. The next fresh Gate 3 review must assess
new test bytes and new RED evidence; this supplement does not pre-approve any
current correction.
