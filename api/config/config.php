<?php

	$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	$protocol = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 'https://' : 'http://';
	$error000 = json_encode(array(array("response"=>"Error", "description"=>"Invalid API Access.")));

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once (dirname(__FILE__) . '/../class/logs.class.php');
		$logs = NEW logs;
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	$log_file_ctr = 0;
	$logs_source = array(
		realpath(dirname(__FILE__)) . "/logs.class.php",
		realpath(dirname(__FILE__)) . "/class/logs.class.php",
		realpath(dirname(__FILE__)) . "/../class/logs.class.php",
		realpath(dirname(__FILE__)) . "/api/class/logs.class.php"
	);

	foreach ($logs_source as $loop) {
		if (file_exists($loop)) {
			$log_file_ctr = 1;
			require_once ($loop);
		}
	}

	if ($log_file_ctr != 1) {
		$message = "Unable to locate Log Class.";
		die(json_encode(array(array("response"=>"Error", "description"=>$message))));
	}

	$logs = NEW logs;
	$settings = process_qry("SELECT * FROM `settings`", array());

	define('DOMAIN', $protocol . $settings[0]['domain']);
	define('MERCHANT', $settings[0]['merchant']);
	define('MERCHANT_APPNAME', $settings[0]['merchant_appname']);
	define('MERCHANT_LINK', $settings[0]['merchant_link']);
	define('MERCHANT_WEBLABEL', $settings[0]['merchant_weblabel']);
	define('JWT_SERUCITY_KEY', 'APPSINC-PH-' . DATE("Y") . '-00030');
	define('RESPONSE_EMAIL',$settings[0]['response_email']);
	
	function process_qry ($query, array $args) {
		$message = NULL;
		$output = array();

		try {
			$env_file = NULL;
			$env_source = array(
				realpath(dirname(__FILE__)) . "/.env",
				realpath(dirname(__FILE__)) . "/misc/.env",
				realpath(dirname(__FILE__)) . "/../config/.env"
			);

			foreach ($env_source as $loop) {
				if (file_exists($loop)) {
					$env_file = file($loop);
				}
			}

			if (is_null($env_file)) {
				$message = "Unable to locate Environment File.";
				die(json_encode(array(array("response"=>"Error", "description"=>$message))));
			}

			$env_arr = array();
			$connection = array();

			foreach ($env_file as $line) {
				array_push($env_arr, $line);
			}

			if (count($env_arr) == 2) {
				$raw_connection = json_decode(base64_decode($env_arr[1]), true);
				$env_step_1 = explode('<br />', base64_decode($env_arr[0]));

				if (count($env_step_1) == 4) {
					$env_step_2 = array();

					foreach ($env_step_1 as $node) {
						$temp_node = explode('::', base64_decode($node));

						if (array_key_exists($temp_node[0], $raw_connection)) {
							$raw_connection[$temp_node[0]] = $temp_node[1];
						}
					}
				}

				$connection = (object) $raw_connection;
			}

			$pdo = new PDO (
				'mysql:host=' . $connection->host . ';port=' . $connection->port . ';dbname=' . $connection->db,
				$connection->un,
				$connection->pw,
				array(
					PDO::ATTR_PERSISTENT => true,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		} catch (PDOException $e) {
			$pdo = NULL;
			$message = "Failed to connect to MySQL: " . $e->getMessage();
			die(json_encode(array(array("response"=>"Error", "description"=>$message))));
		}

		try {
			$pdo_statement = $pdo->prepare($query);
		} catch (PDOException $e) {
			$pdo = NULL;
			$message = "MySQLi Prepare Failed: " . $e->getMessage();
			die(json_encode(array(array("response"=>"Error", "description"=>$message))));
		}

		if (count($args) > 0) {
			for ($i=0; $i < count($args); $i++) {
				$type = PDO::PARAM_STR;

				if (is_int($args[$i])) { $type = PDO::PARAM_INT; }
				elseif (is_bool($args[$i])) { $type = PDO::PARAM_BOOL; }
				elseif (is_null($args[$i])) { $type = PDO::PARAM_NULL; }

				try {
					$pdo_statement->bindValue($i+1, $args[$i], $type);
				} catch (Exception $e) {
					$pdo = NULL;
					$message = "MySQLi Bind Parameter Failed: " . $e->getMessage();
					die(json_encode(array(array("response"=>"Error", "description"=>$message))));
				}
			}
		}

		try {
			$pdo_statement->execute();

			if ($pdo_statement->rowCount()) {
				$output = (preg_match('/\b(update|insert|delete)\b/', strtolower($query)) === 1) ? array(array('response'=>'Success')) : $pdo_statement->fetchAll(PDO::FETCH_ASSOC);
			}
		} catch (PDOException $e) {
			$pdo = NULL;
			$message = "MySQLi Execution Failed: " . $e->getMessage();
			die(json_encode(array(array("response"=>"Error", "description"=>$message))));
		}

		$pdo = NULL;

		if (count($output) > 0) {
			for ($ii=0; $ii < count($output); $ii++) { 
				foreach ($output[$ii] as $key => $value) {
					if ($key == 'image') {
						if ($value) {
							$output[$ii][$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8')."?".date('is');
						}
					} else {
						$output[$ii][$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
					}

					$temp_val = str_replace(array("\\r\\n", "\\r", "\\n","¶"), "<br />", $output[$ii][$key]);
					// $temp_val = str_replace("\\r\\n", "<br />", $temp_val);
					// $temp_val = str_replace("\\r", "<br />", $temp_val);
					// $temp_val = str_replace("\\n", "<br />", $temp_val);
					// $temp_val = str_replace("/[\n\r]/", "<br />", $temp_val);
					//$temp_val = str_replace(array("\\r\\n", "\\r", "\\n"), "<br />", $temp_val);
					// $temp_val = str_replace('"', "&#34;", $temp_val);
					// $temp_val = str_replace("'", "&#39;", $temp_val);  /[\n\r]/
					// $temp_val = str_replace('/', "&#47;", $temp_val);
					// $temp_val = str_replace("\\", "&#92;", $temp_val);
					// $temp_val = nl2br($temp_val);
					$output[$ii][$key] = $temp_val;

				}
			}
		}

		ob_flush();
		flush();
		return $output;
	}

	function randomizer($len, $norepeat = true) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$max = strlen($chars) - 1;

		if ($norepeat && $len > $max + 1) {
			throw new Exception("Non repetitive random string can't be longer than charset");
		}

		$rand_chars = array();

		while ($len) {
			$picked = $chars[mt_rand(0, $max)];

			if ($norepeat) {
				if (!array_key_exists($picked, $rand_chars)) {
					$rand_chars[$picked] = true;
					$len--;
				}
			} else {
				$rand_chars[] = $picked;
				$len--;
			}
		}

		return implode('', $norepeat ? array_keys($rand_chars) : $rand_chars);
	}

	function num_randomizer($len, $norepeat = true) {
		$chars = "0123456789";
		$max = strlen($chars) - 1;

		if ($norepeat && $len > $max + 1) {
			throw new Exception("Non repetitive random string can't be longer than charset");
		}

		$rand_chars = array();

		while ($len) {
			$picked = $chars[mt_rand(0, $max)];

			if ($norepeat) {
				if (!array_key_exists($picked, $rand_chars)) {
					$rand_chars[$picked] = true;
					$len--;
				}
			} else {
				$rand_chars[] = $picked;
				$len--;
			}
		}

		return implode('', $norepeat ? array_keys($rand_chars) : $rand_chars);
	}

	function process_params($default, $required, $params) {
		$keys = array_keys($default);
		$data = array_merge($default, $params);
		$array_diff = array_values(array_diff($required, array_keys($params)));
		$env = NULL;

		if (count($array_diff) > 0) {
			die(json_encode(array(array("response" => "Error", "description" => "Parameter ( " . $array_diff[0] . " ) is required."))));
		} else {
			foreach ($params as $key => $value) {
				if ((in_array($key, $required) && !$params[$key]) || !in_array($key, $keys)) {
					die(json_encode(array(array("response" => "Error", "description" => "Please input " . $key ))));
				}
			}
		}

		return $data;
	}

	function encrypt_decrypt($action, $string) {
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

?>