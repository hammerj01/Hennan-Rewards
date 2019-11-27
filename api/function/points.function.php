<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	require_once ('api/class/points.class.php');

	$class = new points();
	$file = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));

	switch ($_GET['function']) {
		case 'balance_inquiry':
			$default = array("memberID" => NULL);
			$required = array('memberID');
			$data = process_params($default, $required, $params);

			die(substr($class->my_profile($data), 1, -1));
			break;

		case 'my_voucher_list':
			$default = array("memberID" => NULL);
			$required = array('memberID');
			$data = process_params($default, $required, $params);

			die(substr($class->my_voucher_list($data), 1, -1));
			break;

		case 'my_campaign_list':
			$default = array("memberID" => NULL);
			$required = array('memberID');
			$data = process_params($default, $required, $params);

			die(substr($class->my_campaign_list($data), 1, -1));
			break;

		case 'earn_points':
		
			$default = array("branchCode" => NULL, "terminalNum" => NULL, "memberID" => NULL, "transactionType" => NULL, "transactionID" => NULL, "earn" => NULL, "sku" => NULL, "version" => NULL, "transactionDate" => NULL, "officialReceipt" => NULL, "deviceID" => NULL, "remarks"=>NULL, "barCode" => NULL,"userID"=>NULL,"userName"=>NULL,"designation"=>NULL);
			$required = array('deviceID', 'memberID', 'earn', 'version','userID','userName','designation');
			$data = process_params($default, $required, $params);
			$params['terminalNum'] = $params['deviceID'];
			// $params['officialReceipt'] = $params['transactionID'];

			die(substr($class->earn_points($data), 1, -1));
			break;

		case 'redeem_points':
			$default = array("terminalNum" => NULL, "branchCode" => NULL, "memberID" => NULL, "transactionID" => NULL, "points" => NULL, "version" => NULL, "transactionType" => NULL, "officialReceipt" => NULL, "deviceID" => NULL, "rewardsID"=>NULL, "barCode" => NULL,"locID"=> NULL, "locName" => NULL,"userID"=>NULL,"userName"=>NULL,"designation"=>NULL);
			 
			// $params['terminalNum'] = $params['deviceID'];
			$required = array('branchCode', 'deviceID', 'memberID', 'points', 'version','rewardsID',"locID",'userID','userName','designation');
			$data = process_params($default, $required, $params);
			
			// $params['officialReceipt'] = $params['transactionID'];
			die(substr($class->redeem_points($data), 1, -1));
			break;

		case 'redeem_campaign_old':
			$default = array("terminalNum" => NULL, "branchCode" => NULL, "memberID" => NULL, "transactionID" => NULL, "loyaltyID" => NULL, "version" => NULL, "transactionType" => NULL, "officialReceipt" => NULL,"deviceID" => NULL, "remarks"=>NULL, "barCode" => NULL);
			// $params['requestID'] = $params['requestID'];
			$params['terminalNum'] = $params['deviceID'];
			$required = array('branchCode', 'memberID',  'loyaltyID','deviceID');
			$data = process_params($default, $required, $params);
			

			die(substr($class->redeem_campaign_old($data), 1, -1));
			break;

		case 'redeem_campaign':
			$default = array("terminalNum" => NULL, "branchCode" => NULL, "memberID" => NULL, "transactionID" => NULL, "loyaltyID" => NULL, "version" => NULL, "transactionType" => NULL, "officialReceipt" => NULL,"deviceID" => NULL, "remarks"=>NULL, "barCode" => NULL,"platform"=>NULL,"locID"=> NULL, "locName" => NULL);
			// $params['requestID'] = $params['requestID'];
			$params['terminalNum'] = $params['deviceID'];
			$required = array('branchCode', 'memberID',  'loyaltyID','deviceID');
			$data = process_params($default, $required, $params);
			

			die(substr($class->redeem_campaign($data), 1, -1));
			break;

		case 'redeem_voucher':
			$default = array("terminalNum" => NULL, "branchCode" => NULL, "memberID" => NULL, "transactionID" => NULL, "voucherID" => NULL, "version" => NULL, "transactionType" => NULL, "officialReceipt" => NULL, "deviceID" => NULL, "locID"=>NULL, "barCode" => NULL,"userID"=>NULL,"userName"=>NULL,"designation"=>NULL);
			// $params['terminalNum'] = $params['deviceID'];
			$required = array('deviceID', 'memberID','voucherID',"version","branchCode","locID",'userID','userName','designation');
			$data = process_params($default, $required, $params);
			
			// $params['officialReceipt'] = $params['transactionID'];

			die(substr($class->redeem_voucher($data), 1, -1));
			break;

		case 'redeem_voucher_wpm':
			$default = array("terminalNum" => NULL, "branchCode" => NULL, "memberID" => NULL, "transactionID" => NULL, "voucherID" => NULL, "version" => NULL, "transactionType" => NULL, "officialReceipt" => NULL, "deviceID" => NULL, 'requestID' => NULL,"status" => NULL,"userID"=>NULL,"userName"=>NULL,"designation"=>NULL);
			$required = array('deviceID', 'memberID','requestID','voucherID','status','userID','userName','designation');
			$data = process_params($default, $required, $params);
			// $params['terminalNum'] = $params['deviceID'];
			$params['officialReceipt'] = $params['transactionID'];

			die(substr($class->redeem_voucher_wpm($data), 1, -1));
			break;

		case 'redeem_notification_voucher':
			$default = array("terminalNum" => NULL, "branchCode" => NULL, "memberID" => NULL, "transactionID" => NULL, "voucherID" => NULL, "version" => NULL, "transactionType" => NULL, "officialReceipt" => NULL, "deviceID" => NULL,"platform" => NULL);
			$required = array("memberID","voucherID","platform","deviceID");
			$data = process_params($default, $required, $params);

			die(substr($class->redeem_notification_voucher($data), 1, -1));
			break;

		case 'redeem_notification_rewards':
			$default = array("terminalNum" => NULL, "branchCode" => NULL, "memberID" => NULL, "transactionID" => NULL, "rewardsID" => NULL, "version" => NULL, "transactionType" => NULL, "officialReceipt" => NULL, "deviceID" => NULL,"platform"=> NULL, "barCode" => NULL);
			$required = array("memberID","rewardsID","platform");
			$data = process_params($default, $required, $params);

			die(substr($class->redeem_notification_rewards($data), 1, -1));
			break;
			
		default:
			die($error000);
			break;
	}

?>