# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v43 worker FD ownership — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_worker_fd_gate1`
- Reviewed commit: `ce00e070e8d5c9dfc778210afedc1cb789a8a350`
- Triggering gap: `docs/operations/assignment-order-original-worker-fd-validation-gate1-gap-2026-09-05.md`
- Approved specification base: v42 at `21745d4658c3af3c562f2f10ec33e41abe2f54c6`
- Scope: worker FD validation, ownership and lifecycle amendment plus coherence
  with the complete current executable specification and OpenSpec package; no
  test or production implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests, production implementation, prior reviews or historical evidence. This
append-only review is the only authored artifact.

## Independent review

The v43 amendment closes the recorded Gate 1 gap without changing product
behavior. The bootstrap accepts four PHP `int` descriptor values and restricts
each to `3..65535`; stdin, stdout, stderr, negative and overflow/out-of-range
values are therefore invalid. The values must be pairwise distinct before use.
An open succeeds only through one `php://fd/<n>` wrapper in `r+` mode, so the
contract neither relies on nor invents one-way OS pipe access modes.

The parent creates four separate `AF_UNIX,SOCK_STREAM` socketpairs and passes
one child endpoint from each for command-read, barrier-read, barrier-write and
result-write. The worker verifies socket type through `fstat` and requires the
four `(dev,ino)` identities to be pairwise distinct. Consequently a repeated
integer, `dup`/alias of one underlying endpoint, FIFO, regular file, directory,
device or closed descriptor fails closed. Integer distinctness and kernel
identity distinctness are separate checks, so aliasing cannot evade the
contract merely by using a different descriptor number.

Unix stream sockets are full duplex. The four channel names therefore define
logical directions: the worker consumes only command and barrier-in, and emits
only barrier-out and result. It never exercises the opposite direction of any
endpoint. This is coherent with the existing exact command, barrier and result
framing contracts while keeping all four channel identities separate.

All four wrappers must be opened, type/identity-validated and made blocking
before password-file content, command input, database, private storage,
safe-log or barrier access. Partial setup failure attempts closure of every
wrapper already opened exactly once and maps to the existing controlled
configuration outcome: exit `70`, exactly
`ASSIGNMENT_ORDER_ORIGINAL_WORKER_FAILED\n` on stderr once, no stdout, no
result bytes and no barrier bytes, with command input unread. A close failure
does not create an alternate diagnostic or permit secret/resource access.

Ownership is complete and non-overlapping at the declared seam. The parent
owns all peer endpoints and closes them in `finally`; the worker owns the four
child wrappers and closes each exactly once before exit. Separate socketpairs,
resource-backed `proc_open` descriptor inheritance, `php://fd` `r+` wrapping,
`fstat` mode/device/inode inspection and `stream_set_blocking` are available in
the supported PHP/process model. The contract is thus constructible without
FFI, `/proc`, platform-specific raw-descriptor syscalls, pipe direction
assumptions, environment selectors or shared mutable globals.

The executable specification, OpenSpec delta scenario and design state the
same range, endpoint family/type, two-level distinctness, logical-direction,
validation-order and close-ownership rules. Task `1.28` remains open at the
reviewed commit, and task `4.1` remains open; no stale Gate 2 approval or GREEN
claim is introduced. The retained transport RED still uses `proc_open` pipes
and therefore must be amended to the now-approved socket contract before its
fresh Gate 3 review. That expected Gate 2 work is not a Gate 1 ambiguity.

No new role, capability, workflow, HTTP surface, original/composition/opening
fact, schema/runtime DDL, production selector or mutation seam is introduced.
No blocking ambiguity or portability/security contradiction was found.

## Verification

```text
$ git rev-parse HEAD
ce00e070e8d5c9dfc778210afedc1cb789a8a350

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff ce00e070^ ce00e070 --check
(no output; exit 0)

$ git diff --name-only ce00e070^ ce00e070
openspec/changes/replace-pilot-registration-with-original-upload/design.md
openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

The reviewed commit changes specification/OpenSpec artifacts only.

## Verdict and next boundary

Gate 1 v43 is **APPROVED** at exact commit
`ce00e070e8d5c9dfc778210afedc1cb789a8a350`. The integrator may close task
`1.28` and amend the worker Gate 2 RED to exercise the approved Unix-socket FD
contract. Any changed test still requires a fresh independently tasked Gate 3
review before production implementation.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
90cd3472fbab143e9c3dc6d9ac7110468fae4cffad7a7e30889de35b9b4273b2  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
804fb95f272fe1048e76b2cdb71dc48f4b71de8979ceb4d6f42ab6c4fc5607ba  openspec/changes/replace-pilot-registration-with-original-upload/design.md
243798baa57ce8e9cf247625329313e00fb7d5e0ec697a76e63cf30a1daac1f9  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
9137f12594df40c9402e5633a5d1dcf947c2ed8a5b536f03b5d3d73777583a33  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
ba26208588db3cc5e3a6aafb6f1e6f3ddec5c9fdf6d4e78a1113e189e33399b8  docs/operations/assignment-order-original-worker-fd-validation-gate1-gap-2026-09-05.md
```

This review record intentionally omits its own circular hash.
