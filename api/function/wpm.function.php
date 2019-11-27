<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	require_once ('api/class/wpm.class.php');

	$class = new wpm();
	$file = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	
	switch ($_GET['function']) {

		case 'login':
			$default = array("username" => NULL, "password" => NULL); 
			$required = array('username','password');
			$data = process_params($default, $required, $params);

			die($class->login($data));
			break;

		case 'jsonlist':
			$default = array("accountID"=>NULL, "my_session_id"=>NULL, "table"=>NULL, "sortOrder"=>NULL, "pageSize"=>NULL,"currentPage"=>NULL, "searchFilter"=>NULL, "selectedStatus"=>NULL, "selectedType"=>NULL, "sortColumnName"=>NULL,"qrCard"=>NULL, "lname"=>NULL, "fname"=>NULL, "emails"=>NULL);
			$required = array('accountID', 'my_session_id', 'table', 'sortOrder', 'pageSize', 'currentPage');
			$data = process_params($default, $required, $params);

			die($class->jsonlist($data));
			break;	

		case 'redeem_campaign_wpm':
			$default = array("accountID"=>NULL, "my_session_id"=>NULL,"memberID"=>NULL,"requestID"=> NULL,"rewardsID"=> NULL,"barCode" =>NULL,"transactionID"=>NULL,"email"=>NULL,"rewards_name"=>NULL,"promoType"=>NULL,"points"=>NULL,"status"=>NULL,"totalPoints" => NULL,"locID"=> NULL, "locName" => NULL,"platform"=>NULL,"userID"=> NULL,"userName"=>NULL,"designation"=>NULL);


			if ($params['email'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"Email address is empty.")));
					die();
			}elseif (!validate_email($params['email'])) {
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid E-Mail Address")));
				die();
			}elseif ($params['requestID'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"requestID is empty")));
				die();
			}elseif ($params['barCode'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"barCode is empty")));
				die();	
			}elseif ($params['totalPoints'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"current points is empty")));
				die();
			}elseif ($params['points'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"Points is empty")));
				die();
			}elseif ($params['promoType'] == NULL){
				return json_encode(array(array("response"=>"Error", "description"=>"promoType is empty")));
				die();
			}elseif ($params['status'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"status is empty")));
				die();
			}elseif ($params['transactionID'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"transactionID is empty")));
				die();
			}

			$required = array("memberID","requestID","rewardsID","barCode","transactionID","email","promoType","points","status","userName","designation");
			$data = process_params($default, $required, $params);
			die($class->redeem_campaign_wpm($data));
			break;

		case 'view_record':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'table'=>NULL, 'filter'=>NULL);
			$required = array('accountID', 'my_session_id', 'table', 'filter');
			$data = process_params($default, $required, $params);

			die($class->view_record($data));
			break;
		
		case 'change_email':
			$default = array("memberID" => NULL, "email" => NULL,"newEmail" => NULL, "remarks"=> NULL, "accountID"=> NULL, "my_session_id" => NULL);
			
			if ($params['email'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"Email address is empty.")));
					die();
			}elseif (!validate_email($params['email'])) {
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid E-Mail Address")));
				die();
			}

			if ($params['remarks'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"Remark is empty.")));
					die();
			}

			if ($params['newEmail'] == NULL ) {
				return json_encode(array(array("response"=>"Error", "description"=>"Email address is empty.")));
				die();
			}elseif (!validate_email($params['newEmail'])) {
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid E-Mail Address")));
				die();
			}

			$required = array('accountID', 'my_session_id',"memberID","email", "remarks");
			$data = process_params($default, $required, $params);			

			die($class->change_email($data));
			break;

		case 'change_dateOfBirth':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL,"memberID" => NULL,"dateOfBirth" => NULL,"newdateOfBirth" => NULL,"remarks"=> NULL);

			if ($params['remarks'] == NULL ) {
				return json_encode(array(array("response"=>"Error", "description"=>"Remark is empty.")));
				die();
			}elseif ($params['dateOfBirth'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"date of birth is empty.")));
				die();
			}elseif ($params['newdateOfBirth'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"new date of birth is empty.")));
				die();
			}

			$required = array("memberID","dateOfBirth","remarks");
			$data = process_params($default, $required, $params);			
			die($class->change_dateOfBirth($data));
			break;

		case 'change_vouchers':	
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL,"memberID" => NULL,"voucherID" => NULL,"id" => NULL,"status" => NULL, "newStatus"=> NULL,"trans"=>NULL ,"remarks"=> NULL);	

			if ($params['remarks'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"Remark is empty.")));
				die();
			}elseif ($params['memberID'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"memberID is empty.")));
				die();
			}elseif ($params['voucherID'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>" voucherID is empty.")));
				die();
			}elseif ($params['status'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>" status is empty.")));
				die();
			}elseif ($params['newStatus'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>" newstatus is empty.")));
				die();
			}elseif ($params['trans'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>" transaction status is empty.")));
				die();
			}

			$required = array("memberID","voucherID","status","newStatus","remarks", "trans");
			$data = process_params($default, $required, $params);	
			die($class->change_vouchers($data));
			break;

		case 'change_card':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL,"memberID" => NULL,"qrCard" => NULL,"newqrCard" => NULL,"remarks"=> NULL);

			if ($params['remarks'] == NULL ) {
				return json_encode(array(array("response"=>"Error", "description"=>"Remark is empty.")));
				die();
			}elseif ($params['qrCard'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>"qrCard is empty.")));
				die();
			}elseif ($params['newqrCard'] == NULL) {
				return json_encode(array(array("response"=>"Error", "description"=>" new qrCard is empty.")));
				die();
			}

			$required = array("memberID","qrCard","newqrCard","remarks");
			$data = process_params($default, $required, $params);			
			die($class->change_card($data));
			break;

		case 'setIDs':
			die($class->setIDs());
			break;

		case 'available_points':
			die($class->available_points());
			break;

		case 'wpmTransactionID':
				
			die($class->wpmTransactionID);
			break;
		case 'change_password':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL,"memberID" => NULL,"email"=> NULL);	
			if ($params['email'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"Email address is empty.")));
					die();
			}elseif (!validate_email($params['email'])) {
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid E-Mail Address")));
				die();
			}

			$required = array('accountID', 'my_session_id',"memberID","email");
			$data = process_params($default, $required, $params);			

			die($class->change_password($data));
			break;

		case 'user_roles':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, "mroles"=> NULL);
			$required = array('accountID', 'my_session_id',"mroles");
			$data = process_params($default, $required, $params);			

			die($class->user_roles($data));
			break;
		default:
			die($error000);
			break;
	}

	function validate_email($email) {

		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		} else {
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				// echo "local part length exceeded<br/>";
				// local part length exceeded
				$isValid = false;
			}
			else if ($domainLen < 1 || $domainLen > 255) {
				// echo "domain part length exceeded<br/>";
				// domain part length exceeded
				$isValid = false;
			}
			else if ($local[0] == '.' || $local[$localLen-1] == '.') {
				// echo "local part starts or ends with '.'<br/>";
				// local part starts or ends with '.'
				$isValid = false;
			}
			else if (preg_match('/\\.\\./', $local)) {
				// echo "local part has two consecutive dots<br/>";
				// local part has two consecutive dots
				$isValid = false;
			}
			else if (!preg_match('/^[A-z0-9\\+-\\.]+$/', $domain)) {
				// echo "character not valid in domain part<br/>";
				// character not valid in domain part
				$isValid = false;
			}
			else if (preg_match('/\\.\\./', $domain)) {
				// echo "domain part has two consecutive dots<br/>";
				// domain part has two consecutive dots
				$isValid = false;
			}
			else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
				// echo "character not valid in local part unless <br/>";
				// character not valid in local part unless 
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
					// local part is quoted
					// echo "local part is quoted<br/>";
					$isValid = false;
				}
			}
			// echo gethostbyname($domain);
			// $dns_get_record = dns_get_record($domain);
			// var_dump($dns_get_record);
			/*
			// if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
			if ($isValid && !(checkdnsrr($domain,"ANY"))) {
				echo "domain not found in DNS<br/>";
				 // domain not found in DNS
				 $isValid = false;
			}
			*/

		}

		return $isValid;
	}

?>