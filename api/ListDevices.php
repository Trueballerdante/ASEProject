<?php
$dblink=dbconnect("equipment");

$validParameters = array("ListDevices", "deviceType", "manufacturer", "page");

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

$deviceType=$_REQUEST['deviceType'];
$manufacturer=$_REQUEST['manufacturer'];
$page=$_REQUEST['page'];
	
if($deviceType == NULL && $manufacturer == NULL) {
	header('Content-Type: application/json');
	header('HTTP/1.1 200 OK');
	$output[]="Status: NULL Data";
	$output[]="MSG: Device Type and/or Manufacturer must be given.";
	$output[]="";
	$responseData=json_encode($output);
	echo $responseData;
	die();
} else {
	
	//check if val is non-integer, negative, or 0
	if($page != NULL) {
		$isValid = true;
		if(!is_numeric($page)) {
			$isValid = false;
		} else {
			if(intval($page) == 0) {
				$isValid = false;
			} else {
				$pageInt = intval($page);
				if($pageInt < 1) {
					$isValid = false;
				}
			}
			
		}
		if(!$isValid) {
			header('Content-Type: application/json');
			header('HTTP/1.1 200 OK');
			$output[]="Status: Invalid Data";
			$output[]="MSG: Page value must be a positive integer.";
			$output[]="";
			$responseData=json_encode($output);
			echo $responseData;
			die();
		}
	}
	
	$limit = 10;
	$offset;
	if($page == NULL || intval($page) == 1) {
		$offset = 0;
	} else {
		$offset = (intval($page) - 1) * $limit;
	}
	
	$getDeviceTypeSql="Select * from `DeviceTypes`";
	$getDeviceTypeResult=$dblink->query($getDeviceTypeSql) or die("Something went wrong with $getDeviceTypeSql");
	$getManufacturerSql="Select * from `Manufacturers`";
	$getManufacturerResult=$dblink->query($getManufacturerSql) or die("Something went wrong with $getManufacturerSql");

	$deviceTypes = array();
	$deviceManufacturers = array();
	$deviceTypeTables = array();

	while($data=$getDeviceTypeResult->fetch_array(MYSQLI_ASSOC)) {
		$deviceTypes[$data['id']]=$data['name'];
		$deviceTypeTables[$data['id']] = str_replace(" ", "_", $data['name']) . "s";
	}

	while($data=$getManufacturerResult->fetch_array(MYSQLI_ASSOC)) {
		$deviceManufacturers[$data['id']]=$data['name'];
	}
	
	if($deviceType != NULL && $manufacturer != NULL) {
		$deviceType = strtolower($deviceType);
		$deviceId = array_search($deviceType, $deviceTypes);
		$manufacturerId = array_search(strtolower($manufacturer), array_map('strtolower', $deviceManufacturers));
		if($deviceId && $manufacturerId) {
			$deviceTypeTable = $deviceTypeTables[$deviceId];
			$selectQuery = 'select id, deviceTypeId, serialNumber, active ';
			$selectQuery .= 'from '.$deviceTypeTable.' as x';
			$selectQuery .= ' where x.ManufacturerId = '.$manufacturerId. ' limit '.$offset.', '.$limit;
		
			$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");
			
			$devices = array();
			while($data=$getSelectQueryResult->fetch_array(MYSQLI_ASSOC)) {
				$device = array();
				$device[]="Serial Number: ".$data['serialNumber'];
				$isActive = ($data['active'] == 1) ? ("Yes") : ("No");
				$device[]="Is Active: ".$isActive;
				$devices[] = $device;
			}
			
			header('Content-Type: application/json');
			header('HTTP/1.1 200 OK');
			$output[]="Status: OK";
			$output[]="MSG: ";
			$str = "$manufacturer $deviceType".s;
			//$output[ucwords($str)]=$devices;
			$output[]=$devices;
			$responseData=json_encode($output);
			echo $responseData;
		} else {
			if(!$deviceId) {
				header('Content-Type: application/json');
				header('HTTP/1.1 200 OK');
				$output[]="Status: Not Found";
				$output[]="MSG: Device Type: $deviceType not in database";
				$output[]="";
				$responseData=json_encode($output);
				echo $responseData;
			} else {
				header('Content-Type: application/json');
				header('HTTP/1.1 200 OK');
				$output[]="Status: Not Found";
				$output[]="MSG: Manufacturer: $manufacturer not in database";
				$output[]="";
				$responseData=json_encode($output);
				echo $responseData;
			}
		}
		
	} elseif($deviceType != NULL) {
		$deviceType = strtolower($deviceType);
		if($deviceId = array_search($deviceType, $deviceTypes)) {
			$deviceTypeTable = $deviceTypeTables[$deviceId];
			$selectQuery = 'select name, serialNumber, active';
			$selectQuery .= ' from '.$deviceTypeTable.' as x ';
			$selectQuery .=  'inner join Manufacturers as m on x.manufacturerId = m.id limit '.$offset.', '.$limit;

			$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");
			
			$devices = array();
			while($data=$getSelectQueryResult->fetch_array(MYSQLI_ASSOC)) {
				$device = array();
				$device[]="Manufacturer: ".$data['name'];
				$device[]="Serial Number: ".$data['serialNumber'];
				$isActive = ($data['active'] == 1) ? ("Yes") : ("No");
				$device[]="Is Active: ".$isActive;
				$devices[] = $device;
			}
			
			header('Content-Type: application/json');
			header('HTTP/1.1 200 OK');
			$output[]="Status: OK";
			$output[]="MSG: ";
			$str = $deviceType."s";
			//$output[ucwords($str)]=$devices;
			$output[]=$devices;
			$responseData=json_encode($output);
			echo $responseData;
		} else {
			header('Content-Type: application/json');
			header('HTTP/1.1 200 OK');
			$output[]="Status: Not Found";
			$output[]="MSG: Device Type: $deviceType not in database";
			$output[]="";
			$responseData=json_encode($output);
			echo $responseData;
		}
	} elseif($manufacturer != NULL) {
		if($manufacturerId = array_search(strtolower($manufacturer), array_map('strtolower', $deviceManufacturers))) {
			$len = count($deviceTypeTables);
			$selectQuery = "";
			foreach($deviceTypeTables as $key=>$value) {
				$len -= 1;
				$selectQuery .= 'select serialNumber, name, active from '.$value.' as x inner join DeviceTypes as y on y.id = x.deviceTypeId where x.manufacturerId = '.$manufacturerId;
				if($len > 0) {
					$selectQuery .= ' union ';
				}
			}

			$selectQuery .= ' limit '.$offset.', '.$limit;

			$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");
			
			$devices = array();
			while($data=$getSelectQueryResult->fetch_array(MYSQLI_ASSOC)) {
				$device = array();
				$device[]="Device Type: ".$data['name'];
				$device[]="Serial Number: ".$data['serialNumber'];
				$isActive = ($data['active'] == 1) ? ("Yes") : ("No");
				$device[]="Is Active: ".$isActive;
				$devices[] = $device;
			}
			
			header('Content-Type: application/json');
			header('HTTP/1.1 200 OK');
			$output[]="Status: OK";
			$output[]="MSG: ";
			//$output[ucwords($manufacturer).' Devices']=$devices;
			$output[]=$devices;
			$responseData=json_encode($output);
			echo $responseData;
		} else {
			header('Content-Type: application/json');
			header('HTTP/1.1 200 OK');
			$output[]="Status: Not Found";
			$output[]="MSG: Manufacturer: $manufacturer not in database";
			$output[]="";
			$responseData=json_encode($output);
			echo $responseData;
		}
	}
}
?>