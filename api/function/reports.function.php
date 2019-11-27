<?php
	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	require_once ('api/class/reports.class.php');

	$class = new reports();
	$file = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));

	switch ($_GET['function']) { 

		case 'login':
			$default = array('username'=>NULL, 'password'=>NULL);
			$keys = array_keys($default);

			if (!isset($params['username']) || $params['username'] == NULL) {
				die(json_encode(array(array("response"=>"Error", "description"=>"username is not defined or NULL"))));
			}
			if (!isset($params['password']) || $params['password'] == NULL) {
				die(json_encode(array(array("response"=>"Error", "description"=>"password is not defined or NULL"))));
			}

			$data = array_merge($default, $params);

			foreach ($params as $key => $value) {
				if (!in_array($key, $keys)) {
					$response = array(array("response"=>"Error", "description"=>"Invalid parameter ( " . $key . " )", "data"=>array($params)));
					api_logger("error", $function, $file, $response);
					die(json_encode($response));
				}
			}
			die($class->login($data));
			break;


		default:
			echo $error400;
			die();
			break;
	}

	/********** Invalid Access Checker **********/
	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		include_once('logs/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Invalid Access', $file_name, 'Illegal access attempt.');
		die('Access denied'); 
	}

?>