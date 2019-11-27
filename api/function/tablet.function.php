<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	require_once ('api/class/tablet.class.php');

	$class = new tablet();
	$file = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));

	switch ($_GET['function']) {
		// case 'tablet_check_qr_card':
		// 	$default = array("qrCard" => NULL);
		// 	$required = array('qrCard');
		// 	$data = process_params($default, $required, $params);

		// 	die($class->tablet_check_qr_card($data));
		// 	break;

		case 'tablet_check_qr_card_old':
			$default = array("memberID" => NULL);
			$required = array('memberID');
			$data = process_params($default, $required, $params);

			die($class->tablet_check_qr_card_old($data));
			break;
		case 'tablet_check_qr_card':
			$default = array("barCode" => NULL);
			$required = array('barCode');
			$data = process_params($default, $required, $params);

			die($class->tablet_check_qr_card($data));
			break;

		case 'tablet_availability':
			$default = array("deviceID" => NULL, "version" => NULL);
			$required = array('deviceID', 'version');
			$data = process_params($default, $required, $params);

			die($class->tablet_availability($data));
			break;

		case 'tablet_location':
			die($class->tablet_location());
			break;

		case 'assign_tablet':
			$default = array("deviceID" => NULL, "locID" => NULL,  "version" => NULL);
			$required = array('deviceID', 'locID',  'version');
			$data = process_params($default, $required, $params);

			die($class->assign_tablet($data));
			break;

		case 'tablet_perlocID':	
			$default = array("locID" => NULL);
			$required = array('locID');
			$data = process_params($default, $required, $params);
			die($class->tablet_perlocID($data));
			break;
		default:
			die($error000);
			break;
	}

?>