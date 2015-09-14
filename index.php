<?
//IF GET, DELETE, PUT... find the id in the url
$url_elements = explode('/', $_SERVER['REQUEST_URI']); //PATH_INFO
$jobsIdx = -1;
for ($i=0; isset($url_elements[$i]); $i++) {
	if (strtolower($url_elements[$i]) == 'jobs')
		$jobsIdx = $i;
}
if ($jobsIdx == -1) {
	//echo "Invalid url: should have '/jobs' in the address";
	echo "<form method='POST' action='jobs'> URL <input name='url' /> <input type='submit' /></form>";
	exit;
}
//Get the ID if it as passed in the URL
$jobId = '';
if (isset($url_elements[$jobsIdx+1]))
	$jobId = $url_elements[$jobsIdx+1] + 0;


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
//echo "METHOD: $method<BR>";
$dbTbl = 'tbl_jobs';
switch($method) {
case 'POST':
	//Database insert...
	$data = parseIncomingParams();
	if ( empty($data['url']) ) {
		echo "Por favor, post a url amigo";
		
	} else {
		require_once("common_db.php"); //Connect to the DB
		mysql_query("CREATE TABLE IF NOT EXISTS $dbTbl (
			id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			url varchar(1024) NOT NULL,
			html text,
			created_at datetime,
			updated_at datetime)");
	
		$sqlTxt = "INSERT INTO $dbTbl (url) VALUES (\"". $data['url'] ."\")";
		$ok = mysql_query($sqlTxt);
		$jobId = $ok ? GetLastInsertId() : 0;
		header("HTTP/1.1 201 Created job #$jobId");
		echo $jobId;
	}
	break;
	
case 'PUT':
	if ( empty($jobId) ) {
		echo "Please give us a job id to update";
	} else {
		//Database update...
		require_once("common_db.php"); //Connect to the DB
		$sqlTxt = GetUpdateSQL($dbTbl, $data, "ID = $jobId");
		$ok = mysql_query($sqlTxt);
		header('HTTP/1.1 201 Updated - the request was successful');
	}
	break;
	
case 'GET':
	//Database request...
	require_once("common_db.php"); //Connect to the DB
	$sqlTxt = "SELECT * FROM $dbTbl";
	if ($jobId) $sqlTxt .= " WHERE ID=$jobId";
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
	require_once("common_db.php"); //Connect to the DB
	$sqlTxt = "DELETE FROM $dbTbl WHERE id=$id";
	$ok = mysql_query($sqlTxt);
	break;
	
default:
	$_response('Invalid Method', 405);
	break;
}


//------ parseIncomingParams
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