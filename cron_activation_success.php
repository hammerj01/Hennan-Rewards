<?php

	require_once (dirname(__FILE__) . '/api/config/config.php');
	require_once(dirname(__FILE__) . '/api/misc/mailer/mailer.class.php');


	$chck = process_qry("SELECT * from `cron_activation_success` where `emailSent` = 'false' or  `emailSent` = 'failed'",array(),$logs);

	if(count($chck) > 0){
		for ($i=0; $i < count($chck) ; $i++) { 			
			if (count($chck) > 0 ){
					//$chck[$i]['email']
				$sent1 = emailer(array("email"=>$chck[$i]['email'],"recipient_name" => $chck[$i]['fname']. ' '. $chck[$i]['lname'], "subject" => "Successful Account Activation","qrApp"=>$chck[$i]['qrApp']));
				
				if ($sent1[0]['response'] != 'Success'){
				
					$error = $sent1[0]['response'] . $sent1[0]['description']; 
					$update_memberstable_q = process_qry("UPDATE `cron_activation_success` SET emailSent = 'failed', `description` = ? WHERE `email` = ?", array($error, $chck[$i]['email']), $logs);
				}
				
			}else{
				$error = "Error upon insertion for cron_activation_success";
				$update_memberstable_q = process_qry("UPDATE `cron_activation_success` SET emailSent = 'failed', `description` = ? WHERE `email` = ?", array($error, $chck[$i]['email']), $logs);
			}

		}	
	}

     function emailer($param) {
		global $logs;
		global $protocol;
		$data = (object) $param;
		
		$class = new mailer();
		// $qr = new QR_BarCode();
		$m = $data->qrApp;
		// $qr->url($m);
		$ex2 = new QRGenerator($m,130); 
        // echo "<img src=".$ex2->generate().">";
		$file_name = 'core.class.php';
		$message = "<!DOCTYPE html><html><head><title></title><style type='text/css'>body, html{margin: 0;padding: 0 5%;font-family: 'Verdana';font-size: 14px;} img{width: 100%;} header{width: 100%;} .container{background-color: #f5f5f5;padding: 0 0 100px 0;} .emailBody{margin-top: 5%;padding: 0 5%;} .emailBody p{text-align: left;margin-top: 20px;line-height: 1.5} #regDetails p{margin: 0 0 0 20px;} #regDetails p label{font-weight: bold;} .activate{background-color: #35b7e8;padding: 20px;border-radius: 5px;width: 20%;margin: 5% auto;color: #fff;text-transform: uppercase;text-align: center;font-size: 18px;} a {text-decoration: none;}</style></head><body><div class='container'><header><img src='" . DOMAIN . "/assets/images/activation/email_header.png'></header>";

			$message .= "<div class='emailBody'<h2>Hi " . ucwords(strtolower($data->recipient_name)) . "</h2><p>Your account has been successfully activated.</p><p>Below are the details of your membership.</p><p>Members Name: " . $data->recipient_name . "<br />Membership Number: " . $data->qrApp ."<br /> QR Code: <br />" .'<div style="width:400px;"><img id="te" src='.$ex2->generate() . "</div> <br /> <br />*Use your QR code when earning or redeeming points. <br /> <br /> Have a nice day!</p><p> Regards,<br /><b>" . MERCHANT . "</b><br />".RESPONSE_EMAIL."</p><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";

			$sents = $class->send_mail(array('recipient' => $data->email, 'recipient_name' => $data->recipient_name, 'subject' => $data->subject, 'message' =>  $message, 'attachment' => NULL));
		
			$sent = process_qry("UPDATE `cron_activation_success` SET `emailSent` = 'true', `dateModified` = NOW() WHERE `email` = ? and `emailSent`= 'false' ", array($data->email), $logs);
			
			$getCtr = process_qry("SELECT `ctrEmail` from `cron_activation_success` WHERE `email` = ? and `emailSent` in ('false','failed') ",array($data->email), $logs);	
			if($getCtr[0]['ctrEmail'] >= 3 ){
				$updateEmailStatus = process_qry("UPDATE `cron_activation_success` SET `emailSent` = 'true', `dateModified` = NOW() WHERE `email` = ? ",array($data->email), $logs);
			}else{
				$tot = $getCtr[0]['ctrEmail'] + 1;
				$updateCtr = process_qry("UPDATE `cron_activation_success` SET `ctrEmail` = ?, `dateModified` = NOW()  WHERE `email` = ? and `emailSent` in ('false','failed')",array($tot,$data->email), $logs);
			}

			return $sent;
	}

// class QR_BarCode{
//     // Google Chart API URL
//     private $googleChartAPI = 'http://chart.apis.google.com/chart';
//     // Code data
//     private $codeData;
    
//     /**
//      * URL QR code
//      * @param string $url
//      */
//     public function url($url = null){
//         $this->codeData = preg_match("#^https?\:\/\/#", $url) ? $url : "http://{$url}";
//     }
    
//     /**
//      * Text QR code
//      * @param string $text
//      */
//     public function text($text){
//         $this->codeData = $text;
//     }
    
//     /**
//      * Email address QR code
//      *
//      * @param string $email
//      * @param string $subject
//      * @param string $message
//      */
//     public function email($email = null, $subject = null, $message = null) {
//         $this->codeData = "MATMSG:TO:{$email};SUB:{$subject};BODY:{$message};;";
//     }
    
//     /**
//      * Phone QR code
//      * @param string $phone
//      */
//     public function phone($phone){
//         $this->codeData = "TEL:{$phone}";
//     }
    
//     /**
//      * SMS QR code
//      *
//      * @param string $phone
//      * @param string $text
//      */
//     public function sms($phone = null, $msg = null) {
//         $this->codeData = "SMSTO:{$phone}:{$msg}";
//     }
    
//     /**
//      * VCARD QR code
//      *
//      * @param string $name
//      * @param string $address
//      * @param string $phone
//      * @param string $email
//      */
//     public function contact($name = null, $address = null, $phone = null, $email = null) {
//         $this->codeData = "MECARD:N:{$name};ADR:{$address};TEL:{$phone};EMAIL:{$email};;";
//     }
    
//     /**
//      * Content (gif, jpg, png, etc.) QR code
//      *
//      * @param string $type
//      * @param string $size
//      * @param string $content
//      */
//     public function content($type = null, $size = null, $content = null) {
//         $this->codeData = "CNTS:TYPE:{$type};LNG:{$size};BODY:{$content};;";
//     }
    
//     /**
//      * Generate QR code image
//      *
//      * @param int $size
//      * @param string $filename
//      * @return bool
//      */
//     public function qrCode($size = 200, $filename = null) {
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL, $this->googleChartAPI);
//         curl_setopt($ch, CURLOPT_POST, true);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, "chs={$size}x{$size}&cht=qr&chl=" . urlencode($this->codeData));
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_HEADER, false);
//         curl_setopt($ch, CURLOPT_TIMEOUT, 30);
//         $img = curl_exec($ch);
//         curl_close($ch);
    
//         if($img) {
//             if($filename) {
//                 if(!preg_match("#\.png$#i", $filename)) {
//                     $filename .= ".png";
//                 }
                
//                 return file_put_contents($filename, $img);
//             } else {
//                 header("Content-type: image/png");
//                 print $img;
//                 return true;
//             }
//         }
//         return false;
//     }
// }

class QRGenerator { 
 
    protected $size; 
    protected $data; 
    protected $encoding; 
    protected $errorCorrectionLevel; 
    protected $marginInRows; 
    protected $debug; 
 
    public function __construct($data='https://www.phpgang.com',$size='130',$encoding='UTF-8',$errorCorrectionLevel='L',$marginInRows=4,$debug=false) { 
 
        $this->data=urlencode($data); 
        $this->size=($size>130 && $size<130)? $size : 130; 
        $this->encoding=($encoding == 'Shift_JIS' || $encoding == 'ISO-8859-1' || $encoding == 'UTF-8') ? $encoding : 'UTF-8'; 
        $this->errorCorrectionLevel=($errorCorrectionLevel == 'L' || $errorCorrectionLevel == 'M' || $errorCorrectionLevel == 'Q' || $errorCorrectionLevel == 'H') ?  $errorCorrectionLevel : 'L';
        $this->marginInRows=($marginInRows>0 && $marginInRows<10) ? $marginInRows:4; 
        $this->debug = ($debug==true)? true:false;     
    }
	public function generate(){ 
 
        $QRLink = "https://chart.googleapis.com/chart?cht=qr&chs=".$this->size."x".$this->size."&chl=" . $this->data .  
                   "&choe=" . $this->encoding ."&chld=" . $this->errorCorrectionLevel . "|" . $this->marginInRows; 
        if ($this->debug) echo   $QRLink;          
        return $QRLink; 
    }

}

?>