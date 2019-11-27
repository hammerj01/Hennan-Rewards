<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class tablet {
		public static $file_name = 'tablet.class.php';

		public function tablet_check_qr_card_old($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			// $qry_card = "SELECT COUNT(*) AS `count`, `memberID`, `status` FROM `cardseries` WHERE `cardNum` = ? LIMIT 1";
			$qry_memberID = "SELECT COUNT(*) AS `count`, `memberID`, `status` FROM `memberstable` WHERE `memberID` = ? LIMIT 1";
			$step_1 = process_qry($qry_memberID, array($data->memberID));

			// if ($step_1[0]['count'] < 1) {
			// 	$step_1 = process_qry($qry_app, array($data->qrCard));
			// }

			if ($step_1[0]['count'] > 0) {

				// if ($step_1[0]['status'] == 'inactive' && $qrType != 'APP') {
				// 	return json_encode(array("response"=>"Error", "description"=>'Inactive Card Number.'));
				// }

				// $step_2 = process_qry("SELECT `memberID`, `fname`, `mname`, `lname`, `email`, `mobileNum`, `landlineNum`, `dateReg`, `qrApp`, `qrCard`, `occupation`, `civilStatus`, `dateOfBirth`, `address1`, `address2`, `city`, `province`, `zip`, `gender`, `image`, `profileStatus`, `dateReg`,`expiration`, `totalPoints`, `accumulatedPoints`, DATE_FORMAT(`lastTransaction`, '%M %d, %Y') AS `lastTransaction`, (SELECT `loc` FROM (SELECT dateadded, CONCAT(`brandName`) AS `loc` FROM earntable WHERE memberID = ? UNION SELECT dateadded, CONCAT(`brandName`) AS `loc` FROM redeemtable WHERE memberID = ? UNION SELECT dateadded, CONCAT(`brandName`) AS `loc` FROM redeemvouchertable WHERE memberID = ?) a order by dateadded desc limit 1) as `brandName` ,(SELECT `locName` FROM `earntable` WHERE `memberID` = z.`memberID` ORDER BY `dateAdded` DESC LIMIT 1) AS `lastVisited` FROM `memberstable` z WHERE `memberID` = ? LIMIT 1", array($step_1[0]['memberID'], $step_1[0]['memberID'], $step_1[0]['memberID'], $step_1[0]['memberID']));
				$step_2 = process_qry("SELECT `memberID`, `fname`, `mname`, `lname`, `email`, `mobileNum`, `landlineNum`, `dateReg`, `qrApp`, `qrCard`, `occupation`, `civilStatus`, `dateOfBirth`, `address1`, `address2`, `city`, `province`, `zipcode`, `gender`, `image`, `profileStatus`, `dateReg`,`expiration`, `totalPoints`, `accumulatedPoints`, DATE_FORMAT(`lastTransaction`, '%M %d, %Y') AS `lastTransaction`, (SELECT `loc` FROM (SELECT dateadded AS `loc` FROM earntable WHERE memberID = ? UNION SELECT dateadded FROM redeemtable WHERE memberID = ? UNION SELECT dateadded FROM redeemvouchertable WHERE memberID = ?) a order by dateadded desc limit 1) as `brandName` ,(SELECT `locName` FROM `earntable` WHERE `memberID` = z.`memberID` ORDER BY `dateAdded` DESC LIMIT 1) AS `lastVisited` FROM `memberstable` z WHERE `memberID` = ? LIMIT 1", array($step_1[0]['memberID'], $step_1[0]['memberID'], $step_1[0]['memberID'], $step_1[0]['memberID']));

				// SELECT `memberID`, `fname`, `mname`, `lname`, `email`, `mobileNum`, `landlineNum`, `dateReg`, `qrApp`, `qrCard`, `occupation`, `civilStatus`, `dateOfBirth`, `address1`, `address2`, `city`, `province`, `zip`, `gender`, `image`, `profileStatus`, `dateReg`,`expiration`, `totalPoints`, `accumulatedPoints`, DATE_FORMAT(`lastTransaction`, '%M %d, %Y') AS `lastTransaction`, (SELECT `loc` FROM (SELECT dateadded AS `loc` FROM earntable WHERE memberID = 'MEMnz1812104XQvW' UNION SELECT dateadded FROM redeemtable WHERE memberID = 'MEMnz1812104XQvW' UNION SELECT dateadded FROM redeemvouchertable WHERE memberID = 'MEMnz1812104XQvW') a order by dateadded desc limit 1) as `brandName` ,(SELECT `locName` FROM `earntable` WHERE `memberID` = z.`memberID` ORDER BY `dateAdded` DESC LIMIT 1) AS `lastVisited` FROM `memberstable` z WHERE `memberID` = 'MEMnz1812104XQvW' LIMIT 1
				
				if(count($step_2) < 1){
					return json_encode(array("response"=>"Error", "description"=>'Invalid QR Code value.'));
				}

				if (($step_2[0]['image'] != NULL) && ($step_2[0]['image'] != "")) {
					$step_2[0]['image'] = DOMAIN . '/pictures/full/' .  $step_2[0]['image'];
				}

				$step_2[0]['dateReg'] = date('M d,Y', strtotime($step_2[0]['dateReg']));

				return json_encode(array("response"=>"Success", "data"=>array("profile"=>$step_2[0])));

			} else {
				return json_encode(array("response"=>"Error", "description"=>'Invalid QR Code value.'));
			}
		}

		public function tablet_check_qr_card($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;


			$get_Code = json_decode($this->check_code(array("barCode"=>$data->barCode)),true);

			if ($get_Code[0]['response'] != 'Success'){
				return json_encode(array(array("response"=>"Error" , "description"=>$get_Code[0]['description'])));
			}
			$memberID = $get_Code[0]['data'][0]['memberID'];
		
				$step_2 = process_qry("SELECT `memberID`, `fname`, `mname`, `lname`, `email`, `mobileNum`, `landlineNum`, `dateReg`, `qrApp`, `qrCard`, `occupation`, `civilStatus`, `dateOfBirth`, `address1`, `address2`, `city`, `province`, `zipcode`, `gender`, `image`, `profileStatus`, `dateReg`,`expiration`, `totalPoints`, `accumulatedPoints`, DATE_FORMAT(`lastTransaction`, '%M %d, %Y') AS `lastTransaction`, (SELECT `loc` FROM (SELECT dateadded AS `loc` FROM earntable WHERE memberID = ? UNION SELECT dateadded FROM redeemtable WHERE memberID = ? UNION SELECT dateadded FROM redeemvouchertable WHERE memberID = ?) a order by dateadded desc limit 1) as `brandName` ,(SELECT `locName` FROM `earntable` WHERE `memberID` = z.`memberID` ORDER BY `dateAdded` DESC LIMIT 1) AS `lastVisited` FROM `memberstable` z WHERE `memberID` = ? LIMIT 1", array($memberID, $memberID, $memberID, $memberID));

				if(count($step_2) < 1){
					return json_encode(array("response"=>"Error", "description"=>'Invalid qrCode.'));
				}

				if (($step_2[0]['image'] != NULL) && ($step_2[0]['image'] != "")) {
					$step_2[0]['image'] = DOMAIN . '/pictures/full/' .  $step_2[0]['image'];
				}

				$step_2[0]['dateReg'] = date('M d,Y', strtotime($step_2[0]['dateReg']));

				return json_encode(array(array("response"=>"Success", "data"=>array(array("profile"=>$step_2[0])))));

			// } else {
			// 	return json_encode(array("response"=>"Error", "description"=>'Invalid QR Code value.'));
			// }
		}

		public function tablet_availability($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			if ($data->deviceID == 'web'){

				$step_1[0]['count'] = 1;
			}
			else{
				$step_1 = process_qry("SELECT COUNT(`id`) AS `count` FROM `devicecodetable` WHERE `status` = 'active' AND `deploy` = 'true' AND `deviceID` = ?", array($data->deviceID));
			}
			

			if ($step_1[0]['count'] > 0) {
				
				if ($data->deviceID == 'web'){

					$step_3 = process_qry("SELECT a.`company`, a.`merchantCode`, b.`locID`, c.`name` AS `locName`, c.`branchCode`, b.`deviceCode`, DATE_FORMAT(NOW(),'%W, %M %d, %Y, %h:%i %p') AS `updateDate`, a.`redeemBasePoint`, d.`camount` AS `baseValue`, d.`cpoints` AS `basePoint`, 'false' AS `nonCash_status`, d.`ncamount` AS `baseValue_nonCash`, d.`ncpoints` AS `basePoint_nonCash`,t.`tax`, c.`name` as `branch` FROM `settings` a, `devicecodetable` b, `loctable` c, `earnsettingstable` d, `taxtable` t WHERE d.`locID` = b.`locID` and b.`locID` = c.`locID`   GROUP by c.`locID`", array(), $logs);
				}else{
					
					$step_2 = process_qry("UPDATE `devicecodetable` SET `lastSync` = NOW(), `version` = ? WHERE `deviceID` = ? LIMIT 1", array($data->version, $data->deviceID));
					// $step_3 = process_qry("SELECT a.`company`, a.`merchantCode`, b.`locID`, c.`name` AS `locName`, c.`branchCode`, b.`deviceCode`, DATE_FORMAT(NOW(),'%W, %M %d, %Y, %h:%i %p') AS `updateDate`, a.`redeemBasePoint`, d.`camount` AS `baseValue`, d.`cpoints` AS `basePoint`, 'false' AS `nonCash_status`, d.`ncamount` AS `baseValue_nonCash`, d.`ncpoints` AS `basePoint_nonCash`, c.`name` as `branch` FROM `settings` a, `devicecodetable` b, `loctable` c, `earnsettingstable` d WHERE b.`deviceID` = ? AND d.`locID` = b.`locID` GROUP by b.`locID` ", array($data->deviceID));
					$step_3 = process_qry("SELECT a.`company`, a.`merchantCode`, b.`locID`, c.`name` AS `locName`, c.`branchCode`, b.`deviceCode`, DATE_FORMAT(NOW(),'%W, %M %d, %Y, %h:%i %p') AS `updateDate`, a.`redeemBasePoint`, d.`camount` AS `baseValue`, d.`cpoints` AS `basePoint`, 'false' AS `nonCash_status`, d.`ncamount` AS `baseValue_nonCash`, d.`ncpoints` AS `basePoint_nonCash`,t.`tax` , c.`name` as `branch` FROM `settings` a, `devicecodetable` b, `loctable` c, `earnsettingstable` d ,`taxtable` t WHERE b.`deviceID` = ? AND d.`locID` = b.`locID` and b.`locID` = c.`locID`   GROUP by b.`locID` ", array($data->deviceID));
				}
				 
				if (count($step_3) > 0){
					return json_encode(array(array("response"=>"Success", "data"=>$step_3)));	
				}else{
					return json_encode(array(array("response"=>"Error", "description"=>"Tablet not yet assigned.")));
				}
				
			} else {
				return json_encode(array(array("response"=>"Error", "description"=>"Tablet not yet assigned.")));
			}
		}

		public function tablet_location() {
			global $logs;
			global $error000;
			$file_name = self::$file_name;

			$step_1 = process_qry("SELECT a.`locID`, b.`name` AS `locName`, b.`name` as `branch` FROM `devicecodetable` a, `loctable` b WHERE a.`status` = 'active' AND a.`deploy` = 'false' AND b.`status` = 'active' AND b.`locFlag` = 'active' AND b.`locID` = a.`locID`  GROUP BY a.`locID` ORDER BY b.`name` ASC", array());

			if (count($step_1) > 0) {
				return json_encode(array("response"=>"Success", "data"=>$step_1));
			} else {
				return json_encode(array("response"=>"Error", "description"=>"No active location."));
			}
		}

		public function assign_tablet($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$step_1 = process_qry("SELECT COUNT(*) AS `count` FROM `loctable` WHERE `locID` = ? AND `status` = 'active' LIMIT 1", array($data->locID));

			if ($step_1[0]['count'] > 0) {
				$step_2 = process_qry("SELECT COUNT(*) AS `count` FROM `devicecodetable` WHERE `locID` = ? AND `status` = 'active' LIMIT 1", array($data->locID));
				if ($step_2[0]['count'] > 0) {
					$step_3 = process_qry("SELECT COUNT(*) AS `count`, `status`, `deploy` FROM `devicecodetable` WHERE `deviceID` = ? AND `locID` = ?  LIMIT 1", array($data->deviceID, $data->locID));
					if ($step_3[0]['count'] > 0) {
						if (($step_3[0]['status'] == 'active') && ($step_3[0]['deploy'] == 'true')) {
							$step_4 = process_qry("UPDATE `devicecodetable` SET `version` = ?, `lastSync` = NOW(), `dateModified` = NOW() WHERE `deviceID` = ? AND `locID` = ?  AND `status` = 'active' LIMIT 1", array($data->version, $data->deviceID, $data->locID));
							// $step_5 = process_qry("SELECT a.`company`, a.`merchantCode`, b.`locID`, c.`name` AS `locName`, c.`branchCode`, b.`deviceCode`, DATE_FORMAT(NOW(),'%W, %M %d, %Y, %h:%i %p') AS `updateDate`, a.`redeemBasePoint`, d.`camount` AS `baseValue`, d.`cpoints` AS `basePoint`, 'false' AS `nonCash_status`, d.`ncamount` AS `baseValue_nonCash`, d.`ncpoints` AS `basePoint_nonCash`, c.`name` as `branch` FROM `settings` a, `devicecodetable` b, `loctable` c, `earnsettingstable` d WHERE b.`deviceID` = ? AND c.`locID` = b.`locID`  LIMIT 1", array($data->deviceID));
							$step_5 = process_qry("SELECT a.`company`, a.`merchantCode`, b.`locID`, c.`name` AS `locName`, c.`branchCode`, b.`deviceCode`, DATE_FORMAT(NOW(),'%W, %M %d, %Y, %h:%i %p') AS `updateDate`, a.`redeemBasePoint`, d.`camount` AS `baseValue`, d.`cpoints` AS `basePoint`, 'false' AS `nonCash_status`, d.`ncamount` AS `baseValue_nonCash`, d.`ncpoints` AS `basePoint_nonCash`,t.`taxtable` ,c.`name` as `branch` FROM `settings` a, `devicecodetable` b, `loctable` c, `earnsettingstable` d, `taxtable` t WHERE b.`deviceID` = ? AND d.`locID` = b.`locID` and b.`locID` = c.`locID`   GROUP by b.`locID` ", array($data->deviceID));

							$step_5[0]['updateDate'] = date("D, M d, Y, h:i A");
							return json_encode(array("response"=>"Success", "data"=>$step_5[0]));
						} else {
							$logs->write_logs('Error - Assign Tablet', $file_name, array(array("response"=>"Error", "description"=>"Tablet location may either be inactive or undeployed.", "data"=>$param)));
							return json_encode(array(array(array("response"=>"Error", "description"=>"Tablet location may either be inactive or undeployed."))));
						}
					} else {
						$step_4 = process_qry("SELECT COUNT(*) AS `count` FROM `devicecodetable` WHERE `deviceID` = ?  LIMIT 1", array($data->deviceID));

						if ($step_4[0]['count'] > 0) {
							$logs->write_logs('Error - Assign Tablet', $file_name, array(array("response"=>"Error", "description"=>"Tablet is currently assigned to another location.", "data"=>$param)));
							return json_encode(array(array("response"=>"Error", "description"=>"Tablet is currently assigned to another location.")));
						} else {
							$step_5 = process_qry("UPDATE `devicecodetable` SET `version` = ?, `deviceID` = ?, `deploy` = 'true', `lastSync` = NOW(), `dateModified` = NOW() WHERE `locID` = ? AND `status` = 'active' AND `deploy` = 'false' LIMIT 1", array($data->version, $data->deviceID, $data->locID));
							$step_6 = process_qry("SELECT COUNT(*) AS `count` FROM `devicecodetable` WHERE `deviceID` = ? AND `locID` = ?  LIMIT 1", array($data->deviceID, $data->locID));

							if ($step_6[0]['count'] > 0) {
								// $step_7 = process_qry("SELECT a.`company`, a.`merchantCode`, b.`locID`, c.`name` AS `locName`, c.`branchCode`, b.`deviceCode`, DATE_FORMAT(NOW(),'%W, %M %d, %Y, %h:%i %p') AS `updateDate`, a.`redeemBasePoint`, d.`camount` AS `baseValue`, d.`cpoints` AS `basePoint`, 'false' AS `nonCash_status`, d.`ncamount` AS `baseValue_nonCash`, d.`ncpoints` AS `basePoint_nonCash`, c.`name` as `branch` FROM `settings` a, `devicecodetable` b, `loctable` c, `earnsettingstable` d WHERE b.`deviceID` = ? AND c.`locID` = b.`locID`  LIMIT 1", array($data->deviceID));
								$step_7 = process_qry("SELECT a.`company`, a.`merchantCode`, b.`locID`, c.`name` AS `locName`, c.`branchCode`, b.`deviceCode`, DATE_FORMAT(NOW(),'%W, %M %d, %Y, %h:%i %p') AS `updateDate`, a.`redeemBasePoint`, d.`camount` AS `baseValue`, d.`cpoints` AS `basePoint`, 'false' AS `nonCash_status`, d.`ncamount` AS `baseValue_nonCash`, d.`ncpoints` AS `basePoint_nonCash`,t.`tax` , c.`name` as `branch` FROM `settings` a, `devicecodetable` b, `loctable` c, `earnsettingstable` d, `taxtable` t WHERE b.`deviceID` = ? AND d.`locID` = b.`locID` and b.`locID` = c.`locID`   GROUP by b.`locID` ", array($data->deviceID));
								$step_7[0]['updateDate'] = date("D, M d, Y, h:i A");
								return json_encode(array(array("response"=>"Success", "data"=>array($step_7[0]))));
							} else {
								$logs->write_logs('Error - Assign Tablet', $file_name, array(array("response"=>"Error", "description"=>"Unable to complete process.", "data"=>$param)));
								return json_encode(array(array("response"=>"Error", "description"=>"Unable to complete process.")));
							}
						}
					}
				} else {
					$logs->write_logs('Error - Assign Tablet', $file_name, array(array("response"=>"Error", "description"=>"No available location.", "data"=>$param)));
					return json_encode(array(array("response"=>"Error", "description"=>"No available location.")));
				}
			} else {
				$logs->write_logs('Error - Assign Tablet', $file_name, array(array("response"=>"Error", "description"=>"Invalid Location ID.", "data"=>$param)));
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid Location ID.")));
			}
		}

		public function tablet_perlocID($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$output = array();

			$chck = process_qry("SELECT * from loctable WHERE locID = ? AND locFlag = 'active' AND status = 'active' ", array($data->locID), $logs);
			if (count($chck) > 0 ){
				
				$locations = process_qry("SELECT l.* from loctable l left join  loyaltytable lyl on `l`.`locID` = `lyl`.`locID` where `l`.`locFlag` = 'active' and `l`.`status` = 'active' and `lyl`.`status` = 'active' and l.`locID` = ? group by l.`locID` order by `locname` asc ",array($data->locID), $logs);
			
				$loyalty = process_qry("SELECT lyl.* from loctable l right join  loyaltytable lyl on `l`.`locID` = `lyl`.`locID` where `l`.`locFlag` = 'active' and `l`.`status` = 'active' and `lyl`.`status` = 'active' and l.`locID` = ? order by `lyl`.`points`  ASC ",array($data->locID), $logs);
				
				
				for ($i=0; $i<count($locations); $i++) {
					if (($locations[$i]["image"] != NULL) && ($locations[$i]["image"] != " ")){
						$locations[$i]["image"] = DOMAIN . "/assets/images/hotels/full/" . html_entity_decode($locations[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');
					}
				}
				for ($i=0; $i<count($loyalty); $i++) {
					if (($loyalty[$i]["image"] != NULL) && ($loyalty[$i]["image"] != " ")){
						$loyalty[$i]["image"] = DOMAIN . "/assets/images/rewards/full/" . html_entity_decode($loyalty[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');
					}
				}
				
				array_push($output, array("locations"=>$locations, "loyalty"=>$loyalty));
				return json_encode(array(array("response"=>"Success", "data"=> $output)), JSON_UNESCAPED_SLASHES);

			}else{
				$logs->write_logs('Error - Tablet per locID', $file_name, array(array("response"=>"Error", "description"=>"No available location.", "data"=>$param)));
				return json_encode(array(array("response"=>"Error", "description"=>"No available location.")));
			} 
			
		}

		public function check_code($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$output = array();

			$codeType = substr($data->barCode, 0, 6);

			if ($codeType == 'Henann'){
				$qrCode = process_qry("SELECT `memberID` FROM memberstable WHERE `qrCard` = ? and status = 'active' ", array($data->barCode), $logs);	
			}else{
				$qrCode = process_qry("SELECT `memberID` FROM memberstable WHERE `qrApp` = ? and status = 'active' ", array($data->barCode), $logs);	
			}

			if (count($qrCode) > 0 ){
				return json_encode(array(array("response"=>"Success", "data"=>$qrCode)));
			}else{
				$logs->write_logs('Error - Tablet check qrCode', $file_name, array(array("response"=>"Error", "description"=>"No available qrcode.", "data"=>$param)));
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid Member.")));
			}
			
		}

	}

?>