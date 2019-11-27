<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class core extends mailer {
		public static $file_name = 'core.class.php';

		public function test($param) {
			die(self::$file_name);

			$email_params = array(
				'recipient' => 'karlo@appsolutely.ph',
				'recipient_name' => 'Jim Karlo P. Jamero',
				'subject' => 'Test E-Mail',
				'message' => 'This is only a test message!',
				'attachment' => NULL);

			return json_encode($this->send_mail($email_params));
		}

		public function user_roles($param){
			global $logs;
			global $protocol;
			$data = (object) $param;
			$file_name = 'points.class.php';

			$arr = json_decode($data->mroles,true);			
			// return json_encode(array("result"=>$a));
			
			$check_user = process_qry("SELECT * FROM `accounts` WHERE accountID = ? AND role in ('superadmin', 'administrator') and status = 'active' ",array($data->accountID),$logs);
			if(count($check_user) <= 0 ){
				return json_encode(array(array("response" => "Error", "description" =>"User is not authorized to create user priviledges.")));
			}

			echo $arr[0]['roles'][0][1] . ' ' . $arr[0]["memberID"] ; 
			var_dump($arr);

			for ($i=0; $i < count($arr[0]); $i++) { 
				# code...
				echo $arr[0][$i];
			}

		}


		public function temp_registration($param) {
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			// $activation = md5(uniqid(rand(), true));
			$activation = str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT); //. DATE("s").randomizer(4);
			$output = array();
			$data = (object) $param;
			
			// $$param['activation'] = $activation;
			$param['password'] =   $data->password; //str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT);
			$param['title_type'] = "temp_registration";
			$param['procedure'] = "global_temp_registration";

			$check_registration_mobile_from_memberstable = process_qry("SELECT count(*) AS `count`,`email`,`mobileNum` FROM `memberstable` WHERE `email` = ?  LIMIT 1", array($data->email), $logs);
	
			$result = "Available";

			if ($check_registration_mobile_from_memberstable[0]['count'] > 1) {
				$result = "Existing";
				// $check_registration_mobile_from_temp_memberstable = process_qry("SELECT count(*) AS `count`,`email`,`mobileNum` FROM `temp_memberstable` WHERE `email` = ? LIMIT 1", array($data->email), $logs);
				// if ($check_registration_mobile_from_temp_memberstable[0]['count'] < 1) {
				// 	$result = "Available";
				// }
			}
				$emailIsExist = process_qry("SELECT * FROM `memberstable` WHERE `email` = ? ", array($data->email),$logs);
				if(count($emailIsExist) > 0 ){
					return json_encode(array(array("response"=>"Error", "description"=>"Email already exist.")));
				}

			if ($result == "Existing") {
				$logs->write_logs('Error - Temp Registration', $file_name, array(array("response"=>"Error", "description"=>"Existing mobile Number", "data"=>array(array("email"=>$data->email)))));
				return json_encode(array(array("response"=>"Error", "description"=>"Existing email address", "data"=>array(array("email"=>$data->email)))));		
			}
				
			// $chck_mobile = $this->validate_mobile_num($data->mobileNum);
			// if ($chck_mobile == 'Invalid'){
			// 	$logs->write_logs('Error - Temp Registration', $file_name, array(array("response"=>"Error", "description"=>"Invalid mobile number.", "data"=>array(array("email"=>$data->mobileNum)))));
			// 	return json_encode(array(array(array("response"=>"Error", "description" => "Invalid mobile number."))));
			// }

			$mage = process_qry("selecT TIMESTAMPDIFF(YEAR, ?, CURDATE()) as `age`",array($data->dateOfBirth), $logs);
			
			if ($mage[0]['age'] < 18){
				return json_encode(array(array("response" => "Error", "description" => "Age should be above 18 years old.")));
			}
			
			$decrypt_password = encrypt_decrypt('encrypt', $data->password);
			$global_temp_registration = process_qry("CALL global_temp_registration(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($data->email, $decrypt_password, $data->fname, $data->mname, $data->lname, $data->mobileNum, $data->landlineNum, $data->dateOfBirth, $data->gender, $data->address1, $data->address2, $data->city, $data->province, $data->region, $data->zipcode, $data->civilStatus, $data->occupation, $data->regtype, $data->platform, $activation, $data->qrCard,  $data->locID, $data->serverName, $data->nationality), $logs);
			
			if ($global_temp_registration[0]['result'] == "Success") {

					$subject = "Welcome to " . MERCHANT . "!";

					if (!$data->fname && !$data->lname) {
						$recipient_name = "Valued Customer";
					} else {
						$recipient_name = $data->fname." ".$data->lname;
					}

					$this->emailer(array("recipient_name" => $recipient_name, "email" => $data->email, "password" => $data->password, "subject" => $subject, "type" => "temp_registration", "emailFromFB" => NULL,"activation" => $activation));
					$logs->write_logs("Success - Temp Registration", $file_name, array(array("response"=>"Success", "data"=>array($param))));
					return json_encode(array(array("response"=>"Success", "data"=>array(array("description"=>"Please check your email to activate your account.")))));							
			}

			$logs->write_logs('Error - Temp Registration', $file_name, array(array("response"=>"Error", "description"=>"Unable to complete registration process.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "description"=>"Unable to complete registration process.")));
		}
		// ==== end of temp_registration code

 		// ==== start of activation
		public function activation($param){
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
	
			$step_1 = process_qry("SELECT `id`, count(*) AS `count`,`qrCard`,`dateCardPurchased` FROM `temp_memberstable` WHERE `activation` = ? AND `email` = ? LIMIT 1", array($data->activation, $data->email), $logs);
			
			if ($step_1[0]['count'] > 0) {
				$x = 0;
				$xx = 0;
				$memberID = NULL;
				$qrApp = NULL;
				$overide_img_url = NULL;
				if($step_1[0]['qrCard'] != NULL){
					// echo "string";
					$Isactive = process_qry("SELECT  count(*) AS `count` from `cardseries` WHERE `cardNum` = ? and status = 'active' and `memberID` IS not NULL" , array($step_1[0]['qrCard']), $logs);
					
					$IsExist = process_qry("SELECT  count(*) AS `count` from `memberstable` WHERE `qrCard` = ? " , array($step_1[0]['qrCard']), $logs);
					
					if($Isactive[0]['count'] > 0 || $IsExist[0]['count'] > 0){
						
						$logs->write_logs("Error - Activation", $file_name, array(array("response"=>"Error", "description"=>"Card is in use.", "data"=>array("qrCard"=>$step_1[0]['qrCard']))));
						return json_encode(array(array("response"=>"Error", "description"=>"Card is in use.")));
					}
				}

				while ($x < 1) {
					$memberID = substr("MEM" . randomizer(2) . DATE("H") . $step_1[0]['id'] . DATE("s") . randomizer(6), 0, 16);
					$step_2 = process_qry("SELECT count(*) AS `count` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($memberID), $logs);
					$x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
				}

				$entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `memberstable` LIMIT 1", array(), $logs);

				while ($xx < 1) {
					$qrApp =  substr(DATE("Y") . str_pad($entry[0]['entry'], 12, "0", STR_PAD_LEFT), 0, 16);
					
					$step_3 = process_qry("SELECT count(*) AS `count` FROM `memberstable` WHERE `qrApp` = ? LIMIT 1", array($qrApp), $logs);
					$xx = ( $step_3[0]['count'] < 1 ) ? 1 : 0;

				}

				$emailIsExist = process_qry("SELECT * FROM `memberstable` WHERE `email` = ? ", array($data->email),$logs);
				if(count($emailIsExist) > 0 ){
					return json_encode(array(array("response"=>"Error", "description"=>"Email already exist.")));
				}


				$step_3 = process_qry("CALL `global_activation_email`(?, ?, ?, ?, ?)", array($data->email, $memberID, $qrApp,$step_1[0]['qrCard'],$step_1[0]['dateCardPurchased']), $logs);
				// echo $step_3[0]['result'];
				// var_dump($step_3);
				// die();
				if ($step_3[0]['result'] == "Success") {
					if (($step_3[0]['regtype'] == "facebook") || ($step_3[0]['regtype'] == "google")) {
						if ($step_3[0]['image']) {
							$temp_image = explode("?", $step_3[0]['image']);
							$step_3[0]['image'] = ( count($temp_image) > 2 ) ? $temp_image[0] . '?' . $temp_image[1] : $temp_image[0];
							$overide_img_url = $this->overide_img_url(array("memberID" => $memberID, "image" => $step_3[0]['image']));
						}
					}
				}
				// echo $earn_voucher = $this->voucher_earn($memberID);

				$logs->write_logs("Success - Activation", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$dateNow = DATE('Y-m-d H:i:s');
				$success = process_qry("INSERT INTO cron_activation_success(`memberID`,`email`,`activation`,`qrApp`,`fname`,`lname`,`emailSent`,`dateAdded`,`dateModified`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)", array($memberID,$data->email,$data->activation,$qrApp,$step_3[0]['fname'],$step_3[0]['lname'],"false",$dateNow,$dateNow), $logs);

				return json_encode(array(array("response"=>"Success", "data"=>array($param))));
			} else {
				$step_2 = process_qry("SELECT count(*) AS `count` FROM `memberstable` WHERE `email` = ? AND NOW() <= DATE_ADD(`dateAdded`, INTERVAL 5 MINUTE)  LIMIT 1", array($data->email), $logs);

				if ($step_2[0]['count'] > 0) {
					return json_encode(array(array("response"=>"Success", "data"=>array($param))));
				}
			}

			$logs->write_logs("Error - Activation", $file_name, array(array("response"=>"Error", "description"=>"Invalid verification code.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "description"=>"Invalid verification code")));
				
		}
		// ==== end of activation code

		// ==== start unlock
		public function unlock($param) {
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "global_unlock";
			$_POST['title_type'] = "unlock";
	
			$step_1 = process_qry("SELECT COUNT(*) AS `count`,`fname` FROM `memberstable` WHERE `email` = ? LIMIT 1", array($data->email), $logs);

			if($step_1[0]['count'] > 0){
				$unlock = process_qry("CALL `global_unlock_email`(?)", array($data->email), $logs);
			}else{
				$logs->write_logs("Error - Unlock", $file_name, array(array("response"=>"Error", "description"=>"Email not found. ", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Email not found."  )));
			}

			if ($unlock[0]['result'] == "Success") {
				$_POST['password'] =  str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT);
				$logs->write_logs("Success - Unlock", $file_name, array(array("response"=>"Success", "data"=>array($param))));

				// $this->emailer(array("recipient_name" => $step_1[0]['fname'], "email" => $data->email, "password" => $_POST['password'], "subject" => "Unlock Account","activation" => NULL, "type" => "unlock", "emailFromFB" => NULL));
				return json_encode(array(array(array("response"=>"Success", "data"=>array($param)))));
			} else {
				$logs->write_logs("Error - Unlock", $file_name, array(array("response"=>"Error", "description"=>"Unable to complete unlocking process.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Unable to complete unlocking process."  )));
			}
		}
		// === end for unlock

		// === start resend activation
		public function resend_activation($param) {
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;

			$_POST['title_type'] = "resend_activation";
			$param['procedure'] = "global_reactivation_email";
	
			$step_1 = process_qry("SELECT COUNT(*) AS `count` FROM `memberstable` WHERE `email` = ? LIMIT 1", array($data->email), $logs);
			
			if ($step_1[0]['count'] > 0) {
				$logs->write_logs("Error - Resend Activation", $file_name, array(array("response"=>"Error", "description"=>"Account has already been activated.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Account has already been activated.")));
			} else {
				
				$step_2 = process_qry("SELECT COUNT(*) AS `count`, `fname`,`lname` FROM `temp_memberstable` WHERE `email` = ? LIMIT 1", array($data->email), $logs);
				
				
			}	



				if ($step_2[0]['count'] <= 0) {
					$logs->write_logs("Error - Resend Activation", $file_name, array(array("response"=>"Error", "description"=>"Account not yet registered.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Account not yet registered.")));
				} else {
  				    $activation =  str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT); 
					$step_3 = process_qry("CALL `global_reactivation_email`(?, ?)", array($data->email, $activation), $logs);
					
					// $_POST['resend_activation'] = 'resend_activation';
					// $_POST['activation'] = $activation;
					// $this->emailer(array("recipient_name" => $step_2[0]['fname'], "email" => $data->email, "subject" => "Activation code", "activation" => $_POST['activation'], "type" => "resend_activation", "emailFromFB" => NULL));
					$logs->write_logs("Success - Resend Activation for email", $file_name, array(array("response"=>"Success", "data"=>array($param))));
					$dateNow = DATE('Y-m-d H:i:s');
					$insert = process_qry("INSERT INTO cron_resend_activation(`email`,`activation`,`fname`,`lname`,`emailSent`,`dateAdded`,`dateModified`) VALUES(?, ?, ?, ?, ?, ?, ?)", array($data->email,$activation,$step_2[0]['fname'],$step_2[0]['lname'],"false",$dateNow,$dateNow), $logs);
					return json_encode(array(array("response"=>"Success", "data"=>array(array("description"=>"Please check your email for the activation code.")))));	
				}
			
			$logs->write_logs("Error - Resend Activation", $file_name, array(array("response"=>"Error", "description"=>"Unable to complete resending of activation process.", "data"=>array($param))));
			return json_encode(array(array(array("response"=>"Error", "description"=>"Unable to complete resending of activation process."))));
		}
		// === end resend activation

		// === start  reset_password
		public function reset_password($param) {
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "global_reset_password";
			$param['procedure'] = "reset_password";
			$_POST['title_type'] = "reset_password";

			$step_1 = process_qry("SELECT COUNT(*) AS `count`,`memberID`,`fname`,`lname` FROM `memberstable` WHERE `email` = ? LIMIT 1", array($data->email), $logs);

			if ($step_1[0]['count'] > 0) {
				// $password = encrypt_decrypt('encrypt', $data->password);  //str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT);
				// $_POST['password'] = $password;
				$subject = 'Reset Password Link';

				// $step_3 = process_qry("CALL `global_reset_password_email`(?, ?)", array($data->email, $password), $logs);
				// $this->emailer(array("recipient_name" => $step_1[0]['fname'], "email" => $data->email, "password" => $data->password, "subject" => $subject, "activation" => NULL, "type" => "reset_password", "emailFromFB" => NULL));
				$logs->write_logs("Success - Reset Password for email", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$dateNow = DATE('Y-m-d H:i:s');
				$insert_cron_resetpassword = process_qry("INSERT INTO cron_reset_password(`memberID`,`email`,`fname`,`lname`,`emailSent`,`description`,`dateAdded`,`dateModified`)VALUES(?,?,?,?,?,?,?,?)",array($step_1[0]['memberID'],$data->email, $step_1[0]['fname'],$step_1[0]['lname'],'false',NULL,$dateNow,$dateNow),$logs);
				return json_encode(array(array("response"=>"Success", "data"=>array(array("description"=>"Your password has been sent to your email. Please check our message and login again.")))));				
			} else {
				$logs->write_logs("Error - Reset Password", $file_name, array(array("response"=>"Error", "description"=>"Account not yet registered.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Account not yet registered.")));
			}
			
			$logs->write_logs("Error - Reset Password", $file_name, array(array("response"=>"Error", "description"=>"Unable to complete password reseting process.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "description"=>"Unable to complete password reseting process.")));
		}
		//=== end reset_password

		// === start  reset_password
		public function change_password($param) {
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
		
			// $data->email =encrypt_decrypt('decrypt', $data->email); 

			$step_1 = process_qry("SELECT COUNT(*) AS `count`,`memberID`,`fname`,`lname` FROM `memberstable` WHERE `email` = ? LIMIT 1", array($data->email), $logs);

			if ($step_1[0]['count'] > 0) {

				$password = encrypt_decrypt('encrypt', $data->password);  
				//str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT);
				// $_POST['password'] = $password;
				// $subject = 'Reset Password Link';

				$step_3 = process_qry("UPDATE `memberstable` set `password` = ? WHERE email = ? LIMIT 1", array($password, $data->email), $logs);
				$logs->write_logs("Success - Change Password", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				
				return json_encode(array(array("response"=>"Success", "data"=>array(array("description"=>"You have successfully change your password. You may login again.")))));				
			} else {
				$logs->write_logs("Error - Change Password", $file_name, array(array("response"=>"Error", "description"=>"Account not yet registered.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Account not yet registered.")));
			}
			
			$logs->write_logs("Error - Reset Password", $file_name, array(array("response"=>"Error", "description"=>"Unable to complete password reseting process.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "description"=>"Unable to complete password reseting process.")));
		}
		//=== end reset_password

		// === start social_login
		public function social_login($param) {
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
			$data->memberCode = $data->qrCard;
				
			// $activation = ( $data->emailFromFB == 'true' ) ? NULL : md5(uniqid(rand(), true));
			$activation = str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT); 
			$_POST['title_type'] = "social_login";
			$_POST['activation'] = $activation; 
			// $_POST['password'] = str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT);
			$param['procedure'] = "global_social_login";

			$step_1 = process_qry("SELECT count(*) AS `count`,`email`,`mobileNum` FROM `memberstable` WHERE `email` = ?", array($data->email), $logs);
			$result = "Existing";

			if ($step_1[0]['count'] < 1) {
				$step_2 = process_qry("SELECT count(*) AS `count`,`id`,`email`,`mobileNum` FROM `temp_memberstable` WHERE `email` = ?  LIMIT 1", array($data->email), $logs);

				if ($step_2[0]['count'] < 1) {
					$result = "Available";
				}else{
					$logs->write_logs('Error - Temp Registration', $file_name, array(array("response"=>"Error", "description"=>"Existing email address", "data"=>array(array("email"=>$data->email)))));
					return json_encode(array(array("response"=>"Error", "description"=>"Existing email address", "data"=>array(array("email"=>$data->email)))));
				}
			} 	

			if ($result == "Existing"){
				$logs->write_logs('Error - Temp Registration', $file_name, array(array("response"=>"Error", "description"=>"Existing email address", "data"=>array(array("email"=>$data->email)))));
				return json_encode(array(array("response"=>"Error", "description"=>"Existing email address", "data"=>array(array("email"=>$data->email)))));
			}

			$x = 0;
			$xx = 0;
			$memberID = NULL;
			$qrApp = NULL;
			// $password = randomizer(4);

			while ($x < 1) {
				$memberID = substr("MEM" . randomizer(2) . DATE("H") . $step_2[0]['id'] . DATE("s") . randomizer(6), 0, 16);
				$step_3 = process_qry("SELECT count(*) AS `count` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($memberID), $logs);
				$x = ( $step_3[0]['count'] < 1 ) ? 1 : 0;
			}

			$entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `memberstable` LIMIT 1", array(), $logs);

			while ($xx < 1) {
				// $qrApp = substr("APP-" . randomizer(4) . "-" . str_pad($step_2[0]['id'], 5, "0", STR_PAD_LEFT), 0, 16);
				$qrApp = substr(DATE("Y") . str_pad($entry[0]['entry'], 12, "0", STR_PAD_LEFT), 0, 16);
				$step_4 = process_qry("SELECT count(*) AS `count` FROM `memberstable` WHERE `qrApp` = ? LIMIT 1", array($qrApp), $logs);
				$xx = ( $step_4[0]['count'] < 1 ) ? 1 : 0;
			}

			$logs->write_logs("facebook data - Facebook Registration", $file_name, array(array("response"=>"Success", "data"=>array(array($data->email, $data->pid, $data->deviceID, $data->platform, $data->fname, $data->mname, $data->lname, $data->dateOfBirth, $data->gender, $data->image, $activation, $data->regtype, $data->memberCode, $data->locID, $memberID, $qrApp,  $data->password,  $data->mobileNum)))));

			$step_5 = process_qry("CALL global_social_login(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,  ?, ?, ?, ?)", array($data->email, $data->pid, $data->deviceID, $data->platform, $data->fname, $data->mname, $data->lname, $data->dateOfBirth, $data->gender, $data->image, $activation, $data->regtype, $data->memberCode, $data->locID, $memberID, $qrApp, $data->password, $data->mobileNum), $logs);

			if ($step_5[0]['result'] == "Success") {
				$subject = "Welcome to " . MERCHANT;

				if (!$data->fname && !$data->lname) {
					$recipient_name = "Valued Customer";
				} else {
					$recipient_name = $data->fname." ".$data->lname;
				}

				$update_reactivate = process_qry("UPDATE `memberstable` SET `activation`=? WHERE `email` = ?",array($_POST['activation'], $data->email),$logs);

					$logs->write_logs("facebook data - Facebook email Registration ", $file_name, array(array("response"=>"Success", "data"=>array(array("email"=>$data->email,"pid"=> $data->pid,"deviceID"=> $data->deviceID,"platform" => $data->platform, "fname"=>$data->fname,"mname"=> $data->mname,"lname"=> $data->lname,"dateOfBirth"=> $data->dateOfBirth,"gender"=> $data->gender,"image"=> $data->image,"activation"=> $activation,"regtype"=> $data->regtype, "memberCode"=>$data->memberCode,"locID"=> $data->locID,"memberID"=> $memberID,"qrApp"=> $qrApp,"password"=> $data->password,"mobile"=> $data->mobileNum)))));
					$this->emailer(array("recipient_name" => $recipient_name, "email" => $data->email, "password" => $data->password, "subject" => $subject, "activation" => $activation, "type" => "social_login", "emailFromFB" => $data->emailFromFB,"activation"=>$activation));

					// $insert_queueing = process_qry("INSERT INTO ")

				
				$logs->write_logs("Success - Facebook Login", $file_name, array(array("response"=>"Success", "data"=>array($param))));

				if ($data->emailFromFB == 'false' || $data->emailFromFB == false) {
					
					// if($data->sendToEmail == true){
					// 	return json_encode(array(array("response"=>"Success", "data"=>array(array("description"=>"Please check your email for the activation code.")))));
					// }else{
					// 	return json_encode(array(array("response"=>"Success", "data"=>array(array("description"=>"Please check your mobile number for the activation code.")))));
					// }

					$activation_prcss = process_qry("call `activation_email`(?,?)",array($data->email,$activation),$logs);
					
					if($activation_prcss[0]['result'] == "Success"){
						return json_encode(array(array("response"=>"Success", "data"=>array(array("description"=>"Please verify by clicking the activation link that has been sent to your email. Kindly check the spam folder in case it is not in your inbox.")))));
					}
				} else {
					if ($data->image) {
						$overide_img_url = $this->overide_img_url(array("memberID" => $memberID, "image" => $data->image));
					}

					return $this->complete_data(array("memberID"=>$memberID));
				}
			}

			$logs->write_logs("Error - Facebook Login", $file_name, array(array("response"=>"Error", "errorCode"=>"7890", "description"=>"Unable to complete login process.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "errorCode"=>"7890", "description"=>"Unable to complete login process.")));
		}
		//=== end social_login

		//=== start app_login
		public function app_login($param) {
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
	
			$param['procedure'] = "app_login";
			
			$step_1 = process_qry("SELECT COUNT(*) AS `count`, `status`, `lockStatus` FROM `memberstable` WHERE `email` = ? LIMIT 1", array($data->email), $logs);			
			$result = "Existing";

			$notActivated = process_qry("SELECT count(*) as `cnt` FROM `temp_memberstable` WHERE `email` = ? LIMIT 1", array($data->email), $logs);

			if ($step_1[0]['count']  > 0 || $notActivated[0]['cnt'] > 0 ) {

				if($notActivated[0]['cnt'] > 0){
					$logs->write_logs("Error - App Login", $file_name, array(array("response"=>"Error", "description"=>"Account does not exist.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Account is not yet activated. Please check your email.")));
				}

				if ($step_1[0]['lockStatus'] == "true") {
					
					$logs->write_logs("Error - App Login for username", $file_name, array(array("response"=>"Error", "description"=>"Account has been temporarily suspended due to numerous invalid login attempts. To unlock your account, please check your email.", "data"=>array($param))));
						return json_encode(array(array("response"=>"Error", "description"=>"Account has been temporarily suspended due to numerous invalid login attempts. To unlock your account, please check your email.")));
					
				} else {
					if ($step_1[0]['status'] == "inactive") {
						
						$logs->write_logs("Error - App Login", $file_name, array(array("response"=>"Error", "description"=>"Account is inactive.", "data"=>array($param))));
						return json_encode(array(array(array("response"=>"Error", "description"=>"Account is inactive."))));
					} else {

						$password_encrypt = encrypt_decrypt('encrypt', $data->password);
						if (isset($data->email) || $data->email != NULL){	

							$step_2 = process_qry("SELECT COUNT(*) AS `count`, `memberID` FROM `memberstable` WHERE `email` = ? AND `password` = ? LIMIT 1", array($data->email, $password_encrypt), $logs);

						}

						if ($step_2[0]['count'] > 0) {

							if (isset($data->email) || $data->email != NULL){	
								// $step_3 = process_qry("CALL `app_login_email`(?, ?, ?, ?)", array($data->platform, $data->deviceID, $step_2[0]['memberID'], $data->email), $logs);
								$step_3 = process_qry("CALL `app_login_email`(?, ?, ?, ?)", array($data->email,	$password_encrypt,$data->deviceID, $data->platform), $logs);

							}
							
							return $this->complete_data(array("memberID"=>$step_2[0]['memberID']));

						}
						//  else {

						// 	if (isset($data->email) || $data->email != NULL){	
								
						// 		$step_3 = process_qry("SELECT IF (`settings`.`attemptLimit` IS NULL, 1, `settings`.`attemptLimit`) AS `attemptLimit`, IF (`memberstable`.`loginAttempt` IS NULL, 0, `memberstable`.`loginAttempt`) AS `loginAttempt`, `memberstable`.`fname`, `memberstable`.`lname` FROM `settings`, `memberstable` WHERE `memberstable`.`email` = ? LIMIT 1", array($data->email), $logs);						

						// 	}

						// 	if ((int) $step_3[0]['loginAttempt'] < (int) $step_3[0]['attemptLimit']) {
								
						// 		if ($step_3[0]['attemptLimit'] == ($step_3[0]['loginAttempt'] + 1)) {
						// 			$password = str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT); ;
									
						// 			if (isset($data->email) || $data->email != NULL){	
						// 				$step_4 = process_qry("CALL `app_login_fail_step_1_email`(?, ?)", array($data->email, $password), $logs);
											
						// 			}

						// 			if ($step_4[0]['result'] == "Success") {
						// 				$subject = MERCHANT . ' Account Lock';

						// 				if (!$step_3[0]['fname'] && !$step_3[0]['lname']) {
						// 					$recipient_name = "Valued Customer";
						// 				} else {
						// 					$recipient_name = $step_3[0]['fname']." ".$step_3[0]['lname'];
						// 				}
						// 			}

						// 			$logs->write_logs("Error - App Login", $file_name, array(array("response"=>"Error", "description"=>"Account has been temporarily suspended due to numerous invalid login attempts. To unlock your account, please check your mobileNum.", "data"=>array($param))));
						// 			// send email
						// 			$this->emailer(array("recipient_name" => $recipient_name, "email" => $data->email, "password" => $password, "subject" => $subject, "activation" =>NULL, "type" => "unlock", "emailFromFB" => NULL));
						// 			return json_encode(array(array("response"=>"Error", "description"=>"Account has been temporarily suspended due to numerous invalid login attempts. To unlock your account, please check your email.")));

						// 		} else {
									
						// 			if (isset($data->email) || $data->email != NULL){	
						// 				if (isset($data->email) || $data->email != NULL){
						// 						$step_4 = process_qry("CALL `app_login_fail_step_2_email`(?)", array($data->email), $logs);
											
						// 				}

						// 			}
									
						// 			$logs->write_logs("Error - App Login", $file_name, array(array("response"=>"Error", "description"=>"Invalid Username or Password.", "data"=>array($param))));
						// 			return json_encode(array(array("response"=>"Error", "description"=>"Invalid Username or Password.")));
						// 		}
						// 	} else {
						// 		$logs->write_logs("Error - App Login", $file_name, array(array("response"=>"Error", "description"=>"Account has been temporarily suspended due to numerous invalid login attempts. To unlock your account, please check your mobileNum.", "data"=>array($param))));
						// 		return json_encode(array(array("response"=>"Error", "description"=>"Account has been temporarily suspended due to numerous invalid login attempts. To unlock your account, please check your email.")));
						// 	}
						// }
						
						else{
							return json_encode(array(array("response"=>"Error", "description"=>"Invalid username or password.")));
						}
					}
				}
			} else {
				$logs->write_logs("Error - App Login", $file_name, array(array("response"=>"Error", "description"=>"Account does not exist.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Account does not exist.")));
			}
		}
		// === end app_login

		// start update_member_profile
		public function update_member_profile($param){
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$data = (object) $param;
			$param['procedure'] = "global_update_member_data";

			$step_1 = process_qry("SELECT COUNT(*) AS `count`, `email`, `mobileNum`,`gender`,`dateOfBirth`,`address1`,`fname`,`lname` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($data->memberID), $logs);

			
			if ($step_1[0]['count'] > 0) {
				// if(isset($step_1[0]['email'])){
				// 	$data->email = $step_1[0]['email'];
				// }
				if(isset($step_1[0]['mobileNum'])){
					$data->mobileNum = $step_1[0]['mobileNum'];
				}

				$mage = process_qry("selecT TIMESTAMPDIFF(YEAR, ?, CURDATE()) as `age`",array($data->dateOfBirth), $logs);
		
				if ($mage[0]['age'] < 18){
					return json_encode(array(array(array("response" => "Error", "description" => "Age should be above 18 years old."))));
				}

				$step_2 = process_qry("CALL global_update_member_data(?, ?, ?, ?, ?, ?, ?, ?)", array($data->memberID, $data->fname, $data->lname, $data->gender, $data->mobileNum, $data->dateOfBirth, $data->address1,$data->address2), $logs);

				if ($step_2[0]['result'] == 'Success') {
					return $this->complete_data(array("memberID"=>$data->memberID));
				} else {
					$step_3 = process_qry("SELECT COUNT(*) AS `count` FROM `memberstable` WHERE `memberID` = ? AND `fname` = ? AND `lname` = ? AND `gender` = ? AND `mobileNum` = ? AND `dateOfBirth` = ? AND `address1` = ? LIMIT 1", array($data->memberID, $data->fname, $data->lname, $data->gender, $data->mobileNum, $data->dateOfBirth, $data->address1), $logs);

					if ($step_3[0]['count'] > 0) {
						return $this->complete_data(array("memberID"=>$data->memberID));
					}
				}
			}

			$logs->write_logs("Error - Update Member Profile", $file_name, array(array("response"=>"Error", "description"=>"Unable to update profile.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "errorCode"=>"7890", "description"=>"Unable to update profile.")));		

		}
		// end update_member_profile

		// === start complete_data
		public function complete_data($param) {
			global $logs;
			global $protocol;
			global $error000;
			$file_name = 'core.class.php';
			$data = (object) $param;
			$param['procedure'] = "global_complete_data";

			$step_1 = process_qry("SELECT COUNT(*) AS `count`,`totalPoints` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($data->memberID), $logs);

			if ($step_1[0]['count'] > 0) {
				$total_earn = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `earntable` WHERE `memberID` = ? AND `status` = 'true'", array($data->memberID), $logs);

				// $total_earn[0]['points'] = (int) $total_earn[0]['points'];
				// $total_redeem = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `redeemtable` WHERE `memberID` = ? AND `status` = 'true'", array($data->memberID), $logs);
				$level = process_qry("SELECT `levelID`, `name`, `max` FROM `leveltable` WHERE CAST(? AS UNSIGNED) >= `min` AND `status` = 'active' ORDER BY `min` DESC LIMIT 1", array($total_earn[0]['points']), $logs);
				// $sub_total = ($total_earn[0]['points'] - $total_redeem[0]['points']);
				$sub_total =  $step_1[0]['totalPoints'];
				$last_transaction = process_qry("SELECT (a.`dateAdded`) AS `dateAdded`, `locID` FROM (SELECT `dateAdded`, `locID` FROM `earntable` WHERE `memberID` = ? UNION SELECT `dateAdded`, `locID` FROM `redeemtable` WHERE `memberID` = ? UNION SELECT `dateAdded`,`locID` FROM `redeemvouchertable` WHERE `memberID` = ?) a ORDER BY `dateAdded` DESC LIMIT 1", array($data->memberID, $data->memberID, $data->memberID), $logs);

				if ($sub_total < 0) { (int)$sub_total = 0; }
				if ((!$level[0]['max']) || ((int)$total_earn >= (int)$level[0]['max'])) { $level[0]['max'] = 'max'; }
				if (!isset($last_transaction[0]['dateAdded'])) { $last_transaction[0]['dateAdded'] = "NULL"; }

				$step_2 = process_qry("CALL `global_complete_data`(?, ?, ?, ?, ?, ?)", array($sub_total, $total_earn[0]['points'], $last_transaction[0]['dateAdded'], $level[0]['levelID'], $level[0]['name'], $data->memberID), $logs);

				if ($step_2[0]['result'] == "Success") {
					$logs->write_logs("Success - Complete Data", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				}
				
				$step_3 = process_qry("SELECT COUNT(*) AS `count` FROM `memberstable` WHERE `totalPoints` = ? AND`accumulatedPoints` = ? AND `memberID` = ? LIMIT 1", array($sub_total, $total_earn[0]['points'], $data->memberID), $logs);
				
				if ($step_3[0]['count'] > 0) {
					$output = process_qry("SELECT `memberID`, `fname`, `mname`, `lname`, `email`, `mobileNum`, `landlineNum`, `dateReg`, `qrApp`, `qrCard`, `occupation`, `civilStatus`, `dateOfBirth`, `address1`, `address2`, `city`, `province`, `zipcode`, `gender`, `image`, `profileStatus`, `dateReg`, `totalPoints`, `accumulatedPoints`, `lastTransaction`,`expiration`, (SELECT `locName` FROM `earntable` WHERE `memberID` = ? ORDER BY `dateAdded` DESC LIMIT 1) AS `lastVisited`, `levelID`, `levelName`, ? AS `levelMax` FROM `memberstable` WHERE `memberID`= ? LIMIT 1", array($data->memberID, $level[0]['max'], $data->memberID), $logs);

					if (!$output[0]['dateOfBirth']) { $output[0]['dateOfBirth'] = '0000-00-00'; }

					if ($output[0]['image']) {
						if (strpos($output[0]['image'], 'http') === false) {
							$output[0]['image'] = DOMAIN . '/assets/images/profile/full/' .  $output[0]['image'];
						} else {
							$temp_image = explode("?", $output[0]['image']);
							$output[0]['image'] = ( count($temp_image) > 2 ) ? $temp_image[0] . '?' . $temp_image[1] : $temp_image[0];
						}
					}

					$output[0]['dateReg'] = date('M d,Y', strtotime($output[0]['dateReg']));
					$output[0]["lastSync"] = date('M d,Y');
					return json_encode(array(array("response"=>"Success", "data"=>array(array("profile"=>$output[0])))), JSON_UNESCAPED_SLASHES);
				} else {
					$logs->write_logs("Error - Complete Data", $file_name, array(array("response"=>"Error", "description"=>"Unable to fetch member data.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "errorCode"=>"0101", "description"=>"Unable to fetch member data.")));
				}
			}

			$logs->write_logs("Error - Complete Data", $file_name, array(array("response"=>"Error", "description"=>"No user found.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "errorCode"=>"0101", "description"=>"No user found.")));
		} 
		// === end complete_data

		// === start add cards
		public function add_card($param){
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;


			$getmember = process_qry("SELECT count(*) as `count`,`fname`,`lname`,`memberID`,`email` FROM memberstable WHERE `memberID` = ? and status = 'active'", array($data->memberID), $logs);
			$dateNow = DATE('Y-m-d H:i:s');

			if($getmember[0]['count'] > 0){
				$Isactive = process_qry("SELECT  count(*) AS `count` from `cardseries` WHERE `cardNum` = ? and status = 'active' and `memberID` IS not NULL" , array($data->qrCard), $logs);
					
				$IsExist = process_qry("SELECT  count(*) AS `count` from `memberstable` WHERE `qrCard` = ? " , array($data->qrCard), $logs);

				if($Isactive[0]['count'] > 0 || $IsExist[0]['count'] > 0){
					
					$logs->write_logs("Error - Add Card", $file_name, array(array("response"=>"Error", "description"=>"Card is in use.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Card is in use.")));
				}

				$cardExist = process_qry("SELECT  count(*) AS `count` from `cardseries` WHERE `cardNum` = ? ", array($data->qrCard), $logs);
				if($cardExist[0]['count'] < 1 ){
					$logs->write_logs("Error - Add Card", $file_name, array(array("response"=>"Error", "description"=>"Invalid Card.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Invalid Card.")));
				}

				$allowed_cards = process_qry("SELECT * FROM `memberstable` WHERE memberID = ? and qrCard is not NULL", array($getmember[0]['memberID']), $logs);

				if(count($allowed_cards) > 0 ){
					$logs->write_logs("Error - Add Card", $file_name, array(array("response"=>"Error", "description"=>"Note: Only one (1) card can be added.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Note: Only one (1) card can be added.")));
				}

				$logs->write_logs("Success - Add Card", $file_name, array(array("response"=>"Success",  "data"=>array($param))));

				$INSERT = process_qry("UPDATE `memberstable` set `qrCard` = ?, `dateModified` = NOW() WHERE memberID = ?", array($data->qrCard, $getmember[0]['memberID']), $logs);
					//===== update cardseries

				$update_cardseries = process_qry("UPDATE `cardseries` SET `memberID` = ?, status = 'active', `dateCardPurchased` = NOW(), `expiration` = DATE_ADD(NOW(), INTERVAL 1 YEAR) WHERE `cardNum` = ?", array($getmember[0]['memberID'], $data->qrCard), $logs);

				$insert_cron = process_qry("INSERT INTO cron_add_card(`memberID`,`email`,`fname`,`lname`,`cardNum`,`emailSent`,`dateAdded`,`dateModified`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)",array($data->memberID,$getmember[0]['email'],$getmember[0]['fname'],$getmember[0]['lname'],$data->qrCard,"false",$dateNow,$dateNow),$logs);

				// $subject = "Add Card";
				// $this->emailer(array("recipient_name" => $getmember[0]['fname'], "email" => $getmember[0]['email'], "type" => "add_card","subject" => $subject ));
				$param['description'] = "You have successfully added a card"; 
				return json_encode(array(array("response"=>"Success", "data"=>array($param)))); 
			}else{
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid"))); 
			}

		}
		// === end add cards

		// === start request_card
		public function request_card($param){
			global $logs;
			global $protocol;
			$data = (object) $param;
			$file_name = 'core.class.php';

			$getmember = process_qry("SELECT count(*) as `count`,`memberID`,`email`,`fname` FROM memberstable WHERE `memberID` = ? and status = 'active'", array($data->memberID), $logs);
			if($getmember[0]['count'] > 0){
				$logs->write_logs("Success - Request Card", $file_name, array(array("response"=>"Success",  "data"=>array($param))));
				$step_1 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `cron_request_card` LIMIT 1", array());
				$x = 0;
				$requestID = NULL;

				while ($x < 1) {
					$requestID = substr("REQ" . randomizer(2) . DATE("H") . $step_1[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$check_requestID = process_qry("SELECT count(*) AS `count` FROM `cron_request_card` WHERE `requestID` = ? LIMIT 1", array($requestID), $logs);
					$x = ( $check_requestID[0]['count'] < 1 ) ? 1 : 0;
				}
				$mdate = date('Y-m-d', strtotime(date('Y-m-d')));
				
				$INSERT_requestCard = process_qry("INSERT INTO cron_request_card(`requestID`,`memberID`,`email`,`fname`,`lname`,`house_no`,`street_no`,`barangay`,`city`,`mobileNum`,`telNum`,`status`,`dateAdded`,`dateModified`,`emailSent`,`emailSentMarketing`,`status_marketing`,`country`,`zipcode`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , ?, ?,'false','false','inactive',?, ?)",array($requestID,$data->memberID,$getmember[0]['email'],$data->fname,$data->lname,$data->house_no,$data->street_no,$data->barangay,$data->city,$data->mobileNum,$data->telNum,"inactive",$mdate,$mdate,$data->country,$data->zipcode), $logs);
				$logs->write_logs("Success - Request Card", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$subject = " Request Card";
				// $this->emailer(array("recipient_name" => $getmember[0]['fname'], "email" => $getmember[0]['email'], "type" => "request_card","subject" => $subject ));
				return json_encode(array(array("response"=>"Success", "data"=> array(array("description"=>"You have successfully requested for a membership card.", $param))))); 
			}else{
				$logs->write_logs("Error - Request Card", $file_name, array(array("response"=>"Error",  "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid Member."))); 
			}

		}	
		// === end request_card

		public function taxes($param){
			global $logs;
			global $protocol;
			$data = (object) $param;
			$file_name = 'core.class.php';
			$dateNow = DATE('Y-m-d H:i:s');
			$insertTax = process_qry("INSERT INTO taxtable(tax,description,locID,dateAdded,dateModified) VALUES(?,?,?,?,?)",array($data->tax,$data->description,$data->locID,$dateNow,$dateNow), $logs);
			if(count($insertTax) > 0 ){
				$logs->write_logs("Success - TAXES", $file_name, array(array("response"=>"Success",  "data"=>array("tax"=>$data->tax,"description"=>$data->description,"locID"=>$data->locID,"dateAdded"=>$dateNow))));
				return json_encode(array("response"=>"Success", "data"=> array("description"=>"You have successfully added tax."))); 
			}else{
				$logs->write_logs("Error - TAXES", $file_name, array("response"=>"Error",  "data"=>array($param)));
				return json_encode(array("response"=>"Error", "description"=>"Invalid TAXES."));
			}
		}

		public function update_taxes($param){
			global $logs;
			global $protocol;
			$data = (object) $param;
			$file_name = 'core.class.php';
			$dateNow = DATE('Y-m-d H:i:s');
			$descr = NULL;
			// $checkUser = process_qry("SELECT * FROM `accounts` WHERE `accountID` = ? and `status` = 'active'",array($data->accountID));
			// if(count($checkUser) > 0 ){
				$qry = process_qry("SELECT * FROM `taxtable` WHERE `id` = ? and status = 'true' ", array($data->id),$logs );
				if(count($qry) > 0 ){
					if ($data->description == ''){
						$descr = $qry[0]['description'];
					}else{
						$descr = $data->description;
					}
					$updateTax = process_qry("UPDATE `taxtable` SET `tax` = ?,`description` = ? where `id` = ? and status = 'true'",array($data->tax,$descr,$data->id));
					if(count($updateTax) > 0 ){
						return json_encode(array("response"=>"Success", "data"=> array("description"=>"Tax has been successfully updated."))); 
					}else{
						$logs->write_logs("Success - TAXES", $file_name, array("response"=>"Success",  "data"=>array($data)));
						return json_encode(array("response"=>"Error", "description"=>"Unable to update tax."));	
					}
					
				}else{
					return json_encode(array("response"=>"Error", "description"=>"Invalid TAXES."));
				}
			// }else{
			// 	return json_encode(array(array("response"=>"Error", "description"=>"No active user.")));
			// }
		}	
		// public function change_password($param){
		// 	global $logs;
		// 	global $protocol;
		// 	$data = (object) $param;
		// 	$file_name = 'core.class.php';

		// 	$check_email = process_qry("SELECT count(*) FROM memberstable WHERE `email` = ? and status = 'active'", array($data->email), $logs);

		// 	if( count($check_email) > 0 ){
		// 		$update_password = process_qry("UPDATE `memberstable` set `password` = PASSWORD(?) WHERE `email` = ? and `status` = 'active'",array($data->newpassword,$data->email),$logs);
		// 		$logs->write_logs("Success - Change Password", $file_name, array(array("response"=>"Success",  "data"=>array($param))));
		// 		return json_encode(array(array("response"=>"Success"))); 
		// 	}else{
		// 		$logs->write_logs("Error - Change Password", $file_name, array(array("response"=>"Error",  "data"=>array($param))));
		// 		return json_encode(array(array("response"=>"Error", "description"=>"No records found. " . array("email"=>$data->email)))); 
		// 	}

		// }

		// === start mailer
		public function emailer($param) {
			global $logs;
			global $protocol;
			$data = (object) $param;
			$file_name = 'core.class.php';
			$message = "<!DOCTYPE html><html><head><title></title><style type='text/css'>body, html{margin: 0;padding: 0 5%;font-family: 'Verdana';font-size: 14px;} img{width: 100%;} header{width: 100%;} .container{background-color: #f5f5f5;padding: 0 0 100px 0;} .emailBody{margin-top: 5%;padding: 0 5%;} .emailBody p{text-align: left;margin-top: 20px;line-height: 1.5} #regDetails p{margin: 0 0 0 20px;} #regDetails p label{font-weight: bold;} .activate{background-color: #35b7e8;padding: 20px;border-radius: 5px;width: 20%;margin: 5% auto;color: #fff;text-transform: uppercase;text-align: center;font-size: 18px;} a {text-decoration: none;}</style></head><body><div class='container'><header><img src='" . DOMAIN . "/assets/images/activation/email_header.png'></header>";

			if(($data->type == 'temp_registration') || $data->type == 'resend_activation') {
				
				$message .= "Hi <h2> " . $data->recipient_name . "</h2>";
				$message .= "<p>Thank you for registering to " . MERCHANT . " application.</p>";
				$message .= "<p>You are one step away to complete the registration process. Please activate your account by clicking the button provided.</p>";
				$message .= "<a href='" . DOMAIN . "/activation.php?email=" . $data->email . "&activation=" . $data->activation . "'><div class='activate'>Activate</div></a><p>Once activated, you may already use your login details the next time you launch the application.</p>";
				
				$message .= "<p>Have a nice day!</p><p>Regards,</p><p>" . MERCHANT . "</p><br /><br /><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
			}else
			if ($data->type == 'success_activation') {
				$message .= "Hi <h2> " . $data->recipient_name . "</h2>";
				$message .= "<p>Your account has been successfully activated.</p>";
				$message .= "<p>Below are the details of your membership.</p>";
				$message .= "Member’s Name: " . $data->recipient_name . "<br />";
				// $message .= "Membership Number:" .  . "<br />";
				$message .= "<p> *Use your QR code when earning or redeeming points.</p>";
				$message .= "<p>Have a nice day!</p><p>Regards,</p><p>" . MERCHANT . "</p><br /><br /><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
			}
			elseif ($data->type == 'social_login') {
				$message .= "<div clas.s='emailBody'><h2>Dear " . $data->recipient_name . "</h2><p>Thank you for joining " . MERCHANT . "!</p><p>You have registered through the mobile app, with the following credentials:<br /><br />Username: <b>" . $data->email . "<br /></b>Password: <b>" . $data->password . "<br /> Activation code: ". $data->activation  ."</b></p>";

				if ($data->emailFromFB == 'false' || $data->emailFromFB == false) {
					$message .= "<p>Please activate your account by clicking the button below.</p><a href='" . DOMAIN . "/activation.php?username=" . $data->email . "&activation=" . $data->activation . "'><div class='activate'>Activate</div></a><p>Once successful, you may now use " . MERCHANT . " to earn points and redeem rewards!</p>";
				}

				$message .= "<p>Have a good day!</p><p>Regards,</p><p>" . MERCHANT . "</p><br /><br /><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
			} elseif ($data->type == 'unlock') {
				$message .= "<div class='emailBody'><h2>Dear " . $data->recipient_name . "</h2><p>Your account has been temporarily suspended due to the number of login attempts.</p><p>We've reset your password and lock your account for your security. Your updated credentials are username: <b>" . $data->email . "<br /></b>Password: <b>" . $data->password . "</b></p><p>Unlock your account by clicking on the link:</p><a href='" . DOMAIN . "/unlock.php?email=" . $data->email . "'><div class='activate'>Unlock</div></a><p>Please immediately change your password for your protection.</p><p>Have a good day!</p><p>Regards,</p><p>" . MERCHANT . "</p><br /><br /><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
			} elseif ($data->type == "reset_password") {
				$message .= "<div class='emailBody'<h2>Dear " . $data->recipient_name . "</h2><p>We have received your request to reset your " . MERCHANT . " App password.</p><p>Your new credentials are as follows:<br /><br />Username: <b>" . $data->email . "</b><br />Password: <b>" . $data->password .  "</b></p><p>Once successful, you may now use " . MERCHANT . " to earn points and redeem rewards!</p><p>Have a good day!</p><p>Regards,</p><p>" . MERCHANT . "</p><br /><br /><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
			} elseif ($data->type == 'add_card') {
				$message .= "<div class='emailBody'<h2>Hi " . $data->recipient_name . "</h2><p>You have successfully requested for a physical membership card. Our staff may contact you for further instructions.</p><p>For other concerns, you may reach us through the contact details provided below.<br /><br />Thank you! <br /><br /><p> Regards,<b>" . MERCHANT . "</b><br />(+63)9175205265 <br />marketing@henann.com </p><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
			}
			elseif ($data->type == 'request_card') {
				$message .= "<div class='emailBody'<h2>Hi " . $data->recipient_name . "</h2><p>You have successfully requested for a physical membership card. Our staff may contact you for further instructions.</p><p>For other concerns, you may reach us through the contact details provided below.<br /><br />Thank you! <br /><br /><p> Regards,<b>" . MERCHANT . "</b><br />(+63)9175205265 <br />marketing@henann.com </p><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
			}

			$sent = $this->send_mail(array('recipient' => $data->email, 'recipient_name' => $data->recipient_name, 'subject' => $data->subject, 'message' =>  $message, 'attachment' => NULL));
			
			$qry = "CALL email_sent_temp(?)";

			// api_logger("E-mail", $data->subject, $file_name, $sent);

			if ($data->type == "social_login") {
				if ($data->emailFromFB != 'false' || $data->emailFromFB != false) {
					$qry = "CALL email_sent(?)";
				}
			} elseif ($data->type == "reset_password") {
				$qry = "CALL email_sent(?)";
			}

			process_qry($qry, array($data->email), $logs);
			return false;
		}
		// === end mailer

		// start earn voucher
		public function voucher_earn($memberID){
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			// === registration 
			$qry_member = process_qry("SELECT COUNT(*) as `cnt`,`email`,`memberID` FROM `memberstable` WHERE  `memberID` = ? AND `status` = 'active' AND  `activation` IS NULL LIMIT 1;", array($memberID), $logs);
			if($qry_member[0]['cnt'] > 0 ){
				// === registration
				$qry_card_voucher = process_qry("SELECT `quantity`,`cardType`,`cardVersion`,`voucherID`  FROM `vouchertable` WHERE   `type` = 'registration' AND  `status` = 'active' AND  `cardType` = 'henan'",array(), $logs);
				
				if(count($qry_card_voucher) > 0 ){	
					for ($i=0; $i < count($qry_card_voucher); $i++) { 
						for ($x=0; $x < $qry_card_voucher[$i]['quantity']; $x++) { 
						
							$insert_voucher =process_qry("INSERT INTO `earnvouchertable` (`voucherID`, `memberID`, `email`, `name`, `startDate`, `endDate`, `type`, `action`, `status`, `dateAdded`, `dateModified`, `image`) SELECT `voucherID`, ? as memberID , ? AS `email`, `name`, NOW() as `startDate` , DATE_ADD(NOW(), INTERVAL 1 YEAR) AS `endDate`, `type`, `action`, `status`, NOW() AS `dateAdded`, NOW() AS `dateModified`, `image` FROM `vouchertable` WHERE   `type` = 'registration' AND  `cardType` = ? AND  `status` = 'active' AND  `cardVersion` = ? and `voucherID` = ?",array($memberID,$qry_member[0]['email'],$qry_card_voucher[0]['cardType'],$qry_card_voucher[0]['cardVersion'], $qry_caard_voucher[0]['cardVersion'], $qry_card_voucher[$i]['voucherID']), $logs);

						}
					}
				}	
				// === birthday
				$qry_card_voucher_birthday = process_qry("SELECT count(*) as `cnt`, `quantity`,`cardType`,`cardVersion`  FROM `vouchertable` WHERE   `type` = 'birthday' AND  `status` = 'active' AND  `cardType` = 'henann'",array(), $logs);
				if($qry_card_voucher_birthday[0]['cnt'] > 0 ){
					$insert_voucher =process_qry("INSERT INTO `earnvouchertable` (`voucherID`, `memberID`, `email`, `name`, `startDate`, `endDate`, `type`, `action`, `status`, `dateAdded`, `dateModified`, `image`) SELECT `voucherID`, ? as memberID , ? AS `email`, `name`, NOW() as `startDate` , DATE_ADD(NOW(), INTERVAL 1 YEAR) AS `endDate`, `type`, `action`, `status`, NOW() AS `dateAdded`, NOW() AS `dateModified`, `image` FROM `vouchertable` WHERE   `type` = 'birthday' AND  `cardType` = ? AND  `status` = 'active' AND  `cardVersion` = ? AND `voucherID` != 'VCHeB91126c0gbYb';",array($memberID,$qry_member[0]['email'],$qry_card_voucher_birthday[0]['cardType'],$qry_card_voucher_birthday[0]['cardVersion'] ), $logs);
				}

			}	

		}
		// end earn voucher

		// === start my_voucher_list
		public function my_voucher_list($param){
			global $logs;
			global $protocol;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
			
			require_once(dirname(__FILE__) . '/voucher.class.php');
			$mVoucher = new vouchers();
 
			$output = $mVoucher->my_voucher_list(array("memberID"=>$data->memberID));
				// var_dump($output);

			return json_encode(array(array("response"=>"Success", "data"=>json_decode($output))));

			
		}
		// === end my_voucher_list

		// === start tokenizer
		public function tokenizer($param) {
			global $logs;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "app_tokenizer";
			$output = process_qry("CALL app_tokenizer(?, ? , ?)", array($data->platform, $data->deviceID, $data->pushID), $logs);

			if (($output[0]['result'] == 'Success') || ($output[0]['result'] == 'Existing')) {
				$logs->write_logs("Success - Tokenizer", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				return json_encode(array(array("response"=>"Success", "data"=>array($param))));
			} else {
				$logs->write_logs("Error - Tokenizer", $file_name, array(array("response"=>"Error", "description"=>"Unable to complete tokenizing process.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "errorCode"=>"7890", "description"=>"Unable to complete tokenizing process.")));
			}
		}	
		// === end tokenizer

		/********** Validate Mobile Number **********/
		function validate_mobile_num($mobileNum) {
			$mobileNum = str_replace(' ', '', $mobileNum);
			$mobileNum = str_replace('+', '', $mobileNum);
			$mobileNum = str_replace('-', '', $mobileNum);
			
			if (!is_numeric($mobileNum)) {
				return "Invalid";
			}

			if (strlen($mobileNum) < 10) {
				return "Invalid";
			} 
			// elseif (strlen($mobileNum) == 10) {
			// 	return $mobileNum = "63" . $mobileNum;
			// }else {
			// 	$trim = strlen($mobileNum) - 10;
			elseif (strlen($mobileNum) >= 12) {			
				return " Invalid";
			}elseif (strlen($mobileNum) == 11){
				$submobile	= substr($mobileNum, 1,1);
				
				if ($submobile == 9){
					return $mobileNum = "0" . substr($mobileNum, 1);	
				}else{
					return " Invalid";
				}
				
			}
						
		}


	} //==== end class

?>