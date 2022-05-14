<?php

	$validParameters = array("Help");

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
	
	$endpointsList = array();
	$endpointsList[] = array("Endpoint: ViewDeviceTypes","Lists all the device types", "Required Parameters (-Body): None", "Optional Parameters (-Body): None");

	$endpointsList[] = array("Endpoint: ViewManufacturers","Lists all the manufacturers", "Required Parameters (-Body): None", "Optional Parameters (-Body): None");

	$endpointsList[] = array("Endpoint: ViewDevice","Displays the specified devices information", "Required Parameters (-Body): sn (Serial Number)", "Optional Parameters (-Body): None");

	$endpointsList[] = array("Endpoint: ListDevices","Lists all matching devices", "Required Parameters (-Body): At least one of the optional parameters excluding page", "Optional Parameters (-Body): deviceType (Device Type), manufacturer (Manufacturer), and page (the page number to view)");

	$endpointsList[] = array("Endpoint: AddDevice","Adds a new device", "Required Parameters (-Body): sn (Serial Number), deviceType (Device Type), manufacturer (Manufacturer)", "Optional Parameters (-Body): None");

	$endpointsList[] = array("Endpoint: DeleteDevice","Deletes the specified device", "Required Parameters (-Body): sn (Serial Number)", "Optional Parameters (-Body): None");

	$endpointsList[] = array("Endpoint: UpdateDevice","Updates an existing device", "Required Parameters (-Body): sn (Serial Number) and at least one of the optional parameters", "Optional Parameters (-Body): newSN (Serial Number), deviceType (Device Type), manufacturer (Manufacturer), isActive (true or false)");

	$endpointsList[] = array("Endpoint: UploadFile","Uploads a file to a device", "Required Parameters (-Form): sn (Serial Number), uploadFile (pdf file)", "Optional Parameters (-Form): None");

	header('Content-Type: application/json');
	header('HTTP/1.1 200 OK');
	$output[]="Status: OK";
	$output[]="MSG: List of api endpoints";
	$output[]=$endpointsList;
	$responseData=json_encode($output);
	echo $responseData;
	
?>