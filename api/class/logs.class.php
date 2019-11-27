<?php

	class logs {

		public function write_logs($function, $file_name, $message) {
			$path = realpath(dirname(__FILE__)) . "/../../logs/" . date('Y-m-d');
			$file = $path . "/" . $function . ".txt";

			if (!is_dir($path)) { mkdir($path, 0777, true);}
			if (!file_exists($file)) { fopen($file, "w"); }

			$fullmsg = json_encode(array(array("date"=>date("D M d H:i:s Y"), "client"=>$this->get_client_ip(), "file" => $file_name, "data"=>$message))) . "\r\n";
			$fhandler = fopen($file, "a+");

			fwrite($fhandler, $fullmsg);
			fclose($fhandler);
			return;
		}

		public function get_client_ip() {
			$ipaddress = 'UNKNOWN';

			$ip_source = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');

			foreach ($ip_source as $source) {
				if (isset($_SERVER[$source])) {
					$ipaddress = $_SERVER[$source];
					break;
				}
			}

			/*$myexternalip = json_decode(file_get_contents('http://ipv4.myexternalip.com/json'), true);
			$ipaddress = (count($myexternalip) > 0) ? $myexternalip['ip'] : $ipaddress;*/

			return $ipaddress;
		}

	}

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

?>