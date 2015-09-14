<?
require_once("common_db.php"); //DB_Connect & SQL helper functions

//All URLS have /jobs/ in them... ie. localhost/jobs/...
$url_elements = explode('/', $_SERVER['REQUEST_URI']); //PATH_INFO
$jobsIdx = -1;
for ($i=0; isset($url_elements[$i]); $i++) {
	if (strtolower($url_elements[$i]) == 'jobs')
		$jobsIdx = $i;
}
if ($jobsIdx == -1) {
	//Provide a form to add the URLs...
	//echo "Invalid url: should have '/jobs' in the address";
	echo "<div width='100%' style='color: #fff; background-color: #008; padding:10px; font-size:24px'> URL Fetcher </div>";
	echo "<BR><form method='POST' action='jobs'> URL <input name='url' /> <input type='submit' /></form>";
	exit;
}
//Get the ID if it as passed in the URL (GET, DELETE, PUT)
$jobParam = '';
if (isset($url_elements[$jobsIdx+1])) {
	$jobId = $url_elements[$jobsIdx+1] + 0;
	//In case they passed in a url within the url, put it back together (ie. /jobs/url/that/is/long)
	for ($i=$jobsIdx+1; isset($url_elements[$i]); $i++) {
		if ($jobParam) $jobParam .= '/';
		$jobParam .= $url_elements[$i];
	}
}


//GET THE METHOD FOR ROUTING
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
	if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
		$method = 'DELETE';
	} else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
		$method = 'PUT';
	} else {
		throw new Exception("Unexpected Header");
	}
}

//curl -v -X POST http://localhost/MassDrop/RestJobQ/jobs -d '{"url":"google.com" }

//ROUTE THE REQUEST TO THE RIGHT PLACE
$dbTbl = 'tbl_jobs';
switch($method) {
case 'POST':
	//Database insert...
	$data = parseIncomingParams();
	if ( empty($data['url']) )
		$data['url'] = $jobParam;
	
	// <URL ERROR CHECKING HERE IF NECESSARY>
	
	if ( empty($data['url']) ) {
		echo "Por favor, post a url amigo"; //URL should pass in ar url as {'url': 'www.google.com'} or in the URL itself /jobs/www.google.com
		
	} else {
		DB_Connect();
		mysql_query("CREATE TABLE IF NOT EXISTS $dbTbl (
			id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			url varchar(1024) NOT NULL,
			html text,
			created_at datetime,
			updated_at datetime)");
		
		//Add the URL to the job queue
		$upData['url'] = $data['url'];
		$upData['html'] = 'NOT DONE YET';
		$upData['updated_at'] = Date("Y-m-d H:i:s",time()); //'GETDATE()';
		$upData['created_at'] = $upData['updated_at'];
		$sqlTxt = GetInsertSQL($dbTbl, $upData); //"INSERT INTO $dbTbl (url) VALUES (\"". $data['url'] ."\")";
		$ok = mysql_query($sqlTxt);
		$jobId = $ok ? GetLastInsertId() : 0;
		header("HTTP/1.1 201 Created job #$jobId");
		echo $jobId;
	}
	break;
	
case 'PUT': //This doesn't really need to be exposed
	//Database update...
	if ( empty($jobId) ) {
		echo "Please give us a job id to update"; //URL should be /jobs/123
	} else {
		DB_Connect();
		$data = parseIncomingParams();
		$data['updated_at'] = Date("Y-m-d H:i:s",time()); //'GETDATE()';
		$sqlTxt = GetUpdateSQL($dbTbl, $data, "id = $jobId");
		$ok = mysql_query($sqlTxt);
		header('HTTP/1.1 201 Updated - the request was successful');
	}
	break;
	
case 'GET':
	if ( empty($jobId) ) {
		echo "URL should be .../jobs/id";
		break;
	}
	//Database request...
	DB_Connect();
	$sqlTxt = "SELECT * FROM $dbTbl";
	if ( !empty($jobId) ) $sqlTxt .= " WHERE id=$jobId";
	$res = mysql_query($sqlTxt);
	if (!$res) {
		echo $jobId ? "[Job $jobId not found]" : "No jobs found";
	} else {
		$jobQ = array();
		while ($row = mysql_fetch_array($res) ) {
			//only keep the associative fields (not the indexed ones)
			for ($i=0; isset($row[$i]); $i++)
				unset($row[$i]);
			//reformat the html ( &lt; becomes <  etc...)
			//$row['html'] = html_entity_decode( $row['html'] );
			$jobQ[] = $row;
		}
		echo json_encode($jobQ);
	}
	break;
	
case 'DELETE':
	DB_Connect();
	$sqlTxt = "DELETE FROM $dbTbl WHERE id=$id";
	$ok = mysql_query($sqlTxt);
	break;
	
default:
	$_response('Invalid Method', 405);
	break;
}


//------ parseIncomingParams - put the incoming data in an object
function parseIncomingParams() {
	$parameters = array();

	// first of all, pull the GET vars
	if (isset($_SERVER['QUERY_STRING'])) {
		parse_str($_SERVER['QUERY_STRING'], $parameters);
	}

	// now how about PUT/POST bodies? These override what we got from GET
	$body = file_get_contents("php://input");
	$content_type = false;
	if(isset($_SERVER['CONTENT_TYPE'])) {
		$content_type = $_SERVER['CONTENT_TYPE'];
	}
	switch($content_type) {
		case "application/json":
			$body_params = json_decode($body);
			if($body_params) {
				foreach($body_params as $param_name => $param_value) {
					$parameters[$param_name] = $param_value;
				}
			}
			//$this->format = "json";
			break;
		case "application/x-www-form-urlencoded":
			parse_str($body, $postvars);
			foreach($postvars as $field => $value) {
				$parameters[$field] = $value;

			}
			//$this->format = "html";
			break;
		default:
			// we could parse other supported formats here
			break;
	}
	return $parameters;
}
?>