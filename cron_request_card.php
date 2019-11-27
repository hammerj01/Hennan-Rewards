<?php

	require_once (dirname(__FILE__) . '/api/config/config.php');
	require_once(dirname(__FILE__) . '/api/misc/mailer/mailer.class.php');


	$chck = process_qry("SELECT * from `cron_request_card` c  where c.`status` = 'inactive'  and c.`emailSent` = 'false' or c.`emailSent` = 'failed'",array(),$logs);

	if(count($chck) > 0){
		for ($i=0; $i < count($chck) ; $i++) { 			
			if (count($chck) > 0 ){
				$sent1 = emailer(array("email"=>$chck[$i]['email'],"recipient_name" => $chck[$i]['fname'] . " ".$chck[$i]['lname'] , "subject" => "Henann Rewards Card Request","memberID"=>$chck[$i]['memberID']));
				
			}else{
				$error = "Error upon insertion for cron_request_card";
				$update_memberstable_q = process_qry("UPDATE `cron_request_card` SET emailSent = 'failed', `description` = ?, `status`= 'inactive'  WHERE `email` = ?", array($error, $chck[$i]['email']), $logs);
			}

		}	
	}

//email to marketing
	$chck_marketing = process_qry("SELECT * from `cron_request_card` c  where c.`emailSentMarketing` = 'false' or c.`emailSentMarketing` = 'failed'",array(),$logs);
	if(count($chck_marketing) > 0){
		for ($i=0; $i < count($chck_marketing) ; $i++) { 			
			if (count($chck_marketing) > 0 ){
				
				$senttomarketing = email_marketing(array("email"=>$chck_marketing[$i]['email'],"full_name" => $chck_marketing[$i]['fname'] . ' '. $chck_marketing[$i]['lname'], "subject" => "Card Request","address"=>$chck_marketing[$i]['house_no'] . ' '. $chck_marketing[$i]['street_no']. ' ' . $chck_marketing[$i]['barangay'] . ' '. $chck_marketing[$i]['city'], "mobileNum"=>$chck_marketing[$i]['mobileNum'],"memberID"=>$chck_marketing[$i]['memberID']));

				
			}else{
				$error = "Error upon insertion for cron_request_card";
				$update_memberstable_q = process_qry("UPDATE `cron_request_card` SET emailSent = 'failed', `description_marketing` = ?, `status_marketing`= 'failed' WHERE `email` = ?", array($error, $chck_marketing[$i]['email']), $logs);
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

			$message .= "<div class='emailBody'<h2>Hi " . ucwords(strtolower($data->recipient_name)) . "</h2><p>You have successfully requested for a physical membership card. Our staff may contact you for further instructions.</p><p>For other concerns, you may reach us through the contact details provided below.<br /><br />Thank you! <br /><br /><p> Regards,<br /><b>" . MERCHANT . "</b> <br />marketing@henann.com </p><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";

		$sent = $class->send_mail(array('recipient' => $data->email, 'recipient_name' => $data->recipient_name, 'subject' => $data->subject, 'message' =>  $message, 'attachment' => NULL));

		if ($sent[0]['response'] == 'Success'){
			$insert_cron = process_qry("UPDATE `cron_request_card` SET `emailSent` = 'true', `status` = 'active', `dateModified` = NOW(),`description`='Success' WHERE `memberID` = ? and `status`='inactive' and `emailSent`= 'false' or `emailSent`= 'failed'", array($data->memberID), $logs);
		}else{
			$error = $sent1[0]['response'] . $sent1[0]['description']; 
			$update_cron = process_qry("UPDATE `cron_request_card` SET emailSent = 'failed', `description` = ?, `status`= 'inactive'  WHERE `email` = ? and (emailSent`= 'failed' or emailSent`= 'false')", array($error, $chck[$i]['email']), $logs);
		}	
	
	}

	function email_marketing($param) {
		global $logs;
		global $protocol;
		$data = (object) $param;
		var_dump($data);
		$class = new mailer();

		$file_name = 'core.class.php';
		$message = "<!DOCTYPE html><html><head><title></title><style type='text/css'>body, html{margin: 0;padding: 0 5%;font-family: 'Verdana';font-size: 14px;} img{width: 100%;} header{width: 100%;} .container{background-color: #f5f5f5;padding: 0 0 100px 0;} .emailBody{margin-top: 5%;padding: 0 5%;} .emailBody p{text-align: left;margin-top: 20px;line-height: 1.5} #regDetails p{margin: 0 0 0 20px;} #regDetails p label{font-weight: bold;} .activate{background-color: #35b7e8;padding: 20px;border-radius: 5px;width: 20%;margin: 5% auto;color: #fff;text-transform: uppercase;text-align: center;font-size: 18px;} a {text-decoration: none;}</style></head><body><div class='container'><header><img src='" . DOMAIN . "/assets/images/activation/email_header.png'></header>";

			$message .= "<div class='emailBody'<h2>Hi Admin</h2><p>Please be informed that email :". $data->email . " has requested for a physical membership card. Below are the details of the of the customer. <br /> Name:" . ucwords(strtolower($data->full_name))  . "<br> Address: " .$data->address ."<br /> Contact Number: ". $data->mobileNum ." <br /><br />Thank you! <br /><br /><p> Regards,<br /><b>Henann Rewards Support Department <br />(+63)9175205265 <br />".RESPONSE_EMAIL."</p><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
		$merchant_email = RESPONSE_EMAIL;	
		$sent = $class->send_mail(array('recipient' => $merchant_email , 'recipient_name' =>"Admin", 'subject' => $data->subject, 'message' =>  $message, 'attachment' => NULL));
			
		if ($sent[0]['response'] == 'Success'){

			$insert_marketing = process_qry("UPDATE `cron_request_card` SET `emailSentMarketing` = 'true', `dateModified` = NOW(),`description_marketing`='Success',`status_marketing`='active' WHERE `memberID` = ? and `status_marketing`='inactive' and `emailSentMarketing`= 'false'  ", array($data->memberID), $logs);
			
			
		}else{
			$error = $sent1[0]['response'] . $sent1[0]['description']; 
			$update_marketing = process_qry("UPDATE `cron_request_card` SET emailSentMarketing = 'failed', `description_marketing` = ?, `status_marketing`= 'inactive'  WHERE `memberID` = ? and (emailSentMarketing`= 'failed' or emailSentMarketing`= 'false')", array($error, $data->memberID), $logs);
		}	
			
	}

?>