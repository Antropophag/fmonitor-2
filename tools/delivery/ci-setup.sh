#!/usr/bin/env bash
# Dedicated clean CI checkout only; never replaces an existing sibling checkout.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
[[ "${GITHUB_ACTIONS:-}" == true ]] || { echo 'SETUP_FAILURE: ci-setup requires a dedicated GitHub runner' >&2; exit 1; }
sudo apt-get update
sudo apt-get install -y --no-install-recommends ripgrep
command -v rg >/dev/null || { echo 'SETUP_FAILURE: required command unavailable: rg' >&2; exit 1; }
php -r 'if (PHP_VERSION_ID < 80500 || PHP_VERSION_ID >= 80600) exit(1); foreach (["mysqli", "pcntl", "dom", "mbstring"] as $extension) if (!extension_loaded($extension)) exit(1);'
[[ "$(node --version)" == v22.22.0 ]]
docker info >/dev/null
docker compose version
make test-tools
[[ ! -e vendor/tecnickcom/tcpdf && ! -e ../shlz-ui ]] || { echo 'SETUP_FAILURE: dependency destinations must be absent' >&2; exit 1; }
mkdir -p vendor/tecnickcom
git clone --branch 6.11.4 --depth 1 https://github.com/tecnickcom/TCPDF.git vendor/tecnickcom/tcpdf
[[ "$(git -C vendor/tecnickcom/tcpdf rev-parse HEAD)" == fbbaf14cfae8fe646f154f7c530d15ec25764040 ]]
cp rapid-pilot/tcpdf-autoload.php vendor/autoload.php
php -r 'require "vendor/autoload.php"; exit(TCPDF_STATIC::getTCPDFVersion() === "6.11.4" ? 0 : 1);'
git clone https://github.com/Antropophag/shlz-ui.git ../shlz-ui
git -C ../shlz-ui checkout --detach 9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b
[[ "$(git -C ../shlz-ui rev-parse HEAD)" == 9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b ]]
npm --prefix ../shlz-ui ci --no-audit --no-fund
npm --prefix ../shlz-ui run generate
npm --prefix ../shlz-ui run build:packages
../shlz-ui/node_modules/.bin/playwright install --with-deps chromium chrome
printf 'FMONITOR_TEST_NODE_BINARY=%s\n' "$(command -v node)" >> "$GITHUB_ENV"
printf 'FMONITOR_TEST_PLAYWRIGHT_MODULE=%s\n' "$(cd ../shlz-ui/node_modules/playwright && pwd)" >> "$GITHUB_ENV"
docker build --label "org.opencontainers.image.revision=$(git rev-parse HEAD)" -t fmonitor2-pilot:latest .
