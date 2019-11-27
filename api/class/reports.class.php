<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'logs.class.php');

		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		
		header('Location: http://'. $_SERVER['HTTP_HOST']);
	}

	class reports extends mailer{


		public function login($param) {
			include("reports/php/jwt_helper.php");
			global $logs;
			global $error000;
			$file_name = 'reports.class.php';
			$output = array();
			$data = (object) $param;
			$param['procedure'] = "login";
			$step_1 = process_qry("CALL report_login(?, ?)",array($data->username, $data->password),$logs);
			

			if ($step_1 > 0) {
				if($step_1[0]['result'] == 'Failed'){
					return json_encode(array(array("response"=>"Error", "description"=>"Invalid Username/Password.")));
					die();
				} else{
					$token = array();
					$token['accountID'] = $step_1[0]['result'];
					$token['loginSession'] = $step_1[0]['loginSession']; 
					$token['role'] = $step_1[0]['role']; 
					$token['fullname'] = $step_1[0]['fullname']; 
					$token['reportTabs'] = $step_1[0]['reportTabs']; 
					$newtoken = JWT::encode($token, MERCHANT);  

					return json_encode(array(array("response"=>"Success", "token"=>$newtoken )));
				}
				
			} else {
				$logs->write_logs("Error - Dashboard Login", $file_name, array(array("response"=>"Error", "description"=>"Invalid Username/Password.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid Username/Password.")));
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


	/********** Invalid Access Checker **********/
	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		include_once('../../logs/logs.class.php');
		$logs = NEW logs();
		$logs->write_logs('Invalid Access', 'dashboard.class.php', 'Illegal access attempt.');
		die('Access denied'); 
	}

?>