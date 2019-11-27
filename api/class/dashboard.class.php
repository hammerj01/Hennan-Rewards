<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class dashboard extends mailer{

		/********** Login **********/
		public function login($param) {
			require_once(dirname(__FILE__) . '/../misc/jwt_helper.php');
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_login";

			// Check Email and password is correct 
			$check_account = process_qry("SELECT accountID FROM `accounts` WHERE `username` = ? AND `password` = PASSWORD(?) LIMIT 1", array($data->username, $data->password));
			if (!empty($check_account[0]['accountID'])) {

				// Check Role
				$check_role = process_qry("SELECT count(*) AS `count` FROM `accounts` WHERE `username` = ? AND `password` = PASSWORD(?) AND `status` = 'active' AND cmsTabs IS NOT NULL LIMIT 1", array($data->username, $data->password));

				if ($check_role[0]['count'] > 0) {
					$row = process_qry('CALL dashboard_login(?, ?, ?)', array($data->username, $data->password, $check_account[0]['accountID']));
					// var_dump($row);
					// die();
					if ($row[0]['result'] == 'Success') {
						$token = array();
						$token['accountID'] = $row[0]['accountID'];
						$token['loginSession'] = $row[0]['loginSession']; 
						$token['role'] = $row[0]['role']; 
						$token['reportTabs'] = $row[0]['reportTabs']; 
						$token['cmsTabs'] = $row[0]['cmsTabs']; 
						$token['profilePic'] = $row[0]['profilePic']; 
						$token['fullname'] = $row[0]['fullname']; 
						$token['username'] = $row[0]['username']; 
						$newtoken = JWT::encode($token, JWT_SERUCITY_KEY);  
						$param['token'] = $newtoken;

						$logs->write_logs('Login - Dashboard', $file_name, array(array("response"=>"Success", "data"=>$param)));
						return json_encode(array('response'=>'Success', 'token'=>$newtoken )); 
					}
					else{
						$logs->write_logs('Login - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
						return json_encode(array('response'=>'Failed', 'description'=>'Invalid Username or Password.' )); 			
					}
				}
				else{
					$logs->write_logs('Login - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Account Restricted.' ));
				}
			}
			else{
				$logs->write_logs('Login - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Invalid Username or Password.' )); 
			}
		}


		/********** JSON **********/
		public function jsonlist($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "jsonlist";

			/* Check Account ID and Login Session */
			$sessionActive = $this->check_account_session($data->accountID, $data->my_session_id); // false by default

			if ($sessionActive == 0) {
				return json_encode(array("response"=>"Expired"));
			}

			/* Get Total Records */
			$countqry = $this->get_init_total_query($data->table, $data->searchFilter, $data->selectedStatus, $data->selectedType);
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
				$pageqry = $this->get_paged_query($data->table, $data->searchFilter, $data->selectedStatus, $data->sortColumnName, $data->sortOrder, $data->selectedType);

				if ($pageqry['response'] == 'Success') {
					$output = process_qry($pageqry['query'], array((int)$data->pageSize, (int)$offset));
					$pageInfo = array("totalPage"=>$pages, 
						"sortColumnName"=>$data->sortColumnName, "sortOrder"=>$data->sortOrder, "pageSize"=>(int)$data->pageSize, 
						"currentPage"=>$currentPage, "searchFilter"=>$data->searchFilter, "selectedStatus"=>$data->selectedStatus, 
						"totalRecord"=>$totalrecords, "selectedType"=>$data->selectedType);

					// $output = str_replace('\r\n', '<br>', $output);
					// $output = str_replace('\r', '<br>', $output);
					// $output = str_replace('\n\r', '<br>', $output);
					// $output = str_replace(array('\r\n', '\r', '\n'), '<br />', $output);
					// // var_dump($output);

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

		public function get_init_total_query($table, $searchFilter, $selectedStatus, $selectedType){
			if ($table == 'loctable') {
				$qry = "SELECT COUNT(*) as total from loctable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `address` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'loccategorytable') {
				$qry = "SELECT COUNT(*) as total from loccategorytable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `description` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'accounts') {
				$qry = "SELECT COUNT(*) as total from accounts ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`username` LIKE '%".$searchFilter."%' OR `fullname` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'leveltable') {
				$qry = "SELECT COUNT(*) as total from leveltable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `quote` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'vouchertable') {
				$qry = "SELECT COUNT(*) as total from vouchertable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `type` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'loyaltytable') {
				$qry = "SELECT COUNT(*) as total from loyaltytable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `points` LIKE '%".$searchFilter."%' OR `promoType` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'posttable') {
				$qry = "SELECT COUNT(*) as total from posttable  ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`title` LIKE '%".$searchFilter."%' OR `type` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'producttable') {
				$qry = "SELECT COUNT(*) as total from producttable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `price` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'brandtable') {
				$qry = "SELECT COUNT(*) as total from brandtable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'termtable') {
				$qry = "SELECT COUNT(*) as total from termtable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'abouttable') {
				$qry = "SELECT COUNT(*) as total from abouttable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'faqtable') {
				$qry = "SELECT COUNT(*) as total from faqtable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'socialmediatable') {
				$qry = "SELECT COUNT(*) as total from socialmediatable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'devicecodetable') {
				$qry = "SELECT COUNT(*) as total from devicecodetable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`brandName` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'earnsettingstable') {
				$qry = "SELECT COUNT(*) as total from earnsettingstable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'categorytable') {
				$qry = "SELECT COUNT(*) as total from categorytable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `description` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'subcategorytable') {
				$qry = "SELECT COUNT(*) as total from subcategorytable ";

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND `status` = '".$selectedStatus."' " : "WHERE `status` = '".$selectedStatus."' ";
				}
			}
			else if ($table == 'taxtable'){
				$qry = "SELECT COUNT(*) as total  FROM `taxtable` WHERE `status` = 'true'";	

				if ($searchFilter != '') {
					$qry .= "WHERE (`name` LIKE '%".$searchFilter."%') ";
				}
			}elseif ($table == 'cashiertable') {
				$qry = "SELECT COUNT(*) as total FROM `cashiertable` c inner join loctable l on c.`locID` = l.`locID` ";
				if ($searchFilter != '') {
					$qry .= "WHERE (c.`name` LIKE '%".$searchFilter."%' OR c.`status` LIKE '%".$searchFilter."%') ";
				}
				if ($selectedStatus != '') {
					$qry .= ($searchFilter != '') ? "AND c.`status` = '".$selectedStatus."' " : "WHERE c.`status` = '".$selectedStatus."' ";
				}
			}
			else{
				return array("response"=>"Error", "description"=>'No table parameter found for server pagination');
			}

			return array("response"=>"Success", "query"=>$qry);
		}

		public function get_paged_query($table, $searchFilter, $selectedStatus, $sortColumnName, $sortOrder, $selectedType){

			if ($table == 'loctable') {

				$pageqry = "SELECT * FROM `loctable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `address` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'loccategorytable') {

				$pageqry = "SELECT * FROM `loccategorytable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `description` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'accounts') {

				$pageqry = "SELECT * FROM `accounts` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`username` LIKE '%".$searchFilter."%' OR `fullname` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'leveltable') {

				$pageqry = "SELECT * FROM `leveltable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `quote` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'posttable') {

				$pageqry = "SELECT * FROM `posttable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`title` LIKE '%".$searchFilter."%' OR `type` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'loyaltytable') {

				$pageqry = "SELECT * FROM `loyaltytable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `points` LIKE '%".$searchFilter."%' OR `promoType` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'posttable') {

				$pageqry = "SELECT * FROM `posttable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`title` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'producttable') {

				$pageqry = "SELECT * FROM `producttable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `price` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'brandtable') {

				$pageqry = "SELECT * FROM `brandtable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'vouchertable') {

				$pageqry = "SELECT * FROM `vouchertable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `type` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'termtable') {

				$pageqry = "SELECT * FROM `termtable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'abouttable') {

				$pageqry = "SELECT * FROM `abouttable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'faqtable') {

				$pageqry = "SELECT * FROM `faqtable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'socialmediatable') {

				$pageqry = "SELECT * FROM `socialmediatable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'devicecodetable') {

				$pageqry = "SELECT * FROM `devicecodetable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`brandName` LIKE '%".$searchFilter."%' OR `locName` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'earnsettingstable') {

				$pageqry = "SELECT * FROM `earnsettingstable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `status` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}

			else if ($table == 'taxtable') {

				$pageqry = "SELECT * FROM `taxtable` WHERE `status` = 'true'"; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'categorytable') {

				$pageqry = "SELECT * FROM `categorytable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `description` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'subcategorytable') {

				$pageqry = "SELECT * FROM `subcategorytable` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE (`name` LIKE '%".$searchFilter."%' OR `description` LIKE '%".$searchFilter."%') ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND `status` = '".$selectedStatus."' " : 
						"WHERE `status` = '".$selectedStatus."' ";
				}

				if ($sortColumnName != '') {
					$pageqry .= 'ORDER BY LOWER(`'.$sortColumnName.'`) ' . $sortOrder . ' LIMIT ? OFFSET ?';	
				}
				else{				
					$pageqry .= 'LIMIT ? OFFSET ?';
				}

			}
			else if ($table == 'cashiertable') {
				$pageqry = "SELECT c.`name`, l.`name` as 'locName', c.`designation`, c.`username`, c.`image`, c.`status`, c.`cashierID` FROM `cashiertable` c inner join loctable l on c.`locID` = l.`locID` "; 
				$hasfirst = false;

				if ($searchFilter != '') {
					$pageqry .= "WHERE c.`name` LIKE '%".$searchFilter."%' ";
					$hasfirst = true;
				}
				if ($selectedStatus != '') {
					$pageqry .= ($hasfirst) ? "AND c.`status` = '".$selectedStatus."' " : 
						"WHERE c.`status` = '".$selectedStatus."' ";
				}

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

				// $pageqry = str_replace('\r\n', '<br>', $pageqry);
				// 	$pageqry = str_replace('\r', '<br>', $pageqry);
				// 	$pageqry = str_replace('\\r', '<br>', $pageqry);
				// 	$pageqry = str_replace('\\n', '<br>', $pageqry);
				// 	$pageqry = str_replace(array('\r\n', '\r', '\n'), '<br />', $pageqry);
				// 	var_dump($output);

			return array("response"=>"Success", "query"=>$pageqry);
		}

				/********** Send Push Notification **********/
		public function send_push($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$step_1 = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($step_1[0]['count'] > 0) {
				// $step_2 = process_qry("SELECT `pushID`, `platform` FROM `devicetokens` WHERE `pushID` = 'cUmTgesSBlk:APA91bHYNdOEHI5l_0hi_O8zQUrQhSl1a6aqJEyMpwNMXhA87yWwJTYNWWkhytFvK4VSNSKgn9mWtNubDC1GPurNRCknMlE2dYGiAHl73waAhWB0ZI5lnl8tB7biElQERloNOW6ODdhN'", array());
				$step_2 = process_qry("SELECT `pushID`, `platform` FROM `devicetokens` WHERE `pushID` IS NOT NULL AND `pushID` != '' AND `pushID` != ' '", array());

				// $this->add_audit($data->accountID, $data->my_session_id, 'push', 'Send', $data);

				if (count($step_2) > 0) {
					include("api/class/push.class.php");
					$push = new push_notification;
					$pushID = array();
					$error_flag = false;
					$return = "";

					for ($i=0; $i < count($step_2); $i++) {
						if (isset($step_2[$i]['result'])) {
							$error_flag = true;
							$return = $step_2[$i]['result'];
						} else {
							array_push($pushID, $step_2[$i]['pushID']);
						}
					}

					if (!$error_flag) {
						$pushID_chunk = array_chunk($pushID, 1000);

						for ($ii=0; $ii<count($pushID_chunk); $ii++) { 
							$push->push($pushID_chunk[$ii], $data->message, $data->type);
						}

						return json_encode(array("response"=>"Success"));
					} else {
						return json_encode(array("response"=>$return));
					}
				}

				return json_encode(array("response"=>"Success"));
			} else {
				$logs->write_logs('Send Push - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' ));
			}
		}

		/********** JSON ENUMERATE **********/
		public function jsonenumerate($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "jsonenumerate";

			/* Check Account ID and Login Session */
			$sessionActive = $this->check_account_session($data->accountID, $data->my_session_id); // false by default

			if ($sessionActive == 1) {

				/* Count Transactions */
				$countqry = $this->count_transactions($data->selectedLocation);
				$countOutput = process_qry($countqry, array());
				$transaction_count = array("neworder"=>$countOutput[0]['neworder'], 
					"processing"=>$countOutput[0]['processing'], 
					"dispatched"=>$countOutput[0]['dispatched'], 
					"completed"=>$countOutput[0]['completed']);


				/* Get Unpaged Query */
				$pageqry = $this->get_unpaged_query($data->table, $data->selectedStatus, $data->selectedLocation);
				$output = array();
				if($pageqry['response'] == 'Success') {
					$output = process_qry($pageqry['query'], array());
				}
				else if ($pageqry['response'] == 'Error') {
					return json_encode(array("response"=>"Error", "description"=>'No table parameter found for server pagination'));
				}

				$totalrecords = sizeof($output);

				$pageInfo = array("sortColumnName"=>$data->sortColumnName, "sortOrder"=>$data->sortOrder, "searchFilter"=>$data->searchFilter, "selectedStatus"=>$data->selectedStatus, "selectedLocation"=>$data->selectedLocation, "totalRecord"=>$totalrecords);

				return json_encode(array("response"=>"Success", "data"=>$output, "pageinfo"=>$pageInfo, "transaction_count"=>$transaction_count));

			}
			else {
				return json_encode(array("response"=>"Expired"));
			}

		}

		public function get_unpaged_query($table, $selectedStatus, $selectedLocation){
			if (empty($selectedStatus)) {
				$selectedStatus = 'false';
			}
			// AND `paymentStatus` = 'Success'
			if ($table == 'transactiontable') {
				$pageqry = "SELECT t.transactionID, t.memberid, t.transactiondate, CONCAT(t.fname, ' ', t.lname) AS 'customer', t.grandTotal, m.image, t.status, t.locName, m.address1, m.address2, t.email, t.dispatcherName, t.riderIn, t.riderOut, t.city, t.brgy, t.street, t.remarks, t.paymentStatus
					FROM 
					(
					 SELECT * FROM transactiontable WHERE `partition` = 'head' AND `status` = '".$selectedStatus."' AND locID = '".$selectedLocation."'
					) t
					INNER JOIN
					(
					 SELECT * FROM memberstable WHERE activation IS NULL
					) m
					ON m.memberid = t.memberid GROUP BY t.`transactionID` ORDER BY t.`dateModified` DESC"; 
			}
			else{
				return array("response"=>"Error", "description"=>'No table parameter found for server pagination');
			}
			
			return array("response"=>"Success", "query"=>$pageqry);
		}


		public function count_transactions($selectedLocation){
			return "SELECT COUNT(a.transactionID) AS 'neworder',
			(SELECT COUNT(a.transactionID) from transactiontable a, memberstable b WHERE a.memberID = b.memberID AND a.`status` = 'processing' AND a.`partition` = 'head' AND `paymentStatus` = 'Success' AND a.locID = '".$selectedLocation."') as 'processing',
			(SELECT COUNT(a.transactionID) from transactiontable a, memberstable b WHERE a.memberID = b.memberID AND a.`status` = 'dispatched' AND a.`partition` = 'head' AND `paymentStatus` = 'Success' AND a.locID = '".$selectedLocation."') as 'dispatched',
			(SELECT COUNT(a.transactionID) from transactiontable a, memberstable b WHERE a.memberID = b.memberID AND a.`status` = 'completed' AND a.`partition` = 'head' AND `paymentStatus` = 'Success' AND a.locID = '".$selectedLocation."') as 'completed'
			FROM transactiontable a, memberstable b 
			WHERE a.memberID = b.memberID AND a.`status` = 'false'
			AND a.`partition` = 'head' AND `paymentStatus` = 'Success' AND a.locID = '".$selectedLocation."'";
		}

		public function check_account_session($accountID, $my_session_id) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$output = array();
			$param = array('accountID'=>$accountID, 'loginSession'=>$my_session_id);
			$data = (object) $param;
			$param['procedure'] = "check_account_session";

			$qry = process_qry("SELECT COUNT(*) as count FROM `accounts` WHERE BINARY `accountID` = ? AND BINARY `loginSession` = ? LIMIT 1", array($accountID, $my_session_id));

			return $qry[0]['count'];
		}

		/********** View Record **********/
		public function view_record($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "view_record";

			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				if ($data->table == 'loctable') {
					$row = process_qry('SELECT * from loctable WHERE locID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'loccategorytable') {
					$row = process_qry('SELECT * from loccategorytable WHERE locCategoryID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'leveltable') {
					$row = process_qry('SELECT * from leveltable WHERE levelID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'vouchertable') {
					$row = process_qry('SELECT * from vouchertable WHERE voucherID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'loyaltytable') {
					$row = process_qry('SELECT * from loyaltytable WHERE loyaltyID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'posttable') {
					$row = process_qry('SELECT * from posttable WHERE postID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'producttable') {
					$row = process_qry('SELECT * from producttable WHERE prodID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'brandtable') {
					$row = process_qry('SELECT * from brandtable WHERE brandID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'termtable') {
					$row = process_qry('SELECT * from termtable WHERE termID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'abouttable') {
					$row = process_qry('SELECT * from abouttable WHERE aboutID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'faqtable') {
					$row = process_qry('SELECT * from faqtable WHERE faqID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'socialmediatable') {
					$row = process_qry('SELECT * from socialmediatable WHERE socialmediaID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'accounts') {
					$row = process_qry('SELECT * from accounts WHERE accountID = ?', array($data->filter));
					
					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'devicecodetable') {
					$row = process_qry('SELECT * from devicecodetable WHERE deviceCode = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'earnsettingstable') {
					$row = process_qry('SELECT * from earnsettingstable WHERE settingID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'taxtable') {
					$row = process_qry('SELECT tax, id, description from taxtable WHERE id = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'categorytable') {
					$row = process_qry('SELECT * from categorytable WHERE categoryID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'subcategorytable') {
					$row = process_qry('SELECT * from subcategorytable WHERE subcategoryID = ?', array($data->filter));

					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if($data->table == 'cashiertable'){
					$row = process_qry('SELECT * from cashiertable WHERE cashierID = ?', array($data->filter));

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
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "view_record";

			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				if ($data->table == 'settings') {
					$row = process_qry('SELECT * from settings', array());
					return json_encode(array("response"=>"Success", "data"=>$row[0]));
				}
				else if ($data->table == 'location') {
					$row = process_qry("SELECT * from loctable where status = 'active' AND brandID = ? ", array($data->filter));
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'locations') {
					$row = process_qry("SELECT * from loctable where status = 'active' ", array());
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'loccategory') {
					$row = process_qry("SELECT * from loccategorytable where status = 'active' ", array());
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'brand') {
					$row = process_qry("SELECT * from brandtable where status = 'active' ", array());
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'category') {
					$row = process_qry("SELECT * from categorytable where status = 'active' ", array());
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'brand_setting') {
					$row = process_qry("SELECT `brandID`, `name` FROM `brandtable` WHERE `status` = 'active' AND `brandID` NOT IN (SELECT `brandID` FROM `earnsettingstable`) ", array());
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'branchCode') {
					$row = process_qry("SELECT branchCode from loctable where status = 'active' ", array());
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'userName') {
					$row = process_qry("SELECT username, role from accounts ", array());
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
				else if ($data->table == 'aboutStatus') {
					$row = process_qry("SELECT status from abouttable ", array());
					return json_encode(array("response"=>"Success", "data"=>$row));
				}
			}
			else{
				$logs->write_logs('ViewRecord - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}


		/********** Get Brands **********/
		public function get_category(){
            global $logs;
            global $error000;
            $file_name = 'dashboard.class.php';
            $get_category = process_qry("SELECT `brandID`, `name` FROM `brandtable` WHERE `status` = 'active'", array());
            return json_encode(array("response"=>"Success", "data"=>$get_category));
        }


		/********** Add Location **********/
		public function add_location($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_location";

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
				if ($check_account[0]['count'] > 0) {
				
					$x = 0;
	                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `loctable` LIMIT 1", array());
	                $locID = NULL;
	                while ($x < 1) {
	                    $locID = substr("LOC" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
	                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `loctable` WHERE `locID` = ? LIMIT 1", array($locID));
	                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
                	}


				$row = process_qry('CALL dashboard_add_location(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($locID, $data->locCategoryID, $data->locCategoryName, $data->name, $data->address, $data->latitude, $data->longitude, $data->branchCode, $data->region, $data->phone, $data->email, $data->businessHrs, $data->status, $data->locFlag, $data->image));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else{
					$logs->write_logs('Addlocation - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('Addlocation - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update Location **********/
		public function update_location($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_location";
			// var_dump($data);
			// echo $data->locID.','. $data->brandID.','. $data->brandName.','. $data->name.','. $data->address.','. $data->latitude.','. $data->longitude.','. $data->branchCode.','. $data->region.','. $data->phone.','. $data->email.','. $data->businessHrs.','. $data->status.','. $data->locFlag.','. $data->image;
			
			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? 
				AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));

			if ($check_account[0]['count'] > 0) {
				// echo $data->locID;
				$row = process_qry('CALL dashboard_update_location(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($data->locID, $data->locCategoryID, $data->locCategoryName, $data->name, $data->address, $data->latitude, $data->longitude, $data->branchCode, $data->region, $data->phone, $data->email, $data->businessHrs, $data->status, $data->locFlag, $data->image));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('Updatelocation - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('Updatelocation - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add Location Category **********/
		public function add_locationcategory($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_locationcategory";

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
				if ($check_account[0]['count'] > 0) {
				
					$x = 0;
	                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `loccategorytable` LIMIT 1", array());
	                $locCategoryID = NULL;
	                while ($x < 1) {
	                    $locCategoryID = substr("CATLOC" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
	                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `loccategorytable` WHERE `locCategoryID` = ? LIMIT 1", array($locCategoryID));
	                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
                	}


				$row = process_qry('CALL dashboard_add_locationcategory(?, ?, ?, ?)', array($locCategoryID, $data->name, $data->description, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else{
					$logs->write_logs('AddlocationCategory - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('AddlocationCategory - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update Location Category **********/
		public function update_locationcategory($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_locationcategory";
			// var_dump($data);
			// echo $data->locID.','. $data->brandID.','. $data->brandName.','. $data->name.','. $data->address.','. $data->latitude.','. $data->longitude.','. $data->branchCode.','. $data->region.','. $data->phone.','. $data->email.','. $data->businessHrs.','. $data->status.','. $data->locFlag.','. $data->image;
			
			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? 
				AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));

			if ($check_account[0]['count'] > 0) {
				// echo $data->locID;
				$row = process_qry('CALL dashboard_update_locationcategory(?, ?, ?, ?)', array($data->locCategoryID, $data->name, $data->description, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdatelocationCategory - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdatelocationCategory - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add Level **********/
		public function add_level($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_level";

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `leveltable` LIMIT 1", array());
                $levelID = NULL;
                while ($x < 1) {
                    $levelID = substr("LEV" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `leveltable` WHERE `levelID` = ? LIMIT 1", array($levelID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_level(?, ?, ?, ?, ?, ?, ?)', array($levelID, $data->name, $data->min, $data->max, $data->level, $data->quote, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else{
					$logs->write_logs('AddLevel - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('AddLevel - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update Level **********/
		public function update_level($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_level";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_level(?, ?, ?, ?, ?, ?, ?)', array($data->levelID, $data->name, $data->min, $data->max, $data->level, $data->quote, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateLevel - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateLevel - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update VOUCHER **********/
		public function add_voucher($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_voucher";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `vouchertable` LIMIT 1", array());
                $voucherID = NULL;
                while ($x < 1) {
                    $voucherID = substr("VCH" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `vouchertable` WHERE `voucherID` = ? LIMIT 1", array($voucherID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_voucher(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($voucherID, $data->name, $data->image, $data->description, $data->terms, $data->type, $data->cardType, $data->cardVersion, $data->startDate, $data->endDate, $data->frequencyType, $data->frequencyStart, $data->frequencyEnd, $data->startTime, $data->endTime, $data->month, $data->redemptionlimit, $data->wmyperiod, $data->quantity, $data->action, $data->order, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addVoucher - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addVoucher - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update VOUCHER **********/
		public function update_voucher($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_voucher";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_voucher(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($data->voucherID, $data->name, $data->image, $data->description, $data->terms, $data->type, $data->cardType, $data->cardVersion, $data->startDate, $data->endDate, $data->frequencyType, $data->frequencyStart, $data->frequencyEnd, $data->startTime, $data->endTime, $data->month, $data->redemptionlimit, $data->wmyperiod, $data->quantity, $data->action, $data->order, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					$qry_1 =  process_qry("UPDATE earnvouchertable SET `name` = ?, `image` = ?, `startDate` = ?, `endDate` = ?, `type` = ?, `month` = ?, `action` = ?, `status` = ? WHERE voucherID= ?",array($data->name, $data->image, $data->startDate, $data->endDate, $data->type, $data->month, $data->action, $data->status, $data->voucherID),$logs);
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateVoucher - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateVoucher - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add LOYALTY **********/
		public function add_loyalty($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_loyalty";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `loyaltytable` LIMIT 1", array());
                $loyaltyID = NULL;
                while ($x < 1) {
                    $loyaltyID = substr("LOY" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `loyaltytable` WHERE `loyaltyID` = ? LIMIT 1", array($loyaltyID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				// $row = process_qry('CALL dashboard_add_loyalty(?, ?, ?, ?, ?, ?, ?, ?, ?)', array($loyaltyID, $data->locID, $data->locName, $data->name, $data->points, $data->promoType, $data->terms, $data->description, $data->status));

				$row = process_qry('INSERT INTO `loyaltytable` (`loyaltyID`, `locID`, `locName`, `name`, `points`, `promoType`, `terms`, `description`, `status`, `dateAdded`, `dateModified`, `image`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)', array($loyaltyID, $data->locID, $data->locName, $data->name, $data->points, $data->promoType, $data->terms, $data->description, $data->status, $data->image));
				// var_dump($row);
				if ($row[0]['response'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['response'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addLoyalty - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addLoyalty - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update LOYALTY **********/
		public function update_loyalty($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_loyalty";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				// $row = process_qry('CALL dashboard_update_loyalty(?, ?, ?, ?, ?, ?, ?, ?, ?)', array($data->loyaltyID, $data->locID, $data->locName, $data->name, $data->points, $data->promoType, $data->terms, $data->description, $data->status));

				$row = process_qry('UPDATE `loyaltytable` SET  `locID` = ?,`locName` = ?, `name` = ?, `points` = ?, `promoType` = ?, `terms` = ?, `description` = ?, `status` = ?, `dateModified` = NOW(), `image` = ? WHERE loyaltyID = ? LIMIT 1', array($data->locID, $data->locName, $data->name, $data->points, $data->promoType, $data->terms, $data->description, $data->status, $data->image, $data->loyaltyID), $logs);
	
				if ($row[0]['response'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['response'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateLoyalty - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateLoyalty - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add POST **********/
		public function add_post($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_post";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `posttable` LIMIT 1", array());
                $postID = NULL;
                while ($x < 1) {
                    $postID = substr("PST" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `posttable` WHERE `postID` = ? LIMIT 1", array($postID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_post(?, ?, ?, ?, ?, ?)', array($postID, $data->title, $data->description, $data->url, $data->image, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addPost - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addPost - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update POST **********/
		public function update_post($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_post";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_post(?, ?, ?, ?, ?, ?, ?)', array($data->postID, $data->title, $data->type, $data->description, $data->url, $data->image, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdatePost - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdatePost - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add CATEGORY **********/
		public function add_category($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_category";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `categorytable` LIMIT 1", array());
                $categoryID = NULL;
                while ($x < 1) {
                    $categoryID = substr("PST" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `categorytable` WHERE `categoryID` = ? LIMIT 1", array($categoryID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_category(?, ?, ?, ?)', array($categoryID, $data->name, $data->image, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addCategory - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addCategory - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update CATEGORY **********/
		public function update_category($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_category";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_category(?, ?, ?, ?)', array($data->categoryID, $data->name, $data->image, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateCategory - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateCategory - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add SUBCATEGORY **********/
		public function add_subcategory($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_subcategory";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `subcategorytable` LIMIT 1", array());
                $subcategoryID = NULL;
                while ($x < 1) {
                    $subcategoryID = substr("PST" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `subcategorytable` WHERE `subcategoryID` = ? LIMIT 1", array($subcategoryID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_subcategory(?, ?, ?, ?, ?, ?)', array($subcategoryID, $data->categoryID, $data->categoryName, $data->name, $data->image, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addSubcategory - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addSubcategory - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update SUBCATEGORY **********/
		public function update_subcategory($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_subcategory";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_subcategory(?, ?, ?, ?, ?, ?)', array($data->subcategoryID, $data->categoryID, $data->categoryName, $data->name, $data->image, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateSubcategory - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateSubcategory - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add PRODUCT **********/
		public function add_product($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_product";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `producttable` LIMIT 1", array());
                $prodID = NULL;
                while ($x < 1) {
                    $prodID = substr("PROD" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `producttable` WHERE `prodID` = ? LIMIT 1", array($prodID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

            	// $data->name = str_replace("'", "\'", $data->name);

				$row = process_qry('CALL dashboard_add_product(?, ?, ?, ?, ?, ?, ?, ?)', array($prodID, $data->categoryID, $data->categoryName, $data->name, $data->description, $data->url, $data->image, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addProduct - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addProduct - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update PRODUCT **********/
		public function update_product($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_product";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));


			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_product(?, ?, ?, ?, ?, ?, ?, ?)', array($data->prodID, $data->categoryID, $data->categoryName, $data->name, $data->description, $data->url, $data->image, $data->status));

				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}else{
					$logs->write_logs('UpdateProduct - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Somethingsdfdsf went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateProduct - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add BRAND **********/
		public function add_brand($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_brand";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);
			$data->name = str_replace("&#39;", "'", $data->name);
			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `brandtable` LIMIT 1", array());
                $brandID = NULL;
                while ($x < 1) {
                    $brandID = substr("BRND" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `brandtable` WHERE `brandID` = ? LIMIT 1", array($brandID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_brand(?, ?, ?, ?, ?, ?)', array($brandID, $data->name, $data->description, $data->image, $data->order, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addProduct - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addProduct - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update BRAND **********/
		public function update_brand($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_brand";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);
			$data->name = str_replace("&#39;", "'", $data->name);
			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_brand(?, ?, ?, ?, ?, ?)', array($data->brandID, $data->name, $data->description, $data->image, $data->order, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateProduct - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateProduct - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add TERMS AND CONDITIONs **********/
		public function add_term($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_term";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `termtable` LIMIT 1", array());
                $termID = NULL;
                while ($x < 1) {
                    $termID = substr("TRM" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `termtable` WHERE `termID` = ? LIMIT 1", array($termID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_term(?, ?, ?, ?)', array($termID, $data->name, $data->description, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addTerm - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addTerm - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update TERMS AND CONDITIONS **********/
		public function update_term($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_term";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_term(?, ?, ?, ?)', array($data->termID, $data->name, $data->description, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateTerm - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateTerm - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add ABOUT **********/
		public function add_about($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_about";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `abouttable` LIMIT 1", array());
                $aboutID = NULL;
                while ($x < 1) {
                    $aboutID = substr("TRM" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `abouttable` WHERE `aboutID` = ? LIMIT 1", array($aboutID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				// $row = process_qry('CALL dashboard_add_about(?, ?, ?, ?)', array($aboutID, $data->name, $data->description, $data->status));
				$row = process_qry("INSERT INTO abouttable (`aboutID`, `name`, `description`, `status`, `dateAdded`, `dateModified`)VALUES(?, ?, ?, ?, NOW(), NOW())", array($aboutID, $data->name, $data->description, $data->status), $logs);	

				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addAbout - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addAbout - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update ABOUT **********/
		public function update_about($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_about";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_about(?, ?, ?, ?)', array($data->aboutID, $data->name, $data->description, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateAbout - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateAbout - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add FAQ **********/
		public function add_faq($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_faq";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `faqtable` LIMIT 1", array());
                $faqID = NULL;
                while ($x < 1) {
                    $faqID = substr("TRM" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `faqtable` WHERE `faqID` = ? LIMIT 1", array($faqID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_faq(?, ?, ?, ?)', array($faqID, $data->name, $data->description, $data->status));

				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addFaq - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addFaq - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update FAQ **********/
		public function update_faq($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_faq";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('UPDATE `faqtable` SET `name` = ?, `description` = ?, `status` = ?, `dateModified` = NOW() WHERE faqID = ? LIMIT 1', array($data->name, $data->description, $data->status,$data->faqID));
				if (count($row) > 0){
					if ($row[0]['response'] == 'Success') {
						return json_encode(array('response'=>'Success')); 
					}
					else if ($row[0]['response'] == 'No Changes'){
						return json_encode(array('response'=>'Success')); 			
					}
					else{
						$logs->write_logs('UpdateFaq - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
						return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
					}
				}else{
					return json_encode(array('response'=>'Failed', 'description'=>'Cannot update FAQ')); 
				}
				
			}
			else{
				$logs->write_logs('UpdateFaq - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add SOCIAL MEDIA **********/
		public function add_socialmedia($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_socialmedia";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `socialmediatable` LIMIT 1", array());
                $socialmediaID = NULL;
                while ($x < 1) {
                    $socialmediaID = substr("TRM" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `socialmediatable` WHERE `socialmediaID` = ? LIMIT 1", array($socialmediaID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_socialmedia(?, ?, ?, ?)', array($socialmediaID, $data->name, $data->url, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addSocialMedia - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addSocialMedia - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update SOCIAL MEDIA **********/
		public function update_socialmedia($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_socialmedia";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_socialmedia(?, ?, ?, ?)', array($data->socialmediaID, $data->name, $data->url, $data->status));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateSocialMedia - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateSocialMedia - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add ACCOUNT **********/
		public function add_account($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_add_account";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `accounts` LIMIT 1", array());
                $accountID = NULL;
                while ($x < 1) {
                    $accountID = substr("ACCT" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `accounts` WHERE `accountID` = ? LIMIT 1", array($accountID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_account(?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)', array($accountID, $data->username, $data->password, $data->fullname, $data->role, $data->reportTabs, $data->cmsTabs,$data->wpmTabs, $data->profilePic, $data->status,$data->locID ));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addAccount - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addAccount - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update ACCOUNT **********/
		public function update_account($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_account";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);
		
			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id), $logs);
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_account(?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)', array($data->id, $data->username, $data->password, $data->fullname, $data->role, $data->reportTabs, $data->cmsTabs,$data->wpmTabs, $data->profilePic, $data->status,$data->locID ), $logs);
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateAccount - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateAccount - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add Tablet **********/
		public function add_tablet($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "add_tablet";

			// $step_1 = process_qry("CALL dashboard_add_tablet(?, ?, ?, ?, ?, ?)",array($data->accountID, $data->my_session_id, $data->location, $data->brandID, $data->terminal, $data->status),$logs);
			// var_dump($param);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `devicecodetable` LIMIT 1", array());
                $deviceCode = NULL;
                while ($x < 1) {
                    $deviceCode = substr("TAB" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `devicecodetable` WHERE `deviceCode` = ? LIMIT 1", array($deviceCode));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_tablet(?, ?, ?, ?, ?, ?, ?, ?, ?)', array($deviceCode, $data->brandID, $data->brandName, $data->locID, $data->locName, $data->postype, $data->terminalNum, $data->status, $data->deploy ));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addTablet - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addTablet - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update Tablet **********/
		public function update_tablet($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "update_tablet";
 			
			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);
		
			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_tablet(?, ?, ?, ?, ?, ?, ?, ?, ?)', array($data->deviceCode, $data->brandID, $data->brandName, $data->locID, $data->locName, $data->postype, $data->terminalNum, $data->status, $data->deploy ));
				if ($row[0]['result'] == 'Success') {
					$row1 = process_qry("UPDATE `devicecodetable` SET `deviceID` = NULL WHERE deviceCode = ? and `deploy` = 'false' LIMIT 1", array($data->deviceCode),$logs);
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateTablet - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateTablet - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Add Earn Setting **********/
		public function add_setting($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "add_setting";

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {

				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `earnsettingstable` LIMIT 1", array());
                $settingID = NULL;
                while ($x < 1) {
                    $settingID = substr("EARN" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `earnsettingstable` WHERE `settingID` = ? LIMIT 1", array($settingID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

				$row = process_qry('CALL dashboard_add_earnsetting(?, ?, ?, ?, ?, ?, ?)', array($settingID, $data->locID, $data->locName, $data->name, $data->camount, $data->cpoints, $data->status ));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('addSetting - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('addSetting - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update Earn Setting **********/
		public function update_setting($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "update_setting";
 			
			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);
		
			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_earnsetting(?, ?, ?, ?, ?, ?, ?)', array($data->settingID, $data->locID, $data->locName, $data->name, $data->camount, $data->cpoints, $data->status ));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateSetting - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateSetting - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update Profile **********/
		public function update_profile($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_update_profile";

			// $this->add_audit($data->accountID, $data->my_session_id, 'campaignlist', 'Add', $data);

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$row = process_qry('CALL dashboard_update_profile(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($data->company, $data->fname1, $data->mname1, $data->lname1, $data->fname2, $data->mname2, $data->lname2, $data->address, $data->email, $data->website, $data->landline1, $data->landline2, $data->mobile1, $data->mobile2, $data->fax1, $data->fax2, $data->profilePic, $data->about, $data->merchantCode));
				
				if ($row[0]['result'] == 'Success') {
					return json_encode(array('response'=>'Success')); 
				}
				else if ($row[0]['result'] == 'No Changes'){
					return json_encode(array('response'=>'Success')); 			
				}
				else{
					$logs->write_logs('UpdateProfile - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
					return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
				}
			}
			else{
				$logs->write_logs('UpdateProfile - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		/********** Update Password **********/
		public function update_password($param) {
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "dashboard_password";
			// var_dump($data);
			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));

			if ($check_account[0]['count'] > 0) {
				$pass_status = process_qry("SELECT IF((`password` IS NOT NULL AND `password` = PASSWORD(?)), 'true', 'false') AS `password` FROM `accounts` WHERE BINARY `accountID` = ? AND BINARY `loginSession` = ? LIMIT 1", array($data->old_password, $data->accountID, $data->my_session_id));
				if($pass_status[0]['password'] == 'true') {
					$row = process_qry('CALL dashboard_password(?, ?, ?, ?, ?, ?, ?)', array($data->accountID, $data->my_session_id, $data->profilePic, $data->username, $data->fullname, $data->old_password, $data->new_password));
					
					if ($row[0]['result'] == 'Success') {
						return json_encode(array('response'=>'Success')); 
					}
					else if ($row[0]['result'] == 'No Changes'){
						return json_encode(array('response'=>'Success')); 			
					}
					else{
						$logs->write_logs('UpdatePassword - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>$param)));
						return json_encode(array('response'=>'Failed', 'description'=>'Something went wrong' )); 			
					}
				}else{
					return json_encode(array('response'=>'Incorrect', 'description'=>'Incorrect Old Password' )); 	
				}
			}
			else{
				$logs->write_logs('UpdatePassword - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		public function convertHtml($param){
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "convertHtml";

			$file = 'html/'.$data->campaignID.'.html';

			// new file
			if (!file_exists($file)) { 
				$logs->write_logs('convertHtml - Dashboard', $file_name, array(array('response'=>'Success', 'description'=>'File created [$data->campaignID.html]')));
				fopen($file, "w"); 
				$fhandler = fopen($file, "a+");
				fwrite($fhandler, $data->message);
				fclose($fhandler);
			}
			// existing - overwrite it
			else{
				$logs->write_logs('convertHtml - Dashboard', $file_name, array(array('response'=>'Success', 'description'=>'File Updated [$data->campaignID.html]')));
				file_put_contents($file, $data->message);
			}
		}

		public function readHtml($param){
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$param['procedure'] = "readHtml";

			// Check Account ID
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));

			if ($check_account[0]['count'] > 0) {
				$htmlFile = file_get_contents('html/'.$data->campaignID.'.html', true);
				return json_encode(array('response'=>'Success', 'data'=>$htmlFile)); 
			}
			else{
				$logs->write_logs('readHtml - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' )); 				
			}
		}

		public function add_cashier($param){
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;

			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$x = 0;
                $entry = process_qry("SELECT IF(MAX(`id`) IS NULL, 1, MAX(`id`) + 1) AS `entry` FROM `cashiertable` LIMIT 1;", array());
                $cashierID = NULL;
                while ($x < 1) {
                    $cashierID = substr("CASH" . randomizer(2) . DATE("H") . $entry[0]['entry'] . DATE("s") . randomizer(6), 0, 16);
                    $step_2 = process_qry("SELECT count(*) AS `count` FROM `cashiertable` WHERE `cashierID` = ? LIMIT 1", array($cashierID));
                    $x = ( $step_2[0]['count'] < 1 ) ? 1 : 0;
            	}

            	$checkLocID = process_qry("SELECT * FROM loctable WHERE `locID` = ? AND status = 'active'", array($data->locID),$logs);	
            	if(count($checkLocID) <= 0 ){
            		$logs->write_logs('Cashier - Dashboard', $file_name, array(array('response'=>'Error', 'data'=>$param)));
					return json_encode(array('response'=>'Error', 'description'=>'locID not found.' ));
            	}
            	$dateNow = DATE('Y-m-d H:i:s');
            	// $decrypt_password = encrypt_decrypt('encrypt', $data->password);
            	$addCahier = process_qry("INSERT INTO `cashiertable`(`cashierID`,`name`,`designation`,`image`,`locID`,`status`,`dateAdded`,`dateModified`,`username`,`password`) VALUES(?,?,?,?,?,?,?,?,?,PASSWORD(?)) ",array($cashierID,$data->name,$data->designation,$data->image,$data->locID,$data->status,$dateNow,$dateNow,$data->username,$data->password),$logs);
            	if(count($addCahier) > 0 ){
            		$logs->write_logs('Add Cashier - Dashboard', $file_name, array(array('response'=>'Success', 'data'=>array("name"=>$data->name,"description"=>$data->designation,"locID"=>$data->locID,"status"=>$data->status))));
            		return json_encode(array("response"=>"Success","data"=>array("name"=>$data->name,"description"=>$data->designation,"locID"=>$data->locID,"status"=>$data->status)));
            	}else{
            		$logs->write_logs('Add Cashier - Dashboard', $file_name, array(array('response'=>'Failed', 'data'=>array("name"=>$data->name,"description"=>$data->designation,"locID"=>$data->locID,"status"=>$data->status))));
            		return json_encode(array('response'=>'Failed','description'=>"Unable to save Cashier."));
            	}

			}else{
				$logs->write_logs('Add Cashier - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' ));
			}
		}

		public function update_cashier($param){
			global $logs;
			global $error000;
			$file_name = 'dashboard.class.php';
			$data = (object) $param;
			$check_account = process_qry("SELECT COUNT(*) AS count FROM `accounts` WHERE `accountID` = ? AND `loginSession` = ? LIMIT 1", array($data->accountID, $data->my_session_id));
			
			if ($check_account[0]['count'] > 0) {
				$checkLocID = process_qry("SELECT * FROM loctable WHERE `locID` = ? AND status = 'active'", array($data->locID),$logs);	
            	if(count($checkLocID) <= 0 ){
            		$logs->write_logs('Update Cashier - Dashboard', $file_name, array(array('response'=>'Error', 'data'=>$param)));
					return json_encode(array('response'=>'Error', 'description'=>'locID not found.' ));
            	}
            	$dateNow = DATE('Y-m-d H:i:s');
            	// $decrypt_password = encrypt_decrypt('encrypt', $data->password);
            	$updateCashier = process_qry("UPDATE `cashiertable` SET `name` = ? ,`designation` = ?,`locID` = ?,`status` = ?, `password` = PASSWORD(?) WHERE `cashierID` =  ? LIMIT 1;",array($data->name,$data->designation,$data->locID,$data->status,$data->password, $data->cashierID),$logs);
            	if(count($updateCashier) > 0 ){
            		$logs->write_logs('Update Cashier - Dashboard', $file_name, array(array('response'=>'Success', 'data'=>$param)));
            		return json_encode(array("response"=>"Success","data"=>$param));
            	}
			}else{
				$logs->write_logs('Update Cashier - Dashboard', $file_name, array(array('response'=>'Expired', 'data'=>$param)));
				return json_encode(array('response'=>'Failed', 'description'=>'Session Expired' ));
			}

		}

		/********** Unlink Image **********/
		public function unlink_image($source, $module) {	  

			try{
				$path = getcwd()."/assets/images/".$module."/"; 
				$fullpath = str_replace("/", "\\", $path."full/".$source);
				$thumbpath = str_replace("/", "\\", $path."thumb/".$source); 
				// echo "full " . $fullpath;
				if (file_exists($fullpath)){    
				    unlink($fullpath);
				} 
				if (file_exists($thumbpath)){  
				    unlink($thumbpath);
				} 
			}
			catch(Exception $e){	
				$logs->debug_logs('Error - Unlink Image', $file_name, 
					array("module"=>"dashboard", "response"=>"Error", "description"=>$e), 
					array("source"=>$source, "module"=>$module, "fullpath"=>$fullpath, "thumbpath"=>$thumbpath));
			}
			
			return;
		}

	}

?>