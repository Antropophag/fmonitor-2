#!/usr/bin/env bash
# Public development/CI setup. Never repairs a pre-existing dependency tree.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
fail() { printf 'SETUP_FAILURE: %s\n' "$*" >&2; exit 1; }
trap 'printf "SETUP_FAILURE: command failed at setup line %s\n" "$LINENO" >&2' ERR
case "${1:-}" in ''|--check) ;; *) fail 'usage: setup.sh [--check]' ;; esac
[[ $# -le 1 ]] || fail 'usage: setup.sh [--check]'
for tool in bash git make php node npm python3 rg docker cc curl tar; do
    command -v "$tool" >/dev/null || fail "required command unavailable: $tool; see docs/development-setup.md"
done
case "$(uname -s)" in Linux|Darwin) ;; *) fail 'supported hosts: Linux and macOS' ;; esac
# This is repository-owned configuration, never a user-supplied environment file.
source tools/delivery/dependencies.env
php -r 'if (PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION !== $argv[1]) exit(1); foreach(explode(",", $argv[2]) as $e) if (!extension_loaded($e)) {fwrite(STDERR,"missing PHP extension: $e\n"); exit(1);}' "$PHP_VERSION" "$PHP_EXTENSIONS" \
    || fail "PHP $PHP_VERSION with $PHP_EXTENSIONS required"
[[ "$(node --version)" == "v$NODE_VERSION" ]] || fail "Node $NODE_VERSION required; found $(node --version)"
[[ "$(python3 --version)" == "Python $PYTHON_VERSION" ]] || fail "Python $PYTHON_VERSION required; found $(python3 --version)"
npm --version >/dev/null || fail 'npm is unavailable'
docker info >/dev/null 2>&1 || fail 'Docker daemon unavailable; start Docker'
docker compose version >/dev/null || fail 'Docker Compose v2 required'
python3 tools/delivery/render-dependencies.py --check
read -r TCPDF_VERSION TCPDF_REVISION <<< "$(python3 tools/delivery/render-dependencies.py --tcpdf)"
source tools/delivery/setup-dependencies.sh
# Validate all existing destinations before the first installation/build.
check_shlz ../shlz-ui
check_tcpdf
if [[ "${1:-}" == --check ]]; then
    printf 'SETUP_CHECK_OK (missing dependencies, if any, will be installed by make setup)\n'
    exit 0
fi
mkdir -p .local
lock=.local/development-setup.lock
mkdir "$lock" 2>/dev/null || fail "setup already running or interrupted: $lock; remove the empty lock only after confirming no setup is running"
work=''
cleanup() { [[ -z "$work" ]] || rm -rf -- "$work"; rmdir "$lock"; }
trap cleanup EXIT
if [[ ! -e ../shlz-ui ]]; then
    work=$(mktemp -d ../.fmonitor-shlz-setup.XXXXXXXX)
    git clone https://github.com/Antropophag/shlz-ui.git "$work/shlz-ui"
    git -C "$work/shlz-ui" checkout --detach "$SHLZ_UI_REVISION"
    npm --prefix "$work/shlz-ui" ci --no-audit --no-fund
    npm --prefix "$work/shlz-ui" run generate
    npm --prefix "$work/shlz-ui" run build:packages
    check_shlz "$work/shlz-ui"
    publish_dependency "$work/shlz-ui" ../shlz-ui
    rm -rf -- "$work"; work=''
fi
if [[ ! -e vendor/tecnickcom/tcpdf ]]; then
    mkdir -p vendor/tecnickcom
    work=$(mktemp -d vendor/tecnickcom/.tcpdf-setup.XXXXXXXX)
    git clone --branch "$TCPDF_VERSION" --depth 1 https://github.com/tecnickcom/TCPDF.git "$work/tcpdf"
    check_git "$work/tcpdf" "$TCPDF_REVISION"
    publish_dependency "$work/tcpdf" vendor/tecnickcom/tcpdf
    rm -rf -- "$work"; work=''
fi
if [[ ! -e vendor/autoload.php ]]; then
    # Exclusive creation preserves any pre-existing autoloader, including a racing writer.
    (set -o noclobber; cat rapid-pilot/tcpdf-autoload.php > vendor/autoload.php) \
        || fail 'vendor/autoload.php appeared during setup; preserved, rerun setup'
fi
check_tcpdf
# Install browser binaries after preserving/checking source trees. Linux may need sudo.
if [[ "$(uname -s)" == Linux ]]; then
    node ../shlz-ui/node_modules/playwright/cli.js install --with-deps chromium chrome
else
    node ../shlz-ui/node_modules/playwright/cli.js install chromium chrome
fi
make test-tools
docker build --label "org.opencontainers.image.revision=$(git rev-parse HEAD)" -t fmonitor2-pilot:latest .
printf 'SETUP_OK source=%s php=%s node=%s python=%s tcpdf=%s shlz-ui=%s\n' \
    "$(git rev-parse HEAD)" "$(php -r 'echo PHP_VERSION;')" "$NODE_VERSION" "$PYTHON_VERSION" "$TCPDF_REVISION" "$SHLZ_UI_REVISION"
