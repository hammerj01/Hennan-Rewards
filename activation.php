<?php

	// error_reporting(E_ALL);
	// ini_set('display_errors', 1);
	error_reporting(E_ERROR | E_PARSE); // Remove WARNING (Temporary Solution)
	header('Cache-Control: no-cache');
	set_time_limit(0);
	ini_set('memory_limit', '-1');
	ini_set('mysql.connect_timeout','0');
	ini_set('max_execution_time', '0');
	ini_set('date.timezone', 'Asia/Manila');
	
	require_once (dirname(__FILE__) . '/api/config/config.php');
	require_once (dirname(__FILE__) . '/api/class/cipher.class.php');

	$key = randomizer(32);
	$iv = randomizer(16);
	$cipher = NEW cipher($key, $iv);
	$data = array("oauth"=>$key, "token"=>$iv);

	foreach ($_GET as $key => $value) $data[$key] = $cipher->encrypt($value);

	$url = DOMAIN . "/api.php?category=" . urlencode($cipher->encrypt("core")) . "&function=" . urlencode($cipher->encrypt("activation"));
	$cURL = curl_init();

	curl_setopt($cURL, CURLOPT_URL, $url);
	curl_setopt($cURL, CURLOPT_POST, 1);
	curl_setopt($cURL, CURLOPT_POSTFIELDS, $data);
	curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($cURL);

	curl_close ($cURL); 
	$output = json_decode($server_output, true);

?>

<!DOCTYPE html>
<html>
<head>
	<link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
	<title></title>
	<link rel="stylesheet" type="text/css" href="<?php echo DOMAIN; ?>/assets/css/styles.css">
</head>
<body>
	<div class="container">
		<section>
			<div class="content">
<?php 

if ($output[0]["response"] == "Success") { ?>
				<div id="success">
					<div class="image"><img src="<?php echo DOMAIN; ?>/assets/images/success.png"></div>
					<div class="details">
						<!-- <h1>SUCCESS!</h1> -->
						<p style="font-family: Roboto !important;">Thank you for registering and activating your account at Henann Rewards. <br />Enjoy exclusive deals and vouchers for use in any Henann Group of Resorts. <br /></p>
						<a href="<?php echo DOMAIN; ?>/portal/#/loginform"><button style="  background: #1a6986;color: #ffffff;padding: 10px 20px;border: 0 none;font-weight: normal;border-radius: 10px; letter-spacing: 1px;cursor: pointer;font-family: roboto !important;width: 300px;font-size: 20px;margin-top: 80px;">Login </button> </a>
					</div>
				</div>
<?php } else { ?>
				<div id="fail">
					<div class="image"><img src="<?php echo DOMAIN; ?>/assets/images/fail.png"></div>
					<div class="details">
						<h1>OOPS!</h1>
						<p style="font-family: Roboto !important;">Your account could not be activated. <br>Please recheck the link or contact the System's Administrator.</p>
					</div>
				</div>
<?php } ?>
			</div>
		</section>
		<footer>
			<div id="logo"><img id="footer_logo_image" src="<?php echo DOMAIN; ?>/assets/images/logo.png" /></div>
		</footer>
	</div>
</body>
</html>