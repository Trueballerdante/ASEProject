<?php

include 'dbConnection.php';


$selectQuery = $_POST["query"];
$offset = $_POST["offset"];
$limit = $_POST["limit"];
$columnNames = $_POST["columnNames"];
$snSearchedDeviceType = $_POST["snSearchedDeviceType"];
$snSearchedDeviceTypeVal = $_POST["snSearchedDeviceTypeVal"];
$columnHeadersId = $_POST["columnHeadersId"]; 
$totalRecords = $_POST["totalRecords"];
$tableHeaders = array();
$tableColumnNames = array();

if($columnHeadersId == 1) {
	array_push($tableHeaders, "Manufacturer", "Serial Number", "Active/Inactive");
	array_push($tableColumnNames, "name", "serialNumber");
} elseif($columnHeadersId == 2) {
	array_push($tableHeaders, "Device Type", "Serial Number", "Active/Inactive");
	array_push($tableColumnNames, "name", "serialNumber");
} elseif($columnHeadersId == 3) {
	array_push($tableHeaders, "Device Type", "Manufacturer", "Active/Inactive");
	array_push($tableColumnNames, "name");
} else {
	array_push($tableHeaders, "Serial Number", "Active/Inactive");
	array_push($tableColumnNames, "serialNumber");
}


$selectQuery = str_replace("10", "$offset, $limit", $selectQuery);
$getSelectQueryResult=$dblink->query($selectQuery) or die("Something went wrong with $selectQuery");

//echo "$selectQuery";
echo '<table id="infoTable">';
	echo '<thead>';
	echo '<tr>';
	foreach($tableHeaders as $header) {
		echo '<th>'.$header.'</th>';
	}
	echo '<th>View Info</th>';
	echo '</tr>';
	echo '</thead>';
	
	echo '<tbody>';
	while($data=$getSelectQueryResult->fetch_array(MYSQLI_ASSOC)) {
		echo '<tr>';
		if($snSearchedDeviceType == 1) {
			echo '<td>'.$snSearchedDeviceTypeVal.'</td>';
		}
		foreach($tableColumnNames as $name) {
			echo '<td>'.$data[$name].'</td>';
		}
		if($data["active"]) {
			echo '<td>Active</td>';
		} else {
			echo '<td>Inactive</td>';
		}
		echo '<td><a href="viewDevice.php?dt='.$data['deviceTypeId'].'&did='.$data['id'].'">More Info</a></td>';
		echo '</tr>';
	}
	echo '</tbody>';	
echo '</table>';
/*
$displayLimit = $offset + $limit;
$displayOffset = $offset + 1;
echo "<p>Showing records $displayOffset - $displayLimit of $totalRecords</p>";
*/
?>
