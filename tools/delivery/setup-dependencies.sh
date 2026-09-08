# Helpers sourced by setup.sh; failures preserve existing dependency directories.
check_git() {
    local path="$1" revision="$2"
    [[ ! -L "$path" && -d "$path/.git" ]] || fail "$path must be a regular pinned Git checkout; use a fresh parent directory"
    [[ "$(git -C "$path" rev-parse HEAD)" == "$revision" ]] \
        || fail "$path revision differs from $revision; existing files preserved; use a fresh parent directory"
    [[ -z "$(git -C "$path" status --porcelain --untracked-files=no)" ]] \
        || fail "$path has tracked changes; existing files preserved; use a fresh parent directory"
}
check_shlz() {
    local path="$1" artifact
    [[ -e "$path" || -L "$path" ]] || return 0
    check_git "$path" "$SHLZ_UI_REVISION"
    for artifact in packages/styles/dist/shlz.css packages/behaviors/dist/browser.js \
        packages/behaviors/dist/tabs.js packages/icons/dist/sprite.svg node_modules/playwright/cli.js; do
        [[ -f "$path/$artifact" ]] || fail "$path missing $artifact; existing tree preserved; use a fresh parent directory"
    done
}
check_tcpdf() {
    local path
    for path in vendor vendor/tecnickcom vendor/autoload.php; do
        [[ ! -L "$path" ]] || fail "$path is a symlink; existing paths preserved; use a fresh checkout"
    done
    if [[ -e vendor/tecnickcom/tcpdf || -L vendor/tecnickcom/tcpdf ]]; then
        check_git vendor/tecnickcom/tcpdf "$TCPDF_REVISION"
        [[ -f vendor/tecnickcom/tcpdf/tcpdf.php ]] || fail 'existing TCPDF is incomplete; use a fresh checkout'
        if [[ -e vendor/autoload.php ]]; then
            php -r 'require "vendor/autoload.php"; exit(TCPDF_STATIC::getTCPDFVersion() === $argv[1] ? 0 : 1);' "$TCPDF_VERSION" \
                || fail 'existing vendor/autoload.php does not load the pinned TCPDF; preserved'
        fi
    elif [[ -e vendor/autoload.php || -L vendor/autoload.php ]]; then
        fail 'vendor/autoload.php exists without pinned TCPDF; vendor preserved; use a fresh checkout'
    fi
}
publish_dependency() {
    # Directory publication is guarded by a shared parent lock across worktrees.
    local source="$1" destination="$2" guard
    guard="$(dirname "$destination")/.fmonitor-dependency-publish.lock"
    mkdir "$guard" 2>/dev/null || fail "dependency publication already running: $guard; rerun after it finishes"
    if [[ -e "$destination" || -L "$destination" ]]; then
        rmdir "$guard"
        fail "$destination appeared during setup; preserved; rerun setup"
    fi
    if ! python3 -c 'import os, sys; os.rename(sys.argv[1], sys.argv[2])' "$source" "$destination"; then
        rmdir "$guard"
        fail "could not publish $destination; destination preserved"
    fi
    rmdir "$guard"
}
