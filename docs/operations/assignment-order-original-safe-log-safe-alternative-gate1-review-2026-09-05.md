# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 safe verification alternative — Gate 1 review

- Review date: `2026-09-05`
- Reviewer: independently tasked technical reviewer
  `/root/importer_authority_review`; reviewer authored none of the exact
  candidate specification/OpenSpec bytes, tests or production code
- Reviewed repository HEAD: `82d283c9dc5714ec3ecd98d197e3acfc4a433daa`
- Scope: revised non-native safe-log retained-descriptor verification only
- Verdict: **CHANGES_REQUESTED**

## Review result

The revised design direction is legitimate defensive verification and conforms
to the inherited owner decision. A verification-only observer after the final
validated pathname observation and immediately before the real open can change
one task-owned inode from `0600` to `0640` with ordinary owner `chmod`. The same
production logger owner can then observe identical device/inode plus real
descriptor mode `0640`, close it and return the existing fixed redacted factory
error. Production can use the same composition with an inert observer and no
selector. No native interposition, interception, loader injection, privilege
change, external target or timing race is necessary.

The candidate preserves all three normative descriptor assertions: regular
file, current effective UID and exact `0600`. Mode is the safely mutable RED
axis; it does not authorize a mode-only implementation. It also preserves the
existing class/message/code/previous failure shape and the requirement to fail
before database/private-storage access or diagnostic write.

Gate 1 cannot approve the exact current bytes because the executable declaration
is not syntactically constructible and two acceptance boundaries are not exact.

## Blocking findings

### 1. The concrete verification factory declaration is invalid PHP

`specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` declares a `final class` whose
concrete static `create` method ends with `;`. A non-abstract concrete method
requires a body. This repeats the constructibility issue previously corrected
for the object-detail verification API.

Replace the semicolon with a syntax-valid placeholder body such as:

```php
    ): AssignmentOrderOriginalApplication {
        /* same production composition with supplied verification observer */
    }
```

Keep the declaration under the already governing
`FMonitor2\AssignmentOrderOriginal` namespace, or state that namespace directly
beside the snippet if the extracted block is intended to parse independently.
Parse the exact fenced PHP candidate before rereview.

### 2. The metadata expectation is impossible under ordinary `chmod`

The candidate requires metadata to differ “only” by the deliberate mode
transition. Successful `chmod(0600, 0640)` also updates inode change time
(`ctime`) even though bytes, identity and content timestamps remain otherwise
stable. A correct fixture can therefore fail an exact whole-metadata comparison.

Define the observations field by field. Before observer return, require the same
device/inode, regular type, UID, GID, size, link count and bytes; exact mode
`0640`; unchanged `mtime`; and permit `ctime` to advance or remain numerically
equal where filesystem timestamp resolution coalesces the operations. Do not
assert byte-identical whole `stat` output. Cleanup's restoring `chmod(0600)` may
change `ctime` again and must be treated as an enumerated cleanup effect.

The real retained-descriptor `fstat` must independently confirm same device/
inode and actual `0640`; it must not reuse the observer's pathname assertion.

### 3. Parent deadlines are bounded but not numerically specified

“Monotonic and bounded with terminate/reap” does not fix the acceptance result.
A test author could choose an effectively unbounded timeout or an immediate one,
and reviewers could not distinguish compliant cleanup from environment failure.

Specify exact finite durations and escalation order before RED: maximum child
completion deadline, graceful termination/reap allowance, forced-termination
allowance if supported, and the exact setup-failure outcome when any deadline
expires. Every wait must use a monotonic clock. Cleanup must still restore the
owned file to `0600` when the child is terminated, after revalidating its exact
identity.

## Required exact clarifications for Gate 1 rereview

The following points are otherwise constructible but should be recorded
explicitly with the corrections:

- Use a valid private root for the unchanged control. For the mismatch call use
  a separately enumerated absent child path beneath a revalidated task-owned
  parent and require it to remain absent. Because the phase must occur before
  private-root validation/access, premature validation of that absent path
  prevents the phase and cannot masquerade as the expected descriptor failure.
  Phase count, fixed exception and continued absence together provide a safe
  no-root-touch sentinel without interception.
- State how zero database calls are observed on the supplied task-owned mysqli
  connection, using an independent server-side statement counter/audit or an
  already approved exact database-call observer. Merely receiving the same
  fixed factory exception is insufficient because an early DB failure could be
  translated identically. Setup must prove the observation facility before the
  mismatch call.
- The live-child closure method is viable. Snapshot integer-keyed
  `get_resources('stream')` immediately before and after the call and require
  successful `fstat` for every listed stream. STDIO/socket/pipe streams are not
  silently exempt. Fail on an unstatable entry, duplicate key, pre-existing
  fixture identity, unexplained new post-call resource, or any post-call stream
  matching the fixture device/inode. Perform this proof before process exit.
- Define accounting for resources legitimately present in both snapshots and
  forbid the factory from closing the caller-owned mysqli or baseline streams.
  Resource-ID reuse must be evaluated with the associated `(type,device,inode)`
  tuple rather than ID alone.
- Observer `Throwable` remains a separate control: it occurs before open, maps
  to the fixed factory error, emits one phase and leaves database/root/file
  untouched. It does not prove descriptor closure and must not replace the
  `0640` mismatch.
- Keep cleanup enumerated and identity-revalidated. No recursive deletion,
  wildcard targeting, native loading, privilege manipulation or shared path is
  permitted.

These are technical test-contract corrections. They do not alter the owner-
approved safe-log policy or require new owner approval. Fresh exact-hash Gate 1
rereview is required after coherent executable/OpenSpec amendment; RED remains
closed meanwhile.

## Exact reviewed SHA-256

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
f879c3a7e9ddb199dc62234d3d35fa2f8470f360b54825dab5f0d33113076062  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
c1521e7708ca7134b101e78e608bd868834785469194ed295778b5a110b960c5  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f33c4626d29d74d909edb71abd477696c94be822ee6c0a559713b2ea0d4cadee  openspec/changes/replace-pilot-registration-with-original-upload/design.md
f000fcc8cfc282a49ebcbc8e9409cfff23307b4a8863cc030016049466c32d23  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b9627c8b5fcbcea9aeadb33429aae40bbead988164bed138caa73c30e9ec0959  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
fbdddff9498ccdc59b55d8c2c327b621df11ffc046c6d755e90ccc8bab3ae7b3  docs/operations/assignment-order-original-safe-log-descriptor-safe-verification-alternative-2026-09-05.md
3da95e342c0f49d96cfd2d91aca12ba6eecdba3fb75777333fab04e0b1ae5ec6  docs/operations/assignment-order-original-production-safe-log-owner-resolution-2026-09-05.md
0e430ef58c62076feb1291b6742dd5cd61b1f3adce156dc60bf79055e018b97f  docs/operations/assignment-order-original-production-safe-log-descriptor-integrity-audit-2026-09-05.md
```

## Final verdict

**CHANGES_REQUESTED.** The non-native verification design is safe and viable,
but the exact candidate must fix the invalid concrete method declaration,
account accurately for `chmod`-induced `ctime`, specify numeric monotonic
deadlines, and pin the no-root/no-DB/resource-inventory observations before a
fresh technical Gate 1 rereview. No RED or implementation is authorized by this
record.
