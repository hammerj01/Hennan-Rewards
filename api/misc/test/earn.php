<?php

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

	require_once(dirname(__FILE__) . '/../../config/config.php');

	$transactionID = substr("TRANS" . randomizer(2) . DATE("H") . randomizer(2) . DATE("s") . randomizer(6), 0, 16);
	$amount = num_randomizer(4);
	$points = (int) ($amount / 100);
	$officialReceipt = randomizer(32);
	$device = process_qry("SELECT * FROM `devicecodetable` WHERE `status` = 'active' AND `deploy` = 'true' ORDER BY RAND() LIMIT 1", array());
	$barCode = '2017000000000001';
	$deviceID = NULL;
	$locID = NULL;
	$branchCode = NULL;

	if (count($device) > 0) {
		$deviceID = $device[0]['deviceID'];
		$locID = $device[0]['locID'];
		$location = process_qry("SELECT * FROM `loctable` WHERE `locID` = ? LIMIT 1", array($locID));

		if (count($location) > 0) {
			$branchCode = $location[0]['branchCode'];
		}
	}

?>
<style type="text/css">
	input { width: 100%; }
</style>

<form method="POST" action="http://13.250.22.50/api.php">
	<b>category</b>:<br>
	<input type="text" name="category" value="points"><br>
	<b>function</b>:<br>
	<input type="text" name="function" value="earn_points"><br>
	<b>deviceID</b>:<br>
	<input type="text" name="deviceID" value="<?php echo $deviceID; ?>"><br>
	<b>branchCode</b>:<br>
	<input type="text" name="branchCode" value="<?php echo $branchCode; ?>"><br>
	<b>barCode</b>:<br>
	<input type="text" name="barCode" value="<?php echo $barCode; ?>"><br>
	<b>transactionID</b>:<br>
	<input type="text" name="transactionID" value="<?php echo $transactionID; ?>"><br>
	<b>earn</b>:<br>
	<input type="text" name="earn" value='[{"cash":[{"amount":"<?php echo $amount; ?>","points":"<?php echo $points; ?>"}]}]'><br>
	<!-- <b>sku</b>:<br>
	<input type="text" name="sku" value='[{"sku":"00001","qty":"<?php // echo num_randomizer(1)?>"},{"sku":"00002","qty":"<?php // echo num_randomizer(1)?>"}]'><br> -->
	<b>version</b>:<br>
	<input type="text" name="version" value="test"><br>
	<b>transactionDate</b>:<br>
	<input type="text" name="transactionDate" value="<?php echo DATE('Y-m-d H:i:s'); ?>"><br><br>
	<button type="submit">Submit </button>
</form>