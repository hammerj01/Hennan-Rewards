<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class mailer {

		public function send_mail($param) {
			global $logs;
			require_once (dirname(__FILE__) . '/PHPMailerAutoload.php');

			$data = (object) $param;
			$mailer = process_qry("SELECT `mailer_sender`, `mailer_name`, `mailer_smtp`, `mailer_username`, `mailer_password`, `mailer_port`, `mailer_secure` FROM `settings` LIMIT 1", array());
			$mail = new PHPMailer;
			$mail->isSMTP();
			$mail->SMTPDebug = 0;
			$mail->Debugoutput = 'html';
			$mail->Host = $mailer[0]["mailer_smtp"];
			$mail->SMTPSecure = $mailer[0]["mailer_secure"];
			$mail->Port = $mailer[0]["mailer_port"];
			$mail->SMTPAuth = true;
			$mail->Username = $mailer[0]["mailer_username"];
			$mail->Password = $mailer[0]["mailer_password"];
			$mail->setFrom($mailer[0]["mailer_username"], $mailer[0]["mailer_name"]);
			$mail->addReplyTo($mailer[0]["mailer_username"], $mailer[0]["mailer_name"]);
			$mail->addAddress($data->recipient, $data->recipient_name);
			$mail->Subject = $data->subject;
			$mail->msgHTML($data->message, dirname(__FILE__));
			$mail->AltBody = $data->message;
			$mail->addCustomHeader("MIME-Version", "1.0");
			$mail->addCustomHeader("Organization" , $data->recipient_name); 
			$mail->addCustomHeader("Content-Transfer-encoding" , "8bit");
			$mail->addCustomHeader("Message-ID" , "<".md5(uniqid(time()))."@{$_SERVER['SERVER_NAME']}>");
			$mail->addCustomHeader("X-MSmail-Priority" , "High");
			$mail->addCustomHeader("X-Mailer" , "PHPMailer 5.1 (phpmailer.sourceforge.net)");
			$mail->addCustomHeader("X-MimeOLE" , "5.1 (phpmailer.sourceforge.net)");
			$mail->addCustomHeader("X-Sender" , $mailer[0]["mailer_username"]);
			$mail->addCustomHeader("X-AntiAbuse" , "This is a solicited email for - " . $data->recipient_name . " mailing list.");
			$mail->addCustomHeader("X-AntiAbuse" , "Servername - {$_SERVER['SERVER_NAME']}");
			$mail->addCustomHeader("X-AntiAbuse" , $mailer[0]["mailer_username"]);

			if ($data->attachment != NULL) {
				$mail->addStringAttachment( file_get_contents($data->attachment), $data->attachment );
			}

			if (!$mail->send()) {
				return array(array("response" => "Error", "data" => array(array("description" => $mail->ErrorInfo))));
			} else {
				return array(array("response" => "Success"));
			}
		}

	}

?>