#!/usr/bin/env bash
# Lightweight, repository-owned Composer bootstrap for local setup and CI.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
fail() { printf 'SETUP_FAILURE: %s\n' "$*" >&2; exit 1; }
case "${1:-}" in ''|--check) ;; *) fail 'usage: setup-composer.sh [--check]' ;; esac
[[ $# -le 1 ]] || fail 'usage: setup-composer.sh [--check]'

composer_version=''
composer_sha256=''
while IFS= read -r line; do
    [[ -z "$line" || "$line" == \#* ]] && continue
    [[ "$line" =~ ^[A-Z][A-Z0-9_]*=[a-zA-Z0-9/:.,_-]+$ ]] || fail 'invalid dependency manifest'
    key=${line%%=*}; value=${line#*=}
    case "$key" in
        COMPOSER_VERSION) [[ -z "$composer_version" ]] || fail 'duplicate Composer version'; composer_version=$value ;;
        COMPOSER_SHA256) [[ -z "$composer_sha256" ]] || fail 'duplicate Composer digest'; composer_sha256=$value ;;
    esac
done < tools/delivery/dependencies.env
[[ "$composer_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail 'invalid Composer version pin'
[[ "$composer_sha256" =~ ^[0-9a-f]{64}$ ]] || fail 'invalid Composer digest pin'

php -r '$j=json_decode(file_get_contents("composer.json"),true,32,JSON_THROW_ON_ERROR);$l=json_decode(file_get_contents("composer.lock"),true,32,JSON_THROW_ON_ERROR);$p=[];foreach($l["packages"]??[]as$x)$p[$x["name"]]=$x["version"];exit(($j["require"]["yiisoft/yii2"]??null)==="2.0.55"&&($j["require"]["tecnickcom/tcpdf"]??null)==="6.11.4"&&($j["config"]["platform"]["php"]??null)==="8.4.0"&&($p["yiisoft/yii2"]??null)==="2.0.55"&&($p["tecnickcom/tcpdf"]??null)==="6.11.4"?0:1);' \
    || fail 'Composer manifest and lock are inconsistent with dependency contract'
php -r '$sets=[json_decode(file_get_contents("composer.json"),true,32,JSON_THROW_ON_ERROR)["require"]??[]];$l=json_decode(file_get_contents("composer.lock"),true,32,JSON_THROW_ON_ERROR);foreach($l["packages"]??[]as$p)$sets[]=$p["require"]??[];$missing=[];foreach($sets as$r)foreach($r as$n=>$v)if(str_starts_with($n,"ext-")&&!extension_loaded(substr($n,4)))$missing[$n]=true;if($missing){fwrite(STDERR,"missing locked PHP extensions: ".implode(",",array_keys($missing))."\n");exit(1);}' \
    || fail 'locked Composer platform requirements are unavailable'

phar=".local/composer/composer-${composer_version}.phar"
digest() {
    if command -v sha256sum >/dev/null; then sha256sum "$1" | awk '{print $1}'
    elif command -v shasum >/dev/null; then shasum -a 256 "$1" | awk '{print $1}'
    else fail 'SHA-256 utility unavailable'
    fi
}
verify_phar() { [[ "$(digest "$1")" == "$composer_sha256" ]] || fail 'Composer download digest mismatch'; }
verify_vendor() {
    local candidate="${1:-vendor}"
    [[ ! -L "$candidate" && ! -L "$candidate/autoload.php" && -f "$candidate/autoload.php" ]] \
        || fail 'existing vendor is incomplete or unsafe; preserved'
    php -r '$v=$argv[1];require $v."/autoload.php";if(!class_exists("Yii",false))require $v."/yiisoft/yii2/Yii.php";exit(Yii::getVersion()==="2.0.55"&&class_exists("TCPDF_STATIC")&&TCPDF_STATIC::getTCPDFVersion()==="6.11.4"?0:1);' "$candidate" \
        || fail 'existing vendor does not load locked Yii2 and TCPDF; preserved'
    php -r '$v=$argv[1];require $v."/autoload.php";$l=json_decode(file_get_contents("composer.lock"),true,32,JSON_THROW_ON_ERROR);foreach($l["packages"]??[]as$p){$n=$p["name"]??"";$expected=$p["version"]??null;if(!is_string($n)||!is_string($expected)||!Composer\InstalledVersions::isInstalled($n)||Composer\InstalledVersions::getPrettyVersion($n)!==$expected)exit(1);$reference=$p["dist"]["reference"]??$p["source"]["reference"]??null;if(is_string($reference)&&Composer\InstalledVersions::getReference($n)!==$reference)exit(1);$type=$p["type"]??"";$path=Composer\InstalledVersions::getInstallPath($n);if($type!=="metapackage"&&(!is_string($path)||!is_dir($path)))exit(1);}exit(0);' "$candidate" \
        || fail 'existing vendor differs from the locked Composer graph; preserved'
}

if [[ "${1:-}" == --check ]]; then
    if [[ -e "$phar" || -L "$phar" ]]; then
        [[ ! -L "$phar" && -f "$phar" ]] || fail 'Composer phar is unsafe'
        verify_phar "$phar"
        php "$phar" validate --strict --no-check-all --no-interaction
        [[ ! -e vendor && ! -L vendor ]] || php "$phar" check-platform-reqs --no-dev --no-interaction
    fi
    [[ ! -e vendor && ! -L vendor ]] || verify_vendor
    exit 0
fi

phar_stage=''
vendor_stage=''
cleanup() { [[ -z "$phar_stage" ]] || rm -f -- "$phar_stage"; [[ -z "$vendor_stage" ]] || rm -rf -- "$vendor_stage"; }
trap cleanup EXIT
if [[ ! -e "$phar" && ! -L "$phar" ]]; then
    mkdir -p .local/composer
    phar_stage=$(mktemp .local/composer/.composer-download.XXXXXXXX)
    curl --fail --silent --show-error --location --output "$phar_stage" \
        "https://getcomposer.org/download/${composer_version}/composer.phar"
    verify_phar "$phar_stage"
    [[ ! -e "$phar" && ! -L "$phar" ]] || fail 'Composer phar appeared during setup; preserved'
    mv "$phar_stage" "$phar"; phar_stage=''
else
    [[ ! -L "$phar" && -f "$phar" ]] || fail 'Composer phar is unsafe'
    verify_phar "$phar"
fi

php "$phar" validate --strict --no-check-all --no-interaction
if [[ -e vendor || -L vendor ]]; then
    verify_vendor
    php "$phar" check-platform-reqs --no-dev --no-interaction
    exit 0
fi
vendor_stage=$(mktemp -d .vendor-stage.XXXXXXXX)
rmdir "$vendor_stage"
COMPOSER_VENDOR_DIR="$PWD/$vendor_stage" php "$phar" install --no-interaction --no-progress --prefer-dist --working-dir="$PWD"
[[ -f "$vendor_stage/autoload.php" ]] || fail 'Composer install did not produce an autoloader'
verify_vendor "$vendor_stage"
COMPOSER_VENDOR_DIR="$PWD/$vendor_stage" php "$phar" check-platform-reqs --no-dev --no-interaction
[[ ! -e vendor && ! -L vendor ]] || fail 'vendor appeared during setup; preserved'
mv "$vendor_stage" vendor; vendor_stage=''
verify_vendor
