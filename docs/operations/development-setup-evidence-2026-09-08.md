# Issue23 — clean development setup evidence

Implementation source: `f4ab8bd77b96c67769574d27f7103497b89cc40f`, based on
main `321fde662d26d467c16030e1c82687f8ce56b63d` (merge PR43).
Independent code review: [APPROVED](../../reviews/code/DEV-SETUP-001-review-v2.md).

## Clean contour

Fresh `git clone --no-hardlinks` into `/tmp/fmonitor23-clean/fmonitor-2`, with
no vendor or sibling shlz-ui. Host: macOS arm64; PHP8.5.10, Node22.22.0,
npm10.9.4, Python3.12.11; Docker Desktop. Runtime prerequisites were selected
through an isolated PATH, without replacing the system Node/Python. This verifies
an empty dependency checkout, not installation of an OS or Docker on a blank machine.

Public commands:

```sh
make setup
make setup
make test CATEGORY=unit
make test
```

Both setup invocations: `SETUP_OK`, exit0. TCPDF source
`fbbaf14cfae8fe646f154f7c530d15ec25764040` (6.11.4); shlz-ui source
`9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b`; npm dependencies come from its lock.
All 7,916 regular dependency/user-sentinel files (excluding Git internals) retained
identical bytes and modes across repeat setup: aggregate SHA256
`6763119cea55569a37c5e774a737bc700a47814d43d64a299ca11a19cc3403ef`.
`git status --porcelain` remained empty after preparation.

Unit category: 77 tests, 0 failures, 5.866 seconds.
Full `make test`: running; no full readiness claim yet.
Linux Actions: pending PR publication/run.

## Focused checks and discoveries

- Setup acceptance:7/7; existing verification inventory:15/15; CI contracts:9/9.
- Architecture, shell syntax, generated-definition parity and diff-check PASS.
- Failed first clean install exposed macOS system unzip inability to read Unicode
  ZIP member names in shlz-ui design archives. Final destination stayed absent.
  Scoped Python ZIP reader fixes generation without editing shlz-ui or OS tools.
- Malformed-manifest RED proved pre-validation evaluation could create a sentinel.
  Both CI and local setup now use the same validating exporter; negative case GREEN.
- Browser fixture now uses lock-pinned Playwright Chromium. No business assertions
  changed; system Chrome installation/channel is no longer required.
- Frozen inventory digest retained through explicit addition of the new verifier.

## Scope and preservation

Test DB is separate disposable Compose `fmonitor2-test` on port23306. Existing
manual pilot `fmonitor2-manual` on8092 and its volumes were not stopped or migrated.
Setup does not initialize .env, import data or reset a database. Full test owns the
existing disposable reset/migration lifecycle. #42 Bitrix, production runtime and
backup/recovery delivery remain separate work; #27 is addressed for install/start.

Raw local logs: `/tmp/fmonitor23-clean-setup2.log`, `/tmp/fmonitor23-repeat.log`,
`/tmp/fmonitor23-unit.log`, `/tmp/fmonitor23-full.log`. They are local evidence,
not committed primary data. CI links and final full result will be appended.

## Clean-home and browser follow-up

The first /tmp full run failed the inherited OS-account-home filesystem guard and
was stopped. A new empty clone was prepared under
`/Users/antropophag/.local/state/fmonitor23-clean/fmonitor-2`. Setup now rejects
outside-home checkouts early, using POSIX account data, without weakening guards.
Independent setup acceptance now8/8; code/test reviewv3 APPROVED1225c397.
Fresh setup (d5b3b67a) and repeat (1225c397) succeeded. The same7,916-file byte/mode
hash above was preserved. Unit77/0 in5.838s. The full run was restarted for the
browser-mode correction below; no full PASS is claimed from the interrupted run.

First Linux Actions34224764309 exposed a PDF popup difference after removal of
system Chrome. A DB-free independent probe confirmed default headless-shell opens
an empty popup URL, while `channel: 'chromium'` opens the expected inline PDF URL.
Both report pinned Chromium151.0.7922.34. The helper now selects the full bundled
Chromium new-headless mode; all PDF/browser assertions remain unchanged.
[Playwright mode documentation](https://playwright.dev/docs/browsers#chromium-new-headless-mode).

README startup smoke on sourcef4ab8bd: isolated Compose projectfmonitor23-start,
port18092, fresh volumes, image revision label exact. HTTP login200 and headless
Playwright login reached `/pilot/objects` with heading «Объекты монтажа». Owned
containers/volumes and private random-password files were removed afterward;
existing manual stand stayed healthy and source checkout remained clean. The
browser driver for this ancillary smoke was host Node26.8.1; pinned Node22.22.0
is used for the setup and full-suite acceptance runs. With custom Compose ports,
`make up` still prints its default8092 URL; the actual smoke URL was18092.
