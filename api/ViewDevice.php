<?php
$dblink=dbconnect("equipment");

$validParameters = array("ViewDevice", "sn");

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
		$deviceFiles = array();
		$basePath = 'https://ec2-3-143-241-117.us-east-2.compute.amazonaws.com/files/';
		//get uploaded files from the filesystem
		$sql='Select * from files_link where deviceId='.$device['id'].' and deviceTypeId='.$device['deviceTypeId'];
		$result=$dblink->query($sql) or die("Something went wrong with $sql");
		if($result->num_rows>0) {
			while($data=$result->fetch_array(MYSQLI_ASSOC)) {
				$deviceFiles[] = $basePath.$data['fileName'];
			}
		}
		
		
		header('Content-Type: application/json');
		header('HTTP/1.1 200 OK');
		$output[]="Status: OK";
		$output[]="MSG: ";
		$data[]='Maufacturer: '.$device['name'];
		$data[]='Device Type: '.$deviceTypes[$device['deviceTypeId']];
		$data[]='Serial Number: '.$sn;
		$isActive = ($device['active'] == 1) ? ("Yes") : ("No");
		$data[]="Is Active: ".$isActive;
		$data['Files'] = $deviceFiles;
		$output[]=$data;
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
?>