<?php
$dblink=dbconnect("equipment");

$validParameters = array("UpdateDevice", "sn", "deviceType", "manufacturer", "isActive", "newSN");

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
$updatedDeviceType=$_REQUEST['deviceType'];
$updatedManufacturer=$_REQUEST['manufacturer'];
$updatedIsActive=$_REQUEST['isActive'];
$updatedSN=$_REQUEST['newSN'];

if($sn==NULL) {
	header('Content-Type: application/json');
	header('HTTP/1.1 200 OK');
	$output[]="Status: NULL Data";
	$output[]="MSG: Serial Number must not be blank";
	$output[]="";
	$responseData=json_encode($output);
	echo $responseData;
	die();
} elseif(strlen($sn) != 32) {
	header('Content-Type: application/json');
	header('HTTP/1.1 200 OK');
	$output[]="Status: Invalid Data";
	$output[]="MSG: Serial Number must have a length of 32";
	$output[]="";
	$responseData=json_encode($output);
	echo $responseData;
	die();
} else {
	$existingDeviceTypeId;
	$existingManufacturerId;
	$existingIsActive;
	$existingDeviceId;
	

	$getDeviceTypeSql="Select * from `DeviceTypes`";
	$getDeviceTypeResult=$dblink->query($getDeviceTypeSql) or die("Something went wrong with $getDeviceTypeSql");

	$deviceTypes = array();
	$deviceTypeTables = array();
	while($data=$getDeviceTypeResult->fetch_array(MYSQLI_ASSOC)) {
		$deviceTypes[$data['id']]=$data['name'];
		$deviceTypeTables[$data['id']] = str_replace(" ", "_", $data['name']) . "s";
	}
	
	$getManufacturerSql="Select * from `Manufacturers`";
	$getManufacturerResult=$dblink->query($getManufacturerSql) or die("Something went wrong with $getManufacturerSql");

	$deviceManufacturers = array();
	while($data=$getManufacturerResult->fetch_array(MYSQLI_ASSOC)) {
		$deviceManufacturers[$data['id']]=$data['name'];
	}
	
	$device;
	foreach($deviceTypeTables as $key=>$value) {
		$selectQuery = "select * from $value where serialNumber = '$sn'";
		$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");

		if($dblink->affected_rows == 1) {		
			$device=$getSelectQueryResult->fetch_array(MYSQLI_ASSOC);
			$existingDeviceId = $device['id'];
			$existingDeviceTypeId = $device['deviceTypeId'];
			$existingManufacturerId = $device['manufacturerId'];
			$existingIsActive = $device['active'];
			
			$deviceFound = true;
			break;
		} else {
			$selectQuery = "";
		}
	}

	if($deviceFound) {
		$updatedDeviceType = strtolower(trim($updatedDeviceType));
		$updatedManufacturer = trim($updatedManufacturer);
		$updatedSN = strtolower(trim($updatedSN));
		$updatedIsActive = strtolower(trim($updatedIsActive));
		$addNewManufacturerQuery = "";
		$newTableQuery = "";
		$addDeviceQuery = "";
		$enterDeviceTypeQuery = '';
		$dtId;
		$mId;
		$active;
		$moveToNewTable = false;
		$validSn = true;
		$snErrorMessage;
		
		if($updatedDeviceType != "" || $updatedManufacturer != "" || $updatedSN != "" || $updatedIsActive != "") {
			$dtId = $existingDeviceTypeId;
			$mId = $existingManufacturerId;
			$active = $existingIsActive;
			$serialNumber = $sn;
			
			if($updatedManufacturer != NULL) {
				if($index = array_search($updatedManufacturer, $deviceManufacturers)) {
					$mId = $index;
				} else {
					$addNewManufacturerQuery = 'INSERT into Manufacturers (name) VALUES ("'.$updatedManufacturer.'")';
					$dblink->query($addNewManufacturerQuery) or die("Something went wrong with $addNewManufacturerQuery");
					$mId = $dblink->insert_id;
					$deviceManufacturers[$mId] = $updatedManufacturer;
				}
			}
			if($updatedDeviceType != NULL) {
				$moveToNewTable = true;
				if($index = array_search($updatedDeviceType, $deviceTypes)) {
					$dtId = $index;
				} else {
					$newTable = str_replace(" ", "_", $updatedDeviceType) . "s";

					$enterDeviceTypeQuery = 'INSERT into DeviceTypes (name) VALUES ("'.$updatedDeviceType.'")';
					$dblink->query($enterDeviceTypeQuery) or die("Something went wrong with $enterDeviceTypeQuery");
					$dtId = $dblink->insert_id;
					$deviceTypes[$dtId] = $updatedDeviceType;
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

				$deleteQuery = 'Delete from '.$deviceTypeTables[$existingDeviceTypeId].' where id = "'.$existingDeviceId.'"';
				$getDeleteQueryResult = $dblink->query($deleteQuery) or die("Something went wrong with $deleteQuery");

			}
			if($updatedSN != NULL) {
				if(strlen($updatedSN) != 32) {
					$validSn = false;
					header('Content-Type: application/json');
					header('HTTP/1.1 200 OK');
					$output[]="Status: NULL Data";
					$output[]="MSG: New Serial Number must have a length of 32";
					$output[]="";
					$responseData=json_encode($output);
					echo $responseData;
					die();
				} else {
					$checkSnQuery = "";
					foreach($deviceTypeTables as $value) {
						$checkSnQuery = 'select id from '.$value.' where serialNumber = "'.$updatedSN.'"';
						$getCheckSnQueryResult = $dblink->query($checkSnQuery) or die("Something went wrong with $checkSnQuery");

						if($getCheckSnQueryResult->num_rows>0) {
							$validSn = false;
							header('Content-Type: application/json');
							header('HTTP/1.1 200 OK');
							$output[]="Status: Invalid Data";
							$output[]="MSG: Serial Number already exists.";
							$output[]="";
							$responseData=json_encode($output);
							echo $responseData;
							die();
						} else {
							$checkSnQuery = "";
						}
					}

					$serialNumber = $updatedSN;
				}
			}
			if($updatedIsActive != NULL) {
				$updatedIsActive = strtolower(trim($updatedIsActive));
				if($updatedIsActive == "false" || $updatedIsActive == "true") {
					if($updatedIsActive == "true") {
						$active = 1;
					} else {
						$active = 0;
					}
				} else {
					header('Content-Type: application/json');
					header('HTTP/1.1 200 OK');
					$output[]="Status: Invalid Data";
					$output[]="MSG: isActive should be true or false.";
					$output[]="";
					$responseData=json_encode($output);
					echo $responseData;
					die();
				}
			}
			if($validSn) {
				if($moveToNewTable) {
					$insertQuery = 'INSERT into '.$deviceTypeTables[$dtId].' (manufacturerId, deviceTypeId, serialNumber, active) VALUES ';
					$insertQuery .= '('.$mId.', '.$dtId.', "'.$serialNumber.'", '.$active.')';
					$dblink->query($insertQuery) or die("Something went wrong with $insertQuery");
					$deviceId = $dblink->insert_id;
					
					$sql="Update files_link set deviceId = $deviceId, deviceTypeId = $dtId";
					$sql.=" where deviceId = $existingDeviceId AND deviceTypeId = $existingDeviceTypeId";
					$dblink->query($sql) or die("Something went wrong with $sql Message: ".mysqli_error($dblink));
					
				} else {
					$updateQuery = 'Update '.$deviceTypeTables[$dtId].' ';
					$updateQuery .= 'set manufacturerId = '.$mId.', serialNumber = "'.$serialNumber.'", active = '.$active.' ';
					$updateQuery .= 'where id = "'.$existingDeviceId.'"';
					$dblink->query($updateQuery) or die("Something went wrong with $updateQuery");
				}
				
				$activeStr = ($active) ? "Yes" : "No";
				$updatedDevice = array("Device Type: $deviceTypes[$dtId]","Manufacturer: $deviceManufacturers[$mId]","Serial Number: $serialNumber","Is Active: $activeStr");
				
				header('Content-Type: application/json');
				header('HTTP/1.1 200 OK');
				$output[]="Status: Update Successful";
				$output[]="MSG: The device was successfuly updated.";
				$output[]=$updatedDevice;
				$responseData=json_encode($output);
				echo $responseData;
				die();
			} else {
				echo '<p style="color:red;">'.$snErrorMessage.'</p>';
			}
			
		} else {
			header('Content-Type: application/json');
			header('HTTP/1.1 200 OK');
			$output[]="Status: NULL Data";
			$output[]="MSG: No update values given";
			$output[]="";
			$responseData=json_encode($output);
			echo $responseData;
		}
		
	} else {
		header('Content-Type: application/json');
		header('HTTP/1.1 200 OK');
		$output[]="Status: Cannot Delete";
		$output[]="MSG: Device SN: $sn not in database";
		$output[]="";
		$responseData=json_encode($output);
		echo $responseData;
	}
}
?>