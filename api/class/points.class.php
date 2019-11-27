<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class points extends mailer {
		public static $file_name = 'points.class.php';

		public function my_profile($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			// $transactionType = json_decode($this->transactionType(array("memberID"=>$data->memberID)), true);
			// if ((!$transactionType[0]["data"][0]["transactionType"]) || (($transactionType[0]["data"][0]["transactionType"] != 'card') && ($transactionType[0]["data"][0]["transactionType"] != 'app'))) {
			// 	return json_encode(array(array("response"=>"Error", "description"=>"No user found.")));
			// }
			
			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID)), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];
			return $this->complete_data(array("memberID"=>$memberID));
		}

		public function complete_data($param) {
			global $logs;
			global $protocol;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$param['procedure'] = "global_complete_data";

			$step_1 = process_qry("SELECT COUNT(*) AS `count`,`totalPoints` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($data->memberID));

			if ($step_1[0]['count'] > 0) {
				$total_earn = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `earntable` WHERE `memberID` = ? AND `status` = 'true'", array($data->memberID));
				// $total_earn[0]['points'] = (int) $total_earn[0]['points'];
				// $total_redeem = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `redeemtable` WHERE `memberID` = ? AND `status` = 'true'", array($data->memberID));
				$level = process_qry("SELECT `levelID`, `name`, `max` FROM `leveltable` WHERE CAST(? AS UNSIGNED) >= `min` AND `status` = 'active' ORDER BY `min` DESC LIMIT 1", array($total_earn[0]['points']));
				$sub_total =  $step_1[0]['totalPoints'];    //($total_earn[0]['points'] - $total_redeem[0]['points']);
				$last_transaction = process_qry("SELECT (a.`dateAdded`) AS `dateAdded`, `locID` FROM (SELECT `dateAdded`, `locID` FROM `earntable` WHERE `memberID` = ? UNION SELECT `dateAdded`, `locID` FROM `redeemtable` WHERE `memberID` = ? UNION SELECT `dateAdded`,`locID` FROM `redeemvouchertable` WHERE `memberID` = ?) a ORDER BY `dateAdded` DESC LIMIT 1", array($data->memberID, $data->memberID, $data->memberID));

				if ($sub_total < 0) { (int)$sub_total = 0; }
				if ((!$level[0]['max']) || ((int)$total_earn >= (int)$level[0]['max'])) { $level[0]['max'] = 'max'; }
				if (!isset($last_transaction[0]['dateAdded'])) { $last_transaction[0]['dateAdded'] = "NULL"; }

				$step_2 = process_qry("CALL `global_complete_data`(?, ?, ?, ?, ?, ?)", array($sub_total, $total_earn[0]['points'], $last_transaction[0]['dateAdded'], $level[0]['levelID'], $level[0]['name'], $data->memberID));

				if ($step_2[0]['result'] == "Success") {
					$logs->write_logs("Success - Complete Data", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				}
				
				/*$step_3 = process_qry("SELECT COUNT(*) AS `count` FROM `memberstable` WHERE `totalPoints` = ? AND`accumulatedPoints` = ? AND `lastTransaction` = ? AND `levelID` = ? AND `levelName` = ? AND `memberID` = ? LIMIT 1", array($sub_total, $total_earn[0]['points'], $last_transaction[0]['dateAdded'], $level[0]['levelID'], $level[0]['name'], $data->memberID));

				if ($step_3[0]['count'] > 0) {*/
					$output = process_qry("SELECT `memberID`, `fname`, `mname`, `lname`, `email`, `mobileNum`, `landlineNum`, `dateReg`, `qrApp`, `qrCard`, `occupation`, `civilStatus`, `dateOfBirth`, `address1`, `address2`, `city`, `province`, `zipcode`, `gender`, `image`, `profileStatus`, `dateReg`, `totalPoints`, `accumulatedPoints`, `lastTransaction`, (SELECT `locName` FROM `earntable` WHERE `memberID` = ? ORDER BY `dateAdded` DESC LIMIT 1) AS `lastVisited`, `levelID`, `levelName`, ? AS `levelMax` FROM `memberstable` WHERE `memberID`= ? LIMIT 1", array($data->memberID, $level[0]['max'], $data->memberID));

					if (!$output[0]['dateOfBirth']) { $output[0]['dateOfBirth'] = '0000-00-00'; }
					
					if ($output[0]['image']) {
						if (strpos($output[0]['image'], 'http') === false) {
							$output[0]['image'] =  DOMAIN . '/pictures/full/' .  $output[0]['image'];
							// $output[0]['image'] = $protocol . DOMAIN . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . 'full' . DIRECTORY_SEPARATOR .  $output[0]['image'];
						} else {
							$temp_image = explode("?", $output[0]['image']);
							$output[0]['image'] = ( count($temp_image) > 2 ) ? $temp_image[0] . '?' . $temp_image[1] : $temp_image[0];
						}
					}

					$output[0]['dateReg'] = date('M d,Y', strtotime($output[0]['dateReg']));
					$output[0]["lastSync"] = date('M d,Y');

					return json_encode(array(array("response"=>"Success", "data"=>array(array("profile"=>$output[0])))), JSON_UNESCAPED_SLASHES);
				/*} else {
					$logs->write_logs("Error - Complete Data", $file_name, array(array("response"=>"Error", "description"=>"Unable to fetch member data.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "errorCode"=>"0101", "description"=>"Unable to fetch member data.")));
				}*/
			}

			$logs->write_logs("Error - Complete Data", $file_name, array(array("response"=>"Error", "description"=>"No user found.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "errorCode"=>"0101", "description"=>"No user found.")));
		}

		// === start my_voucher_list
		public function my_voucher_list($param){
			global $logs;
			global $protocol;
			global $error000;
			$file_name = 'core.class.php';
			$output = array();
			$data = (object) $param;
			$qry = NULL;

			require_once(dirname(__FILE__) . '/voucher.class.php');
			$mVoucher = new vouchers();
 
			$output = $mVoucher->my_voucher_list(array("memberID"=>$data->memberID));
				// var_dump($output);
			return json_encode(array(array(array("response"=>"Success", "data"=>json_decode($output)))));
			
		}
		// === end my_voucher_list

		public function my_campaign_list($param) {
			global $logs;
			global $protocol;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$transactionType = json_decode($this->transactionType(array("memberID"=>$data->memberID)), true);

			if ((!$transactionType[0]["data"][0]["transactionType"]) || (($transactionType[0]["data"][0]["transactionType"] != 'card') && ($transactionType[0]["data"][0]["transactionType"] != 'app'))) {
				return json_encode(array(array("response"=>"Error", "description"=>"No user found.")));
			}
			
			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID, "transactionType"=>$transactionType[0]["data"][0]["transactionType"])), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$data->memberID = $get_member_id[0]["data"][0]["memberID"];
			$output = process_qry("SELECT loyaltyID, `name`, `points`, `terms`, `description`, `type` FROM `loyaltytable` WHERE `status` = 'active'", array());
			// $output = process_qry("SELECT loyaltyID, `name`, `points`, `terms`, `description` FROM `loyaltytable` WHERE `status` = 'active' AND `points` <= (SELECT `totalPoints` FROM `memberstable` WHERE `memberID` = ? LIMIT 1)", array($data->memberID));
			return json_encode(array(array("response"=>"Success", "data"=>$output)));
		}

		public function earn_points($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			$empty_sku = (($data->sku != NULL) && ($data->sku != '') && $data->sku != ' ') ? "False" : "True";
			$actual_points = 0;
			$earn_json = json_decode($data->earn, true);
			$cash_amount = intval(preg_replace('/[^\d.]/', '', $earn_json[0]["cash"][0]["amount"]));
			$cash_points = (int) $earn_json[0]["cash"][0]["points"];
			$noncash_amount = 0;
			$noncash_points = 0;
			$total_amount = $cash_amount + $noncash_amount;
			
			// $transactionType = json_decode($this->transactionType(array("memberID"=>$data->memberID)), true);
			// 
			// if ((!$transactionType[0]["data"][0]["transactionType"]) || (($transactionType[0]["data"][0]["transactionType"] != 'card') && ($transactionType[0]["data"][0]["transactionType"] != 'app'))) {
			// 	return json_encode(array(array("response"=>"Error", "description"=>"No user found.")));
			// }

			// $data->transactionType = $transactionType[0]["data"][0]["transactionType"];

			$data->transactionType = "app";
			// $data->terminalNum = $data->deviceID;

			if ($data->terminalNum != 'web'){

				$validate_terminal_num = json_decode($this->validate_terminal_num(array("terminalNum"=>$data->deviceID)), true);

				if ($validate_terminal_num[0]["response"] == "Error") {
					$param['description'] = $validate_terminal_num[0]['description'];
					$earn_points_attempt = $this->earn_points_attempt($param);
					return json_encode($validate_terminal_num);
				}

			}
				
			$validate_branchcode = json_decode($this->validate_branchcode(array("branchCode"=>$data->branchCode)), true);

			if ($validate_branchcode[0]["response"] == "Error") {
				$param['description'] = $validate_branchcode[0]['description'];
				$earn_points_attempt = $this->earn_points_attempt($param);
				return json_encode($validate_branchcode);
			}

			$locID = $validate_branchcode[0]["data"][0]["locID"];
			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID, "transactionType"=>$data->transactionType)), true);

			if ($get_member_id[0]["response"] == "Error") {
				$param['description'] = $get_member_id[0]['description'];
				$earn_points_attempt = $this->earn_points_attempt($param);
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];
			
			//=== expired
			$allowed_months = process_qry("select period_diff(extract(year_month from curdate()),extract(year_month from `expiration`)) as remaining from `memberstable` where `memberID` = ?",array($memberID));
			

			if( $allowed_months[0]['remaining'] > 3 ){
				$update_points = process_qry("UPDATE `memberstable` set `totalPoints` = '0', `accumulatedPoints` = '0'  WHERE `memberID` = ? ",array($memberID),$logs);
				$delete_points = process_qry("delete from earntable WHERE `memberID` = ? ",array($memberID),$logs);
					return json_encode(array(array("response"=>"Error", "description"=> "expired account " . $data->memberID ." and  Points = 0")));
			}
			//===
		   $transactionStatus = json_decode($this->check_transaction_id(array("transactionID"=>$data->transactionID, "locID"=>$locID, "branchCode"=>$data->branchCode)), true);

			if ($transactionStatus[0]["response"] == "Error") {
				$param['description'] = $transactionStatus[0]['description'];
				$earn_points_attempt = $this->earn_points_attempt($param);
				return json_encode($transactionStatus);
			}

			if ($this->check_transaction_exist(array("transactionID"=>$data->transactionID,"table"=>"earntable")) == true ){
				return json_encode(array(array("response"=>"Error", "description"=>"Receipt has already been used.")));
			}


			if ($transactionStatus[0]["data"][0]["return"] == 'Valid') {
			  
			  if ($data->terminalNum == 'web')	{
			  	$data->terminalNum = "web";
			  	
			  }
			 //  else{
			  	
				// $data->transactionID = substr("Trans" . randomizer(2) . DATE("H") . DATE("s") . randomizer(6), 0, 16);

			 //  }
			  $step_1 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `transactiontable` LIMIT 1", array(), $logs);
				$x = 0;
				$transID = NULL;

				while ($x < 1) {
					$transID = substr("EFRQ" . randomizer(2) . DATE("H") . $step_1[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$check_transID = process_qry("SELECT count(*) AS `count` FROM `transactiontable` WHERE `transID` = ? LIMIT 1", array($transID), $logs);
					$x = ( $check_transID[0]['count'] < 1 ) ? 1 : 0;
				}


				$data->officialReceipt = $data->transactionID;

				$logs->write_logs("Success - Get Last Insert", $file_name, array(array("response"=>"Success", "data"=>array($param))));

				$sku_temp = json_decode($data->sku, true);

				if (($empty_sku == 'False') && (count($sku_temp) > 0)) {
					for ($i=0; $i<count($sku_temp); $i++) {
						$data->transactionDate = DATE('Y-m-d H:i:s');
						$step_2 = process_qry("CALL insert_transaction(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?)", array($transID, $data->transactionID, $memberID, $locID, $data->branchCode, $data->terminalNum, $data->transactionType, $data->version, $total_amount, $sku_temp[$i]["sku"], (int)$sku_temp[$i]["qty"], $data->transactionDate, $data->officialReceipt,$data->userID,$data->userName,$data->designation));
					}
				} else {
					$data->transactionDate = DATE('Y-m-d H:i:s');
					$step_2 = process_qry("CALL insert_transaction(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?)", array($transID, $data->transactionID, $memberID, $locID, $data->branchCode, $data->terminalNum, $data->transactionType, $data->version, $total_amount, NULL, 0, $data->transactionDate, $data->officialReceipt,$data->userID,$data->userName,$data->designation));
				}
			}

			$step_3 = process_qry("SELECT COUNT(`id`) AS `count` FROM `earntable` WHERE `transactionID` = ? AND `locID` = ? LIMIT 1", array($data->transactionID, $locID));

			if ($step_3[0]['count'] > 0) {
				$param['description'] = "This transaction has already been processed.";
				$earn_points_attempt = $this->earn_points_attempt($param);
				return json_encode($transactionStatus);
			} else {
				$step_4 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, (MAX(`id`) + 1)) AS `entry` FROM `earntable`", array());
				$xx = 0;
				$earnID = NULL;

				while ($xx < 1) {
					$earnID = substr("EARN" . randomizer(2) . DATE("H") . $step_4[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$check_earnID = process_qry("SELECT count(*) AS `count` FROM `earntable` WHERE `earnID` = ? LIMIT 1", array($earnID));
					$xx = ( $check_earnID[0]['count'] < 1 ) ? 1 : 0;
				}

				$step_5 = process_qry("SELECT `email`,`totalPoints`,`accumulatedPoints` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($memberID));
				
				$step_6 = process_qry("SELECT `loctable`.`name`,  `brandtable`.`name` AS `brand` FROM `loctable`, `brandtable` WHERE `loctable`.`locID` = ? AND `loctable`.`branchCode` = ? LIMIT 1", array($locID, $data->branchCode));

				if ($cash_points > 0) {
					
					$data->transactionDate = DATE('Y-m-d H:i:s');
					
					$step_7 = process_qry("CALL earn_points(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?)", array($memberID, $locID, $data->branchCode, $data->terminalNum, $data->transactionID, $data->transactionType, 'cash', $cash_points, $cash_amount, $data->version, $data->transactionDate, $data->officialReceipt, $earnID, $step_5[0]['email'], $step_6[0]['name'], $data->barCode,$data->userID,$data->userName,$data->designation));


					$param['procedure'] = "earn_points";

					if (isset($step_7[0]['result'])) {
						$param['description'] = $step_7[0]['description'];
						$earn_points_attempt = $this->earn_points_attempt($param);

						$logs->write_logs("Error - Earn Points", $file_name, array(array("response"=>"Error", "description"=>$step_7[0]['description'], "data"=>array($param))));
						return json_encode(array(array("response"=>"Error", "description"=>$param['description'])));
					} else {
						$actual_points = $step_7[0]['points'];
						$logs->write_logs("Success - Earn Points", $file_name, array(array("response"=>"Success", "data"=>array($param))));
					}
					
				}
				$remarks = "earn points on " .$data->terminalNum;
				$this->auditLogs(array("userName"=>$data->userName,"memberID"=>$data->memberID,"table"=>"earntable","action"=>"earn points","transactionID"=>$data->transactionID,"fromdata"=>$step_5[0]['totalPoints'],"todata"=>$cash_points,"remarks"=>$remarks,"description"=>"Add Points","designation"=>$data->designation,"transactionDate"=>$data->transactionDate));

				return json_encode(array(array("response"=>"Success", "data"=>array("acquiredPt"=>((int)$cash_points + (int)$noncash_points), "totalPt"=>$actual_points))));
			}
		}

		public function redeem_points($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			
			// $transactionType = json_decode($this->transactionType(array("memberID"=>$data->memberID)), true);

			// if ((!$transactionType[0]["data"][0]["transactionType"]) || (($transactionType[0]["data"][0]["transactionType"] != 'card') && ($transactionType[0]["data"][0]["transactionType"] != 'app'))) {
			// 	return json_encode(array(array("response"=>"Error", "description"=>"No user found.")));
			// }
			$data->transactionType = "app";

			// $data->transactionType = $transactionType[0]["data"][0]["transactionType"];
			$validate_terminal_num = json_decode($this->validate_terminal_num(array("terminalNum"=>$data->deviceID)), true);

			if ($validate_terminal_num[0]["response"] == "Error") {
				return json_encode($validate_terminal_num);
			}

			$validate_branchcode = json_decode($this->validate_branchcode(array("branchCode"=>$data->branchCode)), true);

			if ($validate_branchcode[0]["response"] == "Error") {
				return json_encode($validate_branchcode);
			}

			$locID = $validate_branchcode[0]["data"][0]["locID"];
			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID, "transactionType"=>$data->transactionType)), true);			
			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];

			

			//====			
				$allowed_months = process_qry("select period_diff(extract(year_month from curdate()),extract(year_month from `expiration`)) as remaining from `memberstable` where `memberID` = ?",array($memberID));

				if( $allowed_months[0]['remaining'] > 3 ){
					$update_points = process_qry("UPDATE `memberstable` set `totalPoints` = '0', `accumulatedPoints` = '0'  WHERE `memberID` = ? ",array($memberID),$logs);
					$delete_points = process_qry("delete from earntable WHERE `memberID` = ? ",array($memberID),$logs);
					return json_encode(array(array("response"=>"Error", "description"=> "expired account " . $data->memberID )));
				}
			//====
			
			$data->transactionID = substr("Trans" . randomizer(2) . DATE("H") . DATE("s") . randomizer(6), 0, 16);
			$data->officialReceipt = $data->transactionID;

			$step_1 = process_qry("SELECT COUNT(*) AS `count`,`points` FROM `redeem_attempt` WHERE `memberCode` = ? AND `locID` = ? AND `branchCode` = ? AND `transactionID` = ? AND `officialReceipt` = ? LIMIT 1", array($data->memberID, $locID, $data->branchCode, $data->transactionID, $data->officialReceipt));

			if ($step_1[0]['count'] > 0) {

				$step_2 = process_qry("SELECT COUNT(*) AS `count` FROM `redeem_attempt` WHERE `memberCode` = ? AND `locID` = ? AND `branchCode` = ? AND `transactionID` = ? AND `officialReceipt` = ? AND `status` = 'false' LIMIT 1", array($data->memberID, $locID, $data->branchCode, $data->transactionID, $data->officialReceipt));

				if ($step_2[0]['count'] < 1) {
					$logs->write_logs("Error - Redeem Points", $file_name, array(array("response"=>"Error", "description"=>"Transaction request has already been queued.", "data"=>array($param))));
					// return json_encode(array(array("response"=>"Error", "description"=>"Transaction request has already been queued.")));
					// return $this->complete_data(array("memberID"=>$memberID));
					return json_encode(array(array("response"=>"Success")));
				}
			}

			$step_3 = process_qry("CALL `points_redeem_attempt_insert`(?, ?, ?, ?, ?, ?, ?, ?, ?)", array($data->terminalNum, $locID, $data->branchCode, $memberID, $data->transactionID, $data->transactionType, $data->points, $data->version, $data->officialReceipt));

			if ($step_3[0]['result'] == "Success") {
				// $total_earn = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `earntable` WHERE `memberID` = ? AND `status` = 'true'", array($memberID));
				// $total_redeem = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `redeemtable` WHERE `memberID` = ? AND `status` = 'true'", array($memberID));
				// $sub_total = ((int)$total_earn[0]['points'] - (int)$total_redeem[0]['points']);

				$sub_total = $get_member_id[0]['data'][0]['totalPoints'];
				$redeemBasePoint = process_qry("SELECT IF(`redeemBasePoint` IS NULL, 0, `redeemBasePoint`) AS `redeemBasePoint` FROM `settings` LIMIT 1", array());

				if ($sub_total < 0) { (int)$sub_total = 0; }

				if (((int)$data->points <  (int)$redeemBasePoint[0]['redeemBasePoint']) || ((int)$sub_total < (int)$data->points)) {
					$points_redeem_attempt = $this->points_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "status"=>'false', "description"=>'Insufficient Credits. Sorry, you do not have enough points to redeem this item.', "officialReceipt"=>$data->officialReceipt));
					$logs->write_logs("Error - Redeem Points", $file_name, array(array("response"=>"Error", "description"=>"Insufficient Credits. Sorry, you do not have enough points to redeem this item.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Insufficient Credits. Sorry, you do not have enough points to redeem this item.")));
				}

				$step_4 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `redeemtable` LIMIT 1", array());
				$x = 0;
				$redeemID = NULL;

				while ($x < 1) {
					$redeemID = substr("RDM" . randomizer(2) . DATE("H") . $step_4[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$check_redeemID = process_qry("SELECT count(*) AS `count` FROM `redeemtable` WHERE `redeemID` = ? LIMIT 1", array($redeemID));
					$x = ( $check_redeemID[0]['count'] < 1 ) ? 1 : 0;
				}

				$step_5 = process_qry("SELECT `email`,`totalPoints`,`accumulatedPoints` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($memberID));
				$step_7 = process_qry("SELECT `loctable`.`name`, `brandtable`.`name` AS `brand` FROM `loctable`, `brandtable` WHERE `loctable`.`locID` = ? AND `loctable`.`branchCode` = ?  LIMIT 1", array($locID, $data->branchCode));
				// echo $memberID.','. $data->terminalNum.','. $locID.','. $data->branchCode.','. $data->transactionID.','. $data->transactionType.','. $data->points.','. $data->version.','. $data->memberID.','. $data->officialReceipt.','. $step_5[0]['email'].','. $validate_branchcode[0]["data"][0]["name"].','. $sub_total.','. $redeemID;
				
				$step_6 = process_qry("CALL `redeem_points` (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?)", array($memberID, $data->terminalNum, $locID, $data->branchCode, $data->transactionID, $data->transactionType, $data->points, $data->version, $data->officialReceipt, $step_5[0]['email'], $validate_branchcode[0]["data"][0]["name"], $sub_total, $redeemID, $data->rewardsID,$data->userID,$data->userName,$data->designation));

				if ($step_6[0]['result'] == "Success") {
					$v_status = ($step_6[0]['result'] == "Success") ? "true" : "false";
					$v_description = ($v_status == "false") ? $step_6[0]["description"] : NULL;
					$points_redeem_attempt = $this->points_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "status"=>$v_status, "description"=>$v_description, "officialReceipt"=>$data->officialReceipt));

					if ($v_status == "true") {

						$this->auditLogs(array("userName"=>$data->userName,"memberID"=>$data->memberID,"table"=>"redeemtable","action"=>"redeem points","transactionID"=>$data->transactionID,"fromdata"=>$data->points,"todata"=>$step_5[0]['totalPoints'],"remarks"=>"redeem points","description"=>"redeem Points","designation"=>$data->designation,"transactionDate"=>$data->transactionDate));

						return $this->complete_data(array("memberID"=>$memberID));
					} else {
						$logs->write_logs("Error - Redeem Points", $file_name, array(array("response"=>"Error", "description"=>$v_description, "data"=>array($param))));
						return json_encode(array(array("response"=>"Error", "description"=>$v_description)));
					}
				} else {
					$points_redeem_attempt = $this->points_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "status"=>'false', "description"=>$step_6[0]['description'], "officialReceipt"=>$data->officialReceipt));
					$logs->write_logs("Error - Redeem Points", $file_name, array(array("response"=>"Error", "description"=>$step_6[0]['description'], "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>$step_6[0]['description'])));
				}
			} else {
				$logs->write_logs("Error - Redeem Points", $file_name, array(array("response"=>"Error", "description"=>"Unable to queue transaction request.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Unable to queue transaction request.")));
			}
		}

		public function redeem_voucher($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			// $transactionType = json_decode($this->transactionType(array("memberID"=>$data->memberID)), true);
			$transactionType = "app";
			$data->transactionID = substr("Trans" . randomizer(2) . DATE("H") . DATE("s") . randomizer(6), 0, 16);


			if ($this->check_transaction_exist(array("transactionID"=>$data->transactionID,"table"=>"redeem_request_rewards")) == true ){
				return json_encode(array(array("response"=>"Error", "description"=>"Receipt has already been used.")));
			}

			$data->officialReceipt = $data->transactionID;

			// if ((!$transactionType[0]["data"][0]["transactionType"]) || (($transactionType[0]["data"][0]["transactionType"] != 'card') && ($transactionType[0]["data"][0]["transactionType"] != 'app'))) {
			// 	return json_encode(array(array("response"=>"Error", "description"=>"No user found.")));
			// }

			// $data->transactionType = $transactionType[0]["data"][0]["transactionType"];
			$validate_terminal_num = json_decode($this->validate_terminal_num(array("terminalNum"=>$data->deviceID)), true);

			if ($validate_terminal_num[0]["response"] == "Error") {
				return json_encode($validate_terminal_num);
			}

			$validate_branchcode = json_decode($this->validate_branchcode(array("branchCode"=>$data->branchCode)), true);

			if ($validate_branchcode[0]["response"] == "Error") {
				return json_encode($validate_branchcode);
			}

			$locID = $validate_branchcode[0]["data"][0]["locID"];
			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID)), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];

			//====
					// === 3 months allowed
					// $allowed_months = process_qry("select period_diff(extract(year_month from curdate()),extract(year_month from `expiration`)) as remaining from `memberstable` where `memberID` = ?",array($memberID));
					//$ if( $allowed_months[0]['remaining'] > 3 ){
					// 	return json_encode(array(array("response"=>"Error", "description"=> "expired account " . $data->memberID )));
					// }
			//====


			$voucher_qty = process_qry("SELECT * FROM vouchertable WHERE `voucherID` = ? ", array($data->voucherID),$logs);
			
			if (count($voucher_qty) <= 0 ){
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid voucher.")));
			}
			$count_redeem = process_qry("SELECT count(`voucherID`) as `cnt` FROM redeemvouchertable WHERE `memberID` = ? AND `email` = ? and `voucherID` = ? AND `type` = ? AND `status` = 'active'", array($memberID,$get_member_id[0]["data"][0]["email"], $data->voucherID,$voucher_qty[0]['type'] ), $logs);

			if ($voucher_qty[0]['redemptionlimit'] != 0)  {
				$voucher_qty[0]['redemption_remaining'] = $voucher_qty[0]['redemption_remaining'] + 1;
				if ($voucher_qty[0]['redemption_remaining'] > $voucher_qty[0]['redemptionlimit']){
					return json_encode(array(array("response"=>"Error", "description"=>"Voucher cannot be redeemed")));
				}
			}

			$step_1 = process_qry("SELECT COUNT(*) AS `count` FROM `redeemvoucher_attempt` WHERE `memberCode` = ? AND `locID` = ? AND `branchCode` = ? AND `transactionID` = ? AND `officialReceipt` = ? LIMIT 1", array($data->memberID, $locID, $data->branchCode, $data->transactionID, $data->officialReceipt));

			if ($step_1[0]['count'] > 0) {
				$step_2 = process_qry("SELECT COUNT(*) AS `count` FROM `redeemvoucher_attempt` WHERE `memberCode` = ? AND `locID` = ? AND `branchCode` = ? AND `transactionID` = ? AND `officialReceipt` = ? AND `status` = 'false' LIMIT 1", array($data->memberID, $locID, $data->branchCode, $data->transactionID, $data->officialReceipt));

				if ($step_2[0]['count'] < 1) {
					$logs->write_logs("Error - Redeem Voucher", $file_name, array(array("response"=>"Error", "description"=>"Transaction request has already been queued.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Transaction request has already been queued.")));
				}
			}

			$step_3 = process_qry("CALL `redeemvoucher_attempt_insert`(?, ?, ?, ?, ?, ?, ?, ?, ?)", array($data->memberID, $locID, $data->branchCode, $data->transactionID, $data->transactionType, $data->terminalNum, $data->voucherID, $data->version, $data->officialReceipt));

			if ($step_3[0]['result'] == "Success") {

				$step_4 = process_qry("SELECT COUNT(`earnvouchertable`.`id`) AS `count` FROM `earnvouchertable` WHERE `earnvouchertable`.`memberID` = ? AND `earnvouchertable`.`status` = 'active' AND `earnvouchertable`.`voucherID` = ? LIMIT 1", array($memberID, $data->voucherID));

				if ($step_4[0]['count'] > 0) {
					
					$step_5 = process_qry("SELECT COUNT(`redeemvouchertable`.`id`)  AS `count` FROM `redeemvouchertable` WHERE `redeemvouchertable`.`memberID` = ? AND `redeemvouchertable`.`terminalNum` = ? AND `redeemvouchertable`.`voucherID` = ? AND `redeemvouchertable`.`locID` = ? AND `redeemvouchertable`.`branchCode` = ? AND `redeemvouchertable`.`transactionID` = ? AND `redeemvouchertable`.`transactionType` = ? LIMIT 1", array($memberID, $data->terminalNum, $data->voucherID, $locID, $data->branchCode, $data->transactionID, $data->transactionType));
				

					$steps = process_qry("SELECT `loctable`.`name`, `brandtable`.`name` AS `brand` FROM `loctable`, `brandtable` WHERE `loctable`.`locID` = ? AND `loctable`.`branchCode` = ? LIMIT 1", array($locID, $data->branchCode));


					if ($step_5[0]['count'] < 1) {
						
						$step_6 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `redeemvouchertable` LIMIT 1", array());
						$x = 0;
						$redeemVID = NULL;

						while ($x < 1) {
							$redeemVID = substr("RDM" . randomizer(2) . DATE("H") . $step_6[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
							$check_redeemVID = process_qry("SELECT count(*) AS `count` FROM `redeemvouchertable` WHERE `redeemVID` = ? LIMIT 1", array($redeemVID));
							$x = ( $check_redeemVID[0]['count'] < 1 ) ? 1 : 0;
						}
						// echo $memberID.','. $data->terminalNum.','. $locID.','. $data->branchCode.','. $data->transactionID.','. $data->transactionType.','. $data->voucherID.','. $data->version.','. $data->memberID.','. $validate_branchcode[0]["data"][0]["name"].','. $redeemVID.','. $steps[0]['brand'];
						

						$step_7 = process_qry("CALL redeem_voucher(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($memberID, $data->terminalNum, $locID, $data->branchCode, $data->transactionID, $data->transactionType, $data->voucherID, $data->version, $validate_branchcode[0]["data"][0]["name"], $redeemVID, $data->barCode));

						if ($step_7[0]['result'] == "Success") {
							$v_status = ($step_7[0]['result'] == "Success") ? "true" : "false";
							$v_description = ($v_status == "false") ? $step_7[0]["description"] : NULL;
							$voucher_redeem_attempt = $this->voucher_redeem_attempt(array("memberID"=>$memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "status"=>$v_status, "description"=>$v_description));

							if ($v_status == "true") {
								$step_3 = process_qry("UPDATE `vouchertable` a SET a.`redemption_remaining` = (SELECT COUNT(*) FROM `redeemvouchertable` WHERE `voucherID` = a.`voucherID`)  WHERE a.`status` = 'active' AND DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND `type` in ('flash','registration','birthday')", array(), $logs);
								
								// $step_03 = process_qry("UPDATE `vouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND `redemption_remaining` = 0 AND `type` = 'flash'", array(), $logs);
								$step_registration_status = process_qry("UPDATE `vouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND `redemption_remaining` >= `redemptionlimit` AND `type` = 'registration' and `redemptionlimit` != 0", array());
								$step_birthday_status = process_qry("UPDATE `vouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND `redemption_remaining` >= `redemptionlimit` AND `type` = 'birthday' and `redemptionlimit` != 0", array());

								$step_flash_status = process_qry("UPDATE `vouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND `redemption_remaining` >= `redemptionlimit` AND `type` = 'flash' and `redemptionlimit` != 0", array());

								//===
								$UPDATE_registration_status = process_qry("UPDATE `earnvouchertable` set status = 'expired' WHERE status = (SELECT status FROM vouchertable a WHERE DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND  a.`status` = 'expired' and a.`type` = 'registration' )", array());
								$UPDATE_birthday_status = process_qry("UPDATE `earnvouchertable` set status = 'expired' WHERE status = (SELECT status FROM vouchertable a WHERE DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND  a.`status` = 'expired' and a.`type` = 'birthday' )", array());

								$arr = array("description"=>"You have successfully redeemed your voucher.");
								$output  = array_merge($param,$arr);
								// return json_encode(array(array(array("response"=>"Success", "data"=>array($output) ))));

								$check_voucher = process_qry("SELECT * FROM `vouchertable` WHERE `voucherID` = ?", array($data->voucherID),$logs);
								$dateNow = DATE('Y-m-d H:i:s');
								$cron = process_qry("INSERT INTO `cron_earnvouchertable`(`voucherID`,`memberID`,`email`,`fname`,`lname`,`name`,`type`,`description`,`emailSent`,`dateAdded`,`dateModified`) VALUES(?,?,?,?,?,?,?,?,?,?,?)",array($data->voucherID,$memberID,$get_member_id[0]["data"][0]["email"],$get_member_id[0]["data"][0]["fname"],$get_member_id[0]["data"][0]["lname"],$check_voucher[0]["name"],$check_voucher[0]["type"],$check_voucher[0]["description"],"false",$dateNow,$dateNow),$logs);
								 
								// $this->emailer(array("recipient_name" => $check_member[0]['fname'] . ' '. $check_member[0]['lname'], "email" => $check_member[0]['email'], "type" => "redeem_voucher","subject" => "Voucher Redemption" ));

								// return $this->complete_data(array("memberID"=>$memberID));
								// $data["description"] = "You have successfully requested to redeem your voucher. Please check email for instructions.";

								$this->auditLogs(array("userName"=>$data->userName,"memberID"=>$data->memberID,"table"=>"redeemvouchertable","action"=>"redeem voucher","transactionID"=>$data->transactionID,"fromdata"=>$data->voucherID,"todata"=>$data->voucherID,"remarks"=>"redeem voucher","description"=>"redeem voucher","designation"=>$data->designation,"dateAdded"=>$data->transactionDate));


								return json_encode(array(array(array("response"=>"Success", "data"=>array($output) ))));


							} else {
								$logs->write_logs("Error - Redeem Voucher", $file_name, array(array("response"=>"Error", "description"=>$v_description, "data"=>array($param))));
								return json_encode(array(array(array("response"=>"Error", "description"=>$v_description))));
							}
						} else {
							
							$voucher_redeem_attempt = $this->voucher_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "status"=>"false", "description"=>$step_7[0]['description']));
							$logs->write_logs("Error - Redeem Voucher", $file_name, array(array("response"=>"Error", "description"=>$step_7[0]['description'], "data"=>array($param))));
							return json_encode(array(array(array("response"=>"Error", "description"=>$step_7[0]['description']))));
						}
					} else {
						$voucher_redeem_attempt = $this->voucher_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "status"=>"false", "description"=>"Voucher redemption was already been process."));
						$logs->write_logs("Error - Redeem Voucher", $file_name, array(array("response"=>"Error", "description"=>"Voucher redemption was already been process.", "data"=>array($param))));
						return json_encode(array(array(array("response"=>"Error", "description"=>"Voucher redemption was already been process."))));
					}
				} else {
					$voucher_redeem_attempt = $this->voucher_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "status"=>"false", "description"=>"Voucher does not exist."));
					$logs->write_logs("Error - Redeem Voucher", $file_name, array(array("response"=>"Error", "description"=>"Voucher does not exist.", "data"=>array($param))));
					return json_encode(array(array(array("response"=>"Error", "description"=>"Voucher does not exist."))));
				}
			} else {
				$logs->write_logs("Error - Redeem Voucher", $file_name, array(array("response"=>"Error", "description"=>"Unable to queue transaction request.", "data"=>array($param))));
				return json_encode(array(array(array("response"=>"Error", "description"=>"Unable to queue transaction request."))));
			}	
		}

		// === start redeem_notification_voucher
		public function redeem_notification_voucher($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			
			// $transactionType = json_decode($this->transactionType(array("memberID"=>$data->memberID)), true);
			$transactionType = "app";
			// if ((!$transactionType[0]["data"][0]["transactionType"]) || (($transactionType[0]["data"][0]["transactionType"] != 'card') && ($transactionType[0]["data"][0]["transactionType"] != 'app'))) {
			// 	return json_encode(array(array(array("response"=>"Error", "description"=>"No user found."))));
			// }

			$data->transactionType =  $transactionType;  //$transactionType[0]["data"][0]["transactionType"];
			if ($data->platform == "ios" || $data->platform == "android"){
				$chck_deviceID = process_qry("SELECT count(*) as `cnt` FROM `devicetokens` WHERE `deviceID` = ? AND `platform` = ? ", array($data->deviceID, $data->platform), $logs);
				if($chck_deviceID[0]['cnt'] <= 0 ){
					$logs->write_logs("Error - Redeem notification voucher", $file_name, array(array("response"=>"Error", "description"=>"Transaction request has already been queued.", "data"=>array($param))));
					return json_encode(array(array(array("response" => "Error" , "description" => "No deviceID found"))));
				}
			}



			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID)), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];
			
			$check_requested_voucher = process_qry("SELECT count(*) as `cnt` FROM redeem_request_voucher WHERE `memberID` = ? and `voucherID` = ? and status = 'pending'", array($memberID,$data->voucherID), $logs);

			if ($check_requested_voucher[0]['cnt'] > 0 ){
				$logs->write_logs("Error - Redeem notification voucher", $file_name, array(array("response"=>"Error", "description"=>"Already requested this voucher.", "data"=>array($param))));
				return json_encode(array(array(array("response"=> "Error", "description"=>"Already requested this voucher."))));
			}

			$check_member = process_qry("SELECT * FROM `memberstable` WHERE `memberID` = ? AND `status` = 'active'", array($memberID), $logs );

			$step_1 = process_qry("UPDATE `earnvouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND DATE_FORMAT(NOW(), '%Y-%m-%d') > DATE_FORMAT(`endDate`, '%Y-%m-%d') AND memberID = ?", array($memberID), $logs);
			
			$step_2 = process_qry("SELECT count(*) as `cnt`, `name` FROM earnvouchertable where  `status` = 'active' and memberID = ? and voucherID = ? ", array($memberID, $data->voucherID), $logs);

			if($step_2[0]['cnt'] <= 0 ){
				$logs->write_logs("Error - Redeem notification voucher", $file_name, array(array("response"=>"Error", "description"=>"Voucher is not available.", "data"=>array($param))));
				return json_encode(array("response" => "Error" , "description" => "Voucher is not available."));
			}


			if ($check_member > 0){
				$x = 0;
				$t = 0;
				$requestID = NULL;
				$transactionID = NULL;

				$step_3 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `redeem_request_voucher` LIMIT 1", array());
				while ($x < 1) {
					$requestID = substr("RNV" . randomizer(2) . DATE("H") . $step_3[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$check_RNV = process_qry("SELECT count(*) AS `count` FROM `redeem_request_voucher` WHERE `requestID` = ? LIMIT 1", array($requestID), $logs);
					$x = ( $check_RNV[0]['count'] < 1 ) ? 1 : 0;
				}

				while ($t < 1) {
					$transactionID = substr("RNTranS" . randomizer(5) . DATE("H") . $step_3[0]['entry'] . DATE("s") . randomizer(6), 0, 32);
					$check_RNTranS = process_qry("SELECT count(*) AS `count` FROM `redeem_request_voucher` WHERE `transactionID` = ? LIMIT 1", array($transactionID), $logs);
					$t = ( $check_RNTranS[0]['count'] < 1 ) ? 1 : 0;
				}
				
				$Ins_RNV = process_qry("CALL `redeem_notification_voucher`(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($memberID,$requestID,$data->voucherID,$transactionID, $check_member[0]['email'],$check_member[0]['fname'],$check_member[0]['lname'],$step_2[0]['name'],"pending","pending",$data->platform, $data->memberID), $logs);
				if ($Ins_RNV[0]['result'] == "Success") {
					$param['description'] = "You have successfully redeemed your voucher. Please check email for instructions";
					
					$logs->write_logs("Success - Redeem notification voucher insert", $file_name, array(array("response"=>"Error", "data"=>array($param))));

					$this->emailer(array("recipient_name" => $check_member[0]['fname'] . ' '. $check_member[0]['lname'], "email" => $check_member[0]['email'], "type" => "redeem_notification_voucher","subject" => "Voucher Redemption Request " ));

					return json_encode(array(array(array("response"=>"Success", "data"=>array($param)))));

				}else{
					return json_encode(array("response"=> "Error", "description" => "Unable to save your record."));
				}

			}else{
				return json_encode(array("response"=>"Error", "description"=>"No user found."));
			}
		}
		// === end redeem_notification_voucher

		// === start redeem_notification_rewards
		public function redeem_notification_rewards($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			$data->transactionType =  "app"; //$transactionType[0]["data"][0]["transactionType"];
			$chck_loyalty = process_qry("SELECT * FROM loyaltytable WHERE loyaltyID = ? and status = 'active'", array($data->rewardsID), $logs);

			if( count($chck_loyalty) <= 0 ){
				$logs->write_logs("Error - Redeem notification rewards", $file_name, array(array("response"=>"Error", "description"=>"rewardsID is Invalid.", "data"=>array($param))));
				return json_encode(array(array(array("response" => "Error" , "description" => "rewardsID is Invalid."))));
			}

			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID, "transactionType"=>$data->transactionType)), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];

			$check_member = process_qry("SELECT * FROM `memberstable` WHERE `memberID` = ? AND `status` = 'active'", array($memberID), $logs );

			if ($check_member > 0){
				$x = 0;
				$t = 0;
				$requestID = NULL;
				$transactionID = NULL;

				$step_1 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `redeem_request_rewards` LIMIT 1", array());
				while ($x < 1) {
					$requestID = substr("RNW" . randomizer(2) . DATE("H") . $step_1[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$check_RNV = process_qry("SELECT count(*) AS `count` FROM `redeem_request_rewards` WHERE `requestID` = ? LIMIT 1", array($requestID), $logs);
					$x = ( $check_RNV[0]['count'] < 1 ) ? 1 : 0;
				}

				while ($t < 1) {
					$transactionID = substr("RNTranW" . randomizer(5) . DATE("H") . $step_1[0]['entry'] . DATE("s") . randomizer(6), 0, 32);
					$check_RNTranS = process_qry("SELECT count(*) AS `count` FROM `redeem_request_rewards` WHERE `transactionID` = ? LIMIT 1", array($transactionID), $logs);
					$t = ( $check_RNTranS[0]['count'] < 1 ) ? 1 : 0;
				}
				$total_request = 0;

				// $cnt_rewards = process_qry("SELECT sum(`points`) as `totalpoints` FROM `redeem_request_rewards` where `memberID` = ?", array($memberID), $logs);
				// $total_request = $check_member[0]['totalPoints'] - $cnt_rewards[0]['totalpoints'];
				
				if ($check_member[0]['totalPoints'] < $chck_loyalty[0]['points']){
					
					$logs->write_logs("Error - Redeem notification rewards", $file_name, array(array("response"=>"Error", "description"=>"Not enough points to redeemed.", "data"=>array($param))));
						return json_encode(array(array(array("response"=> "Error", "description"=>"Not enough points to redeem."))));
				}				

				if ($chck_loyalty[0]['promoType'] == 'premium'){

					$check_requested_rewards = process_qry("SELECT count(*) as `cnt` FROM redeem_request_rewards WHERE `memberID` = ? and `rewardsID` = ? AND status = 'pending'", array($memberID,$data->rewardsID), $logs);

					$checkLocation = process_qry("SELECT l.locID,lyl.locName, locCategoryName FROM loctable l inner join `loyaltytable` lyl on l.locID = lyl.locID   WHERE lyl.loyaltyID = ? and l.status ='active' and lyl.status = 'active' limit 1;",array($data->rewardsID),$logs);

					if (count($checkLocation) > 0){
						$data->locID = $checkLocation[0]['locID'];
						$data->locName = $checkLocation[0]['locName'];
					}else{
						$logs->write_logs("Error - Redeem notification rewards", $file_name, array(array("response"=>"Error", "description"=>"Not loyaltyID", "data"=>array($param))));
						return json_encode(array(array(array("response"=> "Error", "description"=>"Not loyaltyID."))));
					}

					// if ($data->platform == "ios" || $data->platform == 'android' || $data->platform == 'Android'){
						
					// 	// $data->locID = 'Moblie App';
					// 	// $data->locName = 'Moblie App';
						
					// }elseif($data->platform == 'web'){
						
					// 	// $data->locID = 'Web';
					// 	// $data->locName = 'Web';
					// }

					$premium = process_qry("CALL `redeem_notification_rewards`(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)", array($memberID,$requestID,$data->rewardsID,$transactionID, $check_member[0]['email'],$check_member[0]['fname'],$check_member[0]['lname'],$chck_loyalty[0]['name'],$chck_loyalty[0]['promoType'],$chck_loyalty[0]['points'],"pending","pending",$data->platform, $data->barCode,$data->locID, $data->locName), $logs);
					
					if ($premium[0]['result'] == "Success") {
						$param['description'] = "You have successfully requested to redeem your reward. Our staff will contact you for further instructions.";
						
						$logs->write_logs("Success - Redeem notification rewards insert", $file_name, array(array("response"=>"Error", "data"=>array($param))));

						// $this->emailer(array("recipient_name" => $check_member[0]['fname'] . ' '. $check_member[0]['lname'], "email" => $check_member[0]['email'], "type" => "redeem_notification_rewards","subject" => "Rewards Redemption Request" ));

						return json_encode(array(array(array("response"=>"Success", "data"=>array($param)))));

					}else{
						return json_encode(array("response"=> "Error", "description" => "Unable to save your record."));
					}
				}else{ // === regular

					$check_requested_rewards = process_qry("SELECT count(*) as `cnt` FROM redeem_request_rewards WHERE `memberID` = ? and `rewardsID` = ? and `dateAdded` = now() and  status = 'approved'", array($memberID,$data->rewardsID), $logs);
					
					if ($check_requested_rewards[0]['cnt'] > 0 ){
						$logs->write_logs("Error - Redeem notification rewards", $file_name, array(array("response"=>"Error", "description"=>"Transaction request has already been queued.", "data"=>array($param))));
						return json_encode(array(array(array("response"=> "Error", "description"=>"Transaction request has already been queued."))));
					}

					$Ins_RNV = process_qry("CALL `redeem_notification_rewards`(?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?, ?, ?,?,?)", array($memberID,$requestID,$data->rewardsID,$transactionID, $check_member[0]['email'],$check_member[0]['fname'],$check_member[0]['lname'],$chck_loyalty[0]['name'],$chck_loyalty[0]['promoType'],$chck_loyalty[0]['points'],"pending","pending",$data->platform, $data->barCode,$data->locID,$data->locName), $logs);
					if ($Ins_RNV[0]['result'] == "Success") {
						$param['description'] = "You have successfully requested to redeem your reward. Our staff may contact you for further instructions.";
						
						$logs->write_logs("Success - Redeem notification rewards insert", $file_name, array(array("response"=>"Error", "data"=>array($param))));

						// $this->emailer(array("recipient_name" => $check_member[0]['fname'] . ' '. $check_member[0]['lname'], "email" => $check_member[0]['email'], "type" => "redeem_notification_rewards","subject" => "Rewards Redemption Request" ));

						return json_encode(array(array(array("response"=>"Success", "data"=>array($param)))));

					}else{
						return json_encode(array(array(array("response"=> "Error", "description" => "Unable to save your record."))));
					}
				}
			}else{
				return json_encode(array(array(array("response"=>"Error", "description"=>"No user found."))));
			}
		}
		// === end redeem_notification_rewards

		public function redeem_campaign_old($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			
			$data->transactionType =  "app";  //$transactionType[0]["data"][0]["transactionType"];
			
			if($data->terminalNum != 'web'){
				$validate_terminal_num = json_decode($this->validate_terminal_num(array("terminalNum"=>$data->deviceID, "branchCode"=>$data->branchCode)), true);

				if ($validate_terminal_num[0]["response"] == "Error") {
					return json_encode($validate_terminal_num);
				}
			}
		
			$validate_branchcode = json_decode($this->validate_branchcode(array("branchCode"=>$data->branchCode)), true);

			if ($validate_branchcode[0]["response"] == "Error") {
				return json_encode($validate_branchcode);
			}

			$locID = $validate_branchcode[0]["data"][0]["locID"];

			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID, "transactionType"=>$data->transactionType)), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];

			// if ($data->status == 'disapproved'){
			// 	$dateModified = DATE('Y-m-d H:i:s');
			// 	$disapproved = process_qry("UPDATE `redeem_request_rewards` SET `status` = ?, dateModified = ? WHERE memberID = ? AND `rewardsID` = ? AND `transactionID` = ? AND `requestID` = ? ", array($data->status, $dateModified, $memberID, $data->loyaltyID, $data->transactionID, $data->requestID));
			// 	return json_encode(array(array(array("response" => "Success" , "data" => array($param)))));
			// }


			$check_rewards = process_qry("SELECT * from `loyaltytable` WHERE loyaltyID = ? and `status`= 'active'", array($data->loyaltyID), $logs);
			$t = 0;
			while ($t < 1) {
				$step_1 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `redeemtable` LIMIT 1", array());
				$transactionID = substr("RNTranW" . randomizer(5) . DATE("H") . $step_1[0]['entry'] . DATE("s") . randomizer(6), 0, 32);
				$check_RNTranS = process_qry("SELECT count(*) AS `count` FROM `redeemtable` WHERE `transactionID` = ? LIMIT 1", array($transactionID), $logs);
				$t = ( $check_RNTranS[0]['count'] < 1 ) ? 1 : 0;
			}


			if (count($check_rewards) > 0){
				if ($check_rewards[0]['promoType'] == 'premium'){

				 	$result_notif =  $this->redeem_notification_rewards(array("terminalNum"=>$data->terminalNum, "branchCode" => $data->branchCode, "memberID"=> $data->memberID, "rewardsID" =>$data->loyaltyID, "version" => $data->version, "transactionType" => $data->transactionType,"platform"=>"tablet","barCode"=>$data->barCode));
				 	$arr = json_decode($result_notif, true);				 	
				 	if ($arr['0'][0]['response'] == 'Success'){
				 		return json_encode(array(array(array("response"=>$arr['0'][0]['response'], "data"=>$arr['0'][0]['data']))));
				 	}else{
				 		return json_encode(array(array(array("response"=>"Error", "description"=>$arr['0'][0]['description']))));
				 	}

				}elseif ($check_rewards[0]['promoType'] != 'regular'){
					return json_encode(array(array(array("response"=>"Error", "description"=> "No promo type is available " ))));
				}
			}else{
				return json_encode(array(array(array("response"=>"Error", "description"=> "No rewards available " . $data->loyaltyID ))));
			}


			$step_3 = process_qry("CALL `points_redeem_attempt_insert`(?, ?, ?, ?, ?, ?, ?, ?, ?)", array($data->terminalNum, $locID, $data->branchCode, $data->memberID, $transactionID, $data->transactionType, $data->loyaltyID, $data->version, $transactionID));

			if ($step_3[0]['result'] == "Success") {
				// $total_earn = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `earntable` WHERE `memberID` = ? AND `status` = 'true'", array($memberID));
				// $total_redeem = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `redeemtable` WHERE `memberID` = ? AND `status` = 'true'", array($memberID));
				// $sub_total = ((int)$total_earn[0]['points'] - (int)$total_redeem[0]['points']);
				$sub_total = $get_member_id[0]["data"][0]["totalPoints"];
				if ($sub_total < 0) { (int)$sub_total = 0; }

				$campaign = process_qry("SELECT COUNT(*) AS `count`, `points` FROM `loyaltytable` WHERE `loyaltyID` = ? AND `status` = 'active' LIMIT 1", array($data->loyaltyID));

				if ($campaign[0]['count'] > 0) {
					if ($campaign[0]['points'] > $sub_total) {
						$campaign_redeem_attempt = $this->campaign_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$transactionID, "status"=>'false', "description"=>'Insuficient points to redeem.', "officialReceipt"=>$transactionID, "loyaltyID"=>$data->loyaltyID));
						$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>"Insuficient points to redeem.", "data"=>array($param))));
						return json_encode(array(array(array("response"=>"Error", "description"=>"Insuficient points to redeem."))));
					}
				} else { 
					$campaign_redeem_attempt = $this->campaign_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$transactionID, "status"=>'false', "description"=>'Loyalty Campaign does not exist.', "officialReceipt"=>$transactionID, "loyaltyID"=>$data->loyaltyID));
					$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>"Loyalty Campaign does not exist.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Loyalty Campaign does not exist.")));
				}

				$step_4 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `redeemtable` LIMIT 1", array());
				$x = 0;
				$redeemID = NULL;

				while ($x < 1) {
					$redeemID = substr("RDM" . randomizer(2) . DATE("H") . $step_4[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$check_redeemID = process_qry("SELECT count(*) AS `count` FROM `redeemtable` WHERE `redeemID` = ? LIMIT 1", array($redeemID));
					$x = ( $check_redeemID[0]['count'] < 1 ) ? 1 : 0;
				}

				$step_5 = process_qry("SELECT `email` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($memberID));
				// $step_6 = process_qry("CALL `redeem_campaign` (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($memberID, $data->terminalNum, $locID, $data->branchCode, $transactionID, $data->transactionType, $data->loyaltyID, $data->version, $transactionID, $step_5[0]['email'], $validate_branchcode[0]["data"][0]["name"], $sub_total, $redeemID, $campaign[0]['points']));
				$step_6 = process_qry("CALL `redeem_campaign` (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($memberID, $data->terminalNum, $locID, $data->branchCode, $transactionID, $data->transactionType, $data->loyaltyID, $campaign[0]['points'], $data->version, $step_5[0]['email'], $validate_branchcode[0]["data"][0]["name"], $sub_total, $redeemID, $data->barCode));

				if ($step_6[0]['result'] == "Success") {

					$v_status = ($step_6[0]['result'] == "Success") ? "true" : "false";
					$v_description = ($v_status == "false") ? $step_6[0]["description"] : NULL;
					$campaign_redeem_attempt = $this->campaign_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$transactionID, "status"=>$v_status, "description"=>$v_description, "officialReceipt"=>$transactionID, "loyaltyID"=>$data->loyaltyID));

					// $dateModified = DATE('Y-m-d H:i:s');
					// echo $dateModified.','. $memberID.','. $data->loyaltyID.','. $data->transactionID.','. $data->requestID;
					// $disapproved = process_qry("UPDATE `redeem_request_rewards` SET `status` = 'approved', dateModified = ? WHERE memberID = ? AND `rewardsID` = ? AND `transactionID` = ? AND requestID = ? ", array($dateModified, $memberID, $data->loyaltyID, $data->transactionID, $data->requestID));

					if ($v_status == "true") {
						// $step_13 =  $this->my_profile(array("memberID"=>$data->memberID));
						$logs->write_logs("Success - Redeem Campaign", $file_name, array(array("response"=>"Success", "data"=>array("memberID"=>$memberID, "terminalNum"=> $data->terminalNum,"locID"=> $locID,"branchCode"=> $data->branchCode,"transactionID"=> $transactionID,"transactionType"=> $data->transactionType,"loyaltyID"=> $data->loyaltyID,"points"=> $campaign[0]['points'],"version"=> $data->version,"email"=> $step_5[0]['email'],"locname"=> $validate_branchcode[0]["data"][0]["name"],"totalPoints"=> $sub_total,"redeemID"=> $redeemID))));

						return json_encode(array(array(array("response"=>"Success", "data" => array(array("memberID"=>$memberID, "terminalNum"=> $data->terminalNum,"locID"=> $locID,"branchCode"=> $data->branchCode,"transactionID"=> $transactionID,"transactionType"=> $data->transactionType,"loyaltyID"=> $data->loyaltyID,"points"=> $campaign[0]['points'],"version"=> $data->version,"email"=> $step_5[0]['email'],"locname"=> $validate_branchcode[0]["data"][0]["name"],"totalPoints"=> $sub_total,"redeemID"=> $redeemID,"description" => "You have successfully redeemed your reward. Please check email for instructions"))))));
					} else {
						$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>$v_description, "data"=>array($param))));
						return json_encode(array(array(array("response"=>"Error", "description"=>$v_description))));
					}
				} else {
					$campaign_redeem_attempt = $this->campaign_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$transactionID, "status"=>'false', "description"=>$step_6[0]['description'], "officialReceipt"=>$transactionID, "loyaltyID"=>$data->loyaltyID));
					$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>$step_6[0]['description'], "data"=>array($param))));
					return json_encode(array(array(array("response"=>"Error", "description"=>$step_6[0]['description']))));
				}
			} else {
				$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>"Unable to queue transaction request.", "data"=>array($param))));
				return json_encode(array(array(array("response"=>"Error", "description"=>"Unable to queue transaction request."))));
			}

			// } //end for
				if ($v_status == "true") {
							$step_13 =  $this->my_profile(array("memberID"=>$data->memberID));
							return json_encode(array(array(array("response"=>"Success", "data" =>array($param) ))));

				} else {
					$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>$v_description, "data"=>array($param))));
					return json_encode(array(array(array("response"=>"Error", "description"=>$v_description))));
				}
		}

		public function redeem_campaign($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			
			$data->transactionType =  "app";  //$transactionType[0]["data"][0]["transactionType"];
			
			if($data->terminalNum != 'web'){
				$validate_terminal_num = json_decode($this->validate_terminal_num(array("terminalNum"=>$data->deviceID, "branchCode"=>$data->branchCode)), true);

				if ($validate_terminal_num[0]["response"] == "Error") {
					return json_encode($validate_terminal_num);
				}
			}
		
			$validate_branchcode = json_decode($this->validate_branchcode(array("branchCode"=>$data->branchCode)), true);

			if ($validate_branchcode[0]["response"] == "Error") {
				return json_encode($validate_branchcode);
			}

			$locID = $validate_branchcode[0]["data"][0]["locID"];

			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID, "transactionType"=>$data->transactionType)), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			// $transactionID = $data->transactionID;

			if ($data->platform == "ios" || $data->platform == 'android' ){
				$data->locID = 'Moblie App';
				$data->locName = 'Moblie App';
			}elseif($data->platform == 'web'){
				$data->locID = 'Web';
				$data->locName = 'Web';
			}

			

			$memberID = $get_member_id[0]["data"][0]["memberID"];

			// if ($data->status == 'disapproved'){
			// 	$dateModified = DATE('Y-m-d H:i:s');
			// 	$disapproved = process_qry("UPDATE `redeem_request_rewards` SET `status` = ?, dateModified = ? WHERE memberID = ? AND `rewardsID` = ? AND `transactionID` = ? AND `requestID` = ? ", array($data->status, $dateModified, $memberID, $data->loyaltyID, $data->transactionID, $data->requestID));
			// 	return json_encode(array(array(array("response" => "Success" , "data" => array($param)))));
			// }


			$check_rewards = process_qry("SELECT * from `loyaltytable` WHERE loyaltyID = ? and `status`= 'active'", array($data->loyaltyID), $logs);
			$t = 0;
			while ($t < 1) {
				$step_1 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `redeem_request_rewards` LIMIT 1", array());
				$transactionID = substr("RNTranW" . randomizer(5) . DATE("H") . $step_1[0]['entry'] . DATE("s") . randomizer(6), 0, 32);
				$check_RNTranS = process_qry("SELECT count(*) AS `count` FROM `redeem_request_rewards` WHERE `transactionID` = ? LIMIT 1", array($transactionID), $logs);
				$t = ( $check_RNTranS[0]['count'] < 1 ) ? 1 : 0;
			}

			if ($this->check_transaction_exist(array("transactionID"=>$transactionID,"table"=>"redeem_request_rewards")) == true ){
				return json_encode(array(array("response"=>"Error", "description"=>"Receipt has already been used.")));
			}

			if (count($check_rewards) > 0){
				if ($check_rewards[0]['promoType'] == 'premium'){

				 	$result_notif =  $this->redeem_notification_rewards(array("terminalNum"=>$data->terminalNum, "branchCode" => $data->branchCode, "memberID"=> $data->memberID, "rewardsID" =>$data->loyaltyID, "version" => $data->version, "transactionType" => $data->transactionType,"platform"=>$data->platform,"barCode"=>$data->barCode,"locID"=> $data->locID, "locName"=>$data->locName));
				 	$arr = json_decode($result_notif, true);

				 	if ($arr['0'][0]['response'] == 'Success'){
				 		return json_encode(array(array(array("response"=>$arr['0'][0]['response'], "data"=>$arr['0'][0]['data']))));
				 	}else{
				 		return json_encode(array(array(array("response"=>"Error", "description"=>$arr['0'][0]['description']))));
				 	}

				}elseif ($check_rewards[0]['promoType'] != 'regular'){
					return json_encode(array(array(array("response"=>"Error", "description"=> "No promo type is available " ))));
				}
			}else{
				return json_encode(array(array(array("response"=>"Error", "description"=> "No rewards available " . $data->loyaltyID ))));
			}

			$step_3 = process_qry("CALL `points_redeem_attempt_insert`(?, ?, ?, ?, ?, ?, ?, ?, ?)", array($data->terminalNum, $locID, $data->branchCode, $data->memberID, $transactionID, $data->transactionType, $data->loyaltyID, $data->version, $transactionID));

			if ($step_3[0]['result'] == "Success") {
				// $total_earn = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `earntable` WHERE `memberID` = ? AND `status` = 'true'", array($memberID));
				// $total_redeem = process_qry("SELECT IF(SUM(`points`) IS NULL, 0, SUM(`points`)) AS `points` FROM `redeemtable` WHERE `memberID` = ? AND `status` = 'true'", array($memberID));
				// $sub_total = ((int)$total_earn[0]['points'] - (int)$total_redeem[0]['points']);
				$sub_total = $get_member_id[0]["data"][0]["totalPoints"];
				if ($sub_total < 0) { (int)$sub_total = 0; }

				$campaign = process_qry("SELECT * FROM `loyaltytable` WHERE `loyaltyID` = ? AND `status` = 'active' LIMIT 1", array($data->loyaltyID));

				if (count($campaign) > 0) {
					if ($campaign[0]['points'] > $sub_total) {
						$campaign_redeem_attempt = $this->campaign_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$transactionID, "status"=>'false', "description"=>'Insuficient points to redeem.', "officialReceipt"=>$transactionID, "loyaltyID"=>$data->loyaltyID));
						$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>"Insuficient points to redeem.", "data"=>array($param))));
						return json_encode(array(array(array("response"=>"Error", "description"=>"Insuficient points to redeem."))));
					}
				} else { 
					$campaign_redeem_attempt = $this->campaign_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$transactionID, "status"=>'false', "description"=>'Loyalty Campaign does not exist.', "officialReceipt"=>$transactionID, "loyaltyID"=>$data->loyaltyID));
					$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>"Loyalty Campaign does not exist.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"Loyalty Campaign does not exist.")));
				}

				$step_4 = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, MAX(`id`)) AS `entry` FROM `redeemtable` LIMIT 1", array());
				$x = 0;
				$redeemID = NULL;

				while ($x < 1) {
					$redeemID = substr("RDM" . randomizer(2) . DATE("H") . $step_4[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$check_redeemID = process_qry("SELECT count(*) AS `count` FROM `redeemtable` WHERE `redeemID` = ? LIMIT 1", array($redeemID));
					$x = ( $check_redeemID[0]['count'] < 1 ) ? 1 : 0;
				}

				// $step_5 = process_qry("SELECT `email` FROM `memberstable` WHERE `memberID` = ? LIMIT 1", array($memberID));

				// $step_6 = process_qry("CALL `redeem_campaign` (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($memberID, $data->terminalNum, $locID, $data->branchCode, $transactionID, $data->transactionType, $data->loyaltyID, $campaign[0]['points'], $data->version, $step_5[0]['email'], $validate_branchcode[0]["data"][0]["name"], $sub_total, $redeemID, $data->barCode));
				require_once(dirname(__FILE__) . '/redeem.class.php');
				$mclass = new redeem();
				// INSERT INTO `redeem_request_rewards` (`memberID`,`requestID`, `rewardsID`, `transactionID`, `email`, `fname`, `lname`, `rewards_name`, `promoType`,`points`, `description`, `status`, `platform`,`barCode` ,`dateAdded`, `dateModified`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?, ?, NOW(), NOW())

				$step_6 = $mclass->redeem_campaign(array("memberID"=>$memberID,"requestID"=>$redeemID, "rewardsID"=>$data->loyaltyID, "transactionID"=>$transactionID, "email"=>$get_member_id[0]["data"][0]["email"], "fname"=>$get_member_id[0]["data"][0]["fname"], "lname"=>$get_member_id[0]["data"][0]["lname"], "rewards_name"=>$campaign[0]["name"], "promoType"=>$campaign[0]["promoType"],"points"=>$campaign[0]["points"], "description"=>"redeem on regular", "status"=>"approve", "platform"=>$data->platform,"barCode"=>$data->barCode,"sub_total"=>$sub_total,"locID"=> $data->locID, "locName"=>$data->locName,"emailSent"=>"false"));

				if ($step_6 == "Success") {

					$v_status = ($step_6 == "Success") ? "true" : "false";
					$v_description = ($v_status == "false") ? "Failed" : NULL;
					$campaign_redeem_attempt = $this->campaign_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$transactionID, "status"=>$v_status, "description"=>$v_description, "officialReceipt"=>$transactionID, "loyaltyID"=>$data->loyaltyID));

					// $dateModified = DATE('Y-m-d H:i:s');
					// echo $dateModified.','. $memberID.','. $data->loyaltyID.','. $data->transactionID.','. $data->requestID;
					// $disapproved = process_qry("UPDATE `redeem_request_rewards` SET `status` = 'approved', dateModified = ? WHERE memberID = ? AND `rewardsID` = ? AND `transactionID` = ? AND requestID = ? ", array($dateModified, $memberID, $data->loyaltyID, $data->transactionID, $data->requestID));

					if ($v_status == "true") {
						// $step_13 =  $this->my_profile(array("memberID"=>$data->memberID));
						$logs->write_logs("Success - Redeem Campaign", $file_name, array(array("response"=>"Success", "data"=>array("memberID"=>$memberID, "terminalNum"=> $data->terminalNum,"locID"=> $locID,"branchCode"=> $data->branchCode,"transactionID"=> $transactionID,"transactionType"=> $data->transactionType,"loyaltyID"=> $data->loyaltyID,"points"=> $campaign[0]['points'],"version"=> $data->version,"email"=> $get_member_id[0]["data"][0]["email"],"locname"=> $validate_branchcode[0]["data"][0]["name"],"totalPoints"=> $sub_total,"redeemID"=> $redeemID, "locID"=> $data->locID, "locName"=>$data->locName))));
						$data->transactionDate = DATE('Y-m-d H:i:s');
						
						$this->auditLogs(array("userName"=>$data->userName,"memberID"=>$data->memberID,"table"=>"redeemtable","action"=>"redeem points","transactionID"=>$data->transactionID,"fromdata"=>$data->points,"todata"=>$step_5[0]['totalPoints'],"remarks"=>"redeem points","description"=>"redeem Points","designation"=>$data->designation,"transactionDate"=>$data->transactionDate));

						return json_encode(array(array(array("response"=>"Success", "data" => array(array("memberID"=>$memberID, "terminalNum"=> $data->terminalNum,"locID"=> $locID,"branchCode"=> $data->branchCode,"transactionID"=> $transactionID,"transactionType"=> $data->transactionType,"loyaltyID"=> $data->loyaltyID,"points"=> $campaign[0]['points'],"version"=> $data->version,"email"=> $get_member_id[0]["data"][0]["email"],"locname"=> $validate_branchcode[0]["data"][0]["name"],"totalPoints"=> $sub_total,"redeemID"=> $redeemID,"locID"=> $data->locID, "locName"=>$data->locName,"description" => "You have successfully redeemed your reward."))))));
					} else {
						$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>$v_description, "data"=>array($param))));
						return json_encode(array(array(array("response"=>"Error", "description"=>$v_description))));
					}
				} else {
					$campaign_redeem_attempt = $this->campaign_redeem_attempt(array("memberID"=>$data->memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$transactionID, "status"=>'false', "description"=>"Failed", "officialReceipt"=>$transactionID, "loyaltyID"=>$data->loyaltyID));
					$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>"Failed", "data"=>array($param))));
					return json_encode(array(array(array("response"=>"Error", "description"=>"Failed"))));
				}
			} else {
				$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>"Unable to queue transaction request.", "data"=>array($param))));
				return json_encode(array(array(array("response"=>"Error", "description"=>"Unable to queue transaction request."))));
			}

			// } //end for
				if ($v_status == "true") {
							$step_13 =  $this->my_profile(array("memberID"=>$data->memberID));
							return json_encode(array(array(array("response"=>"Success", "data" =>array($param) ))));

				} else {
					$logs->write_logs("Error - Redeem Campaign", $file_name, array(array("response"=>"Error", "description"=>$v_description, "data"=>array($param))));
					return json_encode(array(array(array("response"=>"Error", "description"=>$v_description))));
				}
		}

		public function check_transaction_exist($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			
			$isExist = process_qry("SELECT * from ". $data->table ." WHERE transactionID = ? ", array($data->transactionID),$logs);
			return (count($isExist) > 0 ? true : false);	

		}
		
		public function transactionType($param) {
			global $logs;
			global $error000;
			$data = (object) $param;
			$param['transactionType'] = NULL;
			
			$step_1 = process_qry("SELECT COUNT(*) AS `count` FROM `cardseries` WHERE `cardNum` = ? LIMIT 1", array($data->memberID));

			if ($step_1[0]['count'] < 1) {
				$step_2 = process_qry("SELECT COUNT(*) AS `count` FROM `memberstable` WHERE `qrApp` = ? LIMIT 1", array($data->memberID));

				if ($step_2[0]['count'] > 0) {
					$param['transactionType'] = "app";
				}
			} else {
				$param['transactionType'] = "card";
			}

			return json_encode(array(array("response"=>"Success", "data"=>array($param))));
		}

		public function validate_terminal_num($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$return = "False";
			$description = NULL;

			$step_1 = process_qry("SELECT `status`, `deploy`, COUNT(*) AS `count` FROM `devicecodetable` WHERE `deviceID` = ? LIMIT 1", array($data->terminalNum));
			if ($step_1[0]['count'] > 1) {
				$description = "Multiple assignment has been detected. Please contact System Administrator to resolve this problem.";
				$return = "Multiple";
			} elseif ($step_1[0]['count']== 1) {
				if ($step_1[0]['status'] == 'active') {
					if ($step_1[0]['deploy'] == 'true') {
						$logs->write_logs("Success - Validate Terminal Number", $file_name, array(array("response"=>"Success", "data"=>array($param))));
						return json_encode(array(array("response"=>"Success")));
					} else {
						$description = "This POS Terminal has not yet been deployed. Please contact System Administrator to resolve this problem.";
						$return = "Undeployed";
					}
				} else {
					$description = "This POS Terminal has not yet been activated. Please contact System Administrator to resolve this problem.";
					$return = "Inactive";
				}
			} else {
				$description = "Invalid Terminal Number.";
				$return = "False";
			}

			$logs->write_logs("Error - Validate Terminal Number", $file_name, array(array("response"=>"Error", "description"=>$description, "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "description"=>$description)));
		}

		public function validate_branchcode($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$step_1 = process_qry("SELECT `id`, `locID`, `name` FROM `loctable` WHERE `status` = 'active' AND `locFlag` = 'active' AND `branchCode` = ? LIMIT 1", array($data->branchCode));

			if (count($step_1) > 0) {
				$logs->write_logs("Success - Check Branch Code", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				return json_encode(array(array("response"=>"Success", "data"=>$step_1)));
			}

			$logs->write_logs("Error - Check Branch Code", $file_name, array(array("response"=>"Error", "description"=>"Invalid Branch Code.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "description"=>"Invalid Branch Code.")));
		}
		
		public function get_member_id($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			
			$qry = "SELECT `memberID`,`totalPoints`,`fname`,`lname`,`email`, COUNT(*) AS `count` FROM `memberstable` WHERE `memberID`= ?  AND `status` = 'active' LIMIT 1";

			// if ($data->transactionType == 'card') {
			// 	$qry = "SELECT `memberID`,`totalPoints`, COUNT(*) AS `count` FROM `memberstable` WHERE `qrCard`= ? AND `status` = 'active' LIMIT 1";
			// }

			$step_1 = process_qry($qry, array($data->memberID));

			if ($step_1[0]['count'] > 0) {
				$logs->write_logs("Success - Get Member ID", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				return json_encode(array(array("response"=>"Success", "data"=>$step_1)));
			}

			$logs->write_logs("Error - Get Member ID", $file_name, array(array("response"=>"Error", "description"=>"No user found.", "data"=>array($param))));
			return json_encode(array(array("response"=>"Error", "description"=>"No user found.")));
		}

		public function check_transaction_id($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$return = NULL;
			$data = (object) $param;
			$step_1 = process_qry("SELECT COUNT(*) AS `count`, `status` FROM `transactiontable` WHERE `transactionID` = ? AND `locID` = ? AND `branchCode` = ? LIMIT 1", array($data->transactionID, $data->locID, $data->branchCode));

			if ($step_1[0]['count'] > 0) {
				if ($step_1[0] == 'false') {
					$return = "Existing";
				} else {
					$logs->write_logs("Error - Check Transaction ID", $file_name, array(array("response"=>"Error", "description"=>"This receipt has already been used.", "data"=>array($param))));
					return json_encode(array(array("response"=>"Error", "description"=>"This receipt has already been used.")));
				}
			} else {
				$return = "Valid";
			}

			$logs->write_logs("Success - Check Transaction ID", $file_name, array(array("response"=>"Success", "data"=>array($param))));
			return json_encode(array(array("response"=>"Success", "data"=>array(array("return"=>$return)))));
		}

		public function earn_points_attempt($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$step_1 = process_qry("CALL `earn_points_attempt`(?, ?, ?, ?, ?)", array($data->memberID, $data->branchCode, $data->transactionID, $data->officialReceipt, $data->description));
			return;
		}

		public function cancel_earn($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			// $transactionType = json_decode($this->transactionType(array("memberID"=>$data->memberID)), true);

			// if ((!$transactionType[0]["data"][0]["transactionType"]) || (($transactionType[0]["data"][0]["transactionType"] != 'card') && ($transactionType[0]["data"][0]["transactionType"] != 'app'))) {
			// 	return json_encode(array(array("response"=>"Error", "description"=>"No user found.")));
			// }
			$transactionType = "app";
			$validate_branchcode = json_decode($this->validate_branchcode(array("branchCode"=>$data->branchCode)), true);

			if ($validate_branchcode[0]["response"] == "Error") {
				return json_encode($validate_branchcode);
			}

			$locID = $validate_branchcode[0]["data"][0]["locID"];
			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID, "transactionType"=>$transactionType[0]["data"][0]["transactionType"])), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];
			$check_transaction_for_cancellation = json_decode($this->check_transaction_for_cancellation(array("memberID"=>$memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "type"=>"earn")), true);

			if ($check_transaction_for_cancellation[0]["response"] == "Error") {
				return json_encode($check_transaction_for_cancellation);
			}

			$step_1 = process_qry("CALL cancel_earn(?, ?, ?, ?)", array($memberID, $locID, $data->branchCode, $data->transactionID));
			$param['procedure'] = "cancel_earn";

			if ($step_1[0]['result'] == "Success") {
				$logs->write_logs("Success - Cancel Earn Points", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				return json_encode(array(array("response"=>"Success")));
			} else {
				$logs->write_logs("Error - Cancel Earn Points", $file_name, array(array("response"=>"Error", "description"=>"Unable to complete cancellation process.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Unable to complete cancellation process.")));
			}
		}

		public function points_redeem_attempt($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$step_1 = process_qry("CALL `points_redeem_attempt_update`(?, ?, ?, ?, ?, ?, ?)", array($data->memberID, $data->locID, $data->branchCode, $data->transactionID, $data->status, $data->description, $data->officialReceipt));
			return;
		}

		public function campaign_redeem_attempt($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$step_1 = process_qry("CALL `campaign_redeem_attempt_update`(?, ?, ?, ?, ?, ?, ?)", array($data->memberID, $data->locID, $data->branchCode, $data->transactionID, $data->status, $data->description, $data->officialReceipt));
			return;
		}

		public function cancel_redeem($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			// $transactionType = json_decode($this->transactionType(array("memberID"=>$data->memberstable_aws)), true);

			// if ((!$transactionType[0]["data"][0]["transactionType"]) || (($transactionType[0]["data"][0]["transactionType"] != 'card') && ($transactionType[0]["data"][0]["transactionType"] != 'app'))) {
			// 	return json_encode(array(array("response"=>"Error", "description"=>"No user found.")));
			// }

			// $data->transactionType = $transactionType[0]["data"][0]["transactionType"];
			$transactionType = "app";
			$validate_terminal_num = json_decode($this->validate_terminal_num(array("terminalNum"=>$data->terminalNum)), true);

			if ($validate_terminal_num[0]["response"] == "Error") {
				return json_encode($validate_terminal_num);
			}

			$validate_branchcode = json_decode($this->validate_branchcode(array("branchCode"=>$data->branchCode)), true);

			if ($validate_branchcode[0]["response"] == "Error") {
				return json_encode($validate_branchcode);
			}

			$locID = $validate_branchcode[0]["data"][0]["locID"];
			$get_member_id = json_decode($this->get_member_id(array("memberID"=>$data->memberID, "transactionType"=>$transactionType[0]["data"][0]["transactionType"])), true);

			if ($get_member_id[0]["response"] == "Error") {
				return json_encode($get_member_id);
			}

			$memberID = $get_member_id[0]["data"][0]["memberID"];
			$check_transaction_for_cancellation = json_decode($this->check_transaction_for_cancellation(array("memberID"=>$memberID, "locID"=>$locID, "branchCode"=>$data->branchCode, "transactionID"=>$data->transactionID, "type"=>"redeem")), true);

			if ($check_transaction_for_cancellation[0]["response"] == "Error") {
				return json_encode($check_transaction_for_cancellation);
			}

			$step_1 = process_qry("CALL cancel_redeem(?, ?, ?, ?, ?, ?)", array($memberID, $locID, $data->branchCode, $data->transactionID, $data->officialReceipt, $data->terminalNum));
			$param['procedure'] = "cancel_redeem";

			if ($step_1[0]['result'] == "Success") {
				$logs->write_logs("Success - Cancel Redeem Points", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				return json_encode(array(array("response"=>"Success")));
			} else {
				$logs->write_logs("Error - Cancel Redeem Points", $file_name, array(array("response"=>"Error", "description"=>"Unable to complete cancellation process.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Unable to complete cancellation process.")));
			}
		}

		public function auditLogs($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			$xx = 0;
			$auditID = NULL;
			$arrData = array();
			$entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `wpm_audittable` LIMIT 1", array(), $logs);
				while ($xx < 1) {
					$auditID =  substr(DATE("Y") . str_pad($entry[0]['entry'], 12, "0", STR_PAD_LEFT), 0, 16);
					
					$step_3 = process_qry("SELECT count(*) AS `count` FROM `wpm_audittable` WHERE `auditID` = ? LIMIT 1", array($auditID), $logs);
					$xx = ( $step_3[0]['count'] < 1 ) ? 1 : 0;

				}
			$output = json_encode($data);

			$insert_audit = process_qry("INSERT wpm_audittable(`auditID`,`username`,`memberID`,`table`,`action`,`transactionID`,`fromdata`,`todata`,`remarks`,`description`,`data`,`role`,`dateAdded`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($auditID,$data->userName,$data->memberID,$data->table,$data->action,$data->transactionID,$data->fromdata,$data->todata,$data->remarks,$data->description,$output ,$data->designation,$data->transactionDate), $logs);

		}
			
		public function voucher_redeem_attempt($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$step_1 = process_qry("CALL `redeemvoucher_attempt_update`(?, ?, ?, ?, ?, ?)", array($data->memberID, $data->locID, $data->branchCode, $data->transactionID, $data->status, $data->description));
			return;
		}

		public function check_transaction_for_cancellation($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$qry = "SELECT COUNT(*) AS `count` FROM `earntable`WHERE `memberID` = ? AND `locID` =  ? AND `branchCode` =  ? AND `transactionID` = ?";

			if ($data->type == "redeem") {
				$qry = "SELECT COUNT(*) AS `count` FROM `redeemtable`WHERE `memberID` = ? AND `locID` =  ? AND `branchCode` =  ? AND `transactionID` = ?";
			}

			$step_1 = process_qry($qry, array($data->memberID, $data->locID, $data->branchCode, $data->transactionID));

			if ($step_1[0]['count'] > 0) {
				$logs->write_logs("Success - Check Transaction for ". ucwords($data->type) ." Cancellation", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				return json_encode(array(array("response"=>"Success")));
			} else {
				$logs->write_logs("Error - Check Transaction for ". ucwords($data->type) ." Cancellation", $file_name, array(array("response"=>"Error", "description"=>"Transaction does not exist.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Transaction does not exist.")));
			}
		}

	}

?>