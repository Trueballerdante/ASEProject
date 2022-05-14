<?php
//session_start();
function redirect ($uri) 
{ ?>
	<script type="text/javascript">
	<!--
	document.location.href="<?php echo $uri; ?>";
	-->
	</script>
<?php die;}

include 'dbConnection.php';

echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<title>Equipment</title>';
echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>';
echo '</head>';
echo '<body>';

$getDeviceTypeSql="Select * from `DeviceTypes`";
$getDeviceTypeResult=$dblink->query($getDeviceTypeSql) or die("Something went wrong with $getDeviceTypeSql");
$getManufacturerSql="Select * from `Manufacturers`";
$getManufacturerResult=$dblink->query($getManufacturerSql) or die("Something went wrong with $getManufacturerSql");

$selectQueryType;

$deviceTypes = array();
$deviceManufacturers = array();
$deviceTypeTables = array();

$deviceTypes[0]='None';
$deviceManufacturers[0]='None';

while($data=$getDeviceTypeResult->fetch_array(MYSQLI_ASSOC)) {
	$deviceTypes[$data['id']]=$data['name'];
	$deviceTypeTables[$data['id']] = str_replace(" ", "_", $data['name']) . "s";
}

while($data=$getManufacturerResult->fetch_array(MYSQLI_ASSOC)) {
	$deviceManufacturers[$data['id']]=$data['name'];
}

if(!isset($_POST['submit'])) {
	
	//Search for a device
	
	echo "<p>Search Devices</p>";
	echo '<form method="post" action="">';
	echo '<div>';
		echo '<p>Select device type to query by:</p>';
		echo '<select name="device">';
		foreach($deviceTypes as $key=>$value) {
			echo '<option value="'.$key.'">'.$value.'</option>';
		}
		echo '</select>';
	echo '</div>';
	
	echo '<div>';
		echo '<p>Select manufacturer to query by:</p>';
		echo '<select name="manufacturer">';
		foreach($deviceManufacturers as $key=>$value) {
			echo '<option value="'.$key.'">'.$value.'</option>';
		}
		echo '</select>';
	echo '</div>';
	
	echo '<div>';
		echo '<p>Enter serial number to query by:</p>';
		echo '<input type="text" id="serialNumber" name="serialNumber">';
	echo '</div>';

	echo '<div>';
		echo '<button type="submit" name="submit" value="lookUp">Submit</button>';
	echo '</div>';
	echo '</form>';
	
	// Add a new device
	
	echo "<p>Add New Device</p>";
	echo '<form method="post" action="">';
	echo '<div>';
		echo '<p>Enter Device Type:</p>';
		echo '<input type="text" id="addedDeviceType" name="addedDeviceType">';
		echo '<p>Enter Manufacturer:</p>';
		echo '<input type="text" id="addedManufacturer" name="addedManufacturer">';
		echo '<p>Enter Serial Number:</p>';
		echo '<input type="text" id="addedSerialNumber" name="addedSerialNumber">';
		echo '<div>';
			echo '<button type="submit" name="add" value="AddDevice">Add Device</button>';
		echo '</div>';
	echo '</div>';
	echo '</form>';
	
	if(isset($_POST['add']) && $_POST['add']=="AddDevice") {
		$enteredDeviceType = strtolower(trim($_POST['addedDeviceType']));
		$enteredManufacturer = trim($_POST['addedManufacturer']);
		$enteredSN = strtolower(trim($_POST['addedSerialNumber']));
		 
		
		if($enteredDeviceType != "" && $enteredManufacturer != "" && $enteredSN != "") {
			$addNewManufacturerQuery = "";
			$newTableQuery = "";
			$addDeviceQuery = "";
			$enterDeviceTypeQuery = '';
			$dtId;
			$mId;
			
			if(strlen($enteredSN) != 32) {
				echo '<p style="color:red;">Error, serial number must have a length of 32</p>';
			} else if(checkForSN($enteredSN, $deviceTypeTables, $dblink)) {
				echo '<p style="color:red;">A device with the entered serial number already exists.</p>';
			} else {
				//echo '<p>The SN doesnt exist</p>';

				// check if manufacturer already exists
				if($index = array_search($enteredManufacturer, $deviceManufacturers)) {
					echo '<p>Manufacturer exists at index '.$index.'</p>';
					$mId = $index;
				} else {
					echo '<p>Manufacturer does not exist</p>';
					$addNewManufacturerQuery = 'INSERT into Manufacturers (name) VALUES ("'.$enteredManufacturer.'")';
					$dblink->query($addNewManufacturerQuery) or die("Something went wrong with $addNewManufacturerQuery");
					$mId = $dblink->insert_id;
					$deviceManufacturers[$mId] = $enteredManufacturer;
					echo '<p>'.$addNewManufacturerQuery.'</p>';
				}
				// check if device type already exists
				if($index = array_search($enteredDeviceType, $deviceTypes)) {
					//echo '<p>Device Type exists at index '.$index.'</p>';
					$dtId = $index;
				} else {
					//echo '<p>Device Type does not exist</p>';
					$newTable = str_replace(" ", "_", $enteredDeviceType) . "s";
					//echo '<p>Created table '.$newTable.' for device type '.$enteredDeviceType.'</p>';
					
					$enterDeviceTypeQuery = 'INSERT into DeviceTypes (name) VALUES ("'.$enteredDeviceType.'")';
					$dblink->query($enterDeviceTypeQuery) or die("Something went wrong with $enterDeviceTypeQuery");
					$dtId = $dblink->insert_id;
					$deviceTypes[$dtId] = $enteredDeviceType;
					$deviceTypeTables[$dtId] = $newTable;
					//echo '<p>'.$enterDeviceTypeQuery.'</p>';
					
					$newTableQuery = 'create table '.$newTable.' (';
					$newTableQuery .= 'id int AUTO_INCREMENT, ';
					$newTableQuery .= 'manufacturerId int(2) NOT NULL, ';
					$newTableQuery .= 'deviceTypeId int(2) NOT NULL, ';
					$newTableQuery .= 'serialNumber varchar(32) NOT NULL UNIQUE, ';
					$newTableQuery .= 'active boolean NOT NULL DEFAULT 1, ';
					$newTableQuery .= 'PRIMARY KEY (id), ';
					$newTableQuery .= 'FOREIGN KEY (deviceTypeId) REFERENCES DeviceTypes(id), ';
					$newTableQuery .= 'FOREIGN KEY (manufacturerId) REFERENCES Manufacturers(id)';
					$newTableQuery .= ' )';
					$dblink->query($newTableQuery) or die('Something went wrong with '. $newTableQuery);
					//echo '<p>Error: '.mysqli_error($dblink).'</p>';
					//echo '<p>'.$newTableQuery.'</p>';
				}
				
				// query to add the new device to a device table.
				$addDeviceQuery = 'INSERT into '.$deviceTypeTables[$dtId].' (manufacturerId, deviceTypeId, serialNumber) VALUES ';
				$addDeviceQuery .= '('.$mId.', '.$dtId.', "'.$enteredSN.'")';
				$dblink->query($addDeviceQuery) or die("Something went wrong with $addDeviceQuery");
				//echo '<p>'.$addDeviceQuery.'</p>';
				
				echo '<p>Added device: <br></p>';
				echo '<p>Device Type: '.$enteredDeviceType.', Manufacturer: '.$enteredManufacturer.', Serial Number: '.$enteredSN.'</p>';
				
				redirect("https://ec2-3-143-241-117.us-east-2.compute.amazonaws.com");
			}
		} else {
			echo '<p style="color:red;">Error, not all fields where filled</p>';
		}
	}
	
}
if(isset($_POST['submit']) && $_POST['submit']=="lookUp") {
	
	$device=$_POST['device'];
	$chosenDevice = $deviceTypes[$device];
	$manufacturer=$_POST['manufacturer'];
	$chosenManufacturer = $deviceManufacturers[$manufacturer];
	$chosenSerialNumber=$_POST['serialNumber'];
	$selectQuery = "";
	$countQuery;
	$tableHeaders = array();
	$tableColumnNames = array();
	$snSearchedDeviceType = false;
	$snSearchedDeviceTypeVal = "";
	$columnHeadersId;
	$totalRecords = 0;
	/*
	echo '<p>Device chosen: '.$chosenDevice.'</p>';
	echo '<p>Manufacturer chosen: '.$chosenManufacturer.'</p>';
	echo '<p>Serial Number entered: '.$chosenSerialNumber.'</p>';
	*/
	if($chosenDevice == 'None' && $chosenManufacturer == 'None' && $chosenSerialNumber == '') {
		echo '<p style="color:red;">No query given</p>';
	} else if($chosenManufacturer == 'None' && $chosenSerialNumber == '') {
		echo '<p>Device chosen: '.$chosenDevice.'</p>';
		$selectQueryType = 1;
		
		$deviceTypeTable = $deviceTypeTables[$device];
		$selectQuery = 'select x.id, deviceTypeId, name, serialNumber, active';
		$selectQuery .= ' from '.$deviceTypeTable.' as x ';
		$selectQuery .=  'inner join Manufacturers as m on x.manufacturerId = m.id limit 10';
		
		$countQuery = 'select count(id) from '.$deviceTypeTable.' as x ';
		$countQueryResult = $dblink->query($countQuery) or die("Something went wrong with $countQuery");
		$totalRecords = $countQueryResult->fetch_row()[0];
		
		//echo '<p>'.$selectQuery.'</p>';
		$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");
		
		$columnHeadersId = 1;
		array_push($tableHeaders, "Manufacturer", "Serial Number", "Active/Inactive");
		array_push($tableColumnNames, "name", "serialNumber");
		
	} else if($chosenDevice == 'None' && $chosenSerialNumber == '') {
		echo '<p>Manufacturer chosen: '.$chosenManufacturer.'</p>';
		$selectQueryType = 2;
		
		$getManufacturerIdQuery = 'select id from Manufacturers where name = "'.$chosenManufacturer.'"';
		$manufacturerIdData = $dblink->query($getManufacturerIdQuery) or die("Something went wrong with $getManufacturerIdQuery");
		$manufacturerIdDataArray = $manufacturerIdData->fetch_array(MYSQLI_ASSOC);
		$manufacturerId = $manufacturerIdDataArray["id"];
		$len = count($deviceTypeTables);
		foreach($deviceTypeTables as $key=>$value) {
			$len -= 1;
			$selectQuery .= 'select x.id, deviceTypeId, x.serialNumber, y.name, active from '.$value.' as x inner join DeviceTypes as y on y.id = x.deviceTypeId where x.manufacturerId = '.$manufacturerId;
			if($len > 0) {
				$selectQuery .= ' union ';
			}
			
			$countQuery = "select count(id) from $value where manufacturerId = $manufacturerId";
			$countQueryResult = $dblink->query($countQuery) or die("Something went wrong with $countQuery");
			$totalRecords += $countQueryResult->fetch_row()[0];
		}
		
		$selectQuery .= ' limit 10';
		
		//echo '<p>'.$selectQuery.'</p>';
		$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");
		
		
		
		$columnHeadersId = 2;
		array_push($tableHeaders, "Device Type", "Serial Number", "Active/Inactive");
		array_push($tableColumnNames, "name", "serialNumber");
		
	//} else if($chosenDevice == 'None' && $chosenManufacturer == 'None') {
	} else if($chosenSerialNumber != '') {
		echo '<p>Serial Number entered: '.$chosenSerialNumber.'</p>';
		$selectQueryType = 3;
		
		foreach($deviceTypeTables as $key=>$value) {
			$selectQuery = 'select x.id, deviceTypeId, name, active ';
			$selectQuery .= 'from Manufacturers as m ';
			$selectQuery .= 'inner join '.$value.' as x on m.id = x.ManufacturerId ';
			$selectQuery .= 'where x.serialNumber = "'.$chosenSerialNumber.'"';
			$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");
			
			//if($data=$getSelectQueryResult->fetch_array(MYSQLI_ASSOC)) {
			if($dblink->affected_rows == 1) {
				//echo '<p>'.$selectQuery.'</p>';
				
				$columnHeadersId = 3;
				array_push($tableHeaders, "Device Type", "Manufacturer", "Active/Inactive");
				array_push($tableColumnNames, "name");
				$snSearchedDeviceType = true;
				$snSearchedDeviceTypeVal = $deviceTypes[$key];
				
				$totalRecords = 1;
				break;
			} else {
				$selectQuery = "";
			}
		}
		
		
		
	} else if($chosenSerialNumber == '') {
		echo '<p>Device chosen: '.$chosenDevice.'</p>';
		echo '<p>Manufacturer chosen: '.$chosenManufacturer.'</p>';
		$selectQueryType = 4;
		
		$getManufacturerIdQuery = 'select id from Manufacturers where name = "'.$chosenManufacturer.'"';
		$manufacturerIdData = $dblink->query($getManufacturerIdQuery) or die("Something went wrong with $getManufacturerIdQuery");
		$manufacturerIdDataArray = $manufacturerIdData->fetch_array(MYSQLI_ASSOC);
		$manufacturerId = $manufacturerIdDataArray["id"];
		$deviceTypeTable = $deviceTypeTables[$device];
		$selectQuery = 'select id, deviceTypeId, serialNumber, active ';
		$selectQuery .= 'from '.$deviceTypeTable.' as x';
		$selectQuery .= ' where x.ManufacturerId = '.$manufacturerId. ' limit 10';
		
		//echo '<p>'.$selectQuery.'</p>';
		$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");
		
		$countQuery = "select count(id) from $deviceTypeTable as x where x.manufacturerId = $manufacturerId";
		$countQueryResult = $dblink->query($countQuery) or die("Something went wrong with $countQuery");
		$totalRecords = $countQueryResult->fetch_row()[0];
		
		$columnHeadersId = 4;
		array_push($tableHeaders, "Serial Number", "Active/Inactive");
		array_push($tableColumnNames, "serialNumber");
	}
	
	echo "<div id = table>";
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
		if($snSearchedDeviceType) {
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
	echo "</div>";
	
	echo "<p>Total Records: $totalRecords</p>";
	
	echo '<button id = "previous">Previous</button>';
	echo '<button id = "next">Next</button>';
	
	$snSearchedDeviceTypeNum;
	if($snSearchedDeviceType) {
		$snSearchedDeviceTypeNum = 1;
	} else {
		$snSearchedDeviceTypeNum = 0;
	}
	
	echo "<script>";
	echo "$(document).ready(function() {";
		echo "var offset = 0;";
		echo "var limit = 10;";
		echo "$('#next').click(function() {";
			echo "offset += 10;";
			echo "if(offset < $totalRecords)";
			echo "	$('#table').load('processQuerys.php', {";
			echo "	totalRecords: $totalRecords,";
			echo "	offset: offset,";
			echo "	limit: limit,";
			echo "	snSearchedDeviceType: $snSearchedDeviceTypeNum,";
			echo "	snSearchedDeviceTypeVal: '$snSearchedDeviceTypeVal',";
			echo "	columnHeadersId: '$columnHeadersId',";
			echo "	query: '$selectQuery'";
			echo "	});";
		echo "});";
		echo "$('#previous').click(function() {";
			echo "if(offset > 0)";
			echo "	offset -= 10;";
			echo "	$('#table').load('processQuerys.php', {";
			echo "	totalRecords: $totalRecords,";
			echo "	offset: offset,";
			echo "	limit: limit,";
			echo "	snSearchedDeviceType: $snSearchedDeviceTypeNum,";
			echo "	snSearchedDeviceTypeVal: '$snSearchedDeviceTypeVal',";
			echo "	columnHeadersId: '$columnHeadersId',";
			echo "	query: '$selectQuery'";
			echo "	});";
		echo "});";
	echo "});";
	echo "</script>";
	
	/*
	echo '<script>';  
 	echo "$(document).ready(function($) {";
	echo "$('#infoTable').DataTable( {";
	echo "});";
	echo "});";  
 	echo '</script>'; 
	*/
	
}

// functions 

// select functions

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
echo '</body>';
echo '</html>';

?>
