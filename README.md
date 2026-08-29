# FMonitor 2.0

Standalone development repository for the new installation-management process. The legacy production application remains in `../fmonitor`; the corporate design system remains in `../shlz-ui`.

## Current state

- Product truth: `PRODUCT.md` and `CONTEXT.md`.
- Pilot contract: `docs/fmonitor-2-pilot-spec.md`.
- Process/data contract: `docs/fmonitor-2-pilot-data-model.md`.
- Executable demo: `app/demo/`.
- Required engineering workflow: `docs/development-process.md`.

The copied demo is a migration baseline, not evidence that its existing behavior has passed the new SSD + TDD gates. New behavior and corrections must enter through the required workflow; review records are created only by real independent reviews and are never backfilled.

## Run the production pilot locally

With the saved MariaDB service available on `127.0.0.1:23306` and the sibling
`../shlz-ui` checkout present, run from this repository:

```bash
php bin/fmonitor2-pilot-demo.php
```

Open the printed URL. Choose object 4512, installer 1042 and the preselected
engineer 73; prepare and download both documents, register number `12-Р`, then
open the work with actual start date `2026-08-29`. Stop with Ctrl+C. Use
`status`, `reset`, or `cleanup` as the single command argument when needed.

## Run the legacy migration demo

The saved MariaDB container is exposed on `127.0.0.1:23306`. From `~/code`, serve both this repository and sibling `shlz-ui`:

```bash
php -S 127.0.0.1:8092 -t /home/antropophag/code
```

Then open:

```text
http://127.0.0.1:8092/fmonitor-2/app/demo/
```

The demo uses its own `fmonitor2_demo` database. It does not write to the legacy production application.
