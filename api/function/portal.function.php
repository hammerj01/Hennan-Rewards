<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	require_once ('api/class/portal.class.php');

	$class = new portal();
	$file = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	
	switch ($_GET['function']) {

		case 'login':
			$default = array("mobileNumber" => NULL, "password" => NULL); 
			$required = array('mobileNumber','password');
			$data = process_params($default, $required, $params);

			die($class->login($data));
			break;

		case 'jsonlist':
			$default = array("accountID"=>NULL, "my_session_id"=>NULL, "table"=>NULL, "sortOrder"=>NULL, "pageSize"=>NULL,"currentPage"=>NULL, "searchFilter"=>NULL, "selectedStatus"=>NULL, "selectedType"=>NULL, "sortColumnName"=>NULL,"qrCard"=>NULL, "lname"=>NULL, "expiration"=>NULL, "emails"=>NULL,"brandName"=>NULL, "transactionID"=>NULL, "transactionDate"=>NULL, "email"=>NULL);
			$required = array('accountID', 'my_session_id', 'table', 'sortOrder', 'pageSize', 'currentPage');
			$data = process_params($default, $required, $params);

			die($class->jsonlist($data));
			break;

		case 'view_record':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'table'=>NULL, 'filter'=>NULL);
			$required = array('accountID', 'my_session_id', 'table', 'filter');
			$data = process_params($default, $required, $params);

			die($class->view_record($data));
			break;

		case 'get_json':

			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'table'=>NULL, 'filter'=>NULL);
			$keys = array_keys($default);

			if (!isset($params['accountID']) || $params['accountID'] == NULL) {
				die(json_encode(array(array("response" => "Error", "description" => "accountID is not defined or NULL"))));
			}
			if (!isset($params['my_session_id']) || $params['my_session_id'] == NULL) {
				die(json_encode(array(array("response" => "Error", "description" => "my_session_id is not defined or NULL"))));
			}
			if (!isset($params['table']) || $params['table'] == NULL) {
				die(json_encode(array(array("response" => "Error", "description" => "table is not defined or NULL"))));
			}
			
			$data = array_merge($default, $params);
			foreach ($params as $key => $value) {
				if (!in_array($key, $keys)) {
					$response = array(array("response"=>"Error", "description"=>"Invalid parameter ( " . $key . " )", "data"=>array($params)));
					api_logger("error", $function, $file, $response);
					die(json_encode($response));
				}
			}
			die($class->get_json($data));
			break;

		case 'update_profile':

			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'fname'=>NULL, 'lname'=>NULL, 'email'=>NULL, 'qrCard'=>NULL, 'gender'=>NULL, 'dateOfBirth'=>NULL, 'mobileNum'=>NULL,'landlineNum'=>NULL,'address1'=>NULL,'image'=>NULL);

			if (!isset($params['accountID']) || $params['accountID'] == NULL) {
				die(json_encode(array(array("response" => "Error", "description" => "accountID is not defined or NULL"))));
			}
			if (!isset($params['my_session_id']) || $params['my_session_id'] == NULL) {
				die(json_encode(array(array("response" => "Error", "description" => "my_session_id is not defined or NULL"))));
			}

			$data = array_merge($default, $params);
			echo $class->update_profile($data);
			die();
			break;  

		case 'update_password':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL,'new_password_check'=>NULL,'old_password'=>NULL, 'new_password'=>NULL);
			$required = array('accountID', 'my_session_id', 'old_password', 'new_password');
			$data = process_params($default, $required, $params);

			die($class->update_password($data));
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

			$required = array('accountID'=>NULL, 'my_session_id'=>NULL,"memberID","email", "remarks");
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