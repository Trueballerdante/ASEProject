<?php
function dbconnect($name) {
	//echo "Connected to the database";
	$un="webuser";
	$pw="QB3UqLKkeMjBretY";
	$db=$name;
	$hostname="localhost";
	$dblink= new mysqli($hostname, $un, $pw, $db);
	return $dblink;
}

function checkForSN($sn, $deviceTables, $dblink) {
	$selectQuery = "";
	foreach($deviceTables as $value) {
		$selectQuery = 'select id from '.$value.' where serialNumber = "'.$sn.'"';
		$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");
			
		if($getSelectQueryResult->num_rows>0) {
			return true;
		} else {
			$selectQuery = "";
		}
	}
	return false;
}
?>