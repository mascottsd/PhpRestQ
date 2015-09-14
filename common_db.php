<?
$DB_CONN = mysql_connect('localhost', 'root', 'mypwd') or die("unable to mysql_connect to server");
mysql_query("CREATE DATABASE IF NOT EXISTS awesome_db");
mysql_query("USE awesome_db");

//------ GetLastInsertId - call after an insert to get the id of the inserted row
function GetLastInsertId() {
	global $DB_CONN;
	return mysql_insert_id($DB_CONN);
}

//-----------------------------------------------------------------------
// SQL HELPER FUNCTIONS
//-----------------------------------------------------------------------
/*------ GetSQLToken - how the value should be passed in to SQL */
function GetSQLToken($val)
{
	$valTxt = $val;
	if (is_string($val)) {
		$valTxt = "'$val'";
	} else if (is_bool($val)) {
		$valTxt = $val ? 1:0;
	} else if (is_null($val)) {
		$valTxt = 'null';
	}
	return $valTxt;
}

/*------ GetUpdateSQL
/ Returns the SQL for an UPDATE with the given data
/ Like an implode for associative arrays */
function GetUpdateSQL($dbTbl, $data, $where='')
{
	$setTxt = '';
	foreach ($data as $key => $val) {
		if ($setTxt) $setTxt .= ', ';
		$setTxt .= "$key=";
		$setTxt .= GetSQLToken($val);
	}
	$whereTxt = $where ? "WHERE $where" : '';
	return "UPDATE $dbTbl SET ($setTxt) $whereTxt";
}

/*------ GetInsertSQL
/ Returns the SQL for an INSERT with the given data
/ Like an implode for associative arrays */
function GetInsertSQL($dbTbl, $data)
{
	$cols = "";
	$valTxt = "";
	if ( isset($data[0]) ) {
		$rowTxt = "";
		foreach ($data as $i => $row) {
			$vals = '';
			foreach ($row as $key => $val) {
				if ( !$i ) $cols[] = $key;
				//if ($valTxt != '') $valTxt .= ', ';
				$vals[] = GetSQLToken( is_array($val) ? 'ArrayAsTxt' : $val );
			}
			$rowTxt[] = join(', ', $vals);
		}
		$valTxt = join('), (', $rowTxt);
		
	} else {
		$vals = '';
		foreach ($data as $key => $val) {
			$cols[] = $key;
			//if ($valTxt != '') $valTxt .= ', ';
			$vals[] = GetSQLToken( is_array($val) ? 'ArrayAsTxt' : $val );
		}
		$valTxt = join(', ', $vals);
	}
	$colTxt = join(', ', $cols);
	return "INSERT INTO $dbTbl ($colTxt) VALUES ($valTxt)";
}
?>
