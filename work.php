<!-- INITIALIZE THE PAGE SO flushed PHP ECHOs WILL APPEAR -->
<!DOCTYPE html><html><meta charset=utf-8 /></head><title>Processing Job Queue</title></head><body>
<div style='font-family:Verdana,sans-serif'>

<?php
//THIS IS INTENTED TO BE RUNNING ON THE SERVER...
require_once("common_db.php"); //Connect to the DB

//----------------------------------------------------------
//------ GetNextJob - return the next job to be done
//----------------------------------------------------------
function GetNextJob() {
	$res = mysql_query("SELECT * FROM tbl_jobs WHERE html IS NULL OR html = '' OR html = 'NOT DONE YET' ORDER BY ID");
	return $res ? mysql_fetch_array($res) : '';
}

//----------------------------------------------------------
//------ ScrapeJob - retrieve the html for a url
//----------------------------------------------------------
function ScrapeJob($url) {
	if (strpos($url, 'http') !== 0)
		$url = "http://$url" ;
	return file_get_contents($url);
}

//----------------------------------------------------------
//------ FinishJob - save the html for the job to the database
//----------------------------------------------------------
function FinishJob($id, $html) {
 	$html = htmlentities($html);
	//Could use a PUT call to the API as instead...
	$data['html'] = $html;
	$data['updated_at'] = Date("Y-m-d H:i:s",time()); //'GETDATE()';
	$sqlTxt = GetUpdateSQL('tbl_jobs', $data, "id = $id"); //"UPDATE tbl_jobs SET html=\"$html\" WHERE id=$id";
	return mysql_query($sqlTxt);
}

//------------------------------------------------------------
//RUN THE JOB QUEUE FOR THE NEXT URL THAT HAS'T BEEN DONE YET
$job = GetNextJob();
if ($job) {
	//TELL THE USER WHAT WE'RE DOING
	echo "Retrieving Job {$job['id']}: ". $job['url'] ."...<BR>";
	flush(); ob_flush(); //sleep(1);
	
	//SCRAPE THE PAGE CONTENTS
	$html = ScrapeJob( $job['url'] );
	
	//SAVE THE PAGE CONTENTS TO THE DATABASE
	FinishJob($job['id'], $html ? $html : '(EMPTY PAGE)');
}
//------------------------------------------------------------
?>

Job queue resting 5 seconds... <a href=''><font size='-1'>go now</font></a>
<script language="javascript">
setTimeout("document.location = 'work.php';", 5000);
</script>
