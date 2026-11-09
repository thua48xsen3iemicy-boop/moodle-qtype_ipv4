# One deployment command for all repository plugins

Run the repository-root `deploy.sh` on the Moodle server:

```sh
sudo bash deploy.sh
```

Default target: Moodle **5.3** in `/var/www/moodle`, PHP user `www-data`.
The script clones or fast-forwards `main` of
`https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4.git` in the dedicated
checkout `/opt/moodle-practicum-src`. That existing path is retained for compatibility.

Deployment sequence:

1. Lock out concurrent deployments and synchronise the clean source checkout.
2. Check Moodle/version/maintenance state and read plugin-type paths from Moodle.
3. Discover every tracked plugin `version.php` with a literal `$plugin->component`.
4. Build one `<component>.zip` per plugin and a `manifest.json`.
5. Validate archive contents, target paths and PHP syntax before maintenance.
6. Enable maintenance and back up existing plugin directories.
7. Synchronise all discovered plugins into their Moodle locations.
8. Run the standard noninteractive Moodle upgrade, purge caches, disable maintenance.

Currently the discovered packages are `qtype_ipv4` and `mod_practicum`.
A new tracked `local/example/version.php` declaring `local_example`, for example,
is automatically packaged and installed in `public/local/example`. No edits to
`deploy.sh` are needed. Destination directories come from
`core_component::get_plugin_types()`, not a hardcoded list. Unknown types, duplicate
components or targets, and targets outside Moodle's public directory are rejected
before maintenance. Custom subplugin types must already be known to Moodle.
Removing a plugin from Git does not uninstall its existing Moodle installation.
This updates plugins, not Moodle core itself; the CLI applies all pending core or
plugin migrations already present in the installed Moodle tree.

Packaging includes tracked files only. Nested plugins have separate archives.
The legacy root qtype package excludes development tools, docs, other plugin
containers and deployment metadata. Untracked work/secrets are never packaged.
`python3 tools/package.py` can also build the archives without deploying;
`dist/manifest.json` lists their components and source roots.

## Requirements and recovery

Requires root, `git`, `python3`, `rsync`, `runuser`, `flock`, PHP CLI with Moodle's
extensions, and standard Linux/coreutils tools. On Debian/Ubuntu, missing helper
tools can be installed with `apt install git python3 rsync util-linux`.

**Make and verify your normal database backup before running.** The script backs
up plugin files, not the database or moodledata. After a migration, restoring old
PHP files alone is not a complete rollback.

Archives, previous files, source SHA, manifest and deployment plan are saved under
`/var/backups/moodle-practicum/<timestamp>.*`. PHP loading Moodle runs as `www-data`;
installed code is owned by root and readable by the web server.

If installation, upgrade or cache purge fails, maintenance stays enabled. The
script prints the recovery command; finish/recover the upgrade before disabling
maintenance. An already enabled maintenance state causes a pre-install stop.
Local checkout changes or unpublished commits are not discarded.

Optional configuration:

```sh
sudo env WEB_USER=www-data PHP_BIN=/usr/bin/php8.4 \
  MOODLE_ROOT=/var/www/moodle SOURCE_DIR=/opt/moodle-practicum-src \
  BACKUP_ROOT=/var/backups/moodle-practicum BRANCH=main \
  bash deploy.sh
```

`REPO_URL` can select another repository with the same packaging interface.
`tools/deploy-practicum.sh` is a compatibility wrapper for use inside a checkout;
for a standalone downloaded script, use the root `deploy.sh`.

## Validation

- `bash -n deploy.sh tools/deploy-practicum.sh`
- `python3 -m unittest discover -s tools/tests -p test_package.py`
- `python3 tools/tests/test_deploy.py`

Packaging tests cover automatic discovery, package boundaries, untracked-file
exclusion and duplicate rejection. Sandboxed command mocks deploy a third plugin
without changing the deployment code, and verify success/upgrade-failure/cache-
failure paths, backups, cleanup and maintenance retention. No actual server
installation or Moodle database upgrade has been performed for this script.
