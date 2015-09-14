<?
$DB_CONN = mysql_connect('localhost', 'root', 'mypwd') or die("unable to mysql_connect to server");
mysql_query("CREATE DATABASE IF NOT EXISTS awesome_db");
mysql_query("USE awesome_db");

//------ GetLastInsertId - call after an insert to get the id of the inserted row
function GetLastInsertId() {
	global $DB_CONN;
	return mysql_insert_id($DB_CONN);
}


/*------ GetUpdateSQL - like an implode for associative arrays */
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

?>
