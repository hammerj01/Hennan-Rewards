<?php
	header('Access-Control-Allow-Origin: *');  
   	header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization');

	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	// error_reporting(E_ERROR | E_PARSE); // Remove WARNING (Temporary Solution)
	// header('Content-Type: application/json');
	header('Cache-Control: no-cache');
	set_time_limit(0);
	ini_set('memory_limit', '-1');
	ini_set('mysql.connect_timeout','0');
	ini_set('max_execution_time', '0');
	ini_set('date.timezone', 'Asia/Manila');

	require_once(dirname(dirname(dirname(__FILE__))) . '/api/config/config.php');

	if (!isset($_FILES) || (!$_FILES)) { die($error000); }

	$url = dirname(dirname(dirname(__FILE__)));
	$output = array();
	$serialID = uniqid();
	$width = 400;
	$height = 400;
	$counter = 0;
	$valid_formats = array("jpg", "jpeg", "png", "gif", "bmp", "JPG", "JPEG", "PNG", "GIF", "BMP");
	$file_name = 'file';
	$names = $_FILES[$file_name]['name'];
	$size = $_FILES[$file_name]['size'];
	$tmp  = $_FILES[$file_name]['tmp_name'];
	$type = $_FILES[$file_name]['type']; 

	switch ($_POST['module']) { 
		case 'profile':
			$path_full = $url . "/assets/images/profile/full";
			$path_thumb = $url . "/assets/images/profile/thumb";
		break;

		case 'vouchers':
			$path_full = $url . "/assets/images/vouchers/full";
			$path_thumb = $url . "/assets/images/vouchers/thumb";
		break;

		case 'posts':
			$path_full = $url . "/assets/images/posts/full";
			$path_thumb = $url . "/assets/images/posts/thumb";
		break;

		case 'brands':
			$path_full = $url . "/assets/images/brands/full";
			$path_thumb = $url . "/assets/images/brands/thumb";
		break;

		case 'products':
			$path_full = $url . "/assets/images/products/full";
			$path_thumb = $url . "/assets/images/products/thumb";
		break;

		case 'accounts':
			$path_full = $url . "/assets/images/accounts/full";
			$path_thumb = $url . "/assets/images/accounts/thumb";
		break;

		case 'category':
			$path_full = $url . "/assets/images/category/full";
			$path_thumb = $url . "/assets/images/category/thumb";
		break;

		case 'subcategory':
			$path_full = $url . "/assets/images/subcategory/full";
			$path_thumb = $url . "/assets/images/subcategory/thumb";
		break;

		case 'member':
			$path_full = $url . "/assets/images/member/full";
			$path_thumb = $url . "/assets/images/member/thumb";
		break;

		case 'rewards':
			$path_full = $url . "/assets/images/rewards/full";
			$path_thumb = $url . "/assets/images/rewards/thumb";
		break;

		case 'hotels':
			$path_full = $url . "/assets/images/hotels/full";
			$path_thumb = $url . "/assets/images/hotels/thumb";
		break;

		default:
			$path_full = $url . "/assets/images/full";
			$path_thumb = $url . "/assets/images/thumb";
		break;
	}

	if (!file_exists($path_full)) {
		mkdir($path_full, 0777, true);
	}
	if (!file_exists($path_thumb)) {
		mkdir($path_thumb, 0777, true);
	}

	if ($names) {
		if (strlen($names)) {
			$counter += 1;
			$ext_arr = explode(".",$names);
			$ext = end($ext_arr);

			if (in_array($ext,$valid_formats)) {
				if ($size<2e+6) {
					if (move_uploaded_file($tmp, $path_full.'/'.$serialID.'.'.$ext)) {
						list($width_orig, $height_orig) = getimagesize($path_full.'/'.$serialID.'.'.$ext);
						$ratio_orig = $width_orig/$height_orig;

						//Height Driven
						if ($width/$height < $ratio_orig) {
							$width = $height*$ratio_orig;
						} else {
							$height = $width/$ratio_orig;
						}

						if ($ext == 'jpg' || $ext == 'JPG' || $ext == 'jpeg' || $ext == 'JPEG') {
							$imgSrc = $path_full.'/'.$serialID.'.'.$ext;
							$image = imagecreatefromjpeg($imgSrc);
						} else if ($ext == 'png' || $ext == 'PNG') {
							$imgSrc = $path_full.'/'.$serialID.'.'.$ext;
							$image = imagecreatefrompng($imgSrc);
						}

						$image_p = imagecreatetruecolor($width, $height);
						$almostblack = imagecolorallocate($image_p,255,255,255);
						imagefill($image_p,0,0,$almostblack);
						$black = imagecolorallocate($image_p,0,0,0);
						imagecolortransparent($image_p,$almostblack); 
						imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
						imagejpeg($image_p, $path_thumb.'/'.$serialID.'.'.$ext, 100);
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
			array_push($output, array("response" => "Error", "description" => "Image not properly attached."));
		}
	} else {
		array_push($output, array("response" => "Error", "description" => "Please select a file."));
	}

	if (file_exists($path_full.'/'.$serialID.'.'.$ext) && file_exists($path_thumb.'/'.$serialID.'.'.$ext)) {
		$path_full = str_replace($url, DOMAIN, $path_full);
		$path_thumb = str_replace($url, DOMAIN, $path_thumb);
		array_push($output, array("response" => "Success", "data"=>$serialID.'.'.$ext, "path_full"=>$path_full.'/'.$serialID.'.'.$ext, "path_thumb"=>$path_thumb.'/'.$serialID.'.'.$ext));
	} else {
		array_push($output, array("response" => "Error", "description" => "Failed to create image."));
	}

	ob_flush();
	flush();

	$logs->write_logs($output[0]['response'] . ' - File Upload Dashboard', $file_name, array(array("_POST" => $_POST, "_FILES" => $_FILES, "data" => $output)));
	die(json_encode($output[0], JSON_UNESCAPED_SLASHES));

?>