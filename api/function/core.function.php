<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	require_once ('api/class/core.class.php');

	$class = new core();
	$file = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	
	switch ($_GET['function']) {
		case 'test':
			$default = array("host" => NULL, "un" => NULL, "pw" => NULL, "db" => NULL);
			$required = array();
			$data = process_params($default, $required, $params);

			die($class->test($data));
			break;

		case 'user_roles':
			$default = array('accountID'=>NULL, "mroles"=> NULL);
			$required = array('accountID', "mroles");
			$data = process_params($default, $required, $params);			

			die($class->user_roles($data));
			break;
				
		case 'temp_registration':
			$default = array("email" => NULL, "password" => NULL, "fname" => NULL, "mname" => NULL, "lname" => NULL, "mobileNum" => NULL, "landlineNum" => NULL, "dateOfBirth" => NULL, "gender" => NULL, "address1" => NULL, "address2" => NULL, "city" => NULL, "province" => NULL, "region" => NULL, "zipcode" => NULL, "civilStatus" => NULL, "occupation" => NULL, "regtype" => "app", "platform" => NULL, "qrCard" => NULL, "locID" => NULL, "serverName"=>NULL,"pinCode"=>NULL,"sendToEmail"=>NULL,"nationality"=> NULL);

			if ($params['email'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"email address is empty.")));
					die();
			}elseif (!validate_email($params['email'])) {
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid E-Mail Address")));
				die();
			}
			if($params['password'] < 6){
				return json_encode(array(array("response"=>"Error", "description" =>"Password must not lesser than 6 characters." )));
				die();
			}

			$required = array('email', 'fname', 'lname', 'password', 'dateOfBirth');
			$data = process_params($default, $required, $params);

			die($class->temp_registration($data));
			break;
		
		case 'activation':
			$default = array("email" => NULL, "activation" => NULL);
			
			if ($params['email'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"email address is empty.")));
					die();
			}elseif (!validate_email($params['email'])) {
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid E-Mail Address")));
				die();
			}


			$required = array('email', 'activation');
			$data = process_params($default, $required, $params);

			die($class->activation($data));
			break;

		case 'unlock':
			$default = array("email" => NULL);

			if ($params['email'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"email address is empty.")));
					die();
			}elseif (!validate_email($params['email'])) {
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid E-Mail Address")));
				die();
			}

			$required = array('email');
			$data = process_params($default, $required, $params);

			die($class->unlock($data));
			break;

		case 'resend_activation':
			$default = array("email" => NULL);
			
			// $required = array('email');
			// $data = process_params($default, $required, $params);


			if (!validate_email($params['email'])) {
				die(json_encode(array(array("response" => "Error", "description" => "Please input an email address." ))));
			}

			if($params['email'] == ""){
				die(json_encode(array(array(array("response"=>"Error", "description"=>"email address is empty.")))));
			}
			$data = array_merge($default, $params);
			die($class->resend_activation($data));
			break;

		case 'reset_password':
		
			$default = array('email' => NULL);
			
			 // $required = array('email');
			
			if (!validate_email($params['email'])) {
				die(json_encode(array(array("response" => "Error", "description" => "Please input an email address." ))));
			}

			if($params['email'] == ""){
				die(json_encode(array(array(array("response"=>"Error", "description"=>"email address is empty.")))));
			}
			$data = array_merge($default, $params);
			die($class->reset_password($data));
			break;

		case 'change_password':
		
			$default = array('email' => NULL, "password"=>NULL);
			
			if (!validate_email($params['email'])) {
				die(json_encode(array(array("response" => "Error", "description" => "Please input an email address." ))));
			}

			if($params['email'] == ""){
				die(json_encode(array(array(array("response"=>"Error", "description"=>"email address is empty.")))));
			}
			$data = array_merge($default, $params);
			die($class->change_password($data));
			break;

		case 'social_login':
			$default = array("email" => NULL, "pid" => NULL, "platform" => NULL, "deviceID" => NULL, "fname" => NULL, "mname" => NULL, "lname" => NULL, "dateOfBirth" => NULL, "gender" => NULL, "image" => NULL, "emailFromFB" => NULL, "regtype" => NULL, "memberCode" => NULL, "locID" => NULL,  "mobileNum" => NULL, "qrCard" => NULL,"password"=>NULL, "pinCode" => NULL,"serverName" => NULL, "emailFromFB" => NULL);
			$required = array('email',"platform","deviceID","password");
			$data = process_params($default, $required, $params);

			if($params['password'] < 6){
				return json_encode(array(array("response"=>"Error", "description" =>"Password must not lesser than 6 characters." )));
				die();
			}

			die($class->social_login($data));
			break;
		
		case 'app_login':
			$default = array("email" => NULL, "password" => NULL, "platform" => NULL, "deviceID" => NULL);
			$required = array('email',"platform","deviceID");
			$data = process_params($default, $required, $params);

			die($class->app_login($data));
			break;

		case 'complete_data':
			$default = array("memberID" => NULL);
			$required = array('memberID');
			$data = process_params($default, $required, $params);

			die($class->complete_data($data));
			break;
			
		case 'update_member_profile':
			$default = array("memberID" => NULL, "fname" => NULL, "lname" => NULL, "gender" => NULL, "mobileNum" => NULL, "dateOfBirth" => NULL, "address1" => NULL, "address2" => NULL, "email"=> NULL, "platform"=>NULL, "regtype" => NULL);
			$required = array('memberID');
			$data = process_params($default, $required, $params);

			die($class->update_member_profile($data));
			break;

		case 'my_voucher_list':
			$default = array("memberID" => NULL);
			$required = array('memberID');
			$data = process_params($default, $required, $params);

			die($class->my_voucher_list($data));
			break;

		case 'change_password':
			$default = array("email"=> NULL,"password"=>NULL,"newpassword"=>NULL);

			if ($params['email'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"email address is empty.")));
					die();
			}elseif (!validate_email($params['email'])) {
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid E-Mail Address")));
				die();
			}
			if($params['password'] < 6){
				return json_encode(array(array("response"=>"Error", "description" =>"Password must not lesser than 6 characters." )));
				die();
			}
			if ($params['password'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"Password is empty.")));
					die();
			}
			if($params['newpassword'] < 6){
				return json_encode(array(array("response"=>"Error", "description" =>"New password must not lesser than 6 characters." )));
				die();
			}
			if ($params['newpassword'] == NULL ) {
					return json_encode(array(array("response"=>"Error", "description"=>"New password is empty.")));
					die();
			}

			$required = array('email','password','newpassword');
			$data = process_params($default, $required, $params);
			die($class->change_password($data));
			break;

		case 'unlock':
			$default = array("email" => NULL);
			$required = array('email');
			$data = process_params($default, $required, $params);
			die($class->unlock($data));
			break;
			
		case 'tokenizer':
			$default = array("platform" => NULL, "deviceID" => NULL, "pushID" => NULL);
			$required = array('platform','deviceID','pushID');
			$data = process_params($default, $required, $params);
			die($class->tokenizer($data));
			break;

		case 'brand_list':
			die($class->brand_list());
			break;

		case 'location_list':
			$default = array("brandID" => NULL);
			$required = array('brandID');
			$data = process_params($default, $required, $params);

			die($class->location_list($data));
			break;

		case 'verify_qrCard':
			$default = array("qrCard"=>NULL);
			$required = array('qrCard');
			$data = process_params($default, $required, $params);

			die($class->verify_qrCard($data));
			break;

		case 'add_card':
			$default = array("memberID" => NULL, "qrCard"=>NULL,"platform" => NULL);

			if($params['qrCard'] == NULL){
				die(json_encode(array(array("response"=>"Error", "description" =>"Please enter card number." ))));
				
			}

			$required = array('memberID','qrCard','platform');
			$data = process_params($default, $required, $params);

			die($class->add_card($data));
			break;

		case 'request_card':
			$default = array("id" => NULL, "requestID" => NULL, "status" => NULL, "dateAdded" => NULL, "dateModified" => NULL, "platform" => NULL, "memberID" => NULL,"fname" => NULL,"lname" => NULL, "house_no" => NULL,"street_no"=>NULL,"barangay"=>NULL,"city"=>NULL,"mobileNum"=>NULL,"telNum"=>NULL,"zipcode"=>NULL, "country"=>NULL);
			$required =array("platform","fname","lname","house_no","street_no","barangay","city","mobileNum","memberID","country");
			$data = process_params($default, $required, $params);
			die($class->request_card($data));
			break;

		case 'taxes':
			$default = array("tax"=>NULL,"description"=>NULL,"locID"=> NULL);
			$required = array("tax");
			$data = process_params($default,$required,$params);
			die($class->taxes($data));
			break;
		case 'update_taxes':
			$default = array("id"=> NULL, "tax"=> NULL,"description"=>NULL);
			$required = array("id","tax");
			$data = process_params($default, $required, $params);
			die($class->update_taxes($data));
			break;

		case 'tokenizer':
			$default = array("platform" => NULL, "deviceID" => NULL, "pushID" => NULL);
			
			if ($params['platform'] == 'ios') {
				$params['deviceID'] = str_replace('%20', '', $params['deviceID']);
				$params['deviceID'] = str_replace(' ', '', $params['deviceID']);
			}
			
			$required =array("platform","deviceID");
			die($class->tokenizer($data));
			break;

		default:
			die($error000);
			break;
	}


	function validate_email($email) {

		// $email = encrypt_decrypt('decrypt', $email);  
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