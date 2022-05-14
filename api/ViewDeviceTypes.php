<?php
	$dblink=dbconnect("equipment");

	$validParameters = array("ViewDeviceTypes");

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
	
	$getDeviceTypeSql="Select * from `DeviceTypes`";
	$getDeviceTypeResult=$dblink->query($getDeviceTypeSql) or die("Something went wrong with $getDeviceTypeSql");

	$deviceTypes = array();
	while($data=$getDeviceTypeResult->fetch_array(MYSQLI_ASSOC)) {
		$deviceTypes[]=$data['name'];
	}
	
	header('Content-Type: application/json');
	header('HTTP/1.1 200 OK');
	$output[]="Status: OK";
	$output[]="MSG: ";
	$output[]=$deviceTypes;
	$responseData=json_encode($output);
	echo $responseData;
	
?>