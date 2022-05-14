<?php
$dblink=dbconnect("equipment");

$validParameters = array("AddDevice", "deviceType", "manufacturer", "sn");

foreach($_REQUEST as $key=>$param) {
	if(!in_array($key, $validParameters)) {
		header('HTTP/1.1 200 OK');
		$output[]="Status: Invalid Parameter";
		$output[]="MSG: An invalid parameter was given: $key";
		$output[]="";
		$responseData=json_encode($output);
		echo $responseData;
		die();
	}
}

$sn=$_REQUEST['sn'];
$deviceType=$_REQUEST['deviceType'];
$manufacturer=$_REQUEST['manufacturer'];

if($deviceType == NULL || $manufacturer == NULL || $sn == NULL) {
	header('Content-Type: application/json');
	header('HTTP/1.1 200 OK');
	$output[]="Status: NULL Data";
	$output[]="MSG: Device Type, Manufacturer, and Serial Number must be given.";
	$output[]="";
	$responseData=json_encode($output);
	echo $responseData;
	die();
} else {
	$deviceType = strtolower(trim($deviceType));
	$manufacturer = trim($manufacturer);
	$sn = strtolower(trim($sn));
	
	$getDeviceTypeSql="Select * from `DeviceTypes`";
	$getDeviceTypeResult=$dblink->query($getDeviceTypeSql) or die("Something went wrong with $getDeviceTypeSql");

	$deviceTypes = array();
	$deviceTypeTables = array();
	
	while($data=$getDeviceTypeResult->fetch_array(MYSQLI_ASSOC)) {
		$deviceTypes[$data['id']]=$data['name'];
		$deviceTypeTables[$data['id']] = str_replace(" ", "_", $data['name']) . "s";
	}

	if(strlen($sn) != 32) {
		header('Content-Type: application/json');
		header('HTTP/1.1 200 OK');
		$output[]="Status: Invalid Data";
		$output[]="MSG: Serial Number must have a length of 32.";
		$output[]="";
		$responseData=json_encode($output);
		echo $responseData;
		die();
	} else if(checkForSN($sn, $deviceTypeTables, $dblink)) {
		header('Content-Type: application/json');
		header('HTTP/1.1 200 OK');
		$output[]="Status: Invalid Data";
		$output[]="MSG: Serial Number already exists.";
		$output[]="";
		$responseData=json_encode($output);
		echo $responseData;
		die();
	} else {
		$addNewManufacturerQuery = "";
		$newTableQuery = "";
		$addDeviceQuery = "";
		$enterDeviceTypeQuery = '';
		$dtId;
		$mId;
	
		$getManufacturerSql="Select * from `Manufacturers`";
		$getManufacturerResult=$dblink->query($getManufacturerSql) or die("Something went wrong with $getManufacturerSql");

		$deviceManufacturers = array();
		while($data=$getManufacturerResult->fetch_array(MYSQLI_ASSOC)) {
			$deviceManufacturers[$data['id']]=$data['name'];
		}
		
		if($index = array_search(strtolower($manufacturer), array_map('strtolower', $deviceManufacturers))) {
			$mId = $index;
		} else {
			$addNewManufacturerQuery = 'INSERT into Manufacturers (name) VALUES ("'.$manufacturer.'")';
			$dblink->query($addNewManufacturerQuery) or die("Something went wrong with $addNewManufacturerQuery");
			$mId = $dblink->insert_id;
			$deviceManufacturers[$mId] = $manufacturer;
		}
		
		if($index = array_search($deviceType, $deviceTypes)) {
			$dtId = $index;
		} else {
			$newTable = str_replace(" ", "_", $deviceType) . "s";
					
			$enterDeviceTypeQuery = 'INSERT into DeviceTypes (name) VALUES ("'.$deviceType.'")';
			$dblink->query($enterDeviceTypeQuery) or die("Something went wrong with $enterDeviceTypeQuery");
			$dtId = $dblink->insert_id;
			$deviceTypes[$dtId] = $enteredDeviceType;
			$deviceTypeTables[$dtId] = $newTable;
					
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
					
		}
		
		$addDeviceQuery = 'INSERT into '.$deviceTypeTables[$dtId].' (manufacturerId, deviceTypeId, serialNumber) VALUES ';
		$addDeviceQuery .= '('.$mId.', '.$dtId.', "'.$sn.'")';
		$dblink->query($addDeviceQuery) or die("Something went wrong with $addDeviceQuery");
		
		$addedDevice = array("Device Type: $deviceType","Manufacturer: $manufacturer","Serial Number: $sn");
		
		header('Content-Type: application/json');
		header('HTTP/1.1 200 OK');
		$output[]="Status: Add Successful";
		$output[]="MSG: The device was succesfully added.";
		$output[]=$addedDevice;
		$responseData=json_encode($output);
		echo $responseData;
		die();
	}
	
}

?>