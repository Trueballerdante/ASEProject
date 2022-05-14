<?php
$dblink=dbconnect("equipment");
//$sn=$_REQUEST['sn'];
$deviceType=$_REQUEST['deviceType'];
$manufacturer=$_REQUEST['manufacturer'];

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
		
	} elseif($deviceType != NULL) {
		$deviceType = strtolower($deviceType);
		if($deviceId = array_search($deviceType, $deviceTypes)) {
			$deviceTypeTable = $deviceTypeTables[$deviceId];
			$selectQuery = 'select name, serialNumber, active';
			$selectQuery .= ' from '.$deviceTypeTable.' as x ';
			$selectQuery .=  'inner join Manufacturers as m on x.manufacturerId = m.id limit 10';

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
			$output[$deviceType."s"]=$devices;
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
		strtolower($search), array_map('strtolower', $array)
		if($manufacturerId = array_search(strtolower($manufacturer), array_map('strtolower', $deviceManufacturers)) {
			$len = count($deviceTypeTables);
			$selectQuery = "";
			foreach($deviceTypeTables as $key=>$value) {
				$len -= 1;
				$selectQuery .= 'select serialNumber, name, active from '.$value.' as x inner join DeviceTypes as y on y.id = x.deviceTypeId where x.manufacturerId = '.$manufacturerId;
				if($len > 0) {
					$selectQuery .= ' union ';
				}
			}

			$selectQuery .= ' limit 10';

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
			$output[$manufacturer]=$devices;
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
/*
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

	$getDeviceTypeSql="Select * from `DeviceTypes`";
	$getDeviceTypeResult=$dblink->query($getDeviceTypeSql) or die("Something went wrong with $getDeviceTypeSql");

	$deviceTypes = array();
	$deviceTypeTables = array();
	while($data=$getDeviceTypeResult->fetch_array(MYSQLI_ASSOC)) {
		$deviceTypes[$data['id']]=$data['name'];
		$deviceTypeTables[$data['id']] = str_replace(" ", "_", $data['name']) . "s";
	}

	$deviceFound = false;
	$device;
	foreach($deviceTypeTables as $key=>$value) {
		$selectQuery = 'select x.id, deviceTypeId, name, active ';
		$selectQuery .= 'from Manufacturers as m ';
		$selectQuery .= 'inner join '.$value.' as x on m.id = x.ManufacturerId ';
		$selectQuery .= 'where x.serialNumber = "'.$sn.'"';
		$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");

		if($dblink->affected_rows == 1) {		
			$device=$getSelectQueryResult->fetch_array(MYSQLI_ASSOC);
			$deviceFound = true;
			break;
		} else {
			$selectQuery = "";
		}
	}

	if($deviceFound) {
		header('Content-Type: application/json');
		header('HTTP/1.1 200 OK');
		$output[]="Status: OK";
		$output[]="MSG: ";
		$data[]='Maufacturer: '.$device['name'];
		$data[]='Device Type: '.$deviceTypes[$device['deviceTypeId']];
		$datat[]='Serial Number: '.$sn;
		$isActive = ($device['active'] == 1) ? ("Yes") : ("No");
		$data[]="Is Active: ".$isActive;
		$output["Device"]=$data;
		$responseData=json_encode($output);
		echo $responseData;
	} else {
		header('Content-Type: application/json');
		header('HTTP/1.1 200 OK');
		$output[]="Status: Not Found";
		$output[]="MSG: Device SN: $sn not in database";
		$output[]="";
		$responseData=json_encode($output);
		echo $responseData;
	}
}
*/
?>