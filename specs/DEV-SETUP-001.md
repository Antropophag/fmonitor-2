# DEV-SETUP-001

Version 0.1, 2026-09-08. Gate 1 required.

## Простыми словами

Разработчик должен одной командой проверить или подготовить локальную среду на
Linux/macOS. Повторный запуск безопасен для уже готовых зависимостей и пользовательских
данных. Этот slice задаёт только setup/preflight contract; он не меняет приложение,
production runtime, CI или данные пилотного стенда.

## Public seam and actor

Разработчик из корня checkout вызывает:

- `make setup`, эквивалентный `bash tools/delivery/setup.sh`;
- `bash tools/delivery/setup.sh --check`, выполняющий только проверки;
- `make doctor`, эквивалентный `bash tools/delivery/setup.sh --check`.

Другие аргументы отклоняются до установки. Успех имеет exit 0. Любая невозможность
подготовить или проверить среду имеет ненулевой exit и diagnostic в stderr с префиксом
`SETUP_FAILURE:` и конкретным инструментом, версией, путём или недостающим artifact.
OS-доступ достаточен; команды не создают domain facts, audit events или grants.

## Version authority and preflight

`tools/delivery/dependencies.env` — единый shell-readable manifest setup pins:

```text
PHP_VERSION=8.5
NODE_VERSION=22.22.0
PYTHON_VERSION=3.12.11
SHLZ_UI_REVISION=9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b
PHP_EXTENSIONS=mysqli,pcntl,dom,mbstring,curl
```

TCPDF version and immutable source revision are read from the sole
`tecnickcom/tcpdf` package entry in `composer.lock`; the manifest does not duplicate
them. Setup validates the manifest before using it.

Before any clone, checkout, package installation, generation or Docker build, every
entry point validates Linux or macOS and the availability/compatibility of `bash`,
`git`, `make`, `php`, `node`, `npm`, `python3`, `rg`, `docker`, `cc`, `curl`, and
`tar`. PHP must be in the
8.5 series and provide the extensions needed by the repository; Node and Python must
equal their exact pins. Docker daemon and Compose must be usable. An unavailable or
incompatible prerequisite fails immediately with `SETUP_FAILURE`; no dependency
destination or source checkout has been mutated at that point.

## Check-only behavior

`--check` and `make doctor` perform the complete prerequisite and existing-dependency
validation and make no filesystem, Git, npm, package, image or container mutation.
When a dependency destination is absent they report it as ready to install, not as
a doctor failure. When a destination exists they validate it completely. They never
clone, checkout, install, generate, build or repair a dependency.

## Existing dependencies

An existing `../shlz-ui` is reusable only when its tracked source is clean, HEAD is
exactly `SHLZ_UI_REVISION`, and the generated public exports plus the installed
`node_modules/playwright` dependency required by repository tests are present.
Untracked user files do not make the tracked source dirty and must remain unchanged.
Setup never runs `npm ci`, generation, a package build, checkout, reset or clean in a
reused tree. A wrong revision, dirty tracked source or missing required artifact fails
with an actionable `SETUP_FAILURE` and leaves the entire existing tree unchanged.

An existing `vendor/tecnickcom/tcpdf` is reusable only when it matches the version and
source revision from `composer.lock` and the repository autoload entrypoint is usable.
A mismatch or incomplete existing dependency fails and is never overwritten or
repaired in place.

## Fresh dependency publication and repeatability

When a dependency destination is absent, setup clones/installs/builds it in a unique
temporary sibling path. It validates the pinned source identity and all required
artifacts there, then publishes it to the final destination with one atomic rename.
Failure before that rename leaves the final destination absent; a concurrent process
that populated the destination is never overwritten.

After dependencies are valid, setup may perform the repository's repeatable cached
test/pilot Docker builds. A second successful `make setup` revalidates and reuses the
existing dependencies: it does not clone or build `shlz-ui` again. Any sentinel user
file present before the repeat has byte-identical contents afterward.

## Independent acceptance examples

The executable examples use a temporary checkout and trace-only executables in its
`PATH`; no network, live Docker daemon, production system or real sibling checkout is
used.

1. Node reports `v20.0.0` while all other prerequisites satisfy their pins. Setup
   exits nonzero with `SETUP_FAILURE` naming Node/version; the trace contains no Git
   clone, npm install/build/generate or Docker build.
2. A complete clean existing `shlz-ui` reports the pinned HEAD and contains an
   untracked sentinel. Two setup runs both succeed, preserve the tree byte-for-byte,
   and trace no clone, checkout, npm mutation or `shlz-ui` build.
3. Existing `shlz-ui` reports a different HEAD. `--check` fails actionably and leaves
   its files byte-identical, with no repair command.
4. During a fresh setup, the trace npm executable fails while building `shlz-ui` in
   its temporary sibling. Setup reports `SETUP_FAILURE` and `../shlz-ui` remains
   absent.

Gate 1 -> focused intended RED for the missing public script/targets -> independent
Gate 3 -> minimal implementation -> focused GREEN -> independent Gate 5. Catalog,
CI and production deployment are outside this slice.
