<?php

require_once "parse_files.inc.php";

header('Content-type: text/html');

$LAST_ERROR='';

$datestring=file_get_contents(LAST_TEST_DATE_FILE);
if( $datestring ) {
	$datestamp=convert_date_to_timestamp($datestring);
	$date_modified_nice=format_date($datestamp);
	$date_modified_http=get_http_date($datestamp);
	$date_expires_http=get_http_date($datestamp+SECONDS_BETWEEN_TESTS);

	header('Last-Modified: '.$date_modified_http);
	header('Cache-Control: max-age='.(SECONDS_BETWEEN_TESTS));
	header('Expires: '.$date_expires_http);
} else {
	$datestamp=0;
	$date_modified_nice="unknown";
	$date_modified_http="";
	$date_expires_http="";
}
$current_time=format_date();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en"><head><title>Multiple server log</title>
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
else echo '<p class="testtime">Last test executed at <span class="time">'.$date_modified_nice.'</span></p>'.NEWLINE;
?>
</div>
<?php

if( !$LAST_ERROR ) {

	$serverlist=read_server_list();
	$servers=array();
	foreach( $serverlist as $s ) {
		if( isset($_REQUEST['check_'.$s['id']]) )
		{
			$servers[$s['id']]=$s;
		}
	}
	unset($serverlist);

	$maxlen=0;
	foreach( $servers as $s ) {
		$logfile=INDIVIDUAL_LOG_DIR."/".$s['id']."/".INDIVIDUAL_SHORT_LOG_FILE;
		$log[$s['id']]=array();
		foreach( explode(",",LOGROTATE_SUFFIXES) as $suffix ) {
			if( !is_file($logfile.$suffix) || !is_readable($logfile.$suffix) )
				continue;
			$v=read_short_log($logfile.$suffix, false);
			if( !$v )
				continue;
			$log[$s['id']]=array_merge($log[$s['id']],array_reverse($v));
		}
		if( count( $log[$s['id']] ) > $maxlen )
			$maxlen = count( $log[$s['id']] );
	}
	?>
<div class="serverinfo">
<h1>Multiple server log</h1>
<h2><?php
	$names=array();
	foreach( $servers as $s )
		$names[]=$s['name'];
	echo implode(", ",$names);
	unset($names);
?></h2>
</div>

<div class="status"><table class="status">
<thead>
<tr>
<th class="date">Date/time</th>
<?php
	foreach( $servers as $s )
		echo '<th class="updown"><a href="short_log.php?id='.$s['id'].'">'.$s['name'].'</a></th>'.NEWLINE;
?>
</tr>
</thead>
<tbody>
<?php
//	$first_server=array_keys($servers);
//	$first_server=$first_server[0];
	reset($servers);
	$first_server=key($servers);

	$even_odd=true;
	for( $i=0; $i<$maxlen; $i++ ) {
		echo '<tr class="'. ($even_odd?'evenline':'oddline') .'">';
		echo '<td class="date">'.$log[$first_server][$i]['date'].'</td>';
		foreach( $servers as $serv ) {
//			if( $i<count( $log[$serv['id']] ) ) {
			if( isset( $log[$serv['id']][$i] ) ) {
				$s=$log[$serv['id']][$i];
				echo '<td class="updown '. ($s['ok']==0?'success':'fail') . ($serv['important']?' important':'') .'" ';
				echo 'title="'. $serv['name'] .' ('. $s['date'] .'): '. $s['message'] .'">'.($s['ok']==0?'up':'down').'</td>';
			}
			else {
				echo '<td></td>';
			}
		}
		echo '</tr>'.NEWLINE;
		$even_odd=!$even_odd;
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
