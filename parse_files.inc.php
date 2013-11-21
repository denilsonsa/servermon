<?php
/**
 * Includes functions to parse all needed files.
 *
 * @package core
 * @author Denilson
 */

// Setting a default timezone.
date_default_timezone_set("America/Sao_Paulo");

// Name of the configuration file (path relative to the servermon directory)
define("SERVER_LIST_FILE","server_list.conf");
// Directory to store the logs (path relative to the servermon directory), must be web-readable
define("INDIVIDUAL_LOG_DIR","log");
// This interval is used to send the HTTP headers, allowing client-side cache
define("SECONDS_BETWEEN_TESTS",30*60);  // 30 minutes

// Files below will be created inside LOGDIR/SERVERID/
define("INDIVIDUAL_LOG_FILE","full_log.txt");
define("INDIVIDUAL_SHORT_LOG_FILE","short_log");
define("LAST_INDIVIDUAL_LOG_FILE","last_log.txt");
define("LAST_SUCCESS_DATE_FILE","last_success_date");
define("FAIL_COUNT_FILE","fail_count");

// Files below are "global"
define("GLOBAL_LOG_FILE"     ,INDIVIDUAL_LOG_DIR."/global_log");
define("LAST_GLOBAL_LOG_FILE",INDIVIDUAL_LOG_DIR."/last_global_log");
define("LAST_TEST_DATE_FILE" ,INDIVIDUAL_LOG_DIR."/last_test_date");
define("TEST_RUNNING_FILE"   ,INDIVIDUAL_LOG_DIR."/running");

// logrotate-related (this is a quick hack that works well)
define("LOGROTATE_SUFFIXES",",.1");  //Add more suffixes to show more old logs


/*
 * Enable transparent compression of pages.
 *
 * Set this "On" to enable transparent compression of pages. This should reduce
 * the amount of data transfered.
 *
 * Set this "Off" to disable this.
 *
 * Notice: this SHOULD work, but doesn't... So, I've set that option at php.ini.
 */
//ini_set("zlib.output_compression","On");


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
 * @param string $filename File to be read.
 *
 * @return array Associative array containing all servers.
 */
function read_server_list($filename=SERVER_LIST_FILE)  // {{{
{
	global $valid_types, $LAST_ERROR;

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
		$s=trim($s);

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
 * Reads a short_log-like file as an array.
 *
 * @param string $filename File to be read.
 *
 * @param boolean $associative If true, the returned array will be
 * associative, with server ID as key. If false, each log line will
 * be an array element (with numeric keys).
 *
 * @return array The log, as an array
 */
function read_short_log($filename,$associative=true)  // {{{
{
	global $LAST_ERROR;

	if( !is_file($filename) || !is_readable($filename) )
	{
		$LAST_ERROR="read_global_last_log(): Could not read short_log file";
		return NULL;
	}
	$s=file($filename);
	if( !$s )
	{
		$LAST_ERROR="read_global_last_log(): Could not read short_log file";
		return NULL;
	}

	$count=0;
	foreach( $s as $line )
	{
		sscanf($line,"[%[^]]] [%[^]]] %d %[^\n]",$date,$serverid,$ok,$message);
		$serverid=get_alphanumeric_string($serverid);

		if( $associative )
			$id=$serverid;
		else
			$id=$count++;

		$ret[$id]['date']=$date;
		$ret[$id]['id']=$serverid;
		$ret[$id]['ok']=$ok;
		$ret[$id]['message']=$message;
	}

	return $ret;
}  // }}}



/**
 * Prints the "servermon advertisement".
 */
function print_softwaredownload()
{
?>
<div class="softwaredownload">
<p>You can add this to your server! Get it <a href="http://github.com/denilsonsa/servermon">from GitHub</a> or <a href="http://bitbucket.org/denilsonsa/servermon">from BitBucket</a>.</p>
</div>
<?php	
}


?>
