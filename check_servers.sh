#!/bin/bash


SERVER_LIST_FILE="server_list.txt"

INDIVIDUAL_LOG_DIR="./log"

# Files below will be created inside LOGDIR/SERVERID/
INDIVIDUAL_LOG_FILE="full_log"
INDIVIDUAL_SHORT_LOG_FILE="short_log"
LAST_INDIVIDUAL_LOG_FILE="last_log"
LAST_SUCCESS_DATE_FILE="last_success"
FAIL_COUNT_FILE="fail_count"

# Files below are "global"
GLOBAL_LOG_FILE="$INDIVIDUAL_LOG_DIR/global_log"
LAST_GLOBAL_LOG_FILE="$INDIVIDUAL_LOG_DIR/last_log"
LAST_TEST_DATE_FILE="$INDIVIDUAL_LOG_DIR/last_test"


ping_cmd() {
	ping -c 5 "$@" 2>&1
}

http_cmd() {
	wget -t 1 -T 15 -S --spider "$@" 2>&1
}
date_cmd() {
	date '+%F %H:%M:%S'
}


BAD=$'\e[31;01m'
NORMAL=$'\e[0m'


################################################################################
# END OF CONFIGURATION
# Don't change below this line



check_http() {
	OUTPUT=$(http_cmd "$ADDRESS")
	OK=$?
	SINGLE_LINE_OUTPUT=$(echo "$OUTPUT" | fgrep -v 'Giving up' | tail -n 1 | sed -n 's/^ *[0-9]\+:[0-9]\+:[0-9]\+ *//;p')
	SINGLE_LINE_OUTPUT="wget: $SINGLE_LINE_OUTPUT"
#	echo "$TMP" | fgrep "connected" &>/dev/null
#	OK=$?
#	if [[ $OK == 0 ]]; then
#		if echo "$TMP" | fgrep "ERROR" &>/dev/null ; then
#			OK=1
#		fi
#	fi
}

check_ping() {
	OUTPUT=$(ping_cmd "$ADDRESS")
	OK=$?
	if echo "$OUTPUT" | fgrep 'packets transmitted' &>/dev/null; then
		SINGLE_LINE_OUTPUT=$(echo "$OUTPUT" | fgrep 'packets transmitted' | sed 's/, *time *[0-9]\+ms *//;s/packets\? //g')
		SINGLE_LINE_OUTPUT="ping: $SINGLE_LINE_OUTPUT"
	else
		SINGLE_LINE_OUTPUT="$OUTPUT"
	fi
}


incorrect_type() {
	OUTPUT="Incorrect or unsupported type '$TYPE' at line $LINE_NUMBER."
	OK=-1
}


################################################################################
# Main


main() {

DATE=$(date_cmd)
mkdir -p `dirname "$LAST_TEST_DATE_FILE"`
echo "$DATE" > "$LAST_TEST_DATE_FILE"
mkdir -p `dirname "$LAST_GLOBAL_LOG_FILE"`
echo -n "" > "$LAST_GLOBAL_LOG_FILE"

NEWLINE=$'\n'
LINE_NUMBER=0
cat "$SERVER_LIST_FILE" | while read line; do
	let LINE_NUMBER++

	# Skip comments and blank lines
	if [[ $line == '#'* || $line == '' ]]; then continue; fi

	echo "$line" | tr $'\t' "$NEWLINE" | (
		read ID
		read NAME
		read IMPORTANT
		read TYPE
		read ADDRESS
		read DESCRIPTION

		OUTPUT=""
		SINGLE_LINE_OUTPUT=""
		DATE=$(date_cmd)
		OK="1"  # 0=ok ; 1=fail ; -1=incorrect type
		case $TYPE in
			http)	check_http ;;
			ping)	check_ping ;;
			*)	incorrect_type ;;
		esac

		if [[ $OK == -1 ]]; then
			echo "${BAD}Error:${NORMAL} $OUTPUT"
		else
			mkdir -p `dirname "$GLOBAL_LOG_FILE"`
			mkdir -p `dirname "$LAST_LOG_FILE"`
			mkdir -p "$INDIVIDUAL_LOG_DIR/$ID"

			# Writing the short message to global log (and individual short log)
			echo "[${DATE}] [${ID}] ${OK} ${SINGLE_LINE_OUTPUT}" >> "$GLOBAL_LOG_FILE"
			echo "[${DATE}] [${ID}] ${OK} ${SINGLE_LINE_OUTPUT}" >> "$LAST_GLOBAL_LOG_FILE"
			echo "[${DATE}] [${ID}] ${OK} ${SINGLE_LINE_OUTPUT}" >> "$INDIVIDUAL_LOG_DIR/$ID/$INDIVIDUAL_SHORT_LOG_FILE"

			# Writing the individual (long) log
			echo "-- TEST RUN AT ${DATE} --${NEWLINE}-- Status: ${OK} --${NEWLINE}${OUTPUT}" >> "$INDIVIDUAL_LOG_DIR/$ID/$INDIVIDUAL_LOG_FILE"
			echo "-- TEST RUN AT ${DATE} --${NEWLINE}-- Status: ${OK} --${NEWLINE}${OUTPUT}" > "$INDIVIDUAL_LOG_DIR/$ID/$LAST_INDIVIDUAL_LOG_FILE"

			if [[ $OK == 0 ]]; then
				# Test was successful
				echo "$DATE" > "$INDIVIDUAL_LOG_DIR/$ID/$LAST_SUCCESS_DATE_FILE"
				echo "0" > "$INDIVIDUAL_LOG_DIR/$ID/$FAIL_COUNT_FILE"
			else
				# Test failed
				if [[ -e "$INDIVIDUAL_LOG_DIR/$ID/$FAIL_COUNT_FILE" ]]; then
					COUNT=$(cat "$INDIVIDUAL_LOG_DIR/$ID/$FAIL_COUNT_FILE")
				else
					COUNT=0
				fi
				echo $(( COUNT+1 )) > "$INDIVIDUAL_LOG_DIR/$ID/$FAIL_COUNT_FILE"
			fi
		fi
	)
done

} # main()


export LANG=C
main
