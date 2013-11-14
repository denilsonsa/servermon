<?php

require_once "parse_files.inc.php";

header('Content-type: text/html');

$LAST_ERROR='';

$serverid=get_alphanumeric_string($_REQUEST['id']);
$directory=INDIVIDUAL_LOG_DIR."/".$serverid."/";
$logfile=$directory.INDIVIDUAL_SHORT_LOG_FILE;
if( !( is_dir($directory) && is_file($logfile) && is_readable($logfile)) ) {
	$LAST_ERROR="Invalid server ID.";
	$datestamp=0;
	$date_modified_nice="unknown";
	$date_modified_http="";
	$date_expires_http="";
} else {
	$datestamp=filemtime($logfile);
	$date_modified_nice=format_date($datestamp);
	$date_modified_http=get_http_date($datestamp);
	$date_expires_http=get_http_date($datestamp+SECONDS_BETWEEN_TESTS);

	header('Last-Modified: '.$date_modified_http);
	header('Cache-Control: max-age='.(SECONDS_BETWEEN_TESTS));
	header('Expires: '.$date_expires_http);
}
$current_time=format_date();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en"><head><title>Short log for server <?php echo $serverid ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="noindex,nofollow">
<?php
if( $date_expires_http ) echo '<meta http-equiv="Expires" content="'.$date_expires_http.'">'.NEWLINE;
?>
<link rel="stylesheet" type="text/css" href="style.css">
</head><body>
<div class="pagetimes">
<p class="generatedtime">Page generated at <span class="time"><?php echo $current_time ?></span></p>
<?php
if( is_file(TEST_RUNNING_FILE) ) echo '<p class="testtime">Test is running now.</p>'.NEWLINE;
elseif( $datestamp ) echo '<p class="testtime">Last change at <span class="time">'.$date_modified_nice.'</span></p>'.NEWLINE;
?>
</div>
<?php

if( !$LAST_ERROR ) {

	$serverinfo=null;
	$serverlist=read_server_list();
	if($serverlist)
		$serverinfo=$serverlist[$serverid];
	$serverlist=null;
	?>
<div class="serverinfo">
<?php
if( $serverinfo ) {
	?>
<h1><?php echo $serverinfo['name'] ?></h1>
<h2><?php echo $serverinfo['description'] ?></h2>
<?php
}
?>
<p>Read the <a href="<?php echo (INDIVIDUAL_LOG_DIR."/".$serverid."/".INDIVIDUAL_LOG_FILE) ?>">full log</a> for verbose output.</p>
</div>

<div class="status"><table class="status">
<thead>
<tr>
<!--th class="id">ID</th-->
<th class="date">Date/time</th>
<th class="updown" title="Up/Down">U/D</th>
<th class="message">Short message</th>
</tr>
</thead>
<tbody>
<?php

	$even_odd=true;
	foreach( explode(",",LOGROTATE_SUFFIXES) as $suffix ) {
		if( !is_file($logfile.$suffix) || !is_readable($logfile.$suffix) )
			continue;
		$v=read_short_log($logfile.$suffix, false);
		if( !$v )
			continue;
		foreach( array_reverse($v) as $s ) {
			echo '<tr class="'. ($even_odd?'evenline':'oddline') .' '. ($s['ok']==0?'success':'fail') . ($serverinfo['important']?' important':'') . '">';
			echo '<td class="date">'.$s['date'].'</td>';
			echo '<td class="updown">'.($s['ok']==0?'up':'down').'</td>';
			echo '<td class="message">'.$s['message'].'</td>';
			echo '</tr>'.NEWLINE;
			$even_odd=!$even_odd;
		}
	}

	?>
</tbody>
</table></div>
<?php

} else {
//if( $LAST_ERROR ) {
	// BEGIN error printing code
	?>
<div class="error">
<p><?php echo $LAST_ERROR ?></p>
</div>
<?php
	// END error printing code
}

?>

<?php print_softwaredownload(); ?>
</body></html>
