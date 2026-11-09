# Deploy Practicum to Moodle 5.3

Run `tools/deploy-practicum.sh` on the Moodle server as root:

```sh
sudo bash tools/deploy-practicum.sh
```

Defaults:

- Moodle: `/var/www/moodle` (5.3 layout with `public/`).
- PHP/web user: `www-data`; CLI executable: `php` from PATH.
- Dedicated source checkout: `/opt/moodle-practicum-src`, branch `main` of
  `https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4.git`.
- File backups and built archives: `/var/backups/moodle-practicum/<timestamp>.*`.

Requires `git`, `python3`, `rsync`, `runuser`, `flock`, PHP CLI with Moodle's
required extensions, and standard Linux/coreutils tools. For Debian/Ubuntu,
missing deployment tools can be installed with `apt install git python3 rsync util-linux`.
Use the PHP CLI version/extensions matching your Moodle installation.

The script fetches main without discarding local changes or commits, builds the
separate qtype and module ZIPs with `tools/package.py`, checks PHP syntax and the
Moodle version/maintenance state, enables maintenance, backs up existing plugin
files, synchronises both install directories, runs Moodle's normal noninteractive
upgrade, purges caches and disables maintenance on success. PHP commands that load
Moodle run as `www-data`. Installed plugin code is owned by root and readable by
the web server. This is a deployment of the latest main commit at fetch time;
its SHA and the archives are saved with the file backups.

**Make and verify your normal database backup before running.** The script backs
up plugin files, not the database or moodledata. Upgrade runs for all pending
Moodle/plugin changes, as Moodle's standard CLI normally does. After a migration,
restoring just the old PHP files is not a complete database rollback.

On upgrade/cache/installation failure, maintenance stays enabled and the script
prints the recovery command. Investigate and finish/recover the upgrade before
manually disabling maintenance. If maintenance is already enabled at startup,
the script stops before changing installed plugin files.

Overrides, if needed:

```sh
sudo env WEB_USER=www-data PHP_BIN=/usr/bin/php8.4 \
  MOODLE_ROOT=/var/www/moodle \
  SOURCE_DIR=/opt/moodle-practicum-src \
  BACKUP_ROOT=/var/backups/moodle-practicum \
  bash tools/deploy-practicum.sh
```

After success: course → enable editing → Add an activity or resource → Practicum
(«Практикум» in Russian). The current Practicum module exposes the network-address
exercise; other new qtype variants are available in the question editor and quiz
bulk generator, and still need separate Practicum adapters/UI.

Validation: Bash syntax check and three sandboxed command-mock scenarios (success,
upgrade failure, cache purge failure). The mock harness verifies command order,
plugin staging, file backups, cleanup and maintenance retention on failure. No
actual server deployment or Moodle database upgrade was run for this script.
