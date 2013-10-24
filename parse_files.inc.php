<?php
/**
 * Includes functions to parse all needed files.
 *
 * @package core
 * @author Denilson
 */

/**
 * to be written...
 */
define("SERVER_LIST_FILE","server_list.txt");
define("INDIVIDUAL_LOG_DIR","./log");

// Files below will be created inside LOGDIR/SERVERID/
define("INDIVIDUAL_LOG_FILE","full_log");
define("LAST_INDIVIDUAL_LOG_FILE","last_log");
define("LAST_SUCCESS_DATE_FILE","last_success");
define("FAIL_COUNT_FILE","fail_count");

// Files below are "global"
define("GLOBAL_LOG_FILE",INDIVIDUAL_LOG_DIR."/global_log");
define("LAST_GLOBAL_LOG_FILE",INDIVIDUAL_LOG_DIR."/last_log");
define("LAST_TEST_DATE_FILE",INDIVIDUAL_LOG_DIR."/last_test");


/**
 * Just a little define to allow easier adding of newlines in strings.
 *
 * This define is used in many string functions.
 */
define("NEWLINE" , "\n");


/**
 * Valid "types" array.
 */
$valid_types = array( "http","ping" );



/**
 * Returns the first portion of parameter that is formed only by alphanumeric characters.
 *
 * This function simplifies the parse of simple one-word alphanumeric strings.
 * It will return the /^[a-zA-Z_0-9]{@*} portion of the parameter string.
 *
 * It is not implemented using regular expressions, it uses sscanf.
 *
 * @param string $s The input (possibly unsafe) string.
 * @return string A string guaranteed to contain only [a-zA-Z_0-9] characters (so, a safe string).
 */
function get_alphanumeric_string($s)
{
	sscanf($s,"%[A-Za-z_0-9]",$t);
	return $t;
}



/**
 * Returns the date in the format needed by Last-Modified header.
 *
 * This function receives a timestamp (in the same format returned
 * by {@link http://www.php.net/time time()}) and returns a string
 * with that date in HTTP format.
 *
 * The timestamp will be localtime, and the string returned will
 * be GMT (UTC) time.
 *
 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3
 *
 * @param int $timestamp Timestamp in localtime.
 * @return string HTTP-date
 */
function get_http_date($timestamp)
{
	return gmdate("D, d M Y H:i:s",$timestamp) . " GMT";
}



/**
 * Prints a var_dump in a HTML-friendly way (with HTML entities).
 *
 * This is useful while debugging, because it allows to embed var_dump
 * into normal HTML output.
 *
 * Notice this function will not print <samp><pre></samp> tag or similar.
 */
function var_dump_html($mixed = null)
{
  ob_start();
  var_dump($mixed);
  $content = ob_get_contents();
  ob_end_clean();
  echo htmlspecialchars($content);
}



/**
 * Converts a date to Unix timestamp.
 *
 * NOTE: DST is not treated!
 *
 * @param string $datestring The date, in format "YYYY-MM-DD HH:MM:SS".
 * @return int Unix timestamp.
 */
function convert_date_to_timestamp($datestring)
{
	sscanf($datestring,"%d-%d-%d %d:%d:%d",$year,$month,$day,$hour,$minute,$second);
	return mktime($hour,$minute,$second,$month,$day,$year);
}

/**
 * Returns current time in "YYYY-MM-DD HH:MM:SS" format.
 *
 * This function optionally receives a timestamp. If timestamp
 * is null or 0, then current time is used.
 *
 * @param int $timestamp Timestamp to be converted.
 * @return string Time in "YYYY-MM-DD HH:MM:SS" format.
 */

function format_date($timestamp=0)
{
	if( !$timestamp )
		$timestamp=time();
	return strftime("%Y-%m-%d %T",$timestamp);	
}



/**
 * Returns an associative array with all servers.
 *
 * Each field can be accessed by $ret['serverid']['fieldname'].
 *
 * @return array Associative array containing all servers.
 */
function read_server_list()  // {{{
{
	global $valid_types, $LAST_ERROR;

	$filename=SERVER_LIST_FILE;
	if( ! is_file($filename) || ! is_readable($filename) )
	{
		$LAST_ERROR="read_server_list(): File not found or not readable";
		return NULL;
	}
	$f=fopen($filename,"r");
	if( !$f )
	{
		$LAST_ERROR="read_server_list(): Could not open file for reading";
		return NULL;
	}

	// Note: at most 4096 characters from each line are read
	while( ($s=fgets($f,4096))!==FALSE )
	{
		// Skip comments and blank lines
		if( $s=="" || $s{0}=="#" )
			continue;

		$v=explode("\t",$s);

		// Skip invalid types
		if( !in_array($v[3],$valid_types) )
			continue;

		$ret[ $v[0] ] = array(
			'id'         =>$v[0],
			'name'       =>$v[1],
			'important'  =>$v[2],
			'type'       =>$v[3],
			'address'    =>$v[4],
			'description'=>$v[5]
		);
	}

	fclose($f);
	return $ret;
}  // }}}


/**
 * Reads and returns 'last_success', 'fail_count' and 'last_log' from a server.
 *
 * @param string $serverid The server ID.
 * @return array Associative array.
 */
function read_server_status($serverid)  // {{{
{
	global $LAST_ERROR;

	$serverid=get_alphanumeric_string($serverid);
	$directory=INDIVIDUAL_LOG_DIR."/".$serverid."/";
	if( !is_dir($directory) )
	{
//		$LAST_ERROR="read_server_status(): There is no such directory \"$directory\"";
		$LAST_ERROR="read_server_status(): There is no such directory";
		return NULL;
	}

	$ret['last_success']=trim(file_get_contents($directory.LAST_SUCCESS_DATE_FILE));
	$ret['fail_count'  ]=trim(file_get_contents($directory.FAIL_COUNT_FILE));
	$ret['last_log'    ]=file_get_contents($directory.LAST_INDIVIDUAL_LOG_FILE);

	return $ret;
}  // }}}



/**
 * Reads the global last log file.
 *
 * @return array Associative array.
 */
function read_global_last_log()  // {{{
{
	global $LAST_ERROR;

	$s=file(LAST_GLOBAL_LOG_FILE);
	if( !$s )
	{
		$LAST_ERROR="read_global_last_log(): Could not read global last_log file";
		return NULL;
	}

	foreach( $s as $line )
	{
		sscanf($line,"[%[^]]] [%[^]]] %d %[^\n]",$date,$serverid,$ok,$message);
		$serverid=get_alphanumeric_string($serverid);

		$ret[$serverid]['id']=$serverid;
		$ret[$serverid]['date']=$date;
		$ret[$serverid]['ok']=$ok;
		$ret[$serverid]['message']=$message;
	}

	return $ret;
}  // }}}


?>
