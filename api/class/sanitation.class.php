<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}
	class sanitation {

		public function sanitize($data) {
			global $logs;
			global $file_name;
			$required = array('function', 'category');
			$keys = array_keys($data);

			foreach ($required as $node) {
				if (!in_array($node, $keys)) {
					die(json_encode(array(array("response" => "Error", "description" => "Parameter ( " . $node . " ) is required."))));
				}
			}

			foreach ($data as $key => $value) {
				if ($key == "email") {
					if ($data[$key] != '') {
						$data[$key] = filter_var(strtolower($value), FILTER_SANITIZE_EMAIL);
						// echo $data[$key];
						// $mEmail = $this->encrypt_decrypt('decrypt', $value);  
						if (!$this->validate_email($data[$key])) {
						// if (!$this->validate_email($mEmail )) {
							$logs->write_logs('Error - Sanitation', $file_name, array(array("response" => "Error", "email" => $data[$key], "description" => "Invalid E-Mail Address.")));
							return json_encode(array(array("response" => "Error", "description" => "Invalid E-Mail Address.")));
						}
					}
				} elseif ((strrpos($key, "earn") !== false) || (strrpos($key, "sku") !== false) || (strrpos($key, "mroles") !== false)){
					continue;
				} else {
					$data[$key] = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
				}
			}

			$logs->write_logs("Raw - " . ucwords(str_replace("_", " ", strtolower($data['function']))), $file_name, array(array("data" => array($data))));

			return json_encode(array(array("response" => "Success", "data" => array($data))));
		}

		public function validate_email($email) {
			$isValid = true;
			$atIndex = strrpos($email, "@");
			if (is_bool($atIndex) && !$atIndex) {
				$isValid = false;
			} else {
				$domain = substr($email, $atIndex+1);
				$local = substr($email, 0, $atIndex);
				$localLen = strlen($local);
				$domainLen = strlen($domain);
				$domain_arr = explode(".", $domain);

				if ($localLen < 1 || $localLen > 64) {
					$isValid = false;
				} else if ($domainLen < 1 || $domainLen > 255) {
					$isValid = false;
				} else if ($local[0] == '.' || $local[$localLen-1] == '.') {
					$isValid = false;
				} else if (preg_match('/\\.\\./', $local)) {
					$isValid = false;
				} else if (!preg_match('/^[A-z0-9\\+-\\.]+$/', $domain)) {
					$isValid = false;
				} else if (preg_match('/\\.\\./', $domain)) {
					$isValid = false;
				} else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
					if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
						$isValid = false;
					}
				} else if (strrpos($domain, ".") === false) {
					$isValid = false;
				} else if (end($domain_arr) == NULL) {
					$isValid = false;
				}
			}

			return $isValid;
		}

		public function encrypt_decrypt($action, $string) {
		    $output = false;
		    $encrypt_method = "AES-256-CBC";
		    $secret_key = 'This is my secret key';
		    $secret_iv = 'This is my secret iv';
		    // hash
		    $key = hash('sha256', $secret_key);
		    
		    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		    $iv = substr(hash('sha256', $secret_iv), 0, 16);
		    if ( $action == 'encrypt' ) {
		        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
		        $output = base64_encode($output);
		    } else if( $action == 'decrypt' ) {
		        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		    }
		    
		    return $output;
		}

	}

?>