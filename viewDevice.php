<?php
//session_start();
function redirect ($uri) 
{ ?>
	<script type="text/javascript">
	<!--
	document.location.href="<?php echo $uri; ?>";
	-->
	</script>
<?php die;}

include 'dbConnection.php';

echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<title>Device Info</title>';
echo '</head>';
echo '<body>';

$deviceTypeId = $_REQUEST['dt']; //get the dt form the url. request and get both get from the url
$deviceId = $_REQUEST['did'];
$deviceTypeTable;

if($deviceId != "Deleted") {
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
	
	$deviceTypeTable = $deviceTypeTables[$deviceTypeId];

	$selectQuery = 'select * ';
	$selectQuery .= 'from '.$deviceTypeTable.' ';
	$selectQuery .= 'where id = "'.$deviceId.'"';
	$getSelectQueryResult = $dblink->query($selectQuery) or die("Something went wrong with $selectQuery");

	$data=$getSelectQueryResult->fetch_array(MYSQLI_ASSOC);
	//$deviceId = $data['id'];
	//$deviceTypeId = $data['deviceTypeId'];
	$manufacturerId = $data['manufacturerId'];
	$isActive = $data['active'];
	$serialNumber = $data['serialNumber'];
	echo '<p>Device Info: </p>';
	echo '<p>Serial Number: '.$serialNumber.'</p>';
	echo '<p>Device Type: '.$deviceTypes[$deviceTypeId].'</p>';
	echo '<p>Manufacturer: '.$deviceManufacturers[$manufacturerId].'</p>';
	if($isActive == 1) {
		echo '<p>Active</p>';
	} else {
		echo '<p>Inactive</p>';
	}
	
	/*
	// get uploaded files from the database
	$sql="Select * from files where deviceId='$deviceId' and deviceTypeId='$deviceTypeId'";
	$result=$dblink->query($sql) or die("Something went wrong with $sql");
	if($result->num_rows>0) {
		echo '<p>Device Record Files Found:</p>';
		while($data=$result->fetch_array(MYSQLI_ASSOC)) {
			$name=str_replace(" ","_",$data['fileName']);
			$fp=fopen("/var/www/html/files/$name","wb");
			fwrite($fp, $data['content']);
			fclose($fp);
			echo '<div><a class="btn btn-sm btn-primary" href="./files/'.$name.'" target="_blank">View Record</a></div>';
		}

	}
	*/
	
	//get uploaded files from the filesystem
	$sql="Select * from files_link where deviceId='$deviceId' and deviceTypeId='$deviceTypeId'";
	$result=$dblink->query($sql) or die("Something went wrong with $sql");
	if($result->num_rows>0) {
		echo '<p>Device Record Files Found:</p>';
		while($data=$result->fetch_array(MYSQLI_ASSOC)) {
			echo '<div><a class="btn btn-sm btn-primary" href="./files/'.$data["fileName"].'" target="_blank">View Record</a></div>';
		}

	}

	echo "<p>Update Device</p>";
	echo '<form method="post" action="">';
	echo '<div>';
	echo '<p>Only fill in what needs to be updated.</p>';
	echo '<p>Enter Updated Device Type:</p>';
	echo '<input type="text" id="updatedDeviceType" name="updatedDeviceType">';
	echo '<p>Enter Updated Manufacturer:</p>';
	echo '<input type="text" id="updatedManufacturer" name="updatedManufacturer">';
	echo '<p>Enter Updated Serial Number:</p>';
	echo '<input type="text" id="updatedSerialNumber" name="updatedSerialNumber">';
	echo '<p>Update active status: </p>';
	echo '<input type="radio" id="active" name="activeOrNot" value="ACTIVE">';
	echo '<label for="active">Active</label>';
	echo '<input type="radio" id="inactive" name="activeOrNot" value="INACTIVE">';
	echo '<label for="inactive">Inactive</label>';
	echo '<div>';
	echo '<button type="submit" name="update" value="UpdateDevice">Update Device</button>';
	echo '</div>';
	echo '</div>';
	echo '</form>';

	if(isset($_POST['update']) && $_POST['update']=="UpdateDevice") {
		$enteredDeviceType = strtolower(trim($_POST['updatedDeviceType']));
		$enteredManufacturer = trim($_POST['updatedManufacturer']);
		$enteredSN = strtolower(trim($_POST['updatedSerialNumber']));
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

		if($enteredDeviceType != "" || $enteredManufacturer != "" || $enteredSN != "" || isset($_POST['activeOrNot'])) {
			$dtId = $deviceTypeId;
			$mId =$manufacturerId;
			$sn = $serialNumber;
			$active = $isActive;

			if($enteredManufacturer != "") {
				if($index = array_search($enteredManufacturer, $deviceManufacturers)) {
					//echo '<p>Manufacturer exists at index '.$index.'</p>';
					$mId = $index;
				} else {
					//echo '<p>Manufacturer does not exist</p>';
					$addNewManufacturerQuery = 'INSERT into Manufacturers (name) VALUES ("'.$enteredManufacturer.'")';
					$dblink->query($addNewManufacturerQuery) or die("Something went wrong with $addNewManufacturerQuery");
					$mId = $dblink->insert_id;
					$deviceManufacturers[$mId] = $enteredManufacturer;
					//echo '<p>'.$addNewManufacturerQuery.'</p>';
				}
			}
			if($enteredDeviceType != "") {
				$moveToNewTable = true;
				if($index = array_search($enteredDeviceType, $deviceTypes)) {
					//echo '<p>Device Type exists at index '.$index.'</p>';
					$dtId = $index;
				} else {
					//echo '<p>Device Type does not exist</p>';
					$newTable = str_replace(" ", "_", $enteredDeviceType) . "s";
					//echo '<p>Created table '.$newTable.' for device type '.$enteredDeviceType.'</p>';

					$enterDeviceTypeQuery = 'INSERT into DeviceTypes (name) VALUES ("'.$enteredDeviceType.'")';
					$dblink->query($enterDeviceTypeQuery) or die("Something went wrong with $enterDeviceTypeQuery");
					$dtId = $dblink->insert_id;
					$deviceTypes[$dtId] = $enteredDeviceType;
					$deviceTypeTables[$dtId] = $newTable;
					//echo '<p>'.$enterDeviceTypeQuery.'</p>';

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
					//echo '<p>'.$newTableQuery.'</p>';
				}

				$deleteQuery = 'Delete from '.$deviceTypeTables[$deviceTypeId].' where id = "'.$deviceId.'"';
				$getDeleteQueryResult = $dblink->query($deleteQuery) or die("Something went wrong with $deleteQuery");

			}
			if($enteredSN != "") {
				if(strlen($enteredSN) != 32) {
					$validSn = false;
					$snErrorMessage = "Error, the serial number must have a length of 32.";
				} else {
					$checkSnQuery = "";
					foreach($deviceTypeTables as $value) {
						$checkSnQuery = 'select id from '.$value.' where serialNumber = "'.$enteredSN.'"';
						$getCheckSnQueryResult = $dblink->query($checkSnQuery) or die("Something went wrong with $checkSnQuery");

						if($getCheckSnQueryResult->num_rows>0) {
							$validSn = false;
							$snErrorMessage = "Error, a device with the entered serial number already exists.";
							break;
						} else {
							$checkSnQuery = "";
						}
					}

					$sn = $enteredSN;
				}
			}
			if(isset($_POST['activeOrNot'])) {
				$selectedButton = $_POST['activeOrNot'];
				if($selectedButton == "ACTIVE") {
					$active = 1;
				} else {
					$active = 0;
				}
			}
			
			if($validSn) {
				if($moveToNewTable) {
					$insertQuery = 'INSERT into '.$deviceTypeTables[$dtId].' (manufacturerId, deviceTypeId, serialNumber, active) VALUES ';
					$insertQuery .= '('.$mId.', '.$dtId.', "'.$sn.'", '.$active.')';
					$dblink->query($insertQuery) or die("Something went wrong with $insertQuery");
					$deviceId = $dblink->insert_id;
				} else {
					$updateQuery = 'Update '.$deviceTypeTables[$deviceTypeId].' ';
					$updateQuery .= 'set manufacturerId = '.$mId.', serialNumber = "'.$sn.'", active = '.$active.' ';
					$updateQuery .= 'where id = "'.$deviceId.'"';
					$dblink->query($updateQuery) or die("Something went wrong with $updateQuery");
					//echo '<p>'.$updateQuery.'</p>';
				}
				redirect("https://ec2-3-143-241-117.us-east-2.compute.amazonaws.com/viewDevice.php?dt=$dtId&did=$deviceId");
			} else {
				echo '<p style="color:red;">'.$snErrorMessage.'</p>';
			}
	
		} else {
			echo '<p style="color:red;">Error, no update information was given</p>';
		}	
	}


	echo "<p>Delete Device</p>";
	echo '<form method="post" action="">';
	echo '<div>';
		echo '<button type="submit" name="delete" value="DeleteDevice">Delete Device</button>';
	echo '</div>';
	echo '</form>';

	if(isset($_POST['delete']) && $_POST['delete']=="DeleteDevice") {
		$deleteQuery = 'Delete from '.$deviceTypeTables[$deviceTypeId].' where id = "'.$deviceId.'"';
		$dblink->query($deleteQuery) or die("Something went wrong with $deleteQuery");
		
		//$sql="Delete from files where deviceId='$deviceId' and deviceTypeId='$deviceTypeId'";
		//$result=$dblink->query($sql) or die("Something went wrong with $sql");
		
		$sql="Delete from files_link where deviceId='$deviceId' and deviceTypeId='$deviceTypeId'";
		$result=$dblink->query($sql) or die("Something went wrong with $sql");
		
		redirect("https://ec2-3-143-241-117.us-east-2.compute.amazonaws.com/viewDevice.php?dt=$deviceTypeId&did=Deleted");
	}
	
/*	
	// upload file to database
	echo "<p>Upload file:</p>";
	echo '<form method="post" action="" enctype="multipart/form-data">';
	echo '<input type="hidden" name="MAX_FILE_SIZE" value="50000000">';
	echo '<input type="hidden" name="deviceTypeId" value="'.$deviceTypeId.'">';
	echo '<input type="hidden" name="deviceId" value="'.$deviceId.'">';
	echo 'Select pdf to upload:';
	echo '<input type="file" id="fileToUpload" name="fileToUpload">';
	echo '<input type="submit" value="uploadPDF" name="submitFile">';
	echo '</form>';

	if(isset($_POST['submitFile']) && $_POST['submitFile']=="uploadPDF" && $_FILES['fileToUpload']['size'] > 0) {
		$fileType = $_FILES['fileToUpload']['type'];
		$fileSize = $_FILES['fileToUpload']['size'];
		if($fileType == "application/pdf") {
			if($fileSize <= 50000000) {
				//$uploadDir = "/var/www/html/files";
				echo '<input type="hidden" name="deviceTypeId" value="'.$deviceTypeId.'">';
				echo '<input type="hidden" name="deviceId" value="'.$deviceId.'">';
				//$uploadedBy = $_POST['uploadedby'];
				$fileName = $_FILES['fileToUpload']['name'];
				$formatType = $_POST['form_type'];
				$tmpName = $_FILES['fileToUpload']['tmp_name'];
				$fp = fopen($tmpName, 'r');
				$content = fread($fp, filesize($tmpName));
				$content = addslashes($content);
				fclose($fp);

				$sql="Insert into files (fileName, fileType, fileSize, content, deviceId, deviceTypeId) Values";
				$sql.=" ('$fileName', '$fileType', $fileSize, '$content', $deviceId, $deviceTypeId)";
				$dblink->query($sql) or die("Something went wrong with $sql Message: ".mysqli_error($dblink));

				redirect("https://ec2-3-143-241-117.us-east-2.compute.amazonaws.com/viewDevice.php?sn=$serialNumber");
			} else {
				echo "<p style='color:red;''>Error, the file is larger than 50MB.</p>";
			}
		} else {
			echo "<p style='color:red;''>Invalid file type. Only PDFs are accepted.</p>";
		}
*/	


	//upload file to filesystem
	echo "<p>Upload file:</p>";
	echo '<form method="post" action="" enctype="multipart/form-data">';
	echo '<input type="hidden" name="MAX_FILE_SIZE" value="50000000">';
	echo '<input type="hidden" name="deviceTypeId" value="'.$deviceTypeId.'">';
	echo '<input type="hidden" name="deviceId" value="'.$deviceId.'">';
	echo 'Select pdf to upload:';
	echo '<input type="file" id="fileLinkToUpload" name="fileLinkToUpload">';
	echo '<input type="submit" value="uploadPDF" name="submitFileLink">';
	echo '</form>';

	if(isset($_POST['submitFileLink']) && $_POST['submitFileLink']=="uploadPDF" && $_FILES['fileLinkToUpload']['size'] > 0) {
		$fileType = $_FILES['fileLinkToUpload']['type'];
		$fileSize = $_FILES['fileLinkToUpload']['size'];
		if($fileType == "application/pdf") {
			if($fileSize <= 50000000) {
				$uploadDir = "/var/www/html/files";
				$deviceTypeId=$_POST['deviceTypeId'];
				$deviceId=$_POST['deviceId'];
				//$uploadedBy = $_POST['uploadedby'];
				$fileName = $_FILES['fileLinkToUpload']['name'];
				$fileName = str_replace(".pdf","",$fileName);
				$fileName .= "($deviceTypeId)($deviceId).pdf";
				//$formatType = $_POST['form_type'];
				$tmpName = $_FILES['fileLinkToUpload']['tmp_name'];
				$location = "$uploadDir/$fileName";
				move_uploaded_file($tmpName, $location);

				$sql="Insert into files_link (fileName, fileType, fileSize, location, deviceId, deviceTypeId) Values";
				$sql.=" ('$fileName', '$fileType', $fileSize, '$location', $deviceId, $deviceTypeId)";
				$dblink->query($sql) or die("Something went wrong with $sql Message: ".mysqli_error($dblink));

				redirect("https://ec2-3-143-241-117.us-east-2.compute.amazonaws.com/viewDevice.php?dt=$deviceTypeId&did=$deviceId");
			} else {
				echo "<p style='color:red;'>Error, the file is larger than 50MB.</p>";
			}
		} else {
			echo "<p style='color:red;'>Invalid file type. Only PDFs are accepted.</p>";
		}

	}
	
	echo '<form method="post" action="https://ec2-3-143-241-117.us-east-2.compute.amazonaws.com">';
	echo '<div>';
	echo '<button type="submit" name="toSearch" value="toSearch">Back to search</button>';
	echo '</div>';
	echo '</form>';
	
} else {
	echo '<p>The device was succesfully deleted.</p>';
	echo '<form method="post" action="https://ec2-3-143-241-117.us-east-2.compute.amazonaws.com">';
	echo '<div>';
	echo '<button type="submit" name="toSearch" value="toSearch">Back to search</button>';
	echo '</div>';
	echo '</form>';
}

echo '</body>';
echo '</html>';

?>
