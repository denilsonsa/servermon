#!/bin/sh

PKGNAME="servermon-1.1"

FILES="
TODO.txt
INSTALL
check_servers.sh
index.php
short_log.php
make_dist.sh
parse_files.inc.php
server_list-sample.txt
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
