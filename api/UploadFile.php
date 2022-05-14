<?php

$dblink=dbconnect("equipment");
$sn=$_REQUEST['sn'];                          
$file=$_FILES['uploadFile'];

$validParameters = array("UploadFile", "sn", "uploadFile");

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

if($sn == NULL || $file == NULL) {
	header('Content-Type: application/json');
	header('HTTP/1.1 200 OK');
	$output[]="Status: NULL Data";
	$errorMsg = ($sn == NULL) ? "sn must not be blank" : "file must not be blank";
	$output[]="MSG: $errorMsg";
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
		$selectQuery = "select id, deviceTypeId from $value where serialNumber = '$sn'";
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
		/*
		print("File Contents:\n");
		print("Loop: \n");
		foreach($file as $key => $val) {
			print("$key => $val\n");
		}
		print($file["name"]."\n");
		print("Mime Type: ".mime_content_type($file['tmp_name']));
		*/
		
		if($file['size'] > 0) {
			$fileType = mime_content_type($file['tmp_name']);
			$fileSize = $file['size'];
			if($fileType == "application/pdf") {
				if($fileSize <= 50000000) {
					$uploadDir = "/var/www/html/files";
					$deviceTypeId=$device['deviceTypeId'];
					$deviceId=$device['id'];
					//$uploadedBy = $_POST['uploadedby'];
					$fileName = $file['name'];
					$fileName = str_replace(".pdf","",$fileName);
					$fileName .= "($deviceTypeId)($deviceId).pdf";
					//$formatType = $_POST['form_type'];
					$tmpName = $file['tmp_name'];
					$location = "$uploadDir/$fileName";
					move_uploaded_file($tmpName, $location);

					$sql="Insert into files_link (fileName, fileType, fileSize, location, deviceId, deviceTypeId) Values";
					$sql.=" ('$fileName', '$fileType', $fileSize, '$location', $deviceId, $deviceTypeId)";
					$dblink->query($sql) or die("Something went wrong with $sql Message: ".mysqli_error($dblink));
					
					header('Content-Type: application/json');
					header('HTTP/1.1 200 OK');
					$output[]="Status: File Uploaded";
					$output[]="MSG: The file was successfully uplaoded";
					$output[]=$fileName;
					$responseData=json_encode($output);
					echo $responseData;

				} else {
					header('Content-Type: application/json');
					header('HTTP/1.1 200 OK');
					$output[]="Status: Invalid File";
					$output[]="MSG: The file needs to be less than or equal to 50MB";
					$output[]="";
					$responseData=json_encode($output);
					echo $responseData;
				}
			} else {
				header('Content-Type: application/json');
				header('HTTP/1.1 200 OK');
				$output[]="Status: Invalid File";
				$output[]="MSG: The file needs to be a pdf";
				$output[]="";
				$responseData=json_encode($output);
				echo $responseData;
			}
		}
		
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