#!/bin/sh

PKGNAME="servermon-1.5"

FILES="
ChangeLog
INSTALL
TODO.txt
check_servers.sh
index.php
make_dist.sh
multi_log.php
parse_files.inc.php
server_list-sample.conf
short_log.php
style.css"


if [ -e "$PKGNAME" ]; then
	echo "Cannot create directory ./$PKGNAME/, because it already exists."
	echo "Aborting..."
	exit 1
fi

for file in $FILES; do
	if [ ! -f "$file" ]; then
		echo "File '$file' was not found, or is not a regular file."
		echo "Aborting..."
		exit 1
	fi
done

mkdir "$PKGNAME"
cp $FILES "$PKGNAME"
tar cvzf "$PKGNAME".tar.gz "$PKGNAME"
rm -rf "$PKGNAME"
