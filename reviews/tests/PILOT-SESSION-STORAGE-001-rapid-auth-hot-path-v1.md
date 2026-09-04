# Independent Gate 3 rereview — rapid auth hot-path constructor locator

- Date: `2026-09-04`
- Reviewer: fresh independent agent `/root/rapid_auth_hot_path_gate3`
- Test-correction author: not this reviewer
- Base production revision: `80813dc5c637777381fc077ef13e0e1e203f6b4b`
- Reviewed correction: `2d994a3e40aa252a28a344774ce6727bd161d33d`
- Controlling contract: owner-approved `PILOT-SESSION-STORAGE-001` v10,
  especially sections 7–8
- Verdict: **APPROVED**

## Scope and findings

This correction changes only the static rapid-auth verifier and its diagnostic
evidence. Production `rapid-pilot/LocalAuth.php` is unchanged from the base.
The old verifier searched only for literal `public function __construct()` and
therefore rejected the current approved constructor before reaching any hot-path
assertion. The corrected locator requires the semantic exact signature
`public function __construct(?FMonitor\IdentityAccess\PilotSessionStorage
$storage=null)` (allowing insignificant whitespace), requires the following
public `handle` boundary, and inspects only that constructor body.

No blocking finding remains. The three pre-existing constructor assertions are
preserved verbatim: `CREATE TABLE`, `INSERT INTO`, and `ensureSchema` are all
rejected. The separate assertion rejecting a private `ensureSchema`, and every
subsequent identity-bootstrap, credential, activation, password, invitation,
runtime, upload, error-display, and queue assertion are byte-identical to the
base from that assertion through EOF (independent tail SHA-256 on both versions:
`cbd37b0bdc57720f7bb80290d07ec418816e88142da9d57ad697dfe4fff82895`).

The added self-sensitivity probe inserts literal constructor DDL and requires
the exact `request-time auth constructor contains DDL` rejection before PASS.
I also injected the same DDL independently into the real constructor in a
disposable worktree; the corrected verifier failed with that exact message and
exit `255`. Thus the GREEN is not produced by skipping or widening the
constructor body.

## Independent reproduction

Old verifier and unchanged production at exact `80813dc`:

```text
$ php -l rapid-pilot/verify-auth-hot-path.php
No syntax errors detected in rapid-pilot/verify-auth-hot-path.php
$ php rapid-pilot/verify-auth-hot-path.php
PHP Fatal error: Uncaught RuntimeException: LocalAuth constructor unavailable
exit=255
```

Corrected verifier and the same production at exact `2d994a3`:

```text
$ php -l rapid-pilot/verify-auth-hot-path.php
No syntax errors detected in rapid-pilot/verify-auth-hot-path.php
$ php rapid-pilot/verify-auth-hot-path.php
PASS auth hot path is schema-mutation free
exit=0
$ git diff --check 80813dc...HEAD
(no output)
exit=0
```

Independent production mutation in a disposable worktree:

```text
public function __construct(?FMonitor\IdentityAccess\PilotSessionStorage $storage=null)
{
    CREATE TABLE injected_constructor_ddl(id INT);
```

```text
$ php rapid-pilot/verify-auth-hot-path.php
PHP Fatal error: Uncaught RuntimeException: request-time auth constructor contains DDL
exit=255
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a2e376531a4db9364cc16636388d9bc8285bd54b06d16ddd8b68edd6f0818496  reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v1.md
1abbf879022d43d2e85bc4bfcd1ae8845fe46c09c8c7768fb9e8c4f0013c354e  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v2.md
746f5167f3d7e1ae51cc140bc75a7cdf470c316aafbcf4cf09a41b52d4302ca1  rapid-pilot/LocalAuth.php
8faecb00965713c85206c189c7726158aefa19a966eef0f4fa35f73fc4403b13  rapid-pilot/verify-auth-hot-path.php
1919f81294dc3d5a535109bc7ab12920b65ae07b0e31f265d93af77bfb0a67da  docs/operations/rapid-auth-hot-path-constructor-locator-red-correction-2026-09-04.md
```

Gate 3 is approved for these exact bytes. This approves only the verifier
locator correction; it authorizes no production behavior change and does not
alter the status of the broader session-storage delivery slices.
