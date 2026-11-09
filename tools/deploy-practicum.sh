#!/usr/bin/env bash
# Compatibility entry point for existing checkouts. Use root deploy.sh directly.
set -Eeuo pipefail
script="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)/deploy.sh"
if [[ ! -f "$script" ]]; then
    printf 'Download deploy.sh from the repository root; this compatibility wrapper needs a checkout.\n' >&2
    exit 1
fi
exec bash "$script" "$@"
