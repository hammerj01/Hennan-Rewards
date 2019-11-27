<?php
	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	require_once ('api/class/dashboard.class.php');

	$class = new dashboard();
	$file = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	$error400 = "Error";

	switch ($_GET['function']) {
		case 'login':
			$default = array("username" => NULL, "password" => NULL);
			$required = array('username', 'password');
			$data = process_params($default, $required, $params);

			die($class->login($data));
			break;

		case 'jsonlist':
			$default = array("accountID"=>NULL, "my_session_id"=>NULL, "table"=>NULL, "sortOrder"=>NULL, "pageSize"=>NULL, 
				"currentPage"=>NULL, "searchFilter"=>NULL, "selectedStatus"=>NULL, "selectedType"=>NULL, "sortColumnName"=>NULL, "username"=>NULL);
			$required = array('accountID', 'my_session_id', 'table', 'sortOrder', 'pageSize', 'currentPage');
			$data = process_params($default, $required, $params);

			die($class->jsonlist($data));
			break;

		case 'jsonenumerate':
			$default = array("accountID"=>NULL, "my_session_id"=>NULL, "table"=>NULL, "sortColumnName"=>NULL, "sortOrder"=>NULL, "pageSize"=>NULL, 
				"currentPage"=>NULL, "searchFilter"=>NULL, "selectedStatus"=>NULL, "selectedLocation"=>NULL);
			$required = array('accountID', 'my_session_id', 'table');
			$data = process_params($default, $required, $params);

			die($class->jsonenumerate($data));
			break;

		case 'view_record':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'table'=>NULL, 'filter'=>NULL);
			$required = array('accountID', 'my_session_id', 'table', 'filter');
			$data = process_params($default, $required, $params);

			die($class->view_record($data));
			break;

		case 'get_json':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'table'=>NULL, 'filter'=>NULL);
			$required = array('accountID', 'my_session_id', 'table');
			$data = process_params($default, $required, $params);

			die($class->get_json($data));
			break;

		case 'get_category':
			die($class->get_category());
		break;

		case 'update_password':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'profilePic'=>NULL, 'username'=>NULL, 'fullname'=>NULL, 'reenter'=>NULL, 'old_password'=>NULL, 'new_password'=>NULL);
			$required = array('accountID', 'my_session_id', 'old_password', 'new_password');
			$data = process_params($default, $required, $params);

			die($class->update_password($data));
			break;

		case 'update_profile':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'response_email'=>NULL, 'serviceOrder'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'attemptLimit'=>NULL, 'redeemBasePoint'=>NULL, 'merchant'=>NULL, 'merchant_appname'=>NULL, 'merchant_link'=>NULL, 'merchant_weblabel'=>NULL, 'domain'=>NULL, 'domain2'=>NULL, 'mailer_sender'=>NULL, 'mailer_name'=>NULL, 'mailer_smtp'=>NULL, 'mailer_username'=>NULL, 'mailer_password'=>NULL, 'mailer_port'=>NULL, 'mailer_secure'=>NULL, 'company'=>NULL, 'fname1'=>NULL, 'mname1'=>NULL, 'lname1'=>NULL, 'fname2'=>NULL, 'mname2'=>NULL, 'lname2'=>NULL, 'address'=>NULL, 'email'=>NULL, 'website'=>NULL, 'landline1'=>NULL, 'landline2'=>NULL, 'mobile1'=>NULL, 'mobile2'=>NULL, 'fax1'=>NULL, 'fax2'=>NULL, 'profilePic'=>NULL, 'about'=>NULL, 'merchantCode'=>NULL);
			$required = array('accountID', 'my_session_id', 'company');
			$data = process_params($default, $required, $params);

			die($class->update_profile($data));
			break;

		case 'add_location':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'locCategoryID'=>NULL, 'brandID'=>NULL, 'brandName'=>NULL, 'subbrandID'=>NULL, 'order'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'locCategoryID'=>NULL, 'locCategoryName'=>NULL, 'name'=>NULL, 'address'=>NULL, 'latitude'=>NULL, 'longitude'=>NULL, 'branchCode'=>NULL, 'region'=>NULL, 'phone'=>NULL, 'email'=>NULL, 'businessHrs'=>NULL, 'status'=>NULL, 'locFlag'=>NULL, 'image'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_location($data));
			break;

		case 'update_location':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'locCategoryID'=>NULL, 'brandID'=>NULL, 'brandName'=>NULL, 'subbrandID'=>NULL, 'order'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'locID'=>NULL, 'locCategoryID'=>NULL, 'locCategoryName'=>NULL, 'name'=>NULL, 'address'=>NULL, 'latitude'=>NULL, 'longitude'=>NULL, 'branchCode'=>NULL, 'region'=>NULL, 'phone'=>NULL, 'email'=>NULL, 'businessHrs'=>NULL, 'status'=>NULL, 'locFlag'=>NULL, 'image'=>NULL);
			$required = array('accountID', 'my_session_id', 'locID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_location($data));
			break;

		case 'add_locationcategory':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'order'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'name'=>NULL, 'description'=>NULL, 'status'=>NULL, 'image'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_locationcategory($data));
			break;

		case 'update_locationcategory':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'image'=>NULL, 'order'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'locCategoryID'=>NULL, 'name'=>NULL, 'description'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'locCategoryID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_locationcategory($data));
			break;

		case 'add_level':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'name'=>NULL, 'min'=>NULL, 'max'=>NULL, 'level'=>NULL, 'quote'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_level($data));
			break;

		case 'update_level':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'levelID'=>NULL, 'name'=>NULL, 'min'=>NULL, 'max'=>NULL, 'level'=>NULL, 'quote'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'levelID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_level($data));
			break;

		case 'add_voucher':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'redemption_remaining'=>NULL, 'shouldexpire'=>NULL, 'shouldmultiple'=>NULL, 'action'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'name'=>NULL, 'image'=>NULL, 'description'=>NULL, 'terms'=>NULL, 'type'=>NULL, 'cardType'=>NULL, 'cardVersion'=>NULL, 'startDate'=>NULL, 'endDate'=>NULL, 'startTime'=>NULL, 'endTime'=>NULL, 'month'=>NULL, 'frequencyType'=>NULL, 'frequencyStart'=>NULL, 'frequencyEnd'=>NULL, 'redemptionlimit'=>NULL, 'wmyperiod'=>NULL, 'quantity'=>NULL, 'action'=>NULL, 'order'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_voucher($data));
			break;

		case 'update_voucher':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'redemption_remaining'=>NULL, 'shouldexpire'=>NULL, 'shouldmultiple'=>NULL, 'action'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'voucherID'=>NULL, 'name'=>NULL, 'image'=>NULL, 'description'=>NULL, 'terms'=>NULL, 'type'=>NULL, 'cardType'=>NULL, 'cardVersion'=>NULL, 'startDate'=>NULL, 'endDate'=>NULL, 'startTime'=>NULL, 'endTime'=>NULL, 'month'=>NULL, 'frequencyType'=>NULL, 'frequencyStart'=>NULL, 'frequencyEnd'=>NULL, 'redemptionlimit'=>NULL, 'wmyperiod'=>NULL, 'quantity'=>NULL, 'action'=>NULL, 'order'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'voucherID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_voucher($data));
			break;

		case 'add_loyalty':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'order'=>NULL, 'locID'=>NULL, 'locName'=>NULL, 'name'=>NULL, 'points'=>0, 'promoType'=>NULL, 'categoryID'=>NULL, 'categoryName'=>NULL, 'terms'=>NULL, 'description'=>NULL, 'status'=>NULL, 'image'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_loyalty($data));
			break;

		case 'update_loyalty':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'type'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'order'=>NULL, 'loyaltyID'=>NULL, 'locID'=>NULL, 'locName'=>NULL, 'name'=>NULL, 'points'=>0, 'promoType'=>NULL, 'categoryID'=>NULL, 'categoryName'=>NULL, 'terms'=>NULL, 'description'=>NULL, 'status'=>NULL, 'image'=>NULL);
			$required = array('accountID', 'my_session_id', 'loyaltyID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_loyalty($data));
			break;

		case 'add_post':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'title'=>NULL, 'type'=>NULL, 'description'=>NULL, 'url'=>NULL, 'image'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'title');
			$data = process_params($default, $required, $params);

			die($class->add_post($data));
			break;

		case 'update_post':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'postID'=>NULL, 'title'=>NULL, 'type'=>NULL, 'description'=>NULL, 'url'=>NULL, 'image'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'postID', 'title');
			$data = process_params($default, $required, $params);

			die($class->update_post($data));
			break;

		case 'add_category':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'description'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'name'=>NULL, 'image'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_category($data));
			break;

		case 'update_category':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'description'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'categoryID'=>NULL, 'name'=>NULL, 'image'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'categoryID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_category($data));
			break;

		case 'add_subcategory':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'categoryID'=>NULL, 'categoryName'=>NULL, 'name'=>NULL, 'image'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name', 'categoryID', 'categoryName');
			$data = process_params($default, $required, $params);

			die($class->add_subcategory($data));
			break;

		case 'update_subcategory':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'subcategoryID'=>NULL, 'categoryID'=>NULL, 'categoryName'=>NULL, 'name'=>NULL, 'image'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'subcategoryID', 'name', 'categoryID');
			$data = process_params($default, $required, $params);

			die($class->update_subcategory($data));
			break;

		case 'add_product':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'price'=>NULL, 'order'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'categoryID'=>NULL, 'categoryName'=>NULL, 'name'=>NULL, 'description'=>NULL, 'url'=>NULL, 'image'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_product($data));
			break;

		case 'update_product':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'price'=>NULL, 'order'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'prodID'=>NULL, 'categoryID'=>NULL, 'categoryName'=>NULL, 'name'=>NULL, 'description'=>NULL, 'url'=>NULL, 'image'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'prodID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_product($data));
			break;

		case 'send_push':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'message'=>NULL, 'type'=>NULL);
			$required = array('accountID', 'my_session_id', 'message');
			$data = process_params($default, $required, $params);

			die($class->send_push($data));
			break;
			
		case 'add_brand':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'name'=>NULL, 'description'=>NULL, 'image'=>NULL, 'order'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_brand($data));
			break;

		case 'update_brand':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'brandID'=>NULL, 'name'=>NULL, 'description'=>NULL, 'image'=>NULL, 'order'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'brandID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_brand($data));
			break;

		case 'add_term':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'name'=>NULL, 'description'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_term($data));
			break;

		case 'update_term':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'termID'=>NULL, 'name'=>NULL, 'description'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'termID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_term($data));
			break;

		case 'add_about':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'name'=>NULL, 'description'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_about($data));
			break;

		case 'update_about':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'aboutID'=>NULL, 'name'=>NULL, 'description'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'aboutID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_about($data));
			break;

		case 'add_faq':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'name'=>NULL, 'description'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_faq($data));
			break;

		case 'update_faq':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'faqID'=>NULL, 'name'=>NULL, 'description'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'faqID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_faq($data));
			break;

		case 'add_socialmedia':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'name'=>NULL, 'url'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'name');
			$data = process_params($default, $required, $params);

			die($class->add_socialmedia($data));
			break;

		case 'update_socialmedia':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'socialmediaID'=>NULL, 'name'=>NULL, 'url'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'socialmediaID', 'name');
			$data = process_params($default, $required, $params);

			die($class->update_socialmedia($data));
			break;

		case 'add_account':
			$default = array('my_session_id'=>NULL, 'id'=>NULL, 'locID'=>NULL, 'branchCode'=>NULL, 'brandID'=>NULL, 'dateAdded'=>NULL, 'loginSession'=>NULL, 'lastLogin'=>NULL, 'multiLogin'=>NULL, 'dateModified'=>NULL, 'reenter'=>NULL, 'accountID'=>NULL, 'username'=>NULL, 'password'=>NULL, 'fullname'=>NULL, 'role'=>NULL, 'reportTabs'=>NULL, 'cmsTabs'=>NULL, 'profilePic'=>NULL, 'status'=>NULL,'wpmTabs'=>NULL);
			$required = array('accountID', 'my_session_id', 'username');
			$data = process_params($default, $required, $params);

			die($class->add_account($data));
			break;

		case 'update_account':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'locID'=>NULL, 'branchCode'=>NULL, 'brandID'=>NULL, 'dateAdded'=>NULL, 'loginSession'=>NULL, 'lastLogin'=>NULL, 'multiLogin'=>NULL, 'dateModified'=>NULL, 'reenter'=>NULL, 'username'=>NULL, 'password'=>NULL, 'fullname'=>NULL, 'role'=>NULL, 'reportTabs'=>NULL, 'cmsTabs'=>NULL, 'profilePic'=>NULL, 'status'=>NULL,'wpmTabs'=>NULL);
			$required = array('my_session_id', 'accountID', 'username');
			$data = process_params($default, $required, $params);
			// var_dump($data);
			// echo " string ththt ";
			die($class->update_account($data));
			break;

		case 'add_tablet':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'deviceCode'=>NULL, 'brandID'=>NULL, 'brandName'=>NULL, 'locID'=>NULL, 'locName'=>NULL, 'postype'=>NULL, 'terminalNum'=>NULL, 'status'=>NULL, 'deploy'=>NULL);
			$required = array('accountID', 'my_session_id', 'status');
			$data = process_params($default, $required, $params);

			die($class->add_tablet($data));
			break;

		case 'update_tablet':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'deviceID'=>NULL, 'timeStamp'=>NULL, 'version'=>NULL, 'lastSync'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'deviceCode'=>NULL, 'brandID'=>NULL, 'brandName'=>NULL, 'locID'=>NULL, 'locName'=>NULL, 'postype'=>NULL, 'terminalNum'=>NULL, 'status'=>NULL, 'deploy'=>NULL);
			$required = array('accountID', 'my_session_id', 'deviceCode', 'status');
			$data = process_params($default, $required, $params);

			die($class->update_tablet($data));
			break;

		case 'add_setting':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'brandID'=>NULL, 'description'=>NULL, 'ncamount'=>NULL, 'ncpoints'=>NULL, 'icon'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'settingID'=>NULL, 'locID'=>NULL, 'locName'=>NULL, 'name'=>NULL, 'camount'=>NULL, 'cpoints'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'status');
			$data = process_params($default, $required, $params);

			die($class->add_setting($data));
			break;

		case 'update_setting':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'id'=>NULL, 'brandID'=>NULL, 'description'=>NULL, 'ncamount'=>NULL, 'ncpoints'=>NULL, 'icon'=>NULL, 'dateAdded'=>NULL, 'dateModified'=>NULL, 'settingID'=>NULL, 'locID'=>NULL, 'locName'=>NULL, 'name'=>NULL, 'camount'=>NULL, 'cpoints'=>NULL, 'status'=>NULL);
			$required = array('accountID', 'my_session_id', 'settingID', 'status');
			$data = process_params($default, $required, $params);

			die($class->update_setting($data));
			break;

		case 'add_cashier':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'name'=>NULL, 'designation'=>NULL, 'image'=>NULL, 'status'=>NULL,"locID"=> NULL,"password"=>NULL,"username"=>NULL);
			$required = array('accountID', 'my_session_id', 'status','name','designation','locID','username');
			$data = process_params($default, $required, $params);
			die($class->add_cashier($data));
			break;

		case 'update_cashier':
			$default = array('accountID'=>NULL, 'my_session_id'=>NULL, 'cashierID'=>NULL, 'name'=>NULL, 'designation'=>NULL, 'image'=>NULL, 'status'=>NULL,"locID"=> NULL,"password"=>NULL,"username"=>NULL);
			$required = array('accountID', 'my_session_id', 'status','name','designation','locID','username');
			$data = process_params($default, $required, $params);
			die($class->update_cashier($data));
			break;

		default:
			echo $error400;
			die();
			break;

	}

?>