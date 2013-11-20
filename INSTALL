# servermon INSTALL

## Requirements

- Shell-script backend: bash, wget, ping
- Web frontend: PHP (4 or newer), apache (or anything that runs PHP)

## Quick install instructions

1. Clone the repository or uncompress servermon-<version>.tar.gz to some
   directory.
2. To access the web interface, make sure that directory is accessible via HTTP
   (it should be inside one of the apache directories, e.g.
   `/var/www/htdocs/servermon/`).
3. Create a `server_list.conf` (use `server_list-sample.conf` as example).
4. Change the default timezone at `parse_files.inc.php`. Feel free to tweak any
   other value in that file (but it is not needed).
5. Run `./check_servers.sh` to check the servers and write the logs.
6. To it run automatically, put `cd /some/directory/ ; ./check_servers.sh` in
   your crontab.

### Configuring built-in logrotate

servermon already comes with sane defaults, but if you want to tweak the
log-rotation behavior, follow these instructions:

1. Edit `LOGROTATE_MAX_COUNT`, inside `check_servers.sh`, and put there how many
  "checks" must be done before rotating logs.
  Example: if you run this script every half hour, putting 48 on above variable
  will rotate logs every day.
2. Edit `LOGROTATE_KEEP_HOW_MANY`, inside `check_servers.sh`, and put there how many
  old logs must be kept.

Note: if you decrease the `LOGROTATE_KEEP_HOW_MANY` value, logfiles with number
greater than the new value won't be deleted. You must delete them manually.

### Known bug (that won't be fixed)

When comparing multiple servers, no date-matching is done. The PHP script just
displays each log message side-by-side, sequentially. If one of the servers has
holes in its log (maybe because its line was commented at conf file for some
time), then the status from a wrong date will be displayed.
