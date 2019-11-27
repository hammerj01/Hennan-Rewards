<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class vouchers
	{
		public static $file_name = 'vouchers.class.php';

		public function my_voucher_list($param) {
			global $logs;
			global $protocol;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$output1 = array();
			$flash = array();
			$birthdays = array();
			$registrations = array();
			$getwemissyou = array();
			$myVouchers = array();
			$data = (object) $param;
			

			$step_1 = process_qry("SELECT COUNT(`id`) AS `count`, `dateOfBirth`, `lastTransaction`, `profileStatus`, `email`,`dateReg` FROM `memberstable` WHERE `memberID` = ? AND `status` = 'active' LIMIT 1", array($data->memberID));

			if ($step_1[0]['count'] > 0 ){

				// $step_2 = process_qry("UPDATE `vouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND DATE_FORMAT(NOW(), '%Y-%m-%d') > DATE_FORMAT(`endDate`, '%Y-%m-%d')", array());
				// $flash_update = process_qry("UPDATE `vouchertable` a SET a.`redemption_remaining` = (SELECT COUNT(*) FROM `redeemvouchertable` WHERE `voucherID` = a.`voucherID`) WHERE a.`status` = 'active' AND DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND `type` = 'flash'", array());
				// $registration_update = process_qry("UPDATE `vouchertable` a SET a.`redemption_remaining` = (SELECT COUNT(*) FROM `redeemvouchertable` WHERE `voucherID` = a.`voucherID`) WHERE a.`status` = 'active' AND DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND `type` = 'registration'", array());

				// $birthday_update = process_qry("UPDATE `vouchertable` a SET a.`redemption_remaining` = (SELECT COUNT(*) FROM `redeemvouchertable` WHERE `voucherID` = a.`voucherID`) WHERE a.`status` = 'active' AND DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND `type` IN ('birthday', 'registration')", array());
				// $step_flash_status = process_qry("UPDATE `vouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND `redemption_remaining` > `redemptionlimit` AND `type` = 'flash'", array());
				// $step_4 = process_qry("UPDATE `earnvouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND `memberID` = ? AND `voucherID` IN (SELECT `voucherID` FROM `vouchertable` WHERE `status` = 'expired')", array($data->memberID));
				// $step_registration_status = process_qry("UPDATE `vouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND `redemption_remaining` >= `redemptionlimit` AND `type` = 'registration' and `redemptionlimit` != 0", array());
				// $step_birthday_status = process_qry("UPDATE `vouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND `redemption_remaining` >= `redemptionlimit` AND `type` = 'birthday' and `redemptionlimit` != 0", array());

				// //===
				// $UPDATE_registration_status = process_qry("UPDATE `earnvouchertable` set status ='expired' WHERE status =  (SELECT status FROM vouchertable a WHERE DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND  a.`status` = 'expired' and a.`type` = 'registration' )", array());
				// $UPDATE_birthday_status = process_qry("UPDATE `earnvouchertable` set status = 'expired' WHERE status = (SELECT status FROM vouchertable a WHERE DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND  a.`status` = 'expired' and a.`type` = 'birthday' )", array());

				$step_5 = process_qry("SELECT `voucherID`, `quantity`, (SELECT COUNT(*) FROM `earnvouchertable` WHERE `memberID` = ? AND `voucherID` = `vouchertable`.`voucherID` LIMIT 1) AS `ev_count`, (SELECT COUNT(*) FROM `redeemvouchertable` WHERE `memberID` = ? AND `voucherID` = `vouchertable`.`voucherID` LIMIT 1) AS `rv_count` FROM `vouchertable` WHERE `status` = 'active'", array($data->memberID, $data->memberID));
				

				if (count($step_5) > 0) {
					for ($i = 0; $i < count($step_5); $i++) {
						$v_count = (int) ( $step_5[$i]['quantity'] - ( $step_5[$i]['ev_count'] + $step_5[$i]['rv_count'] ) );
						$v_count = (int) ( $v_count < 0 ) ? 0 : $v_count;

						if ($v_count > 0) {
							for ($ii = 0; $ii < $v_count; $ii++) {
								$step_6 = process_qry("INSERT INTO `earnvouchertable` (`voucherID`, `memberID`, `email`, `name`,`month`, `startDate`, `endDate`, `type`, `action`, `status`, `dateAdded`, `dateModified`, `image`) SELECT `voucherID`, ? AS `memberID`, ? AS `email`, `name`,`month`,`startDate`,`endDate`, `type`, `action`, `status`, NOW() AS `dateAdded`, NOW() AS `dateModified`, `image` FROM `vouchertable` WHERE  `status` = 'active' AND `voucherID` = ? ", array($data->memberID, $step_1[0]['email'], $step_5[$i]['voucherID']), $logs);
							}
						}
					}
				}
				require_once(dirname(__FILE__) . '/voucher_registration.class.php');
				$class_registration = new registration();

				// require_once(dirname(__FILE__) . '/voucher_bday.class.php');
				// $class_birthday = new birthday();

				// require_once(dirname(__FILE__). '/voucher_flash.class.php');
				// $class_flash = new flash();

				// require_once(dirname(__FILE__). '/voucher.wemissyou.class.php');
				// $class_wemissyou = new wemissyou();


			    $registrations = $class_registration->get_registration(array("memberID"=>$data->memberID));
				
				// $bday = DATE('m', strtotime($step_1[0]['dateOfBirth']));
				
				// $birthdays = $class_birthday->get_birthday(array("memberID"=>$data->memberID,"month"=>$bday));			
				// $output = array_merge( $registrations,$birthdays);
				// $flash = $class_flash->get_flash(array("memberID"=>$data->memberID));

				// $getwemissyou = $class_wemissyou->get_wemissyou(array("memberID"=>$data->memberID,"lastTransaction"=>$step_1[0]['lastTransaction']));

				// $output1 = array_merge($output,$flash);
				// $myVouchers = array_merge($output1,$getwemissyou);
			    // return json_encode($myVouchers);


				return json_encode($registrations);
			}else{
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid account.")));
			}
		}
}	
		
?>