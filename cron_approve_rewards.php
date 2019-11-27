<?php

	require_once (dirname(__FILE__) . '/api/config/config.php');
	require_once(dirname(__FILE__) . '/api/misc/mailer/mailer.class.php');


	$chck = process_qry("SELECT * from `redeem_request_rewards` where `status` = 'approve' and `emailSentApprove` in ('false','failed')",array(),$logs);
	if(count($chck) > 0){
		for ($i=0; $i < count($chck) ; $i++) { 			
			if (count($chck) > 0 ){
					//$chck[$i]['email']
				$sent1 = emailer(array("email"=>$chck[$i]['email'],"recipient_name" => $chck[$i]['fname']. ' '. $chck[$i]['lname'], "subject" => "Henann Rewards: Approved Reward Redemption","memberID"=>$chck[$i]['memberID'],"rewards_name"=>$chck[$i]['rewards_name'],"points"=>$chck[$i]['points']));
				
				if ($sent1[0]['response'] != 'Success'){
				
					$error = $sent1[0]['response'] . $sent1[0]['description']; 
					$update_memberstable_q = process_qry("UPDATE `redeem_request_rewards` SET `emailSentApprove` = 'true', `description` = ? WHERE `email` = ? and `emailSentApprove` in ('false', 'failed')", array($error, $chck[$i]['email']), $logs);
				}
				
			}else{
				$error = "Error upon insertion for redeem_request_rewards";
				$update_memberstable_q = process_qry("UPDATE `redeem_request_rewards` SET `emailSentApprove` = 'failed', `description` = ? WHERE `email` = ? and `emailSentApprove` in ('false', 'failed')", array($error, $chck[$i]['email']), $logs);
			}

		}	
	}

     function emailer($param) {
		global $logs;
		global $protocol;
		$data = (object) $param;
		
		$class = new mailer();

		$file_name = 'core.class.php';
		$message = "<!DOCTYPE html><html><head><title></title><style type='text/css'>body, html{margin: 0;padding: 0 5%;font-family: 'Verdana';font-size: 14px;} img{width: 100%;} header{width: 100%;} .container{background-color: #f5f5f5;padding: 0 0 100px 0;} .emailBody{margin-top: 5%;padding: 0 5%;} .emailBody p{text-align: left;margin-top: 20px;line-height: 1.5} #regDetails p{margin: 0 0 0 20px;} #regDetails p label{font-weight: bold;} .activate{background-color: #35b7e8;padding: 20px;border-radius: 5px;width: 20%;margin: 5% auto;color: #fff;text-transform: uppercase;text-align: center;font-size: 18px;} a {text-decoration: none;}</style></head><body><div class='container'><header><img src='" . DOMAIN . "/assets/images/activation/email_header.png'></header>";

			$message .= "<div class='emailBody'<h2>Hi " . ucwords(strtolower($data->recipient_name)) . "</h2><p>Your redemption request has been approved.</p> <p>Below are the details of your reward: <br /> Reward Name: " . $data->rewards_name . "<br /> Equivalent Points: " . $data->points . " <br /> <br />Thank you!</p><p> Regards,<br /><b>" . MERCHANT . "</b> <br />".RESPONSE_EMAIL."</p><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";

		$sent = $class->send_mail(array('recipient' => $data->email, 'recipient_name' => $data->recipient_name, 'subject' => $data->subject, 'message' =>  $message, 'attachment' => NULL));
		
			$sentUpdate = process_qry("UPDATE `redeem_request_rewards` SET `status` = 'approved',`emailSentApprove` = 'true', `dateModified` = NOW(), `emailSentApprove` = 'true' WHERE `email` = ? and `memberID` = ? and `status`= 'approve' and `emailSentApprove` in ('false', 'failed')", array($data->email,$data->memberID), $logs);
			
			$getCtr = process_qry("SELECT `ctrApprove` from `redeem_request_rewards` WHERE `email` = ? and `emailSentApprove` = 'false' ",array($data->email), $logs);	
			if($getCtr[0]['ctrApprove'] >= 3 ){
				$updateEmailStatus = process_qry("UPDATE `redeem_request_rewards` SET `emailSentApprove` = 'true', `dateModified` = NOW() WHERE `email` = ? ",array($data->email), $logs);
			}else{
				$tot = $getCtr[0]['ctrApprove'] + 1;
				$updateCtr = process_qry("UPDATE `redeem_request_rewards` SET `ctrApprove` = ?, `dateModified` = NOW()  WHERE `email` = ? and `emailSentApprove` = 'false'",array($tot,$data->email), $logs);
			}
			// return false;

			return $sent;
	}

?>