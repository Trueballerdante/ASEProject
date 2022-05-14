<?php
include("functions.php");
$uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY); //gets everything after the ?
//echo "<p>$uri</p>";
$uri=explode('&',$uri);
$endPoint=$uri[0];
//die("End Point: $endPoint");
//echo "<p>$uri</p>";
//echo "<p>$endPoint</p>";
switch($endPoint) {
	case "ViewDevice":
		include("ViewDevice.php");
		break;
	case "ViewDeviceTypes":
		include("ViewDeviceTypes.php");
		break;
	case "ViewManufacturers":
		include("ViewManufacturers.php");
		break;
	case "ListDevices":
		include("ListDevices.php");
		break;
	case "AddDevice":
		include("AddDevice.php");
		break;
	case "UpdateDevice":
		include("UpdateDevice.php");
		break;
	case "DeleteDevice":
		include("DeleteDevice.php");
		break;
	case "UploadFile":
		include("UploadFile.php");
		break;
	case "Help":
		include("Help.php");
		break;
	case "ListFiles":
		break;
	case "ViewFile":
		break;
	default:
		header('Content-Type: application/json');
		header("HTTP/1.1 404 Not Found");
		$message[]="Status: Error";
		$message[]="MSG: Endpoint not found";
		$message[]="";
		echo json_encode($message);
		die();	
}

/*
echo "<p>$uri</p>";
foreach($data as $key=>$value) {
	echo "<p>Key: $key Value:$value</p>";
}
*/
?>