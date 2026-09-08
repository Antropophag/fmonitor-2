#!/usr/bin/env bash
# Compatibility entrypoint: local setup and CI execute the same implementation.
set -euo pipefail
exec bash "$(dirname "${BASH_SOURCE[0]}")/setup.sh" "$@"
