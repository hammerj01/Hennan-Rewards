<?php

	// error_reporting(E_ALL);
	// ini_set('display_errors', 1);
	error_reporting(E_ERROR | E_PARSE); // Remove WARNING (Temporary Solution)
	header('Content-Type: application/json');
	header('Cache-Control: no-cache');
	set_time_limit(0);
	ini_set('memory_limit', '-1');
	ini_set('mysql.connect_timeout','0');
	ini_set('max_execution_time', '0');
	ini_set('date.timezone', 'Asia/Manila');

	require_once (dirname(__FILE__) . '/../config/config.php');

	if (!isset($_FILES) || (!$_FILES)) { die($error000); }


	$path = realpath(dirname(__FILE__)) . "../../../assets/images/profile/full";
	$path1 = realpath(dirname(__FILE__)) . "../../../assets/images/profile/thumb";
	$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));

	if (!file_exists($path)) { mkdir($path, 0777, true); }
	if (!file_exists($path1)) { mkdir($path1, 0777, true); }
	$url = dirname(dirname(dirname(__FILE__)));

	$output = array();
	$width = 400;
	$height = 400;
	$uniqID = uniqid();
	
	$path_full = "assets/images/profile/full/";
	$path_thumb = "assets/images/profile/thumb/";

	$valid_formats = array("jpg", "JPG", "jpeg", "JPEG", "gif", "GIF", "png", "PNG", "bmp", "BMP");
	$file_name = 'filename';
	$name = $_FILES[$file_name]["name"];
	echo "string " . $name;
	$name = $_FILES["filename"]["name"];
	$picture_path = "assets/images/profile/full/" . $name;
	$picture_path = "assets/images/profile/thumb/" . $name;
	$filepath = getcwd() . $picture_path;
	$v_pathinfo = pathinfo($filepath);
	$memberID = $v_pathinfo['filename'];
	
	$size = $_FILES[$file_name]['size'];
	$tmp  = $_FILES[$file_name]['tmp_name'];
	$type = $_FILES[$file_name]['type'];

	if ($name) {
		$ext_arr = explode(".", $name);
		$ext = end($ext_arr);

		if (in_array($ext, $valid_formats)){
			if ($size<10000000) {
				if (move_uploaded_file($tmp, $path . '/' . $uniqID . '.' . $ext)) {
					if (strtolower($ext) != 'gif') {
						list($width_orig, $height_orig) = getimagesize($path . '/' . $uniqID . '.' . $ext);
						$ratio_orig = $width_orig/$height_orig;

						//Height Driven
						if (($width / $height )< $ratio_orig) {
							$width = ($height * $ratio_orig);
						} else {
							$height = ($width / $ratio_orig);
						}

						if ($ext == 'jpg' || $ext == 'JPG' || $ext == 'jpeg' || $ext == 'JPEG') {
							$imgSrc = $path . '/' . $uniqID . '.' . $ext;
							$image = imagecreatefromjpeg($imgSrc);

						} else if ($ext == 'png' || $ext == 'PNG') {
							$imgSrc = $path . '/' . $uniqID . '.' . $ext;
							$image = imagecreatefrompng($imgSrc);
						}
						$image_p = imagecreatetruecolor($width, $height);
						$almostblack = imagecolorallocate($image_p,255,255,255);
						imagefill($image_p, 0, 0, $almostblack);
						$black = imagecolorallocate($image_p, 0, 0 ,0);
						imagecolortransparent($image_p, $almostblack);
						imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
						// imagejpeg($image_p, $path . '/' . $uniqID . '.' . $ext, 80);

						if (imagejpeg($image_p, $path . '/' . $memberID . '.' . $ext, 100)) {
							imagejpeg($image_p, $path1 . '/' . $memberID . '.' . $ext, 100);
							$logs->write_logs('Raw - Image File Upload', $file_name, array(array("data" => array("memberID" =>$memberID,"name"=>$name))));
							$upload = process_qry("CALL `global_overide_profile_image`(?, ?)", array($memberID, $name), $logs);

							if ($upload[0]['result'] == "Success") {
								array_push($output, array("response"=>"Success", "data"=>array(array("description"=>"Your profile image has been successfully updated."))));
							} 
							// else {
							// 	array_push($output, array("response"=>"Error", "description"=>"Failed to update your profile image."));
							// }
						}
						//  else {
						// 	array_push($output, array("response"=>"Error", "description"=>"Unable to convert image file. Please try again later."));
						// }

					}

					array_push($output, array("response" => "Success", "data"=>array(array("description"=> strtoupper($ext) . " file has been successfully uploaded.", "image" => $uniqID . '.' . $ext, "file" => $ext))));
				} else {
					array_push($output, array("response" => "Error", "description" => "Unable to upload file. Please try again later."));
				}
			} else {
				array_push($output, array("response" => "Error", "description" => "File exceeds minimum size requirements. Please try again later."));
			}
		} else {
			array_push($output, array("response" => "Error", "description" => "Invalid file format."));
		}
	} else {
		array_push($output, array("response" => "Error", "description" => "Please select a file."));
	}

	ob_flush();
	flush();

	$logs->write_logs($output[0]['response'] . ' - File Upload', $file_name, array(array("_POST" => $_POST, "_FILES" => $_FILES, "data" => $output)));
	die(json_encode($output));

?>