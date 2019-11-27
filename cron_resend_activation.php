<?php

	require_once (dirname(__FILE__) . '/api/config/config.php');
	require_once(dirname(__FILE__) . '/api/misc/mailer/mailer.class.php');


	$chck = process_qry("SELECT * from cron_resend_activation c WHERE c.`emailSent` = 'false'",array(),$logs);
	if(count($chck) > 0){
		for ($i=0; $i < count($chck) ; $i++) { 			
			if (count($chck) > 0 ){
				$sent1 = emailer(array("email"=>$chck[$i]['email'],"recipient_name" => $chck[$i]['fname'] . " ". $chck[$i]['lname'], "subject" => "Henann Rewards: Resend Account Activation","activation"=>$chck[$i]['activation']));
				if ($sent1[0]['response'] != 'Success'){
				
					$error = $sent1[0]['response'] . $sent1[0]['description']; 
					$update_memberstable_q = process_qry("UPDATE `cron_resend_activation` SET emailSent = 'failed', `description` = ? WHERE `email` = ?", array($error, $chck[$i]['email']), $logs);
				}
				
			}else{
				$error = "Error upon insertion for cron_resend_activation";
				$update_memberstable_q = process_qry("UPDATE `cron_resend_activation` SET emailSent = 'failed', `description` = ? WHERE `email` = ?", array($error, $chck[$i]['email']), $logs);
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

				$message .= "Hi <strong> " . ucwords(strtolower($data->recipient_name)) . "</strong>";
				$message .= "<p>Thank you for registering to Henann Rewards' application.</p>";
				$message .= "<p>You are one step away to complete the registration process. Please activate your account by clicking the button provided.</p>";
				$message .= "<a href='" . DOMAIN . "/activation.php?email=" . $data->email . "&activation=" . $data->activation . "'><div class='activate'>Activate</div></a><p>Once activated, you may already use your login details the next time you launch the application.</p>";
				
				$message .= "<p>Have a nice day!</p><p>Regards,</p><p>" . MERCHANT . "  <br />".RESPONSE_EMAIL."</p><br /><br /><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";

		$sent = $class->send_mail(array('recipient' => $data->email, 'recipient_name' => $data->recipient_name, 'subject' => $data->subject, 'message' =>  $message, 'attachment' => NULL));
		
			$sent = process_qry("UPDATE `cron_resend_activation` SET `emailSent` = 'true', `dateModified` = NOW() WHERE `email` = ?", array($data->email), $logs);
			// return false;

			return $sent;
	}

?>