#!/usr/bin/env bash
# Build and deploy every plugin in the repository; run on the Moodle server as root.
set -Eeuo pipefail
umask 022

REPO_URL="${REPO_URL:-https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4.git}"
MOODLE_ROOT="${MOODLE_ROOT:-/var/www/moodle}"
SOURCE_DIR="${SOURCE_DIR:-/opt/moodle-practicum-src}"
WEB_USER="${WEB_USER:-www-data}"
PHP_BIN="${PHP_BIN:-php}"
BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/moodle-practicum}"
BRANCH="${BRANCH:-main}"
STAGING=''
MAINTENANCE_STARTED=0

log() { printf '\n[deploy] %s\n' "$*"; }
fail() { printf '[deploy] ERROR: %s\n' "$*" >&2; exit 1; }
as_web() { runuser -u "$WEB_USER" -- "$PHP_BIN" "$@"; }
cleanup() {
    local result=$?
    trap - EXIT
    if [[ -n "$STAGING" ]]; then rm -rf -- "$STAGING"; fi
    if (( result != 0 && MAINTENANCE_STARTED )); then
        printf '\nDeployment failed. Maintenance remains ON to protect the site.\n' >&2
        printf 'Inspect the error and finish/recover the upgrade before disabling it:\n' >&2
        printf 'runuser -u %q -- %q %q --disable\n' "$WEB_USER" "$PHP_BIN" "$MOODLE_ROOT/admin/cli/maintenance.php" >&2
        printf 'Plugin file backups: %s (database is not backed up by this script).\n' "$BACKUP_ROOT" >&2
    fi
    exit "$result"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

[[ $EUID -eq 0 ]] || fail 'Run with sudo bash deploy.sh.'
for command in git python3 rsync runuser flock mktemp tar; do
    command -v "$command" >/dev/null || fail "Missing command: $command"
done
PHP_BIN="$(command -v "$PHP_BIN")" || fail 'PHP CLI not found.'
id "$WEB_USER" >/dev/null 2>&1 || fail "Unknown web user: $WEB_USER"
WEB_GROUP="$(id -gn "$WEB_USER")"
[[ "$MOODLE_ROOT" = /* && "$SOURCE_DIR" = /* && "$BACKUP_ROOT" = /* ]] || fail 'Paths must be absolute.'
MOODLE_ROOT="$(realpath -e -- "$MOODLE_ROOT")"
SOURCE_DIR="$(realpath -m -- "$SOURCE_DIR")"
BACKUP_ROOT="$(realpath -m -- "$BACKUP_ROOT")"
[[ "$SOURCE_DIR" != / && "$BACKUP_ROOT" != / ]] || fail 'Unsafe deployment paths.'
case "$SOURCE_DIR/" in "$MOODLE_ROOT/"*) fail 'SOURCE_DIR must be outside Moodle.' ;; esac
case "$BACKUP_ROOT/" in "$MOODLE_ROOT/"*) fail 'BACKUP_ROOT must be outside Moodle.' ;; esac
[[ -f "$MOODLE_ROOT/config.php" && -f "$MOODLE_ROOT/public/version.php" ]] || fail 'Expected Moodle 5.3 layout with config.php and public/version.php.'
for script in maintenance.php upgrade.php purge_caches.php; do
    [[ -f "$MOODLE_ROOT/admin/cli/$script" ]] || fail "Missing Moodle CLI: admin/cli/$script"
done
exec 9>/var/lock/moodle-plugins-deploy.lock
flock -n 9 || fail 'Another deployment is running.'

log "Synchronising repository ($BRANCH)"
if [[ ! -e "$SOURCE_DIR" ]]; then
    git clone --branch "$BRANCH" --single-branch "$REPO_URL" "$SOURCE_DIR"
else
    [[ -d "$SOURCE_DIR/.git" ]] || fail "SOURCE_DIR is not a Git checkout: $SOURCE_DIR"
    [[ "$(git -C "$SOURCE_DIR" remote get-url origin)" = "$REPO_URL" ]] || fail 'Checkout origin differs from the expected repository.'
    [[ -z "$(git -C "$SOURCE_DIR" status --porcelain)" ]] || fail 'Checkout contains local changes; use a clean dedicated SOURCE_DIR.'
    [[ "$(git -C "$SOURCE_DIR" branch --show-current)" = "$BRANCH" ]] || fail "Checkout must be on $BRANCH."
    git -C "$SOURCE_DIR" fetch origin "$BRANCH"
    git -C "$SOURCE_DIR" merge --ff-only "origin/$BRANCH"
    [[ "$(git -C "$SOURCE_DIR" rev-parse HEAD)" = "$(git -C "$SOURCE_DIR" rev-parse "origin/$BRANCH")" ]] || fail 'Checkout has local commits; refusing to deploy unpublished code.'
fi
COMMIT="$(git -C "$SOURCE_DIR" rev-parse HEAD)"
log "Building plugins from $COMMIT"
STAGING="$(mktemp -d /var/tmp/moodle-practicum.XXXXXXXX)"
log 'Checking Moodle and existing maintenance state'
as_web -r 'define("CLI_SCRIPT", true); require $argv[1];
require $CFG->dirroot . "/version.php";
if ((int)$branch !== 503) { fwrite(STDERR, "Expected Moodle 5.3.\n"); exit(1); }
if (!empty($CFG->maintenance_enabled) || file_exists($CFG->dataroot . "/climaintenance.html")) {
    fwrite(STDERR, "Maintenance is already enabled. Resolve it before deploying.\n"); exit(1);
}
echo json_encode(\core_component::get_plugin_types(), JSON_THROW_ON_ERROR);
' "$MOODLE_ROOT/config.php" > "$STAGING/types.json"
python3 "$SOURCE_DIR/tools/package.py" --output "$STAGING/zips"
python3 - "$STAGING" "$MOODLE_ROOT" <<'PYTHON'
from pathlib import Path
import json
import re
import sys
import zipfile
root = Path(sys.argv[1])
public = (Path(sys.argv[2]) / 'public').resolve()
types = json.loads((root / 'types.json').read_text())
manifest = json.loads((root / 'zips/manifest.json').read_text())
with (root / 'plan.tsv').open('w') as plan:
    targets = set()
    for plugin in manifest['plugins']:
        component, kind, name = plugin['component'], plugin['type'], plugin['name']
        if not re.fullmatch(r'[a-z][a-z0-9]*_[a-z][a-z0-9_]*', component) or component != kind + '_' + name:
            raise SystemExit('Invalid plugin component')
        if kind not in types:
            raise SystemExit(f'Unknown Moodle plugin type: {kind}')
        target = Path(types[kind]) / name
        if target.is_symlink() or not target.resolve().is_relative_to(public) or (target.exists() and not target.is_dir()):
            raise SystemExit(f'Unsafe plugin target: {target}')
        if target.resolve() in targets:
            raise SystemExit(f'Duplicate target: {target}')
        targets.add(target.resolve())
        archive = component + '.zip'
        if plugin['archive'] != archive:
            raise SystemExit('Invalid archive name')
        with zipfile.ZipFile(root / 'zips' / archive) as z:
            for entry in z.infolist():
                path = Path(entry.filename)
                if path.is_absolute() or '..' in path.parts or not path.parts or path.parts[0] != name:
                    raise SystemExit(f'Unexpected archive entry: {entry.filename}')
            z.extractall(root / 'plugins' / component)
        source = root / 'plugins' / component / name
        if not (source / 'version.php').is_file():
            raise SystemExit(f'Missing version.php in {archive}')
        if any(c in str(target) for c in '\t\r\n'):
            raise SystemExit('Unsupported whitespace in target path')
        plan.write(f'{component}\t{source}\t{target}\n')
        print(f'{component} -> {target}')
    if not targets:
        raise SystemExit('Empty deployment plan')
PYTHON
# Catch syntax errors before maintenance or changes to installed plugins.
while IFS= read -r -d '' file; do
    "$PHP_BIN" -l "$file" >/dev/null
done < <(find "$STAGING/plugins" -type f -name '*.php' -print0)

# File backups are useful for investigation; DB migrations need a separate DB backup.
mkdir -p -- "$BACKUP_ROOT"
chmod 700 "$BACKUP_ROOT"
BACKUP_DIR="$(mktemp -d "$BACKUP_ROOT/$(date -u +%Y%m%dT%H%M%SZ).XXXXXXXX")"
printf '%s\n' "$COMMIT" > "$BACKUP_DIR/source-commit.txt"
cp "$STAGING/zips/"*.zip "$STAGING/zips/manifest.json" "$STAGING/plan.tsv" "$BACKUP_DIR/"
log "Enabling maintenance; file backups: $BACKUP_DIR"
# Mark first so an interrupted maintenance command does not trigger false success.
MAINTENANCE_STARTED=1
as_web "$MOODLE_ROOT/admin/cli/maintenance.php" --enable
while IFS=$'\t' read -r component source target; do
    if [[ -d "$target" ]]; then
        tar -czf "$BACKUP_DIR/$component-before.tar.gz" -C "$(dirname "$target")" "$(basename "$target")"
    fi
done < "$STAGING/plan.tsv"

log 'Installing all plugins in deployment plan'
while IFS=$'\t' read -r component source target; do
    log "Installing $component"
    mkdir -p -- "$target"
    rsync -a --delete --chown="root:$WEB_GROUP" --chmod=D755,F644 "$source/" "$target/"
done < "$STAGING/plan.tsv"

log 'Running Moodle upgrade'
as_web "$MOODLE_ROOT/admin/cli/upgrade.php" --non-interactive
log 'Purging caches'
as_web "$MOODLE_ROOT/admin/cli/purge_caches.php"
log 'Disabling maintenance'
as_web "$MOODLE_ROOT/admin/cli/maintenance.php" --disable
MAINTENANCE_STARTED=0
log "Done. All repository plugins deployed from $COMMIT. Archives and backups: $BACKUP_DIR"
