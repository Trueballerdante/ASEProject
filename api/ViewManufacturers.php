<?php
	$dblink=dbconnect("equipment");

	$validParameters = array("ViewManufacturers");

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
	
	$getManufacturersSql="Select * from `Manufacturers`";
	$getManufacturersResult=$dblink->query($getManufacturersSql) or die("Something went wrong with $getManufacturersSql");

	$Manufacturers = array();
	while($data=$getManufacturersResult->fetch_array(MYSQLI_ASSOC)) {
		$Manufacturers[]=$data['name'];
	}
	
	header('Content-Type: application/json');
	header('HTTP/1.1 200 OK');
	$output[]="Status: OK";
	$output[]="MSG: ";
	$output[]=$Manufacturers;
	$responseData=json_encode($output);
	echo $responseData;
	
?>