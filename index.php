<?php

require_once "parse_files.inc.php";

header('Content-type: text/html');

$datestring=file_get_contents(LAST_TEST_DATE_FILE);
if( $datestring ) {
	$datestamp=convert_date_to_timestamp($datestring);
	$date_modified_nice=format_date($datestamp);
	$date_modified_http=get_http_date($datestamp);
	$date_expires_http=get_http_date($datestamp+60*30); //30 minutes

	header('Last-Modified: '.$date_modified_http);
	header('Cache-Control: max-age='.(60*30)); //30 minutes
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
<html lang="en"><head><title>Last test summary</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<?php
if( $date_expires_http ) echo '<meta http-equiv="Expires" content="'.$date_expires_http.'">'.NEWLINE;
?>
<link rel="stylesheet" type="text/css" href="style.css">
</head><body>
<div class="pagetimes">
<p class="generatedtime">Page generated at <span class="time"><?=$current_time?></span></p>
<p class="testtime">Last test executed at <span class="time"><?=$date_modified_nice?></span></p>
</div>
<?php

$LAST_ERROR='';
$serverlist=read_server_list();
$ERROR1=$LAST_ERROR;

$LAST_ERROR='';
$lastlog=read_global_last_log();
$ERROR2=$LAST_ERROR;

if( $serverlist && $lastlog ) {

	// Joining those two arrays into only one:
	foreach( $lastlog as $s ) {
		$servers[$s['id']]=$s+$serverlist[$s['id']];
		$status=read_server_status($s['id']);
		if( $status )
			$servers[$s['id']]+=$status;
	}

	// Printing the table
	?>
<div class="status"><table class="status">
<thead>
<tr>
<!--th class="id">ID</th-->
<th class="name">Name</th>
<th class="updown" title="Up/Down (fail count)">U/D</th>
<th class="last_success" title="Last time the host was up">Last time</th>
<th class="message">Last message</th>
</tr>
</thead>
<tbody>
<?php
	$even_odd=true;
	foreach( $servers as $s ) {
		echo '<tr class="'. ($even_odd?'evenline':'oddline') .' '. ($s['ok']==0?'success':'fail') . ' '.$s['type'] . ($s['important']?' important':'') .'">';
//		echo '<td class="id">'.$s['id'].'</td>';
		echo '<td class="name">'.$s['name'].'</td>';
		echo '<td class="updown">'.($s['ok']==0?'up':'down ('.$s['fail_count'].')').'</td>';
		echo '<td class="last_success">'.$s['last_success'].'</td>';
		echo '<td class="message"><a href="'. (INDIVIDUAL_LOG_DIR."/".$s['id']."/".LAST_INDIVIDUAL_LOG_FILE) .'">'. $s['message'] .'</a></td>';
		echo '</tr>'.NEWLINE;
		$even_odd=!$even_odd;
	}
	?>
</tbody>
</table></div>
<?php

} else {
	// BEGIN error printing code
	?>
<div class="error">
<p>Error reading files!</p>
<pre>
<?=$ERROR1?>
<?=$ERROR2?>
</pre>
</div>
<?php
	// END error printing code
}

?>

<div class="softwaredownload">
<p>You can add this to your server! Download <a href="servermon-1.0.tar.gz">servermon-1.0.tar.gz</a>.</p>
</div>
</body></html>
