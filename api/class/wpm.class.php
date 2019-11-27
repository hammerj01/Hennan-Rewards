<?php

	
	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class wpm extends mailer {
		public static $file_name = 'wpm.class.php';

		/********** Login **********/
		public function login($param) {
			require_once(dirname(__FILE__) . "/../misc/jwt_helper.php");
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "login";

			$check_account = process_qry("call `wpm_login`(?, ?)", array($data->username, $data->password), $logs);
			if($check_account[0]['result'] != 'Failed'){
				$token = array();
					$token['accountID'] = $check_account[0]['result'];
					$token['loginSession'] = $check_account[0]['loginSession']; 
					$token['role'] = $check_account[0]['role'];  
					$token['fullname'] = $check_account[0]['fullname'];
					$token['profilePic'] = $check_account[0]['profilePic'];  
					$token['branchCode'] = $check_account[0]['branchCode'];  
					$token['wpmTabs'] = $check_account[0]['wpmTabs'];  
					$token['username'] = $check_account[0]['username'];
					$newtoken = JWT::encode($token, JWT_SERUCITY_KEY);  
 
					return json_encode(array("response"=>"Success", "token"=>$newtoken)); 
			}else{
				return json_encode(array("response"=>"Error", "description"=>"Invalid Username/Password."));
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
			// echo $data->fname;
			/* Check Account ID and Login Session */
			// echo $data->fname . '  = ' . date('M d,Y', strtotime(substr($data->fname , 4,11)));
			$sessionActive = $this->check_account_session($data->accountID, $data->my_session_id); // false by default

			if ($sessionActive == 0) {
				return json_encode(array("response"=>"Expired"));
			}

			/* Get Total Records */
			$countqry = $this->get_init_total_query($data->table, $data->searchFilter, $data->selectedStatus, $data->selectedType, $data->emails, $data->qrCard, $data->fname, $data->lname);
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
				$pageqry = $this->get_paged_query($data->table, $data->searchFilter, $data->selectedStatus, $data->sortColumnName, $data->sortOrder, $data->selectedType, $data->emails, $data->qrCard, $data->fname, $data->lname);

				//kuwangan pa ug data

				if ($pageqry['response'] == 'Success') {
					$output = process_qry($pageqry['query'], array((int)$data->pageSize, (int)$offset));
					$pageInfo = array("totalPage"=>$pages, 
						"sortColumnName"=>$data->sortColumnName, "sortOrder"=>$data->sortOrder, "pageSize"=>(int)$data->pageSize, 
						"currentPage"=>$currentPage, "searchFilter"=>$data->searchFilter, "selectedStatus"=>$data->selectedStatus, 
						"totalRecord"=>$totalrecords, "selectedType"=>$data->selectedType, "emails"=>$data->emails, "lname"=>$data->lname,
						"fname"=>$data->fname, "qrCard"=>$data->qrCard);

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
		public function get_init_total_query($table, $searchFilter, $selectedStatus, $selectedType, $emails, $qrCard, $fname, $lname){
			if ($table == 'memberstable') {
				
				
				$qry = "SELECT COUNT(*) as total from memberstable  ";


				if ($emails != "") {
					$qry .= "WHERE `email` LIKE '%" . $emails. "%'";
				}
				if ($qrCard != "") {
					$qry .=  "WHERE `qrCard` LIKE '%" . $qrCard. "%'";
				}
				if ($lname != "") {
					$qry .=  "WHERE `lname` LIKE '%" . $lname. "%'";
				}
				if ($fname != "") {
					$qry .=  "WHERE `fname` LIKE '%" .$fname. "%'";
				}

			}
			else if ($table == 'wpm_audittable') {

				// $qry = "SELECT COUNT(*) as total from wpm_audittable ";
				$qry = "SELECT COUNT(*) as total FROM `wpm_audittable` w inner join memberstable m on w.`memberID` = m.`memberID` ";

			}
			else if ($table == 'redeem_request_voucher') {

				$qry = "SELECT COUNT(*) as total from redeem_request_voucher WHERE `status` = 'pending' ";

			}
			else if ($table == 'redeem_request_rewards') {

				$qry = "SELECT count(*) as total from redeem_request_rewards r inner join memberstable m on r.memberID = m.memberID WHERE  r.`status` = 'pending' or r.`status` = 'acknowledge' or r.`status` = 'acknowledged' and m.status='active'  ";
			}
			else{
				return array("response"=>"Error", "description"=>'No table parameter found for server pagination');
			}
			
			return array("response"=>"Success", "query"=>$qry);
		}
		public function get_paged_query($table, $searchFilter, $selectedStatus, $sortColumnName, $sortOrder, $selectedType, $emails, $qrCard, $fname, $lname){

			
			if ($table == 'memberstable') {

				$pageqry = "SELECT * FROM `memberstable`  "; 

				if ($emails != "") {
					$pageqry .= "WHERE `email` LIKE '%" . $emails. "%'";
				}
				if ($qrCard != "") {
					$pageqry .=  "WHERE `qrCard` LIKE '%" . $qrCard. "%'";
				}
				
				if ($lname != "") {
					$pageqry .=  "WHERE `lname` LIKE '%" . $lname. "%'";
				}
				
				if ($fname != "") {
					$pageqry .=  "WHERE `fname` LIKE '%" . $fname. "%'";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY `'.$sortColumnName.'` ' . $sortOrder . ' LIMIT ? OFFSET ?';
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}
			}
			else if ($table == 'wpm_audittable') {
				
				$pageqry = "SELECT w.`username`,m.`qrCard`,w.`action`,w.`transactionID`,w.`fromdata`,w.`todata`,w.`remarks`,w.`dateAdded` FROM `wpm_audittable` w inner join memberstable m on w.`memberID` = m.`memberID` ";

				if ($sortColumnName != '') {
					if ($sortColumnName == 'qrCard'){
						// echo "string dd";
						$pageqry .= 'ORDER BY m.`'.$sortColumnName.'` ' . $sortOrder . ' LIMIT ? OFFSET ?';
					}else{
						// echo "string";
						$pageqry .= 'ORDER BY w.`'.$sortColumnName.'` ' . $sortOrder . ' LIMIT ? OFFSET ?';
					}	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}
			}
			else if ($table == 'redeem_request_rewards') {

				// $pageqry = "SELECT * from redeem_request_rewards r inner join memberstable m on r.memberID = m.memberID WHERE  r.`status` = 'pending' or r.`status` = 'acknowledge' ";
					$pageqry = "SELECT r.`transactionID`,r.`rewardsID`,r.`requestID`,m.`memberID`,m.`qrApp`,r.`email`,m.`fname`,m.`lname`,r.`rewards_name`,r.`promoType`,r.`points`,m.`totalPoints`,r.`status`,r.`description`,r.`platform`,r.`rewardsID`,r.`dateAdded`, r.`locID`, r.`locName`, r.`platform` from redeem_request_rewards r inner join memberstable m on r.`memberID` = m.`memberID` WHERE m.`status`='active' ";
				
				if ($searchFilter != ''){
					$pageqry .= "AND r.`locID` = '".$searchFilter."' ";
				}
				if ($sortColumnName != '') {
					$pageqry .= 'AND r.`status` = "pending" OR r.`status` = "acknowledge" OR r.`status` = "acknowledged" ORDER BY LOWER(r.`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}

				else{				
					$pageqry .= 'AND r.`status` = "pending" OR r.`status` = "acknowledge" OR r.`status` = "acknowledged" LIMIT ? OFFSET ?';
				}
			}
			else if ($table == 'redeem_request_voucher') {

				$pageqry = "SELECT * FROM `redeem_request_voucher` WHERE `status` = 'pending' ";

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
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


		// ==== loctable

		// public function location($param){
		// 	global $logs;
		// 	global $error000;
		// 	$file_name = self::$file_name;
		// 	$data = (object) $param;
		// 	$param['procedure'] = "view_record";
		// 	$output = array();

		// 	$qry = "SELECT *, (SELECT `name` FROM `loccategorytable` WHERE `locCategoryID` = `loctable`.`locCategoryID` AND `status` = 'active') AS `locCategoryName` FROM `loctable` WHERE `status` = 'active'";
		// 	return json_encode(array(array("response"=>"Success", "data"=>$output)), JSON_UNESCAPED_SLASHES);

		// }

		// ==== end loctable

		/********** View Record **********/
		public function view_record($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;
			$param['procedure'] = "view_record";

			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				if ($data->table == 'memberstable') {
					$members_table = process_qry('SELECT * FROM memberstable WHERE memberID = ?', array($data->filter));
					$redeem_table = process_qry('SELECT SUM(points) AS count FROM redeem_request_rewards WHERE status = ? AND memberID = ?', array('approved', $data->filter));
					$earn_table = process_qry('SELECT COUNT(*) AS visitCount,  IF(SUM(`amount`) IS NULL, 0, SUM(`amount`)) AS `amount` FROM `earntable` WHERE `memberID` = ?', array($data->filter));

					$output = array();
					array_push($output, array("memberstable"=>$members_table[0], "redeemtable"=>$redeem_table[0], "earntable"=>$earn_table[0]));
					return json_encode(array("response"=>"Success", "data"=>$output[0]));
				}
				else if ($data->table == 'lyl_bonus') {
					$row = process_qry("SELECT mem.qrcard AS 'barcode', earn.transactiontype AS 'type', earn.memberid, earn.transactionid AS 'transactionID',
					 IFNULL(CONCAT(mem.fname, ' ', mem.lname),earn.email) AS 'name', earn.locname AS 'branch', earn.dateAdded AS 'date', earn.amount AS 'amount', earn.points AS 'points'
					      FROM
					      (
					        SELECT * FROM earntable WHERE memberid = ?
					       ) 
					      AS earn
					      INNER JOIN
					      (
					      SELECT * FROM memberstable WHERE memberid = ?
					      ) 
					      AS mem ON earn.memberid = mem.memberid ORDER BY earn.dateadded ASC", array($data->filter,$data->filter), $logs);

					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'points_redemption') {
					
					$row = process_qry("SELECT * FROM redeem_request_rewards WHERE status = 'approved' AND memberID = ?", array($data->filter), $logs);

					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'voucher_redemption') {
					$row = process_qry("SELECT redeem.memberid, mem.qrcard, redeem.email, redeem.locname, redeem.name, redeem.dateadded, redeem.transactionid
				      FROM
				      (
				        SELECT memberid, email, locname, name, transactionid, dateadded from redeemvouchertable 
				        where memberid = ?
				       ) 
				      AS redeem
				      INNER JOIN
				      (
				      select qrcard, memberid, image from memberstable where memberid = ?
				      ) 
				      AS mem ON redeem.memberid = mem.memberid order by mem.qrcard, redeem.dateadded", array($data->filter,$data->filter), $logs);

					return json_encode(array("response"=>"Success", "data"=>$row));
				}else if ($data->table == 'earnsettingstable') {
					$row = process_qry("SELECT settingID, amount, points FROM  earnsettingstable WHERE levelID = ? ", array($data->filter),$logs);
					
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'loctable'){
					$row = process_qry("SELECT *, (SELECT `name` FROM `loccategorytable` WHERE `locCategoryID` = `loctable`.`locCategoryID` AND `status` = 'active') AS `locCategoryName` FROM `loctable` WHERE `status` = 'active'",array(),$logs);
					return json_encode(array("response"=>"Success", "data"=>$row));
				}

				if ($data->table == 'vouchers_lists') {
					$voucherList = process_qry("SELECT id, voucherid, name, status, 'Available' as trans FROM earnvouchertable WHERE memberid = ?  union all SELECT id,voucherid, name, status, 'Redeemed' as trans FROM redeemvouchertable WHERE  memberid = ? ", array($data->filter,$data->filter), $logs);
					// $output = array();
					// array_push($output, array("vouchers"=>$voucherList));
				
					return json_encode(array("response"=>"Success", "data"=>$voucherList));
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


		// === change password thru email
		public function change_password($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			$isAccountActive = process_qry("SELECT * FROM `accounts` WHERE `accountID` = ?", array($data->accountID), $logs);

			if(count($isAccountActive) < 1){
				return json_encode(array("response"=>"Error", "description"=>"Unauthorized user."));
			}
			$check_member = process_qry("SELECT * FROM `memberstable` WHERE memberID = ? and email = ? and status = 'active'", array($data->memberID, $data->email),$logs ); 

			if( count($check_member) > 0 ){
				$logs->write_logs("Success - Change password", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$dateModified = DATE('Y-m-d H:i:s');
				$password = encrypt_decrypt("encrypt",str_pad(mt_rand(1,999999),6,'0',STR_PAD_LEFT));
				$update_email = process_qry("UPDATE `memberstable` set `password` = ?, `dateModified` = ?  WHERE memberID = ? and email = ? and status = 'active'", array($password, $dateModified,  $data->memberID, $data->email),$logs);

				$this->audittrails(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change dateOfBirth","transactionID"=>NULL,"fromdata"=>$check_member[0]['password'],"todata"=>$password,"remarks"=>"change password","description"=>"Update","data"=> json_encode(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change email","transactionID"=>NULL,"fromdata"=>$check_member[0]['password'],"todata"=>$password,"remarks"=>"change password","description"=>"Update","role"=>$isAccountActive[0]['role'])),"role"=>$isAccountActive[0]['role']));

				// send email
				$this->emailer(array("recipient_name" => $check_member[0]['fname'], "email" => $data->email, "password" => $password, "subject" => "Resend Password", "activation" =>NULL, "type" => "change_password", "emailFromFB" => NULL));
				return json_encode(array("response"=>"Success", "data"=>array($param)));
			}else{
				return json_encode(array("response"=>"Error", "description"=>"No records found."));
			}

		}// === end password

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

				$mage = process_qry("selecT TIMESTAMPDIFF(YEAR, ?, CURDATE()) as `age`",array($data->newdateOfBirth), $logs);
			
				if ($mage[0]['age'] < 18){
					return json_encode(array(array("response" => "Error", "description" => "Age should be above 18 years old.")));
				}

				$logs->write_logs("Success - Change date of birth", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$dateModified = DATE('Y-m-d H:i:s');
				$update_email = process_qry("UPDATE `memberstable` set `dateOfBirth` = ?, `dateModified` = ?  WHERE memberID = ? and status = 'active'", array($data->newdateOfBirth, $dateModified,  $data->memberID),$logs);

				$this->audittrails(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change date of birth","transactionID"=>NULL,"fromdata"=>$data->dateOfBirth,"todata"=>$data->newdateOfBirth,"remarks"=>$data->remarks,"description"=>"Update","data"=> json_encode(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change date of birth","transactionID"=>NULL,"fromdata"=>$data->dateOfBirth,"todata"=>$data->newdateOfBirth,"remarks"=>$data->remarks,"description"=>"Update","role"=>$isAccountActive[0]['role'])),"role"=>$isAccountActive[0]['role']));

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
			$valid_card = process_qry("SELECT * FROM `cardseries` WHERE `cardNum` = ?", array($data->newqrCard), $logs);

			if (count($valid_card) > 0) {
				$card_active = process_qry("SELECT * FROM `cardseries` WHERE `cardNum` = ? and `memberID` is not NULL ", array($data->newqrCard), $logs);
				if (count($card_active) > 0 ){
					return json_encode(array("response"=>"Error", "description"=>"Change Card Failed! Card is active."));
				}
			}else{
				return json_encode(array("response"=>"Error", "description"=>"Change Card Failed! Invalid Card."));
			}


			if( count($check_member) > 0 ){
				$logs->write_logs("Success - Change date of birth", $file_name, array(array("response"=>"Success", "data"=>array($param))));
				$dateModified = DATE('Y-m-d H:i:s');
				$update_email = process_qry("UPDATE `memberstable` set `qrCard` = ?, `dateModified` = ?  WHERE memberID = ? and qrCard = ? and status = 'active'", array($data->newqrCard, $dateModified,  $data->memberID, $data->qrCard),$logs);
				$update_prevCard = process_qry("UPDATE `cardseries` set `memberID` = NULL, `status`= 'inactive', `dateCardPurchased` = null, `expiration` = NULL, `dateModified` = ? WHERE memberID = ? and cardNum = ?",array($dateModified, $data->memberID,$data->qrCard),$logs);
				$expiration =  date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s", strtotime($dateModified)) . " +1 year"));
				$UPDATE_newCard =  process_qry("UPDATE `cardseries` set `memberID` = ?, `status`= 'active', `dateCardPurchased` = ?, `expiration` = ?, `dateModified` = ? WHERE `cardNum` = ? and `status` = 'inactive' and `memberID` is null",array($data->memberID,$dateModified,$expiration,$dateModified,$data->newqrCard ),$logs);

				$this->audittrails(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change card number","transactionID"=>NULL,"fromdata"=>$data->qrCard,"todata"=>$data->newqrCard,"remarks"=>$data->remarks,"description"=>"Update","data"=> json_encode(array("username"=>$isAccountActive[0]['username'],"memberID"=>$data->memberID,"table"=>"memberstable","action"=>"change card number","transactionID"=>NULL,"fromdata"=>$data->qrCard,"todata"=>$data->newqrCard,"remarks"=>$data->remarks,"description"=>"Update","role"=>$isAccountActive[0]['role'])),"role"=>$isAccountActive[0]['role']));

				return json_encode(array("response"=>"Success", "data"=>array($param)));
			}else{
				return json_encode(array("response"=>"Error", "description"=>"No records found."));
			}
		}

		// === starts change_vouchers
		public function change_vouchers($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$xx = 0;
			$auditID = NULL;

			$isAccountActive = process_qry("SELECT * FROM `accounts` WHERE `accountID` = ?", array($data->accountID), $logs);
			$newdate = DATE('Y-m-d H:i:s');

			if(count($isAccountActive) < 1){
				return json_encode(array("response"=>"Error", "description"=>"Unauthorized user."));
			}
			$check_member = process_qry("SELECT * FROM `memberstable` WHERE memberID = ? and `status` = 'active'", array($data->memberID),$logs );

			if( count($check_member) > 0 ){
				require_once(dirname(__FILE__). '/reactivate_deactivate.class.php');
				$class_redeVoucher = new reactivate_deactivate();

				if($data->trans == "Available"){
					$qry_1 = process_qry("SELECT * from earnvouchertable WHERE `memberID`= ? and `id` = ? and `voucherid` = ? ",array($data->memberID,$data->id,$data->voucherID),$logs);
					
					if(count($qry_1) > 0){

						$step_r = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, (MAX(`id`) + 1)) AS `entry` FROM `redeemvouchertable`", array(), $logs);
						$xx = 0;
						$redeem_ID = NULL;

						while ($xx < 1) {
							$redeem_ID = substr("RDM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
							$redeem_ID_1 = process_qry("SELECT count(*) AS `count` FROM `redeemvouchertable` WHERE `redeemVID` = ? LIMIT 1", array($redeem_ID), $logs);
							$xx = ( $redeem_ID_1[0]['count'] < 1 ) ? 1 : 0;
						}
						$trans_ID = substr("tWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
						 // $redeems = process_qry("call `wpm_change_voucher_redeem`(?,?,?,?,?,?)",array($redeem_ID,$trans_ID,$data->memberID,$qry_1[0]['email'], $data->voucherID,$qry_1[0]['id']),$logs);
						 
						 $reactivate = json_decode($class_redeVoucher->get_reactivate_deactivate(array("redeem_ID" => $redeem_ID, "trans_ID"=>$trans_ID,"memberID"=>$data->memberID, "email"=> $qry_1[0]['email'], "voucherID"=>$data->voucherID,"id"=>$qry_1[0]['id'],"actionOfVoucher"=>"deactivate")),true);

						 if ($reactivate[0]['response'] == 'Success'){
						 	$logs->write_logs("Success - Change Insert RedeemVoucher", $file_name, array(array("response"=>"Success", "data"=>array("redeem_ID"=>$redeem_ID,"trans_ID"=>$trans_ID,"earnDate"=>$newdate,"dateAdded"=>$newdate,"dateModified"=>$newdate,"memberID"=>$data->memberID,"email"=>$qry_1[0]['email'], "voucherID"=>$data->voucherID, "ID"=>$qry_1[0]['id']))));
							return json_encode(array("response"=>"Success")); 	
						 }else{
						 	return json_encode(array("response"=>"Error", "description"=>"Change Voucher Failed to redeem voucher!."));
						 }
						
					}

				}else{ //reactivate
					$step_r = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, (MAX(`id`) + 1)) AS `entry` FROM `earnvouchertable`", array(), $logs);
					$xx = 0;
					$earn_ID = NULL;
					$qry_2 = process_qry("SELECT * from redeemvouchertable WHERE `memberID`= ? and `id` = ? and `voucherid` = ? ",array($data->memberID,$data->id,$data->voucherID),$logs);

					while ($xx < 1) {
						$earn_ID = substr("VCHWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
						$earn_ID_1 = process_qry("SELECT count(*) AS `count` FROM `earnvouchertable` WHERE `voucherID` = ? LIMIT 1", array($earn_ID), $logs);
						$xx = ( $earn_ID_1[0]['count'] < 1 ) ? 1 : 0;
					}
					$trans_ID = substr("TEWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					// $re_earnVoucher = process_qry("call wpm_change_voucher_earn(?,?,?,?)",array($qry_2[0]['redeemVID'],$data->memberID,$qry_2[0]['email'], $qry_2[0]['voucherID']), $logs);
				
					$re_earnVoucher = json_decode($class_redeVoucher->get_reactivate_deactivate(array("memberID"=>$data->memberID, "email"=> $qry_2[0]['email'],"redeemVID"=>$qry_2[0]['redeemVID'],"voucherID"=>$qry_2[0]['voucherID'],"actionOfVoucher"=>"reactivate")),true);
					if ($re_earnVoucher[0]['response'] == 'Success'){
						$logs->write_logs("Success - Change Insert earnvouchertable", $file_name, array(array("response"=>"Success", "data"=>array("earn_ID"=>$earn_ID,"trans_ID"=>$trans_ID,"dateAdded"=>$newdate,"dateModified"=>$newdate,"memberID"=>$data->memberID,"email"=>$qry_2[0]['email'], "voucherID"=>$data->voucherID))));
						return json_encode(array("response"=>"Success")); 	
					}else{
						return json_encode(array("response"=>"Error", "description"=>"Change Voucher Failed to earn voucher!. "));
					}
				}
				
			}

		}// === end change_vouchers
		
		// === start insert audit trails
		public function audittrails($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;
			$xx = 0;
			$auditID = NULL;

			$entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `wpm_audittable` LIMIT 1", array(), $logs);
				while ($xx < 1) {
					$auditID =  substr(DATE("Y") . str_pad($entry[0]['entry'], 12, "0", STR_PAD_LEFT), 0, 16);
					
					$step_3 = process_qry("SELECT count(*) AS `count` FROM `wpm_audittable` WHERE `auditID` = ? LIMIT 1", array($auditID), $logs);
					$xx = ( $step_3[0]['count'] < 1 ) ? 1 : 0;

				}
				$step_r = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, (MAX(`id`) + 1)) AS `entry` FROM `wpm_audittable`", array(), $logs);
				$xx = 0;
				$trans_ID = NULL;
				while ($xx < 1) {
					$trans_ID = substr("RDMWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
					$trans_ID_1 = process_qry("SELECT count(*) AS `count` FROM `wpm_audittable` WHERE `transactionID` = ? LIMIT 1", array($trans_ID), $logs);
					$xx = ( $trans_ID_1[0]['count'] < 1 ) ? 1 : 0;
				}

				$transactionID = substr("TRWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);

			$dateAdded = DATE('Y-m-d H:i:s');
			
			$insert_audit = process_qry("INSERT wpm_audittable(`auditID`,`username`,`memberID`,`table`,`action`,`transactionID`,`fromdata`,`todata`,`remarks`,`description`,`data`,`role`,`dateAdded`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($auditID,$data->username,$data->memberID,$data->table,$data->action,$transactionID,$data->fromdata,$data->todata,$data->remarks,$data->description,$data->data,$data->role,$dateAdded), $logs);
		} // === end audit trails

		// === redeem_campaign
		public function redeem_campaign_wpm($param){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();
			$data = (object) $param;

			$isAccountActive = process_qry("SELECT * FROM `accounts` WHERE `accountID` = ?", array($data->accountID), $logs);
			$newdate = DATE('Y-m-d H:i:s');

			if(count($isAccountActive) < 1){
				return json_encode(array("response"=>"Error", "description"=>"Unauthorized user."));
			}
			$check_member = process_qry("SELECT * FROM `memberstable` WHERE memberID = ? and `status` = 'active'", array($data->memberID),$logs );

			if ($data->status == 'disapprove'){
				$dateModified = DATE('Y-m-d H:i:s');
				$disapproved = process_qry("UPDATE `redeem_request_rewards` SET `status` = ?,`emailSentDisapprove`='false', dateModified = ? WHERE memberID = ? AND `rewardsID` = ? AND `transactionID` = ? AND `requestID` = ? ", array($data->status, $newdate, $data->memberID, $data->rewardsID, $data->transactionID, $data->requestID));

				$this->auditLogs(array("userName"=>$data->userName,"memberID"=>$data->memberID,"table"=>"redeem_request_rewards","action"=>"disapprove points","transactionID"=>$data->transactionID,"fromdata"=>$data->status,"todata"=>"disapprove points","remarks"=>"disapprove rewards","description"=>"disapprove rewards","designation"=>$data->designation,"transactionDate"=>$dateModified));

				return json_encode(array("response" => "Success"));
			}

			if( count($check_member) > 0 ){

				if ($data->status == 'acknowledge') {
					$update_ack = process_qry("UPDATE `redeem_request_rewards` set `status`= ?, dateModified = ? WHERE memberID = ? AND `rewardsID` = ? AND `transactionID` = ? AND `requestID` = ? ",array("acknowledge", $newdate, $data->memberID, $data->rewardsID, $data->transactionID, $data->requestID),$logs);
					$subject = "Welcome to " . MERCHANT . "!";
					// $this->emailer(array("recipient_name" => $check_member[0]['fname'], "email" => $check_member[0]['email'], "type" => "request_card","subject" => $subject ));
					$dateModified = DATE('Y-m-d H:i:s');
					$this->auditLogs(array("userName"=>$data->userName,"memberID"=>$data->memberID,"table"=>"redeem_request_rewards","action"=>$data->status,"transactionID"=>$data->transactionID,"fromdata"=>$data->status,"todata"=>"acknowledged","remarks"=>"acknowledged rewards","description"=>"acknowledged rewards","designation"=>$data->designation,"transactionDate"=>$dateModified));

					return json_encode(array("response"=>"Success")); 

				}elseif ($data->status == 'approve') {
					if ($data->totalPoints > $data->points ) {
						$terminalnum = "";
						$locID = "";
						$branchCode = "";
						$version = "";
						$locName = "";
						$brandID = "";

						$terminalNum = $isAccountActive[0]['username'] . ' '. $data->accountID;
						$locID = $terminalnum;
						$branchCode = $terminalnum;
						$transactionType = $terminalnum;
						$version = $transactionType;
						$locName = $version;
						$brandID = $locName;

						$step_r = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, (MAX(`id`) + 1)) AS `entry` FROM `redeemtable`", array(), $logs);
						$xx = 0;
						$redeem_ID = NULL;
						while ($xx < 1) {
							$redeem_ID = substr("RDMWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
							$redeem_ID_1 = process_qry("SELECT count(*) AS `count` FROM `redeemtable` WHERE `redeemID` = ? LIMIT 1", array($redeem_ID), $logs);
							$xx = ( $redeem_ID_1[0]['count'] < 1 ) ? 1 : 0;
						}

						$trans_ID = substr("TRWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
						
						require_once(dirname(__FILE__) . '/redeem.class.php');
						$mclass = new redeem();

						$toredeem = $mclass->redeem_campaign(array("memberID"=>$data->memberID,"requestID"=>$data->requestID, "rewardsID"=>$data->rewardsID, "transactionID"=>$data->transactionID, "email"=>$data->email, "fname"=>$check_member[0]['fname'], "lname"=>$check_member[0]['lname'], "rewards_name"=>$data->rewards_name, "promoType"=>$data->promoType,"points"=>$data->points, "description"=>"redeem on premium", "status"=>$data->status, "platform"=>$data->platform,"barCode"=>$data->barCode,"sub_total"=>$data->totalPoints,"locID"=>$data->locID,"locName"=>$data->locName ));

						$approved = process_qry("UPDATE `redeem_request_rewards` SET `status` = 'approve',`emailSentApprove`='false', dateModified = ? WHERE memberID = ? AND `rewardsID` = ? AND `transactionID` = ? AND requestID = ? ", array($newdate, $data->memberID, $data->rewardsID, $data->transactionID, $data->requestID));
						$dateModified = DATE('Y-m-d H:i:s');
						$this->auditLogs(array("userName"=>$data->userName,"memberID"=>$data->memberID,"table"=>"redeem_request_rewards","action"=>$data->status,"transactionID"=>$data->transactionID,"fromdata"=>$data->status,"todata"=>"approve","remarks"=>"approve rewards","description"=>"approve rewards","designation"=>$data->designation,"transactionDate"=>$dateModified));

						return json_encode(array("response" => "Success"));
					}else{
						return json_encode(array("response"=>"Error", "description"=>"Insufficient points to redeem."));
					}	
				
				}
			}

		}// === end

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

		} // === end setIDs

		// === get points
		public function available_points(){
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$output = array();

			$qry = process_qry("SELECT * FROM  earnsettingstable WHERE status = 'active' ", array(), $logs);
			if (count($qry) > 0 ){
				// array_push($output, array("points"=>$qry) );
				return json_encode(array("response" => "Success", "data" =>$qry));
			}else{
				return json_encode(array(array("response" => "Error", "description" =>"No available points")));
			}
		}

		public function wpmtransactionID(){
			$step_r = process_qry("SELECT IF(MAX(`id`) IS NULL, 0, (MAX(`id`) + 1)) AS `entry` FROM `redeemtable`", array(), $logs);
			$xx = 0;
			$redeem_ID = NULL;
			while ($xx < 1) {
				$redeem_ID = substr("RDMWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
				$redeem_ID_1 = process_qry("SELECT count(*) AS `count` FROM `redeemtable` WHERE `redeemID` = ? LIMIT 1", array($redeem_ID), $logs);
				$xx = ( $redeem_ID_1[0]['count'] < 1 ) ? 1 : 0;
			}

			$trans_ID = substr("TRWPM" . randomizer(2) . DATE("H") . $step_r[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
		}

		public function user_roles($param){
			global $logs;
			global $protocol;
			$data = (object) $param;
			$file_name = 'points.class.php';


			$check_user = process_qry("SELECT * FROM `accounts` WHERE accountID = ? AND role in ('superadmin', 'administrator') and status = 'active' ",array($data->accountID),$logs);
			if(count($check_user) <= 0 ){
				return json_encode(array(array("response" => "Error", "description" =>"User is not authorized to create user priviledges.")));
			}

			$arr = json_decode($data->mroles,true);	


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

		// === start mailer
		public function emailer($param) {
			global $logs;
			global $protocol;
			$data = (object) $param;
			$file_name = 'points.class.php';
			$message = "<!DOCTYPE html><html><head><title></title><style type='text/css'>body, html{margin: 0;padding: 0 5%;font-family: 'Verdana';font-size: 14px;} img{width: 100%;} header{width: 100%;} .container{background-color: #f5f5f5;padding: 0 0 100px 0;} .emailBody{margin-top: 5%;padding: 0 5%;} .emailBody p{text-align: left;margin-top: 20px;line-height: 1.5} #regDetails p{margin: 0 0 0 20px;} #regDetails p label{font-weight: bold;} .activate{background-color: #35b7e8;padding: 20px;border-radius: 5px;width: 20%;margin: 5% auto;color: #fff;text-transform: uppercase;text-align: center;font-size: 18px;} a {text-decoration: none;}</style></head><body><div class='container'><header><img src='" .  DOMAIN . "/assets/images/activation/email_header.png'></header>";

			if($data->type == 'change_password') {
				
				$message = "<div class='emailBody'><h2>Hey ".$data->recipient_name."</h2><p>Thank you for registering in our" . MERCHANT .  "app.</p><p>Your registration is successful with username: <b>".$data->email."</b> and password: <b>".$data->password."</b></p><p>You may now use your log-in information the next time you open the ". MERCHANT ." app.</p><p>Have a good day!</p><p>Regards,</p><p>". MERCHANT . "</p></div></div></body></html>";

			}
			elseif ($data->type == 'request_card') {
				$message .= "<div class='emailBody'<h2>Hi " . $data->recipient_name . "</h2><p>You have successfully requested for a physical membership card. Our staff may contact you for further instructions.</p><p>For other concerns, you may reach us through the contact details provided below.<br /><br />Thank you! <br /><br /><p> Regards,<b>" . MERCHANT . "</b><br />(+63)9175205265 <br />marketing@henann.com </p><p><center>**This is a system-generated message. Please do not reply to this email.**</center></p></div></div></body></html>";
			}

			$sent = $this->send_mail(array('recipient' => $data->email, 'recipient_name' => $data->recipient_name, 'subject' => $data->subject, 'message' =>  $message, 'attachment' => NULL));
			
			if ($sent[0]['response'] == 'Success'){
				return true;
			}else{
				return false;
			}	
			
		}
		// === end mailer

	} // ==== end wpm	


?>