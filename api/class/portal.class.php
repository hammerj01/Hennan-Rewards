<?php

	
	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class portal extends mailer {
		public static $file_name = 'portal.class.php';

		/********** Login **********/
		// public function login($param) {
		// 	require_once(dirname(__FILE__) . "/../misc/jwt_helper.php");
		// 	global $logs;
		// 	global $error000;
		// 	$file_name = self::$file_name;
		// 	$output = array();
		// 	$data = (object) $param;
		// 	$param['procedure'] = "login";

		// 	$check_account = process_qry("call `portal_login`(?, ?)", array($data->mobileNumber, $data->password), $logs);
		// 	if($check_account[0]['result'] != 'Failed'){
		// 		$token = array();
		// 			$token['accountID'] = $check_account[0]['result'];
		// 			$token['loginSession'] = $check_account[0]['loginSession']; 
		// 			$token['role'] = $check_account[0]['role'];  
		// 			$token['fullname'] = $check_account[0]['fullname'];
		// 			$token['profilePic'] = $check_account[0]['profilePic'];  
		// 			$token['branchCode'] = $check_account[0]['branchCode'];    
		// 			$newtoken = JWT::encode($token, JWT_SERUCITY_KEY);  
 
		// 			return json_encode(array("response"=>"Success", "token"=>$newtoken)); 
		// 	}else{
		// 		return json_encode(array("response"=>"Error", "description"=>"Invalid Username/Password."));
		// 	}

		// }

		public function login($param) {
			require_once(dirname(__FILE__) . "/../misc/jwt_helper.php");
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "login";

			$mobile = $this->validate_mobile_num($data->mobileNumber);
			$email = $this->validate_email_address($data->mobileNumber);
			$data->password = encrypt_decrypt('encrypt', $data->password); 
			if($mobile != 'Invalid'){
				$step_1 = process_qry("CALL portal_login(?, ?)",array($mobile, $data->password),$logs);
			}else if ($email == 1){
				$step_1 = process_qry("CALL portal_login_email(?, ?)",array($data->mobileNumber, $data->password),$logs);
			}else{
				$logs->write_logs("Error - Dashboard Login", $file_name, array(array("response"=>"Error", "description"=>"Invalid Username/Password.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid Username/Password.")));
			}

			if ($step_1 > 0) {

				if($step_1[0]['result'] == 'Failed'){
					return json_encode(array(array("response"=>"Error", "description"=>"Invalid Username/Password.")));
					die();
				}elseif ($step_1[0]['result'] == 'not activated'){
					return json_encode(array(array("response"=>"Error", "description"=>"Account is not yet activated. Please check your email.")));
					die();
				} else{
					// print_r($step_1[0]);
					$token = array();
					$token['accountID'] = $step_1[0]['result'];
					$token['loginSession'] = $step_1[0]['loginSession']; 
					$token['fname'] = $step_1[0]['fname'];  
					$token['lname'] = $step_1[0]['lname'];  
					$token['image'] = $step_1[0]['image'];  
					$token['email'] = $step_1[0]['email'];  
					$token['qrCard'] = $step_1[0]['qrCard'];  
					$newtoken = JWT::encode($token, JWT_SERUCITY_KEY);  

					return json_encode(array(array("response"=>"Success", "token"=>$newtoken )));
				}
				
			} else {
				$logs->write_logs("Error - Dashboard Login", $file_name, array(array("response"=>"Error", "description"=>"Invalid Username/Password.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid Username/Password.")));
			}
		}

		// === start json
		public function jsonlist($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "jsonlist";
			// echo $data->expiration;
			/* Check Account ID and Login Session */
			// echo $data->expiration . '  = ' . date('M d,Y', strtotime(substr($data->expiration , 4,11)));
			$sessionActive = $this->check_account_session($data->accountID, $data->my_session_id); // false by default

			if ($sessionActive == 1) {
				return json_encode(array("response"=>"Expired"));
			}

			/* Get Total Records */
			$countqry = $this->get_init_total_query($data->accountID, $data->table, $data->searchFilter, $data->selectedStatus, $data->selectedType, $data->emails, $data->qrCard, $data->expiration, $data->lname, $data->email, $data->brandName, $data->transactionDate, $data->transactionID);
			if($countqry['response'] == 'Success') {

				$rowCount = process_qry($countqry['query'], array());
				$totalrecords = $rowCount[0]['total'];

				/* How many pages will there be */
				$pages = ceil($totalrecords / $data->pageSize);

				// If ever sobra ang current page sa total page
				$currentPage = (int)$data->currentPage;
				if ($currentPage > $pages) {
					$currentPage = 1;
				}

				/* What page are we currently on? (OPTIONAL) since we have parameter value for currentPage*/
			    $page = $currentPage;

				/* Calculate the offset for the query */
	    		$offset = ($page - 1)  * $data->pageSize;

	    		// echo "\noffset: " . $offset;
	    		// echo "\npageSize: " .  $data->pageSize;


				/* Get Paged Query */
				$pageqry = $this->get_paged_query($data->accountID, $data->table, $data->searchFilter, $data->selectedStatus, $data->sortColumnName, $data->sortOrder, $data->selectedType, $data->emails, $data->qrCard, $data->expiration, $data->lname, $data->email, $data->brandName, $data->transactionDate, $data->transactionID);

				//kuwangan pa ug data

				if ($pageqry['response'] == 'Success') {
					$output = process_qry($pageqry['query'], array((int)$data->pageSize, (int)$offset));
					$pageInfo = array("totalPage"=>$pages, 
						"sortColumnName"=>$data->sortColumnName, "sortOrder"=>$data->sortOrder, "pageSize"=>(int)$data->pageSize, 
						"currentPage"=>$currentPage, "searchFilter"=>$data->searchFilter, "selectedStatus"=>$data->selectedStatus, 
						"totalRecord"=>$totalrecords, "selectedType"=>$data->selectedType);

					return json_encode(array("response"=>"Success", "data"=>$output, "pageinfo"=>$pageInfo));
				}
				else if ($pageqry['response'] == 'Error') {
					return json_encode(array("response"=>"Error", "description"=>'No table parameter found for server pagination'));
				}

			
			}
			else if ($countqry['response'] == 'Error') {
				return json_encode(array("response"=>"Error", "description"=>'No table parameter found for server pagination'));
			}
			
		}
		public function get_init_total_query($accountID, $table, $searchFilter, $selectedStatus, $selectedType, $emails, $qrCard, $expiration, $lname, $email, $brandName, $transactionDate, $transactionID){
			if ($table == 'memberstable') {
				
				
				$qry = "SELECT COUNT(*) as total from memberstable WHERE `status` = 'active' ";


				if ($emails != "") {
					$qry .= "AND `email` LIKE '%" . $emails. "%'";
				}
				if ($qrCard != "") {
					$qry .=  "AND `qrCard` LIKE '%" . $qrCard. "%'";
				}
				if ($lname != "") {
					$qry .=  "AND `lname` LIKE '%" . $lname. "%'";
				}
				if ($expiration != "") {
					$qry .=  "AND DATE_FORMAT(expiration, '%Y/%m/%d') = DATE_FORMAT('".date('Y-m-d', strtotime(substr($expiration , 4,11)))."','%Y/%m/%d') ";
				}

			}
			else if ($table == 'earntable') {
				
				
				$qry = "SELECT COUNT(*) as total FROM earntable e inner join memberstable m on e.`memberID` = m.`memberID` where `m`.`memberID` = '" . $accountID ."'";

				

				if ($email != "") {
					$qry .= "AND `email` LIKE '%" . $email. "%' ";
				}
				if ($brandName != "") {
					$qry .=  "AND `brandName` LIKE '%" . $brandName. "%' ";
				}
				if ($transactionID != "") {
					$qry .=  "AND `transactionID` LIKE '%" . $transactionID. "%' ";
				}
				if ($transactionDate != "") {
					$qry .=  "AND DATE_FORMAT(transactionDate, '%Y/%m/%d') = DATE_FORMAT('".date('Y-m-d', strtotime(substr($transactionDate , 4,11)))."','%Y/%m/%d') ";
				}

			}
			else if ($table == 'redeemtable') {
				
				
				$qry = "SELECT count(*) as `total` FROM redeem_request_rewards r inner join  loyaltytable l on r.`rewardsID` = l.`loyaltyID`  where r.`memberID` = '" . $accountID ."' AND r.`status` = 'approved'";

				

				if ($email != "") {
					$qry .= "AND `email` LIKE '%" . $email. "%' ";
				}
				if ($brandName != "") {
					$qry .=  "AND `brandName` LIKE '%" . $brandName. "%' ";
				}
				if ($transactionID != "") {
					$qry .=  "AND `transactionID` LIKE '%" . $transactionID. "%' ";
				}
				if ($transactionDate != "") {
					$qry .=  "AND DATE_FORMAT(transactionDate, '%Y/%m/%d') = DATE_FORMAT('".date('Y-m-d', strtotime(substr($transactionDate , 4,11)))."','%Y/%m/%d') ";
				}

			}
			else{
				return array("response"=>"Error", "description"=>'No table parameter found for server pagination');
			}

			return array("response"=>"Success", "query"=>$qry);
		}
		public function get_paged_query($accountID, $table, $searchFilter, $selectedStatus, $sortColumnName, $sortOrder, $selectedType, $emails, $qrCard, $expiration, $lname, $email, $brandName, $transactionDate, $transactionID){

			if ($table == 'memberstable') {

				$pageqry = "SELECT * FROM `memberstable` WHERE `status` = 'active' "; 

				if ($emails != "") {
					$pageqry .= "AND `email` LIKE '%" . $emails. "%'";
				}
				if ($qrCard != "") {
					$pageqry .=  "AND `qrCard` LIKE '%" . $qrCard. "%'";
				}
				
				if ($lname != "") {
					$pageqry .=  "AND `lname` LIKE '%" . $lname. "%'";
				}
				
				if ($expiration != "") {
					$pageqry .=  "AND DATE_FORMAT(expiration, '%Y/%m/%d') = DATE_FORMAT('".date('Y-m-d', strtotime(substr($expiration , 4,11)))."','%Y/%m/%d') ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}
			}
			else if ($table == 'earntable') {

				$pageqry = "SELECT e.earnID, e.memberID, concat(m.fname, ' ', m.`lname`) as name, m.`dateOfBirth`, m.`qrCard`, m.`expiration`, m.email, e.locID, e.locName, e.points, e.amount, e.transactionID, e.transactionDate FROM earntable e inner join memberstable m on e.`memberID` = m.`memberID` where m.memberID ='" . $accountID . "' "; 
				// $pageqry = "SELECT earnID, memberID, email, locID, locName, points, amount, brandName, transactionID, transactionDate FROM earntable where memberID == $accountID ORDER BY dateAdded "; 

				if ($email != "") {
					$pageqry .= "AND `m`.`email` LIKE '%" . $email. "%' ";
				}
				// if ($brandName != "") {
				// 	$pageqry .=  "AND `m`.`brandName` LIKE '%" . $brandName. "%' ";
				// }
				
				if ($transactionID != "") {
					$pageqry .=  "AND `m`.`transactionID` LIKE '%" . $transactionID. "%' ";
				}
				
				if ($transactionDate != "") {
					$pageqry .=  "AND DATE_FORMAT(transactionDate, '%Y/%m/%d') = DATE_FORMAT('".date('Y-m-d', strtotime(substr($transactionDate , 4,11)))."','%Y/%m/%d') ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY `e`.`'.$sortColumnName.'`' . $sortOrder . ' LIMIT ? OFFSET ?';	
					// echo $pageqry;
					
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}
			}
			else if ($table == 'redeemtable') {

				$pageqry = "SELECT r.*, l.`locName` FROM redeem_request_rewards r inner join  loyaltytable l on r.`rewardsID` = l.`loyaltyID`  where r.`memberID` ='" . $accountID . "'  AND r.`status` = 'approved'"; 
				// $pageqry = "SELECT earnID, memberID, email, locID, locName, points, amount, brandName, transactionID, transactionDate FROM earntable where memberID == $accountID ORDER BY dateAdded "; 

				if ($email != "") {
					$pageqry .= "AND email LIKE '%" . $email. "%' ";
				}
				// if ($brandName != "") {
				// 	$pageqry .=  "AND `m`.`brandName` LIKE '%" . $brandName. "%' ";
				// }
				
				if ($transactionID != "") {
					$pageqry .=  "AND transactionID LIKE '%" . $transactionID. "%' ";
				}
				
				if ($transactionDate != "") {
					$pageqry .=  "AND DATE_FORMAT(transactionDate, '%Y/%m/%d') = DATE_FORMAT('".date('Y-m-d', strtotime(substr($transactionDate , 4,11)))."','%Y/%m/%d') ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY '.$sortColumnName.' ' . $sortOrder . ' LIMIT ? OFFSET ?';	
					// echo $pageqry;
					
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}
			}
			else{
				return array("response"=>"Error", "description"=>'No table parameter found for server pagination');
			}

			return array("response"=>"Success", "query"=>$pageqry);

		}
		// === end json

		/********** View Record **********/
		public function view_record($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$param['procedure'] = "view_record";

			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				// if ($data->table == 'memberstable') {
				// 	$members_table = process_qry('SELECT * FROM memberstable WHERE memberID = ?', array($data->filter));
				// 	$redeem_table = process_qry('SELECT COUNT(*) AS count FROM redeemtable WHERE memberID = ?', array($data->filter));
				// 	$earn_table = process_qry('SELECT COUNT(*) AS visitCount,  IF(SUM(`amount`) IS NULL, 0, SUM(`amount`)) AS `amount` FROM `earntable` WHERE `memberID` = ?', array($data->filter));

				// 	$output = array();
				// 	array_push($output, array("memberstable"=>$members_table[0], "redeemtable"=>$redeem_table[0], "earntable"=>$earn_table[0]));
				// 	return json_encode(array("response"=>"Success", "data"=>$output[0]));
				// }
				if ($data->table == 'memberstable') {
					$row = process_qry('SELECT * from memberstable WHERE memberID = ?', array($data->filter), $logs);

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				
			}
			else{
				$logs->write_logs('ViewRecord - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Get Json **********/
		public function get_json($param) {
			global $logs;
			global $error000;
			$file_name = 'portal.class.php';
			$data = (object) $param;
			$param['procedure'] = "view_record";

			// $check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id), $logs);

			$check_account = process_qry("SELECT COUNT(*) AS count FROM `memberstable` WHERE `memberID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id), $logs);
			
			if ($check_account[0]['count'] > 0) {

				if ($data->table == 'profile') {
					$row = process_qry("SELECT * from memberstable where memberID = '" . $data->accountID . "' ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'profileImg') {
					$row = process_qry("SELECT image from memberstable where memberID = '" . $data->accountID . "' ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'vouchers') {
					$row = process_qry("SELECT * from earnvouchertable where memberID = '" . $data->accountID . "' GROUP BY voucherID", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'location') {
					$row = process_qry("SELECT * from loctable where status = 'active' AND brandID = ? ", array($data->filter), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'brand') {
					$row = process_qry("SELECT * from brandtable where status = 'active' ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'category') {
					$row = process_qry("SELECT * from categorytable where status = 'active' ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'brand_setting') {
					$row = process_qry("SELECT `brandID`, `name` FROM `brandtable` WHERE `status` = 'active' AND `brandID` NOT IN (SELECT `brandID` FROM `earnsettingstable`) ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'branchCode') {
					$row = process_qry("SELECT branchCode from loctable where status = 'active' ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'totalEarnedPoints') {
					$row = process_qry("SELECT accumulatedPoints as total FROM memberstable where `memberID` = '" . $data->accountID . "' ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'currentPoints') {
					$row = process_qry("SELECT totalPoints as total FROM memberstable where `memberID` = '" . $data->accountID . "' ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				// else if ($data->table == 'totalEarnedPoints') {
				// 	$row = process_qry("SELECT sum(`points`) as total FROM earntable e inner join memberstable m on e.`memberID` = m.`memberID` where `m`.`memberID` = '" . $data->accountID . "' ", array(), $logs);
				// 	return json_encode(array("response"=>"Success", "data"=>$row));
				// }
				else if ($data->table == 'totalRedeemPoints') {
					$row = process_qry("SELECT SUM(points) AS total FROM redeem_request_rewards WHERE `status` = 'approved' AND `memberID` = '" . $data->accountID . "'", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				// else if ($data->table == 'totalRedeemPoints') {
				// 	$row = process_qry("SELECT sum(`points`) as total FROM redeemtable e inner join memberstable m on e.`memberID` = m.`memberID` where `m`.`memberID` = '" . $data->accountID . "' ", array(), $logs);
				// 	return json_encode(array("response"=>"Success", "data"=>$row));
				// }
				else if ($data->table == 'request_cardtable') {
					$row = process_qry("SELECT * from request_card where memberID = '" . $data->accountID . "' ", array(), $logs);
					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}

			}
			else{
				$logs->write_logs('ViewRecord - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		public function check_account_session($accountID, $my_session_id) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$param = array('accountID'=>$accountID, 'loginSession'=>$my_session_id);
			$data = (object) $param;
			$param['procedure'] = "check_account_session";

			$qry = process_qry("SELECT COUNT(*) as count FROM `accounts` WHERE `accountID` = ? AND  `loginSession` = ? LIMIT 1", array($accountID, $my_session_id));

			return $qry[0]['count'];
		}

		/********** Update Profile **********/
		public function update_profile($param) {
			global $logs;
			global $error000;
			$file_name = 'portal.class.php';
			$data = (object) $param;
			$param['procedure'] = "portal_update_profile";

			// echo "update_profile";
			// var_dump($data);
			// die();
			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count, concat(fname, ' ', lname) as fullname,email FROM `memberstable` WHERE `memberID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id), $logs);
		
			if ($check_account[0]['count'] > 0) {
				if ($data->qrCard != NULL || $data->qrCard != ""){
					$valid_card = process_qry("SELECT * FROM `cardseries` WHERE `cardNum` = ? ", array($data->qrCard), $logs);

					if (count($valid_card) <= 0) {
						return json_encode(array("response"=>"Error", "description"=>"Invalid Card."));
					}
					if ($valid_card[0]['status'] == 'active') {
						# code...
						return json_encode(array("response"=>"Error", "description"=>"Card is already in use."));
					}
				}
				
				$row = process_qry('CALL portal_update_profile(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($data->fname, $data->lname, $data->email, $data->qrCard, $data->gender, $data->dateOfBirth, $data->mobileNum, $data->landlineNum, $data->address1, $data->image, $data->accountID), $logs);
				
				if ($row[0]['result'] == 'Success') {
					
						if ($valid_card[0]['status'] == 'inactive') {
							$dateReg = DATE('Y-m-d H:i:s');
							$logs->write_logs('Update Card - Customer Portal', $file_name, array(array('response'=>'Success', 'data'=>array("memberID"=>$data->accountID,"name" =>$check_account[0]['fullname'],"email" => $check_account[0]['email'] ,"qrCard" => $data->qrCard,"dateAdded"=>$dateReg))));

							$updateCard = process_qry("UPDATE `cardseries` SET `memberID` = ?, `status` = 'active',`dateModified` = ? WHERE `cardNum` = ?  ",array($data->accountID, $dateReg,$data->qrCard),$logs);
						}

					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateProfile - Customer Portal', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateProfile - Customer Portal', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update Password **********/
		public function update_password($param) {
			global $logs;
			global $error000;
			$file_name = 'portal.class.php';
			$data = (object) $param;
			$param['procedure'] = "portal_password";
			// var_dump($data);
			// Check Account ID
			$data->old_password = encrypt_decrypt('encrypt', $data->old_password); 
			$data->new_password = encrypt_decrypt('encrypt', $data->new_password); 
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `memberstable` WHERE `memberID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id), $logs);

			if ($check_account[0]['count'] > 0) {
				$pass_status = process_qry("SELECT IF((`password` IS NOT NULL AND `password` = ?), 'true', 'false') AS `password` FROM `memberstable` WHERE `memberID` = ? AND `loginSession` = ? LIMIT 1", array($data->old_password, $data->accountID, $data->my_session_id), $logs);
				
				if($pass_status[0]['password'] == 'true') {
					$row = process_qry('CALL portal_password(?, ?, ?, ?)', array($data->accountID, $data->my_session_id, $data->old_password, $data->new_password), $logs);
					
					if ($row[0]['result'] == 'Success') {
						return json_encode(array('response'=>'Success')); 
					}
					else if ($row[0]['result'] == 'No Changes'){
						return json_encode(array('response'=>'Success')); 			
					}
					else{
						$logs->write_logs('UpdatePassword - Customer Portal', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
						return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
					}
				}else{
					return json_encode(array('response'=>'Incorrect', 'description'=>'Incorrect Old Password' )); 	
				}
			}
			else{
				$logs->write_logs('UpdatePassword - Customer Portal', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		// === start change email
		public function change_email($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			$isAccountActive = process_qry("SELECT * FROM `accounts` WHERE `accountID` = ?", array($data->accountID), $logs);

			if(count($isAccountActive) < 1){
				return json_encode(array("response"=>"Error", "description"=>"Unauthorized user."));
			}

			$isExistEmail = process_qry("SELECT * FROM `memberstable` WHERE email = ? ", array($data->newEmail), $logs);
			if(count($isExistEmail) > 0){
				return json_encode(array("response"=>"Error", "description"=>"Email already exist."));
			}
			$check_member = process_qry("SELECT * FROM `memberstable` WHERE memberID = ? and email = ? and status = 'active'", array($data->memberID, $data->email),$logs ); 

			if( count($check_member) > 0 ){
				$logs->write_logs("Success - Change Email", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$dateModified = DATE('Y-m-d H:i:s');

				$update_email = process_qry("UPDATE `memberstable` set `email` = ?, `dateModified` = ? WHERE memberID = ? AND email = ? and status = 'active'", array($data->newEmail, $dateModified, $data->memberID,$data->email),$logs);
	
				$this->audittrails(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change email","transactionID"=>NULL,"fromdata"=>$data->email,"todata"=>$data->newEmail,"remarks"=>$data->remarks,"description"=>"Update","data"=> json_encode(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change email","transactionID"=>NULL,"fromdata"=>$data->email,"todata"=>$data->newEmail,"remarks"=>$data->remarks,"description"=>"Update","role"=>$isAccountActive[0]['role'])),"role"=>$isAccountActive[0]['role']));

				return json_encode(array("response"=>"Success", "data"=>array($param)));
			}else{
				return json_encode(array("response"=>"Error", "description"=>"No records found."));
			}

		} // end change email

		// === start change_dateOfBirth
		public function change_dateOfBirth($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			$isAccountActive = process_qry("SELECT * FROM `accounts` WHERE `accountID` = ?", array($data->accountID), $logs);

			if(count($isAccountActive) < 1){
				return json_encode(array("response"=>"Error", "description"=>"Unauthorized user."));
			}
			$check_member = process_qry("SELECT * FROM `memberstable` WHERE memberID = ? and status = 'active'", array($data->memberID),$logs ); 

			if( count($check_member) > 0 ){
				$logs->write_logs("Success - Change date of birth", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$dateModified = DATE('Y-m-d H:i:s');
				$update_email = process_qry("UPDATE `memberstable` set `dateOfBirth` = ?, `dateModified` = ?  WHERE memberID = ? and status = 'active'", array($data->newdateOfBirth, $dateModified,  $data->memberID),$logs);

				$this->audittrails(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change dateOfBirth","transactionID"=>NULL,"fromdata"=>$data->dateOfBirth,"todata"=>$data->newdateOfBirth,"remarks"=>$data->remarks,"description"=>"Update","data"=> json_encode(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change email","transactionID"=>NULL,"fromdata"=>$data->dateOfBirth,"todata"=>$data->newdateOfBirth,"remarks"=>$data->remarks,"description"=>"Update","role"=>$isAccountActive[0]['role'])),"role"=>$isAccountActive[0]['role']));

				return json_encode(array("response"=>"Success", "data"=>array($param)));
			}else{
				return json_encode(array("response"=>"Error", "description"=>"No records found."));
			}

		} // === end change_dateOfBirth

		public function change_card($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			$isAccountActive = process_qry("SELECT * FROM `accounts` WHERE `accountID` = ?", array($data->accountID), $logs);

			if(count($isAccountActive) < 1){
				return json_encode(array("response"=>"Error", "description"=>"Unauthorized user."));
			}
			$check_member = process_qry("SELECT * FROM `memberstable` WHERE memberID = ? and `qrCard` = ?  and `status` = 'active'", array($data->memberID, $data->qrCard),$logs ); 

			if( count($check_member) > 0 ){
				$logs->write_logs("Success - Change date of birth", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$dateModified = DATE('Y-m-d H:i:s');
				$update_email = process_qry("UPDATE `memberstable` set `qrCard` = ?, `dateModified` = ?  WHERE memberID = ? and qrCard = ? and status = 'active'", array($data->newqrCard, $dateModified,  $data->memberID, $data->qrCard),$logs);

				$this->audittrails(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change qrCard","transactionID"=>NULL,"fromdata"=>$data->qrCard,"todata"=>$data->newqrCard,"remarks"=>$data->remarks,"description"=>"Update","data"=> json_encode(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change email","transactionID"=>NULL,"fromdata"=>$data->qrCard,"todata"=>$data->newqrCard,"remarks"=>$data->remarks,"description"=>"Update","role"=>$isAccountActive[0]['role'])),"role"=>$isAccountActive[0]['role']));

				return json_encode(array("response"=>"Success", "data"=>array($param)));
			}else{
				return json_encode(array("response"=>"Error", "description"=>"No records found."));
			}
		}

		

		// === setIDs
		public function setIDs(){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();

			$mIDs = process_qry("SELECT l.locid, l.name, l.branchcode, d.deviceid, d.terminalnum from loctable l inner join devicecodetable d on l.locid = d.locid where d.deviceid is not null and l.status ='active' group by l.locid order by l.name asc",array(),$logs);

			if (count($mIDs) > 0 ){
				array_push($output, array("locCategory"=>$mIDs));
			}

			return json_encode(array(array("response"=>"Success", "data"=>$output)));


		}
		// === end setIDs

		function validate_mobile_num($mobileNum) {
			$mobileNum = str_replace(' ', '', $mobileNum);
			$mobileNum = str_replace('+', '', $mobileNum);
			$mobileNum = str_replace('-', '', $mobileNum);
			if (!is_numeric($mobileNum)) {
				return "Invalid";
			}

			if (strlen($mobileNum) < 11) {
				return "Invalid";
			} else {
				$trim = strlen($mobileNum) - 10;
				return $mobileNum = "+63" . substr($mobileNum, $trim);
			}
		}

		function validate_email_address($email){
			return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email);
		}

	} // ==== end wpm	


?>